<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    packages: Array,
    gallery: Array,
    laravelVersion: String,
    phpVersion: String,
});

// Fallback gallery images if database has no photos yet
const defaultGallery = [
    {
        id: 'f1',
        url: 'https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=800&auto=format&fit=crop',
        caption: 'Couple & Prewedding Studio'
    },
    {
        id: 'f2',
        url: 'https://images.unsplash.com/photo-1511285560929-80b456fea0bc?q=80&w=800&auto=format&fit=crop',
        caption: 'Full Wedding Documentation'
    },
    {
        id: 'f3',
        url: 'https://images.unsplash.com/photo-1532712938310-34cb3982ef74?q=80&w=800&auto=format&fit=crop',
        caption: 'Maternity Portrait Session'
    },
    {
        id: 'f4',
        url: 'https://images.unsplash.com/photo-1555252333-9f8e92e65df9?q=80&w=800&auto=format&fit=crop',
        caption: 'Newborn & Baby Photography'
    },
    {
        id: 'f5',
        url: 'https://images.unsplash.com/photo-1542038784456-1ea8e935640e?q=80&w=800&auto=format&fit=crop',
        caption: 'Family & Group Portrait'
    },
    {
        id: 'f6',
        url: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=800&auto=format&fit=crop',
        caption: 'Personal Portrait Session'
    }
];

const displayGallery = computed(() => {
    return props.gallery && props.gallery.length > 0 ? props.gallery : defaultGallery;
});

// Helper to resolve package images dynamically
const getPackageImage = (pkg) => {
    if (pkg.image_path) {
        return '/storage/' + pkg.image_path;
    }
    
    const name = pkg.name.toLowerCase();
    if (name.includes('wedding')) {
        return 'https://images.unsplash.com/photo-1511285560929-80b456fea0bc?q=80&w=600&auto=format&fit=crop';
    } else if (name.includes('couple') || name.includes('prewedding')) {
        return 'https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=600&auto=format&fit=crop';
    } else if (name.includes('family') || name.includes('group')) {
        return 'https://images.unsplash.com/photo-1542038784456-1ea8e935640e?q=80&w=600&auto=format&fit=crop';
    } else if (name.includes('maternity')) {
        return 'https://images.unsplash.com/photo-1532712938310-34cb3982ef74?q=80&w=600&auto=format&fit=crop';
    } else if (name.includes('newborn') || name.includes('baby')) {
        return 'https://images.unsplash.com/photo-1555252333-9f8e92e65df9?q=80&w=600&auto=format&fit=crop';
    }
    
    return 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=600&auto=format&fit=crop'; // fallback portrait
};

// Lightbox Modal state
const isLightboxOpen = ref(false);
const activePhoto = ref(null);

const openLightbox = (photo) => {
    activePhoto.value = photo;
    isLightboxOpen.value = true;
};

const closeLightbox = () => {
    isLightboxOpen.value = false;
    activePhoto.value = null;
};

// Currency Formatter
const formatIDR = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(value);
};
</script>

