<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    laravelVersion: String,
    phpVersion: String,
});

const modules = ref([
    {
        title: 'Smart Scheduling & Booking',
        description: 'Kalender booking interaktif dengan sistem auto-blocking jadwal untuk mencegah bentrok sesi.',
        icon: '📅',
    },
    {
        title: 'Interactive Client Gallery',
        description: 'Galeri foto online ber-watermark otomatis untuk proses seleksi foto (proofing) langsung oleh klien.',
        icon: '🖼️',
    },
    {
        title: 'Digital SPK & E-Contract',
        description: 'Tanda tangan kontrak digital secara langsung melalui layar HP/sentuhan untuk keamanan kerja sama hukum.',
        icon: '✍️',
    },
    {
        title: 'Automated WA Notifications',
        description: 'Pengiriman invoice, link pembayaran Midtrans, dan detail status pemesanan secara instan via WhatsApp.',
        icon: '💬',
    }
]);
</script>

<template>
    <Head title="Welcome to Raka Photo Studio" />

    <div class="relative min-h-screen bg-slate-50 text-slate-800 flex flex-col justify-between overflow-hidden font-sans">
        <!-- Background Soft Glow Effects (Light Mode Version) -->
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-violet-200/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-10 right-1/4 w-96 h-96 bg-indigo-200/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Header -->
        <header class="max-w-7xl mx-auto w-full px-6 py-6 flex justify-between items-center z-10">
            <div class="flex items-center space-x-3">
                <Link href="/" class="text-2xl font-black bg-gradient-to-r from-violet-600 to-indigo-600 bg-clip-text text-transparent">
                    📷 Raka Photo
                </Link>
            </div>
            
            <div class="flex items-center space-x-3 sm:space-x-4">
                <Link 
                    href="/login"
                    class="px-4 py-2 text-slate-600 hover:text-slate-900 text-xs font-bold transition-all"
                >
                    🔑 Portal Admin
                </Link>
                <Link 
                    href="/booking"
                    class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white font-bold text-xs rounded-xl shadow-sm hover:shadow transition-all"
                >
                    📅 Booking Online
                </Link>
            </div>
        </header>

        <!-- Hero Content -->
        <main class="max-w-7xl mx-auto w-full px-6 py-16 sm:py-24 flex-grow flex flex-col justify-center z-10">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <!-- Soft Badge -->
                <span class="inline-flex items-center px-3 py-1 text-xs font-bold bg-violet-50 text-violet-600 border border-violet-100 rounded-full mb-6 uppercase tracking-wider">
                    ✨ Integrated Photography Studio System
                </span>
                
                <h1 class="text-4xl sm:text-6xl font-black tracking-tight mb-6 text-slate-900 leading-tight">
                    Abadikan Momen Anda Bersama
                    <span class="block mt-2 bg-gradient-to-r from-violet-600 to-indigo-600 bg-clip-text text-transparent">
                        Raka Photo Studio
                    </span>
                </h1>
                
                <p class="text-base sm:text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto">
                    Sistem reservasi modern, galeri proofing interaktif klien dengan sistem watermark, pembayaran Midtrans terintegrasi, dan administrasi digital dalam satu platform.
                </p>
                
                <div class="mt-8 flex flex-col sm:flex-row justify-center items-center gap-4">
                    <Link 
                        href="/booking"
                        class="w-full sm:w-auto px-6 py-3 bg-violet-600 hover:bg-violet-700 text-white font-bold text-sm rounded-xl shadow-md shadow-violet-500/10 hover:shadow-lg transition-all text-center"
                    >
                        🚀 Mulai Booking Sesi Foto
                    </Link>
                    <Link 
                        href="/login"
                        class="w-full sm:w-auto px-6 py-3 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-bold text-sm rounded-xl shadow-sm transition-all text-center"
                    >
                        Masuk ke Portal Staf
                    </Link>
                </div>
            </div>

            <!-- Features Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mx-auto">
                <div 
                    v-for="(mod, index) in modules" 
                    :key="index"
                    class="p-6 bg-white border border-slate-200/80 hover:border-violet-300 hover:shadow-md rounded-2xl transition-all duration-300 group hover:-translate-y-1"
                >
                    <div class="w-10 h-10 rounded-xl bg-violet-50 text-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                        {{ mod.icon }}
                    </div>
                    <h3 class="text-lg font-bold mb-2 text-slate-900 group-hover:text-violet-600 transition-colors">
                        {{ mod.title }}
                    </h3>
                    <p class="text-slate-500 text-xs leading-relaxed">
                        {{ mod.description }}
                    </p>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-200 bg-white">
            <div class="max-w-7xl mx-auto px-6 py-6 flex flex-col md:flex-row justify-between items-center text-xs text-slate-450 font-medium">
                <p>© 2026 Raka Photo Studio. All rights reserved.</p>
                <div class="flex items-center space-x-6 mt-4 md:mt-0">
                    <span class="text-slate-400">Laravel v{{ laravelVersion }} + PHP v{{ phpVersion }}</span>
                    <span class="text-slate-400">System: <span class="text-emerald-500 font-bold">● Operational</span></span>
                </div>
            </div>
        </footer>
    </div>
</template>
