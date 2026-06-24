<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import ThemeToggle from '../../Components/ThemeToggle.vue';

const props = defineProps({
    booking: Object,
    hash: String,
});

// Flash Notifications
const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const showSuccessNotification = ref(false);
const showErrorNotification = ref(false);

watch(flashSuccess, (newVal) => {
    if (newVal) {
        showSuccessNotification.value = true;
        setTimeout(() => showSuccessNotification.value = false, 4000);
    }
}, { immediate: true });

watch(flashError, (newVal) => {
    if (newVal) {
        showErrorNotification.value = true;
        setTimeout(() => showErrorNotification.value = false, 4000);
    }
}, { immediate: true });

// Auto-determine proofing selection limit based on package name
const selectionLimit = computed(() => {
    const name = props.booking.package?.name || '';
    if (name.includes('Personal')) return 5;
    if (name.includes('Couple') || name.includes('Prewedding')) return 10;
    if (name.includes('Family') || name.includes('Group')) return 15;
    if (name.includes('Wedding')) return 30;
    return 10; // Default limit
});

// Selection state
const selectedPhotoIds = ref(
    props.booking.photos?.filter(p => p.is_selected).map(p => p.id) || []
);

const isLimitReached = computed(() => {
    return selectedPhotoIds.value.length >= selectionLimit.value;
});

const togglePhotoSelection = (photoId) => {
    const index = selectedPhotoIds.value.indexOf(photoId);
    if (index > -1) {
        // Unselect
        selectedPhotoIds.value.splice(index, 1);
    } else {
        // Select (check limit)
        if (isLimitReached.value) {
            alert(`Batas seleksi maksimum untuk paket Anda adalah ${selectionLimit.value} foto.`);
            return;
        }
        selectedPhotoIds.value.push(photoId);
    }
};

// Lightbox state
const isLightboxOpen = ref(false);
const lightboxIndex = ref(0);

const currentLightboxPhoto = computed(() => {
    const photos = props.booking.photos || [];
    return photos[lightboxIndex.value] || null;
});

const openLightbox = (photoId) => {
    const photos = props.booking.photos || [];
    const idx = photos.findIndex(p => p.id === photoId);
    if (idx > -1) {
        lightboxIndex.value = idx;
        isLightboxOpen.value = true;
    }
};

const closeLightbox = () => {
    isLightboxOpen.value = false;
};

const nextLightboxPhoto = () => {
    const photos = props.booking.photos || [];
    if (lightboxIndex.value < photos.length - 1) {
        lightboxIndex.value++;
    } else {
        lightboxIndex.value = 0; // wrap around
    }
};

const prevLightboxPhoto = () => {
    const photos = props.booking.photos || [];
    if (lightboxIndex.value > 0) {
        lightboxIndex.value--;
    } else {
        lightboxIndex.value = photos.length - 1; // wrap around
    }
};

// Form for submitting selected photos
const selectionForm = useForm({
    photo_ids: [],
});

const submitSelection = () => {
    selectionForm.photo_ids = selectedPhotoIds.value;
    selectionForm.post(`/booking/${props.booking.id}/gallery/${props.hash}/select`, {
        preserveScroll: true,
        onSuccess: () => {
            // Success alert
        }
    });
};

const formatLocalDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-955 text-slate-800 dark:text-slate-100 flex flex-col font-sans transition-colors duration-300">
        <Head title="Portal Galeri Foto & Proofing" />

        <!-- Floating Notifications -->
        <div class="fixed top-6 right-6 z-50 space-y-3">
            <div v-if="showSuccessNotification && flashSuccess"
                class="flex items-center p-4 bg-emerald-50 dark:bg-emerald-950/85 border border-emerald-250 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-lg shadow-lg max-w-sm transition-all animate-in fade-in"
            >
                <span class="text-lg mr-3">✅</span>
                <div class="text-sm font-medium mr-8">{{ flashSuccess }}</div>
                <button @click="showSuccessNotification = false" class="text-emerald-500 hover:text-emerald-700 ml-auto">✕</button>
            </div>

            <div v-if="showErrorNotification && flashError"
                class="flex items-center p-4 bg-red-50 dark:bg-red-95/85 border border-red-255 dark:border-red-800 text-red-800 dark:text-red-300 rounded-lg shadow-lg max-w-sm transition-all animate-in fade-in"
            >
                <span class="text-lg mr-3">⚠️</span>
                <div class="text-sm font-medium mr-8">{{ flashError }}</div>
                <button @click="showErrorNotification = false" class="text-red-500 hover:text-red-700 ml-auto">✕</button>
            </div>
        </div>

        <!-- Premium Header -->
        <header class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 py-4 px-6 flex justify-between items-center shadow-sm sticky top-0 z-30 transition-colors">
            <div class="flex items-center space-x-3">
                <span class="text-xl">📷</span>
                <div>
                    <h1 class="text-base font-bold bg-gradient-to-r from-violet-600 to-indigo-600 dark:from-violet-400 dark:to-indigo-400 bg-clip-text text-transparent">
                        PhotoStudio
                    </h1>
                    <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Client Portal</p>
                </div>
            </div>

            <!-- Page Title details -->
            <div class="hidden md:block text-center">
                <h2 class="text-sm font-bold">Galeri Foto Klien: {{ booking.client_name }}</h2>
                <p class="text-[10px] text-slate-400 dark:text-slate-500">
                    Sesi {{ booking.package?.name }} — {{ formatLocalDate(booking.booking_date) }}
                </p>
            </div>

            <div class="flex items-center space-x-4">
                <ThemeToggle />
            </div>
        </header>

        <!-- Main Body -->
        <main class="flex-grow p-6 max-w-7xl w-full mx-auto">
            
            <!-- Mobile Header details -->
            <div class="block md:hidden mb-6 text-center bg-white dark:bg-slate-900 p-4 border border-slate-200 dark:border-slate-800 rounded-2xl">
                <h2 class="text-sm font-bold">Galeri Foto Klien: {{ booking.client_name }}</h2>
                <p class="text-[10px] text-slate-400 dark:text-slate-500">
                    Sesi {{ booking.package?.name }} — {{ formatLocalDate(booking.booking_date) }}
                </p>
            </div>

            <!-- Proofing Stats Bar -->
            <div class="mb-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 transition-all">
                <div class="space-y-1">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center">
                        🎯 Menu Seleksi Foto Klien (Proofing)
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Silakan pilih foto-foto mentah di bawah ini yang ingin Anda edit. Pilihan Anda akan langsung masuk ke tim editor kami.
                    </p>
                </div>

                <!-- Selection Counter and submit -->
                <div class="flex items-center space-x-4 self-start sm:self-center">
                    <div class="text-right">
                        <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Terpilih</p>
                        <p class="text-base font-extrabold text-violet-600 dark:text-violet-400">
                            {{ selectedPhotoIds.length }} / {{ selectionLimit }} <span class="text-xs text-slate-550 dark:text-slate-500">Foto</span>
                        </p>
                    </div>
                    
                    <button 
                        @click="submitSelection"
                        :disabled="selectedPhotoIds.length === 0 || selectionForm.processing"
                        class="px-5 py-2.5 bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold rounded-lg shadow-md hover:shadow-lg disabled:opacity-40 disabled:cursor-not-allowed transition-all cursor-pointer flex items-center space-x-1.5"
                    >
                        <span>{{ selectionForm.processing ? 'Menyimpan...' : '💾 Kirim Pilihan' }}</span>
                    </button>
                </div>
            </div>

            <!-- Photo Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4" v-if="booking.photos?.length > 0">
                <div 
                    v-for="photo in booking.photos" 
                    :key="photo.id"
                    class="bg-white dark:bg-slate-900 border rounded-2xl overflow-hidden shadow-sm flex flex-col relative group select-none transition-all duration-300"
                    :class="selectedPhotoIds.includes(photo.id) 
                        ? 'border-violet-500 dark:border-violet-400 ring-2 ring-violet-500/10' 
                        : 'border-slate-200 dark:border-slate-800'"
                >
                    <!-- Image Wrapper with anti-download protections -->
                    <div class="aspect-square relative overflow-hidden bg-slate-100 dark:bg-slate-950 flex items-center justify-center">
                        <img 
                            :src="photo.raw_url" 
                            class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105 pointer-events-none"
                            oncontextmenu="return false;"
                        />
                        
                        <!-- Transparent Overlay to block drag/clicks -->
                        <div class="absolute inset-0 z-10 bg-transparent"></div>

                        <!-- Zoom/Lightbox trigger -->
                        <button 
                            @click="openLightbox(photo.id)"
                            class="absolute bottom-2.5 right-2.5 z-20 w-8 h-8 rounded-full bg-slate-950/60 hover:bg-slate-950/80 text-white flex items-center justify-center text-sm shadow-md transition-all opacity-0 group-hover:opacity-100"
                            title="Perbesar Foto"
                        >
                            🔍
                        </button>
                    </div>

                    <!-- Card Body Select check -->
                    <div 
                        @click="togglePhotoSelection(photo.id)"
                        class="p-3 flex items-center justify-between cursor-pointer border-t border-slate-150 dark:border-slate-850/80 bg-white dark:bg-slate-900 z-20"
                    >
                        <div class="flex items-center space-x-2">
                            <!-- Custom Checkbox -->
                            <div 
                                class="w-4 h-4 rounded border flex items-center justify-center transition-colors"
                                :class="selectedPhotoIds.includes(photo.id) 
                                    ? 'bg-violet-600 border-violet-600 text-white' 
                                    : 'border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950'"
                            >
                                <span v-if="selectedPhotoIds.includes(photo.id)" class="text-[9px] font-bold">✓</span>
                            </div>
                            <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400">PILIH</span>
                        </div>

                        <!-- Status badge -->
                        <div class="flex space-x-1">
                            <span v-if="photo.status === 'edited'" class="inline-flex px-1.5 py-0.5 rounded text-[8px] font-extrabold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400">
                                EDITED
                            </span>
                            <a 
                                v-if="photo.status === 'edited' && photo.edited_url" 
                                :href="photo.edited_url" 
                                target="_blank"
                                @click.stop
                                class="text-[8px] font-bold text-violet-600 hover:text-violet-750 dark:text-violet-400 dark:hover:text-violet-300"
                            >
                                DOWNLOAD ↗
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-20 rounded-2xl text-center text-slate-400">
                <p class="text-5xl mb-4">🖼️</p>
                <p class="text-sm font-semibold">Galeri Foto Belum Tersedia.</p>
                <p class="text-xs text-slate-500 mt-1">Tim fotografer kami sedang memproses unggah file mentah. Silakan hubungi admin studio jika ada pertanyaan.</p>
            </div>
        </main>

        <!-- Lightbox Fullscreen Browser -->
        <div v-if="isLightboxOpen && currentLightboxPhoto" class="fixed inset-0 z-50 bg-slate-950/95 backdrop-blur-md flex flex-col justify-between p-4 animate-in fade-in duration-200">
            <!-- Lightbox Header -->
            <div class="flex justify-between items-center text-white p-2">
                <div class="text-xs">
                    <p class="font-bold">Foto {{ lightboxIndex + 1 }} dari {{ booking.photos.length }}</p>
                    <p class="text-[9px] text-slate-400 font-mono mt-0.5">ID: {{ currentLightboxPhoto.id }}</p>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Lightbox Select Button -->
                    <button 
                        @click="togglePhotoSelection(currentLightboxPhoto.id)"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors flex items-center space-x-1.5 cursor-pointer"
                        :class="selectedPhotoIds.includes(currentLightboxPhoto.id) 
                            ? 'bg-violet-600 text-white' 
                            : 'bg-slate-800 hover:bg-slate-700 text-slate-300'"
                    >
                        <span>{{ selectedPhotoIds.includes(currentLightboxPhoto.id) ? '✓ Terpilih' : '➕ Pilih Foto' }}</span>
                    </button>
                    
                    <button @click="closeLightbox" class="text-white hover:text-slate-300 text-lg">✕</button>
                </div>
            </div>

            <!-- Lightbox Main Area -->
            <div class="flex-grow flex items-center justify-between relative overflow-hidden select-none">
                <!-- Prev Button -->
                <button 
                    @click="prevLightboxPhoto"
                    class="absolute left-4 z-30 w-10 h-10 rounded-full bg-slate-900/60 hover:bg-slate-900/80 text-white flex items-center justify-center text-lg shadow transition-all cursor-pointer"
                >
                    ◀
                </button>

                <!-- Fullscreen Image Wrapper with transparent protection overlay -->
                <div class="w-full h-full flex items-center justify-center p-8 relative">
                    <img 
                        :src="currentLightboxPhoto.raw_url" 
                        class="max-w-full max-h-full object-contain pointer-events-none rounded shadow-2xl"
                        oncontextmenu="return false;"
                    />
                    <div class="absolute inset-0 bg-transparent z-10"></div>
                </div>

                <!-- Next Button -->
                <button 
                    @click="nextLightboxPhoto"
                    class="absolute right-4 z-30 w-10 h-10 rounded-full bg-slate-900/60 hover:bg-slate-900/80 text-white flex items-center justify-center text-lg shadow transition-all cursor-pointer"
                >
                    ▶
                </button>
            </div>

            <!-- Lightbox Footer Details -->
            <div class="text-center text-slate-400 p-2 text-xs flex justify-center items-center space-x-2">
                <span class="text-[9px] bg-slate-800 text-slate-350 px-1.5 py-0.5 rounded">PROTECTED PREVIEW</span>
                <span class="text-slate-600">•</span>
                <span>Watermark otomatis dan kualitas resolusi tinggi akan disertakan setelah pelunasan dan proses editing selesai.</span>
            </div>
        </div>
    </div>
</template>