<template>
    <Head title="Raka Photo Studio - Jasa Dokumentasi & Fotografi Premium" />

    <div class="relative min-h-screen bg-[#F3E4C9] text-amber-950 font-sans antialiased overflow-x-hidden selection:bg-[#FFE8B4]">
        <!-- Premium Texture Soft Glows -->
        <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-[#FFE8B4]/30 rounded-full blur-3xl pointer-events-none -z-10"></div>
        <div class="absolute bottom-20 left-10 w-[400px] h-[400px] bg-[#FFE8B4]/20 rounded-full blur-3xl pointer-events-none -z-10"></div>

        <!-- Sticky Header Navbar -->
        <header class="sticky top-0 bg-[#F3E4C9]/90 backdrop-blur-md z-40 border-b border-amber-900/10 px-6 py-4 transition-all">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <!-- Logo -->
                <Link href="/" class="flex items-center space-x-2 text-xl font-bold tracking-wider uppercase text-amber-950">
                    <span class="text-2xl">📷</span>
                    <span>Raka Studio</span>
                </Link>

                <!-- Navigation Desktop -->
                <nav class="hidden md:flex items-center space-x-8 text-xs font-semibold uppercase tracking-widest text-amber-900/80">
                    <a href="#home" class="hover:text-amber-950 transition-colors">Beranda</a>
                    <a href="#about" class="hover:text-amber-950 transition-colors">Keunggulan</a>
                    <a href="#gallery" class="hover:text-amber-950 transition-colors">Galeri Karya</a>
                    <a href="#packages" class="hover:text-amber-950 transition-colors">Paket Foto</a>
                </nav>

                <!-- Action Button -->
                <div class="flex items-center space-x-4">
                    <Link 
                        href="/login" 
                        class="text-xs font-bold uppercase tracking-widest text-amber-900 hover:text-amber-950 transition-colors"
                    >
                        Portal Staf
                    </Link>
                    <Link 
                        href="/booking" 
                        class="px-5 py-2.5 bg-amber-950 hover:bg-amber-900 text-[#F3E4C9] text-xs font-bold uppercase tracking-widest rounded-lg shadow-sm hover:shadow transition-all"
                    >
                        Pesan Sesi Foto
                    </Link>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section id="home" class="max-w-7xl mx-auto px-6 py-12 md:py-24 grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            <!-- Text Content -->
            <div class="lg:col-span-6 space-y-6 text-left">
                <span class="inline-flex items-center px-3.5 py-1 text-[10px] font-bold uppercase tracking-widest bg-[#FFE8B4] border border-amber-900/10 text-amber-950 rounded-full">
                    ✨ EST. 2026 • Premium Photography Studio
                </span>
                <h1 class="text-4xl sm:text-6xl font-serif font-black leading-tight text-amber-950">
                    Abadikan Kisah <br class="hidden sm:inline" />
                    Terindah <span class="italic font-normal text-amber-800">Hidup Anda</span>
                </h1>
                <p class="text-sm sm:text-base text-amber-900/80 leading-relaxed max-w-lg">
                    Sentuhan fotografi profesional berestetika tinggi untuk mendokumentasikan kehangatan pernikahan, momen kehamilan indah, kelucuan buah hati, hingga potret tahunan keluarga besar Anda.
                </p>
                <div class="pt-4 flex flex-col sm:flex-row gap-4">
                    <a 
                        href="#packages"
                        class="px-8 py-3.5 bg-amber-950 hover:bg-amber-900 text-[#F3E4C9] text-xs font-bold uppercase tracking-widest rounded-lg shadow-lg hover:shadow-xl transition-all text-center"
                    >
                        Lihat Paket & Harga
                    </a>
                    <Link 
                        href="/booking"
                        class="px-8 py-3.5 bg-white/40 hover:bg-white/60 border border-amber-900/20 text-amber-950 text-xs font-bold uppercase tracking-widest rounded-lg transition-all text-center"
                    >
                        Booking Jadwal Online
                    </Link>
                </div>
            </div>

            <!-- Visual Banner Card -->
            <div class="lg:col-span-6 relative">
                <div class="absolute inset-0 bg-[#FFE8B4] rounded-3xl rotate-3 transform translate-x-3 translate-y-3 -z-10"></div>
                <div class="rounded-3xl border border-amber-900/10 overflow-hidden shadow-xl aspect-[4/3] bg-amber-900">
                    <img 
                        src="https://images.unsplash.com/photo-1492691527719-9d1e07e534b4?q=80&w=1200&auto=format&fit=crop" 
                        class="w-full h-full object-cover filter contrast-[1.02]" 
                        alt="Studio Photography Showcase" 
                    />
                </div>
            </div>
        </section>

        <!-- Value Propositions Section -->
        <section id="about" class="bg-[#FFE8B4]/40 border-y border-amber-900/10 py-16 px-6">
            <div class="max-w-7xl mx-auto space-y-12">
                <div class="text-center space-y-3 max-w-xl mx-auto">
                    <h2 class="text-2xl sm:text-3xl font-serif font-bold">Kenapa Memilih Raka Studio?</h2>
                    <p class="text-xs sm:text-sm text-amber-900/70">Kami berkomitmen memberikan pengalaman fotografi yang menyenangkan dan hasil pascaproduksi yang maksimal.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    <!-- Point 1 -->
                    <div class="bg-white/60 dark:bg-white/5 p-6 rounded-2xl border border-amber-900/5 hover:border-amber-900/20 hover:-translate-y-1 shadow-sm transition-all duration-300">
                        <div class="w-12 h-12 bg-[#FFE8B4] rounded-xl flex items-center justify-center text-2xl mb-4 shadow-inner">
                            📸
                        </div>
                        <h4 class="font-bold text-sm text-amber-950 mb-2">Kamera & Studio Kelas Atas</h4>
                        <p class="text-xs text-amber-900/70 leading-relaxed">
                            Menggunakan sensor full-frame resolusi tinggi dan pencahayaan studio premium untuk kualitas detail foto terbaik.
                        </p>
                    </div>

                    <!-- Point 2 -->
                    <div class="bg-white/60 dark:bg-white/5 p-6 rounded-2xl border border-amber-900/5 hover:border-amber-900/20 hover:-translate-y-1 shadow-sm transition-all duration-300">
                        <div class="w-12 h-12 bg-[#FFE8B4] rounded-xl flex items-center justify-center text-2xl mb-4 shadow-inner">
                            👥
                        </div>
                        <h4 class="font-bold text-sm text-amber-950 mb-2">Kru & Fotografer Profesional</h4>
                        <p class="text-xs text-amber-900/70 leading-relaxed">
                            Staf fotografer, pengarah gaya, dan penata rias (MUA) bersertifikat yang ramah, asyik, dan komunikatif.
                        </p>
                    </div>

                    <!-- Point 3 -->
                    <div class="bg-white/60 dark:bg-white/5 p-6 rounded-2xl border border-amber-900/5 hover:border-amber-900/20 hover:-translate-y-1 shadow-sm transition-all duration-300">
                        <div class="w-12 h-12 bg-[#FFE8B4] rounded-xl flex items-center justify-center text-2xl mb-4 shadow-inner">
                            💻
                        </div>
                        <h4 class="font-bold text-sm text-amber-950 mb-2">Sistem Proofing Digital Klien</h4>
                        <p class="text-xs text-amber-900/70 leading-relaxed">
                            Klien dapat meninjau, mengunduh, dan memilih foto-foto mentah yang ber-watermark secara online dari rumah.
                        </p>
                    </div>

                    <!-- Point 4 -->
                    <div class="bg-white/60 dark:bg-white/5 p-6 rounded-2xl border border-amber-900/5 hover:border-amber-900/20 hover:-translate-y-1 shadow-sm transition-all duration-300">
                        <div class="w-12 h-12 bg-[#FFE8B4] rounded-xl flex items-center justify-center text-2xl mb-4 shadow-inner">
                            ✍️
                        </div>
                        <h4 class="font-bold text-sm text-amber-950 mb-2">Administrasi & SPK Transparan</h4>
                        <p class="text-xs text-amber-900/70 leading-relaxed">
                            Kesepakatan aman dengan tanda tangan SPK digital dan sistem pembayaran Midtrans yang fleksibel (DP 30%).
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Gallery Section -->
        <section id="gallery" class="max-w-7xl mx-auto px-6 py-16 space-y-12">
            <div class="text-center space-y-3 max-w-xl mx-auto">
                <h2 class="text-2xl sm:text-3xl font-serif font-bold">Galeri Hasil Karya</h2>
                <p class="text-xs sm:text-sm text-amber-900/70">Beberapa cuplikan kebahagiaan klien yang berhasil didokumentasikan oleh tim fotografer kami.</p>
            </div>

            <!-- Mosaic Photo Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                <div 
                    v-for="photo in displayGallery" 
                    :key="photo.id"
                    @click="openLightbox(photo)"
                    class="group relative bg-[#FFE8B4] rounded-2xl overflow-hidden border border-amber-900/10 aspect-[4/3] sm:aspect-square cursor-pointer shadow-sm hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1"
                >
                    <img 
                        :src="photo.url" 
                        class="w-full h-full object-cover filter grayscale group-hover:grayscale-0 group-hover:scale-105 transition-all duration-500" 
                        alt="Gallery Photo" 
                    />
                    <!-- Overlay Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-t from-amber-950/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-4">
                        <div class="text-left">
                            <p class="text-[10px] uppercase tracking-widest text-[#FFE8B4] font-semibold">🔍 Klik untuk memperbesar</p>
                            <h4 class="text-sm font-bold text-white font-serif mt-1">{{ photo.caption }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Packages Section -->
        <section id="packages" class="bg-[#FFE8B4]/20 border-t border-amber-900/10 py-16 px-6">
            <div class="max-w-7xl mx-auto space-y-12">
                <div class="text-center space-y-3 max-w-xl mx-auto">
                    <h2 class="text-2xl sm:text-3xl font-serif font-bold">Paket Sesi Foto</h2>
                    <p class="text-xs sm:text-sm text-amber-900/70">Sesuaikan layanan sesi dokumentasi dengan kebutuhan dan anggaran dana Anda secara transparan.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Package Card -->
                    <div 
                        v-for="pkg in packages" 
                        :key="pkg.id"
                        class="bg-white dark:bg-slate-900/60 rounded-3xl border border-amber-900/10 shadow-sm overflow-hidden flex flex-col justify-between hover:shadow-xl hover:border-amber-900/20 transition-all duration-300"
                    >
                        <div>
                            <!-- Package Image Banner -->
                            <div class="h-48 bg-amber-900 overflow-hidden relative border-b border-amber-900/5">
                                <img 
                                    :src="getPackageImage(pkg)" 
                                    class="w-full h-full object-cover" 
                                    :alt="pkg.name" 
                                />
                                <div class="absolute top-4 right-4 bg-amber-950 text-[#F3E4C9] text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-md">
                                    ⏱️ {{ pkg.duration_minutes >= 60 ? `${Math.floor(pkg.duration_minutes / 60)} Jam` : '' }} 
                                    {{ pkg.duration_minutes % 60 > 0 ? `${pkg.duration_minutes % 60}m` : '' }}
                                </div>
                            </div>

                            <!-- Package Details -->
                            <div class="p-6 space-y-4">
                                <div>
                                    <h3 class="font-serif font-bold text-lg text-amber-950">{{ pkg.name }}</h3>
                                    <p class="text-xs text-amber-900/70 mt-1 leading-relaxed">{{ pkg.description || 'Sesi foto profesional studio atau lokasi.' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Price & Action Button -->
                        <div class="p-6 pt-0 space-y-4">
                            <div class="flex items-baseline justify-between border-t border-amber-900/5 pt-4">
                                <span class="text-xs font-semibold text-amber-900/60 uppercase tracking-widest">Harga Paket</span>
                                <span class="text-lg font-bold text-amber-950">{{ formatIDR(pkg.price) }}</span>
                            </div>
                            <Link 
                                :href="`/booking?package=${pkg.id}`"
                                class="block w-full py-3 bg-amber-950 hover:bg-amber-900 text-[#F3E4C9] text-xs font-bold uppercase tracking-widest rounded-xl transition text-center shadow-md hover:shadow-lg"
                            >
                                Pesan Sesi Sekarang
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonial & Stats Section -->
        <section class="max-w-7xl mx-auto px-6 py-16 grid grid-cols-1 md:grid-cols-3 gap-8 text-center border-t border-amber-900/10">
            <!-- Stat 1 -->
            <div class="space-y-2">
                <p class="text-4xl font-serif font-black text-amber-950">1.200+</p>
                <h4 class="font-bold text-xs uppercase tracking-widest text-amber-900/60">Klien Tersenyum</h4>
                <p class="text-xs text-amber-900/70 max-w-[240px] mx-auto">Telah mempercayakan momen penting mereka kepada lensa studio kami.</p>
            </div>
            <!-- Stat 2 -->
            <div class="space-y-2 border-y md:border-y-0 md:border-x border-amber-900/10 py-6 md:py-0">
                <p class="text-4xl font-serif font-black text-amber-950">150+</p>
                <h4 class="font-bold text-xs uppercase tracking-widest text-amber-900/60">Hari Pernikahan</h4>
                <p class="text-xs text-amber-900/70 max-w-[240px] mx-auto">Liputan foto pernikahan lengkap di dalam dan luar kota Bogor.</p>
            </div>
            <!-- Stat 3 -->
            <div class="space-y-2">
                <p class="text-4xl font-serif font-black text-amber-950">100%</p>
                <h4 class="font-bold text-xs uppercase tracking-widest text-amber-900/60">Garansi Kepuasan</h4>
                <p class="text-xs text-amber-900/70 max-w-[240px] mx-auto">Kami mengutamakan revisi penataan rias dan kualitas cetakan foto premium.</p>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-amber-900/10 bg-[#FFE8B4]/30 py-12 px-6">
            <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-12 gap-8 text-left">
                <!-- Brand -->
                <div class="md:col-span-5 space-y-4">
                    <span class="flex items-center space-x-2 text-base font-bold tracking-wider uppercase text-amber-950">
                        <span>📷</span>
                        <span>Raka Studio</span>
                    </span>
                    <p class="text-xs text-amber-900/70 leading-relaxed max-w-sm">
                        Menangkap detak kehidupan, memendam kebahagiaan dalam bentuk potret fisik berbingkai premium untuk keluarga dan momen spesial Anda.
                    </p>
                    <p class="text-[10px] text-amber-900/50">
                        Powered by Laravel v{{ laravelVersion }} + PHP v{{ phpVersion }}
                    </p>
                </div>

                <!-- Open Hours -->
                <div class="md:col-span-3 space-y-3">
                    <h5 class="text-xs font-bold uppercase tracking-wider text-amber-950">Jam Operasional</h5>
                    <ul class="text-xs text-amber-900/70 space-y-1.5 font-medium">
                        <li>Senin - Jumat: 09.00 - 18.00 WIB</li>
                        <li>Sabtu - Minggu: 08.00 - 20.00 WIB</li>
                        <li class="text-[10px] text-amber-800 italic font-semibold">Sesi outdoor perlu booking minimal H-7</li>
                    </ul>
                </div>

                <!-- Contact -->
                <div class="md:col-span-4 space-y-3">
                    <h5 class="text-xs font-bold uppercase tracking-wider text-amber-950">Hubungi Kami</h5>
                    <p class="text-xs text-amber-900/70 leading-relaxed font-medium">
                        📍 Jl. Raya Pajajaran No. 123, Bogor Tengah, Jawa Barat<br />
                        📞 CS WhatsApp: 0812-3456-7890<br />
                        ✉️ Email: info@raka.photo
                    </p>
                </div>
            </div>
            
            <div class="max-w-7xl mx-auto border-t border-amber-900/5 mt-8 pt-6 flex justify-between items-center text-[10px] text-amber-900/50 font-bold uppercase tracking-widest">
                <span>© 2026 Raka Photo Studio. Hak Cipta Dilindungi.</span>
                <span>Bogor • Indonesia</span>
            </div>
        </footer>

        <!-- Lightbox Modal Fullscreen -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div 
                v-if="isLightboxOpen" 
                class="fixed inset-0 z-50 bg-black/90 backdrop-blur-md flex flex-col justify-between p-6"
                @click.self="closeLightbox"
            >
                <!-- Lightbox Header -->
                <div class="flex items-center justify-between text-white w-full max-w-6xl mx-auto z-10">
                    <h4 class="font-serif text-base font-semibold">{{ activePhoto?.caption }}</h4>
                    <button 
                        @click="closeLightbox" 
                        class="p-2 bg-white/10 hover:bg-white/20 text-white rounded-full text-lg transition duration-200"
                    >
                        ✕
                    </button>
                </div>

                <!-- Lightbox Image -->
                <div class="flex-grow flex items-center justify-center p-4">
                    <img 
                        :src="activePhoto?.url" 
                        class="max-w-full max-h-[80vh] rounded-xl object-contain shadow-2xl border border-white/10" 
                        alt="Fullscreen View" 
                    />
                </div>

                <!-- Lightbox Footer -->
                <div class="text-center text-white/50 text-xs w-full max-w-6xl mx-auto">
                    Klik di luar gambar untuk menutup tampilan penuh.
                </div>
            </div>
        </Transition>
    </div>
</template>
