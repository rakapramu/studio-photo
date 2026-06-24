<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';
import axios from 'axios';

// Configure axios defaults for Laravel CSRF protection
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;
axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

const props = defineProps({
    booking: Object,
    hash: String,
    clientKey: String,
});

const isPaying = ref(false);
const errorMessage = ref(null);
const successMessage = ref(null);

// Format Currency
const formatIDR = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(value);
};

// Check if SPK Contract is signed
const isContractSigned = computed(() => {
    return !!props.booking.contract?.signed_at;
});

// DP (30%) & Pelunasan (70%) amounts
const dpAmount = computed(() => (parseFloat(props.booking.total_price) * 0.3));
const finalAmount = computed(() => (parseFloat(props.booking.total_price) * 0.7));

// Find payment record status
const dpPayment = computed(() => {
    return props.booking.payments?.find(p => p.type === 'down_payment');
});

const finalPayment = computed(() => {
    return props.booking.payments?.find(p => p.type === 'final_payment');
});

// Load Midtrans Snap JS library dynamically
onMounted(() => {
    if (!window.snap) {
        const script = document.createElement('script');
        const isSandbox = props.clientKey.startsWith('SB-');
        script.src = isSandbox 
            ? 'https://app.sandbox.midtrans.com/snap/snap.js'
            : 'https://app.midtrans.com/snap/snap.js';
        script.setAttribute('data-client-key', props.clientKey);
        document.head.appendChild(script);
    }
});

// Trigger Payment flow
const pay = (type) => {
    if (isPaying.value) return;
    isPaying.value = true;
    errorMessage.value = null;

    axios.post(`/booking/${props.booking.id}/payment/${props.hash}/initiate`, {
        type: type
    })
    .then(response => {
        const snapToken = response.data.token;
        
        if (!window.snap) {
            errorMessage.value = 'Midtrans Snap library belum termuat. Silakan muat ulang halaman.';
            isPaying.value = false;
            return;
        }

        window.snap.pay(snapToken, {
            onSuccess: function (result) {
                successMessage.value = 'Pembayaran berhasil diproses! Halaman akan dimuat ulang.';
                isPaying.value = false;
                setTimeout(() => {
                    router.reload({ preserveScroll: true });
                }, 2000);
            },
            onPending: function (result) {
                successMessage.value = 'Pembayaran Anda sedang menunggu penyelesaian (pending).';
                isPaying.value = false;
                setTimeout(() => {
                    router.reload({ preserveScroll: true });
                }, 2000);
            },
            onError: function (result) {
                errorMessage.value = 'Terjadi kesalahan saat memproses pembayaran dengan Midtrans.';
                isPaying.value = false;
            },
            onClose: function () {
                isPaying.value = false;
            }
        });
    })
    .catch(error => {
        errorMessage.value = error.response?.data?.error || 'Gagal memproses inisiasi pembayaran. Silakan coba lagi.';
        isPaying.value = false;
    });
};

// Check if running on localhost for dev-testing tools
const isLocalEnv = computed(() => {
    return window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
});

