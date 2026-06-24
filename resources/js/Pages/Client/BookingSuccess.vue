<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '../../Layouts/GuestLayout.vue';
</script>

<template>
    <GuestLayout>
        <Head title="Booking Berhasil" />

        <div class="text-center py-6">
            <!-- Animated Checkmark Icon -->
            <div class="w-16 h-16 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500 rounded-full flex items-center justify-center text-4xl mx-auto mb-6 shadow-inner">
                🎉
            </div>

            <h2 class="text-2xl font-extrabold text-slate-800 dark:text-slate-100 mb-2">
                Pemesanan Berhasil Dikirim!
            </h2>
            
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 leading-relaxed">
                Jadwal pemotretan Anda telah terdaftar di sistem. Tim kami akan segera meninjau ketersediaan slot dan menghubungi Anda kembali.
            </p>

            <div class="p-4 bg-slate-50 dark:bg-slate-950/60 border border-slate-200/60 dark:border-slate-850 rounded-xl mb-6 text-left text-xs text-slate-500 dark:text-slate-400 space-y-2">
                <p class="font-bold text-slate-700 dark:text-slate-350">Langkah Selanjutnya:</p>
                <ul class="list-disc pl-4 space-y-1">
                    <li>Kami akan mengirimkan notifikasi persetujuan via WhatsApp/Email.</li>
                    <li>Anda akan diminta menandatangani <strong>Surat Perjanjian Kerja (SPK) digital</strong>.</li>
                    <li>Melakukan pembayaran **Uang Muka (DP)** untuk mengamankan jadwal Anda.</li>
                </ul>
            </div>

            <Link 
                href="/" 
                class="inline-flex w-full justify-center px-4 py-2.5 bg-violet-600 hover:bg-violet-700 text-white font-medium text-sm rounded-lg shadow-sm hover:shadow transition-all"
            >
                Kembali ke Halaman Utama
            </Link>
        </div>
    </GuestLayout>
</template>
