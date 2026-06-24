<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import Pagination from '../../../Components/Pagination.vue';

const props = defineProps({
    payments: Array,
    stats: Object,
});

// Search & Filter state
const searchQuery = ref('');
const statusFilter = ref('all');
const typeFilter = ref('all');

// Pagination state
const currentPage = ref(1);
const itemsPerPage = 5;

// Reset page when filters change
watch([searchQuery, statusFilter, typeFilter], () => {
    currentPage.value = 1;
});

// Modal state
const selectedPayment = ref(null);
const isDetailModalOpen = ref(false);

// Format IDR Currency
const formatIDR = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(value);
};

// Format DateTime
const formatDateTime = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short'
    }) + ' WIB';
};

// Filtered Payments
const filteredPayments = computed(() => {
    return props.payments.filter(payment => {
        const matchesSearch = payment.booking?.client_name
            ?.toLowerCase()
            .includes(searchQuery.value.toLowerCase()) || 
            payment.booking?.client_email
            ?.toLowerCase()
            .includes(searchQuery.value.toLowerCase());

        const matchesStatus = statusFilter.value === 'all' || payment.status === statusFilter.value;
        const matchesType = typeFilter.value === 'all' || payment.type === typeFilter.value;

        return matchesSearch && matchesStatus && matchesType;
    });
});

const paginatedPayments = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredPayments.value.slice(start, end);
});

const openDetailModal = (payment) => {
    selectedPayment.value = payment;
    isDetailModalOpen.value = true;
};

const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    selectedPayment.value = null;
};
</script>