// Trigger Local Webhook Simulation
const simulateWebhookPayment = (type) => {
    if (isPaying.value) return;
    isPaying.value = true;
    errorMessage.value = null;
    successMessage.value = null;

    axios.post(`/booking/${props.booking.id}/payment/${props.hash}/simulate-webhook`, {
        type: type
    })
    .then(response => {
        successMessage.value = response.data.message;
        isPaying.value = false;
        setTimeout(() => {
            router.reload({ preserveScroll: true });
        }, 1500);
    })
    .catch(error => {
        errorMessage.value = error.response?.data?.error || 'Gagal memproses simulasi.';
        isPaying.value = false;
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Dasbor Pembayaran Sesi" />

        <div class="py-2">
            <!-- Header -->
            <div class="text-center mb-6">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-violet-100 text-violet-850 dark:bg-violet-950/40 dark:text-violet-300 mb-2">
                    Portal Reservasi & Klien
                </span>
                <h2 class="text-2xl font-extrabold text-slate-900 dark:text-slate-100">
                    Dasbor Pembayaran Sesi
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Kelola tanda tangan kontrak digital (SPK) dan cicilan pembayaran sesi foto Anda.
                </p>
            </div>

            <!-- Notifications -->
            <div v-if="successMessage" class="mb-5 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm flex items-start space-x-2">
                <span class="text-base">✅</span>
                <span>{{ successMessage }}</span>
            </div>

            <div v-if="errorMessage" class="mb-5 p-4 rounded-xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 text-sm flex items-start space-x-2">
                <span class="text-base">⚠️</span>
                <span>{{ errorMessage }}</span>
            </div>

            <!-- Main Sections Grid -->
            <div class="space-y-5">
                
                <!-- 1. Booking Info Card -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm p-5 transition-all">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-4">
                        Informasi Sesi Foto
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-slate-450 dark:text-slate-400">Paket Terpilih</p>
                            <p class="font-bold text-slate-800 dark:text-slate-200 text-base">
                                {{ booking.package?.name || 'Paket Kustom' }}
                            </p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Durasi: {{ booking.package?.duration_minutes }} Menit
                            </p>
                        </div>
                        
                        <div>
                            <p class="text-xs text-slate-450 dark:text-slate-400">Total Harga</p>
                            <p class="font-extrabold text-violet-600 dark:text-violet-400 text-lg">
                                {{ formatIDR(booking.total_price) }}
                            </p>
                        </div>
                        
                        <div class="border-t border-slate-100 dark:border-slate-800/80 pt-3 md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-slate-650 dark:text-slate-350">
                            <div>
                                <p class="text-xs text-slate-450 dark:text-slate-400">Tanggal & Waktu</p>
                                <p class="font-medium mt-0.5">
                                    📅 {{ new Date(booking.booking_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                                </p>
                                <p class="text-xs font-mono text-slate-400">
                                    ⏱️ {{ booking.start_time.substring(0, 5) }} - {{ booking.end_time.substring(0, 5) }} WIB
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-450 dark:text-slate-400">Lokasi Pelaksanaan</p>
                                <p class="font-medium mt-0.5">📍 {{ booking.location }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. SPK E-Contract Card -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm p-5 transition-all">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            Surat Perjanjian Kerja (SPK)
                        </h3>
                        <span 
                            class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold"
                            :class="isContractSigned 
                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400' 
                                : 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400'"
                        >
                            {{ isContractSigned ? 'SPK DITANDATANGANI' : 'MENUNGGU TANDA TANGAN' }}
                        </span>
                    </div>

                    <div v-if="isContractSigned" class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                        <p>Kontrak SPK digital telah ditandatangani secara sah pada:</p>
                        <p class="font-medium text-slate-800 dark:text-slate-200 mt-1">
                            ✅ {{ new Date(booking.contract.signed_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) }} WIB
                        </p>
                        <p class="text-xs font-mono text-slate-400 mt-0.5">IP Address: {{ booking.contract.ip_address }}</p>
                    </div>

                    <div v-else class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                        <p class="mb-4">
                            Sesuai kebijakan studio, Anda wajib meninjau dan menandatangani dokumen Surat Perjanjian Kerja (SPK) digital sebelum melakukan pembayaran Uang Muka (DP).
                        </p>
                        <Link 
                            :href="`/contract/${booking.id}/${hash}`"
                            class="inline-flex items-center justify-center px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-medium text-xs rounded-lg shadow-sm transition-all"
                        >
                            ✍️ Baca & Tanda Tangani SPK Sekarang
                        </Link>
                    </div>
                </div>

                <!-- 3. Payments Stage Card -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm p-5 transition-all">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-4">
                        Tahapan Pembayaran
                    </h3>

                    <div class="space-y-6">
                        
                        <!-- Tahap 1: DP 30% -->
                        <div class="flex items-start space-x-4">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold mt-0.5 border"
                                :class="dpPayment?.status === 'paid' 
                                    ? 'bg-emerald-500 border-emerald-500 text-white' 
                                    : 'bg-slate-100 dark:bg-slate-800 border-slate-350 dark:border-slate-700 text-slate-600 dark:text-slate-350'"
                            >
                                <span v-if="dpPayment?.status === 'paid'">✓</span>
                                <span v-else>1</span>
                            </div>
                            
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-bold text-sm text-slate-800 dark:text-slate-250">
                                        Tahap 1: Uang Muka (DP 30%)
                                    </h4>
                                    <span 
                                        v-if="dpPayment"
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold"
                                        :class="dpPayment.status === 'paid' 
                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400' 
                                            : 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400'"
                                    >
                                        {{ dpPayment.status.toUpperCase() }}
                                    </span>
                                </div>
                                <p class="text-sm font-extrabold text-slate-900 dark:text-slate-100 mt-1">
                                    {{ formatIDR(dpAmount) }}
                                </p>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    Diperlukan setelah tanda tangan SPK untuk mengunci tanggal jadwal pemotretan Anda.
                                </p>
                                
                                <div class="mt-3" v-if="!dpPayment || dpPayment.status !== 'paid'">
                                    <button 
                                        v-if="isContractSigned"
                                        @click="pay('down_payment')"
                                        :disabled="isPaying"
                                        class="w-full md:w-auto px-4 py-2 bg-violet-600 hover:bg-violet-750 disabled:bg-violet-400 text-white font-medium text-xs rounded-lg shadow-sm transition-all"
                                    >
                                        {{ isPaying ? 'Memproses...' : '💳 Bayar Uang Muka (DP 30%)' }}
                                    </button>
                                    <span v-else class="text-xs text-amber-600 dark:text-amber-400 font-medium italic block p-2 bg-amber-50 dark:bg-amber-950/20 border border-amber-200/50 dark:border-amber-900/40 rounded-lg">
                                        ⚠️ Tanda tangani SPK di atas terlebih dahulu untuk melakukan pembayaran DP.
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Divider line -->
                        <div class="h-px bg-slate-100 dark:bg-slate-800/80 my-4 ml-12"></div>

                        <!-- Tahap 2: Pelunasan 70% -->
                        <div class="flex items-start space-x-4">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold mt-0.5 border"
                                :class="finalPayment?.status === 'paid' 
                                    ? 'bg-emerald-500 border-emerald-500 text-white' 
                                    : 'bg-slate-100 dark:bg-slate-800 border-slate-350 dark:border-slate-700 text-slate-600 dark:text-slate-350'"
                            >
                                <span v-if="finalPayment?.status === 'paid'">✓</span>
                                <span v-else>2</span>
                            </div>
                            
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-bold text-sm text-slate-800 dark:text-slate-250"
                                        :class="dpPayment?.status !== 'paid' ? 'opacity-50' : ''"
                                    >
                                        Tahap 2: Pelunasan Sisa Kontrak (70%)
                                    </h4>
                                    <span 
                                        v-if="finalPayment"
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold"
                                        :class="finalPayment.status === 'paid' 
                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400' 
                                            : 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400'"
                                    >
                                        {{ finalPayment.status.toUpperCase() }}
                                    </span>
                                </div>
                                <p class="text-sm font-extrabold text-slate-900 dark:text-slate-100 mt-1"
                                    :class="dpPayment?.status !== 'paid' ? 'opacity-50' : ''"
                                >
                                    {{ formatIDR(finalAmount) }}
                                </p>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    Wajib dibayarkan paling lambat pada hari-H pelaksanaan sebelum sesi pemotretan dimulai.
                                </p>
                                
                                <div class="mt-3" v-if="dpPayment?.status === 'paid' && (!finalPayment || finalPayment.status !== 'paid')">
                                    <button 
                                        @click="pay('final_payment')"
                                        :disabled="isPaying"
                                        class="w-full md:w-auto px-4 py-2 bg-violet-600 hover:bg-violet-750 disabled:bg-violet-400 text-white font-medium text-xs rounded-lg shadow-sm transition-all"
                                    >
                                        {{ isPaying ? 'Memproses...' : '💳 Bayar Pelunasan (70%)' }}
                                    </button>
                                </div>
                                <div class="mt-3 text-xs text-slate-400 italic" v-else-if="dpPayment?.status !== 'paid'">
                                    * Pembayaran sisa kontrak terbuka setelah Uang Muka (DP) lunas terverifikasi.
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
            
            <!-- 4. Developer Webhook Simulation Tools -->
            <div v-if="isLocalEnv" class="mt-6 p-5 border border-dashed border-violet-300 dark:border-violet-800 rounded-2xl bg-violet-50/20 dark:bg-violet-950/10 text-center transition-all">
                <p class="text-xs font-bold text-violet-850 dark:text-violet-300 tracking-wider uppercase mb-1">
                    🧑‍💻 Developer Sandbox Tools
                </p>
                <p class="text-[11px] text-slate-450 dark:text-slate-400 mb-4 leading-relaxed">
                    Gunakan tombol di bawah ini untuk mensimulasikan callback webhook lunas dari Midtrans secara instan tanpa ngrok.
                </p>
                <div class="flex flex-wrap justify-center gap-3">
                    <button 
                        @click="simulateWebhookPayment('down_payment')"
                        :disabled="isPaying || dpPayment?.status === 'paid'"
                        class="px-3.5 py-1.5 bg-emerald-50 hover:bg-emerald-105 disabled:opacity-40 text-emerald-850 dark:bg-emerald-950/30 dark:hover:bg-emerald-950/50 dark:text-emerald-300 text-xs font-semibold rounded-lg border border-emerald-250/30 dark:border-emerald-900/30 transition-all cursor-pointer"
                    >
                        ✓ Simulasikan Lunas DP (30%)
                    </button>
                    <button 
                        @click="simulateWebhookPayment('final_payment')"
                        :disabled="isPaying || dpPayment?.status !== 'paid' || finalPayment?.status === 'paid'"
                        class="px-3.5 py-1.5 bg-blue-50 hover:bg-blue-105 disabled:opacity-40 text-blue-850 dark:bg-blue-950/30 dark:hover:bg-blue-950/50 dark:text-blue-300 text-xs font-semibold rounded-lg border border-blue-250/30 dark:border-blue-900/30 transition-all cursor-pointer"
                    >
                        🏆 Simulasikan Lunas Pelunasan (70%)
                    </button>
                </div>
            </div>
            
            <!-- Help Footer -->
            <div class="text-center mt-8 text-xs text-slate-400 dark:text-slate-500">
                Punya kendala dengan sistem pembayaran Snap? Hubungi Customer Service kami via WhatsApp.
            </div>
        </div>
    </GuestLayout>
</template>