<template>
    <AdminLayout>
        <Head title="Manajemen Pembayaran" />

        <template #title>
            💳 Manajemen Pembayaran
        </template>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                    Total Terbayar (Lunas)
                </p>
                <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-2">
                    {{ formatIDR(stats.total_revenue) }}
                </p>
                <p class="text-xs text-slate-450 dark:text-slate-450 mt-1">
                    Akumulasi seluruh nominal lunas
                </p>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                    Transaksi Sukses
                </p>
                <p class="text-2xl font-black text-slate-900 dark:text-white mt-2">
                    {{ stats.success_count }}
                </p>
                <p class="text-xs text-emerald-500 font-medium mt-1">
                    🟢 Pembayaran Berhasil
                </p>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                    Menunggu Pembayaran
                </p>
                <p class="text-2xl font-black text-slate-900 dark:text-white mt-2">
                    {{ stats.pending_count }}
                </p>
                <p class="text-xs text-amber-500 font-medium mt-1">
                    🟡 Menunggu penyelesaian
                </p>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                    Gagal / Kedaluwarsa
                </p>
                <p class="text-2xl font-black text-slate-900 dark:text-white mt-2">
                    {{ stats.failed_count }}
                </p>
                <p class="text-xs text-red-500 font-medium mt-1">
                    🔴 Transaksi kedaluwarsa/gagal
                </p>
            </div>
        </div>

        <!-- Filter and Search controls -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 mb-6 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center gap-4">
                <!-- Search input -->
                <div class="flex-1 relative">
                    <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                        🔍
                    </span>
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        placeholder="Cari nama atau email klien..." 
                        class="w-full pl-9 pr-4 py-2 border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-violet-600 transition"
                    />
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center space-x-2">
                        <label class="text-xs font-bold text-slate-450 uppercase">Status:</label>
                        <select 
                            v-model="statusFilter"
                            class="px-3 py-1.5 border border-slate-250 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 rounded-lg text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-violet-600"
                        >
                            <option value="all">Semua Status</option>
                            <option value="paid">Paid (Sukses)</option>
                            <option value="pending">Pending</option>
                            <option value="failed">Failed</option>
                            <option value="expired">Expired</option>
                        </select>
                    </div>

                    <div class="flex items-center space-x-2">
                        <label class="text-xs font-bold text-slate-450 uppercase">Tipe:</label>
                        <select 
                            v-model="typeFilter"
                            class="px-3 py-1.5 border border-slate-250 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 rounded-lg text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-violet-600"
                        >
                            <option value="all">Semua Tipe</option>
                            <option value="down_payment">Uang Muka (DP 30%)</option>
                            <option value="final_payment">Pelunasan (70%)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-955 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-450 uppercase tracking-wider">
                            <th class="px-6 py-4">Klien & Pemesanan</th>
                            <th class="px-6 py-4">Paket Sesi</th>
                            <th class="px-6 py-4">Nominal</th>
                            <th class="px-6 py-4">Tipe Tagihan</th>
                            <th class="px-6 py-4">Metode</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Tanggal Transaksi</th>
                            <th class="px-6 py-4 text-center">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-sm">
                        <tr v-if="filteredPayments.length === 0">
                            <td colspan="8" class="px-6 py-10 text-center text-slate-450 dark:text-slate-500 italic">
                                Tidak ada data transaksi pembayaran yang cocok dengan kriteria filter.
                            </td>
                        </tr>
                        <tr 
                            v-for="payment in paginatedPayments" 
                            :key="payment.id"
                            class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-all"
                        >
                            <!-- Klien & Pemesanan -->
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-800 dark:text-slate-250">
                                    {{ payment.booking?.client_name || 'N/A' }}
                                </div>
                                <div class="text-xs text-slate-450">
                                    {{ payment.booking?.client_email || 'N/A' }}
                                </div>
                            </td>

                            <!-- Paket Sesi -->
                            <td class="px-6 py-4 font-medium text-slate-700 dark:text-slate-350">
                                {{ payment.booking?.package?.name || 'Paket Kustom' }}
                            </td>

                            <!-- Nominal -->
                            <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">
                                {{ formatIDR(payment.amount) }}
                            </td>

                            <!-- Tipe Tagihan -->
                            <td class="px-6 py-4">
                                <span 
                                    class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold"
                                    :class="payment.type === 'down_payment' 
                                        ? 'bg-purple-100 text-purple-800 dark:bg-purple-950/40 dark:text-purple-300' 
                                        : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300'"
                                >
                                    {{ payment.type === 'down_payment' ? 'DP (30%)' : 'Pelunasan (70%)' }}
                                </span>
                            </td>

                            <!-- Metode -->
                            <td class="px-6 py-4 font-mono text-xs text-slate-650 dark:text-slate-400">
                                {{ payment.payment_method ? payment.payment_method.toUpperCase() : '-' }}
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4">
                                <span 
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                    :class="{
                                        'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400': payment.status === 'paid',
                                        'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400': payment.status === 'pending',
                                        'bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-400': ['failed', 'expired'].includes(payment.status)
                                    }"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5"
                                        :class="{
                                            'bg-emerald-500': payment.status === 'paid',
                                            'bg-amber-500': payment.status === 'pending',
                                            'bg-red-500': ['failed', 'expired'].includes(payment.status)
                                        }"
                                    ></span>
                                    {{ payment.status.toUpperCase() }}
                                </span>
                            </td>

                            <!-- Tanggal Transaksi -->
                            <td class="px-6 py-4 text-xs text-slate-500 dark:text-slate-400">
                                {{ formatDateTime(payment.created_at) }}
                            </td>

                            <!-- Tindakan -->
                            <td class="px-6 py-4 text-center">
                                <button 
                                    @click="openDetailModal(payment)"
                                    class="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-350 text-xs font-semibold rounded-lg border border-slate-250/20 dark:border-slate-700 transition"
                                >
                                    ℹ️ Detail
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <Pagination 
                :total-items="filteredPayments.length"
                :items-per-page="itemsPerPage"
                v-model:current-page="currentPage"
            />
        </div>

        <!-- Detail Modal -->
        <div v-if="isDetailModalOpen && selectedPayment" class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-md shadow-2xl p-6 relative">
                <button 
                    @click="closeDetailModal"
                    class="absolute top-4 right-4 text-slate-400 hover:text-slate-650 dark:hover:text-slate-200 p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition"
                >
                    ✕
                </button>

                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">
                    Rincian Transaksi Pembayaran
                </h3>

                <div class="space-y-4 text-sm">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Order ID / ID Transaksi</p>
                        <p class="font-mono text-slate-700 dark:text-slate-300 font-semibold select-all mt-0.5">
                            {{ selectedPayment.transaction_id || '-' }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Klien</p>
                            <p class="font-medium mt-0.5 text-slate-800 dark:text-slate-200">{{ selectedPayment.booking?.client_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Status</p>
                            <p class="font-medium mt-0.5 uppercase" 
                                :class="selectedPayment.status === 'paid' ? 'text-emerald-500' : (selectedPayment.status === 'pending' ? 'text-amber-500' : 'text-red-500')"
                            >
                                {{ selectedPayment.status }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Jumlah Dibayar</p>
                            <p class="font-bold mt-0.5 text-slate-900 dark:text-white">{{ formatIDR(selectedPayment.amount) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tipe</p>
                            <p class="font-medium mt-0.5 text-slate-700 dark:text-slate-350">
                                {{ selectedPayment.type === 'down_payment' ? 'Uang Muka (DP)' : 'Pelunasan' }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Metode Bayar</p>
                            <p class="font-mono mt-0.5 text-slate-700 dark:text-slate-300 uppercase">{{ selectedPayment.payment_method || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Midtrans Token</p>
                            <p class="font-mono text-xs truncate mt-0.5 select-all text-slate-650 dark:text-slate-400" :title="selectedPayment.snap_token">
                                {{ selectedPayment.snap_token || '-' }}
                            </p>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 dark:border-slate-800/80 pt-4 grid grid-cols-2 gap-4 text-xs text-slate-500">
                        <div>
                            <p class="font-bold text-slate-400 uppercase tracking-wider">Tanggal Dibuat</p>
                            <p class="mt-0.5">{{ formatDateTime(selectedPayment.created_at) }}</p>
                        </div>
                        <div>
                            <p class="font-bold text-slate-400 uppercase tracking-wider">Pembaruan Terakhir</p>
                            <p class="mt-0.5">{{ formatDateTime(selectedPayment.updated_at) }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button 
                        @click="closeDetailModal"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl transition"
                    >
                        Batal
                    </button>
                    <Link 
                        :href="`/admin/bookings`"
                        class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold rounded-xl transition"
                    >
                        Buka Detail Reservasi
                    </Link>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
