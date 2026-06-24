<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    booking: Object,
    clientGalleryUrl: String,
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

// Copy link to clipboard helper
const showCopiedLabel = ref(false);
const copyLinkToClipboard = () => {
    navigator.clipboard.writeText(props.clientGalleryUrl);
    showCopiedLabel.value = true;
    setTimeout(() => showCopiedLabel.value = false, 2000);
};

// Upload Raw Photos Form
const uploadForm = useForm({
    photos: [],
});

const isUploading = ref(false);
const handleFileChange = (e) => {
    uploadForm.photos = Array.from(e.target.files);
};

const submitUpload = () => {
    isUploading.value = true;
    uploadForm.post(`/admin/bookings/${props.booking.id}/gallery/upload`, {
        onSuccess: () => {
            uploadForm.reset();
            isUploading.value = false;
            // Reset input element
            const input = document.getElementById('photo-upload-input');
            if (input) input.value = '';
        },
        onError: () => {
            isUploading.value = false;
        }
    });
};

// Upload Edited Version Form
const activePhotoForEdit = ref(null);
const isEditUploadModalOpen = ref(false);

const editedForm = useForm({
    edited_photo: null,
});

const openEditUploadModal = (photo) => {
    activePhotoForEdit.value = photo;
    editedForm.reset();
    isEditUploadModalOpen.value = true;
};

const closeEditUploadModal = () => {
    activePhotoForEdit.value = null;
    editedForm.reset();
    isEditUploadModalOpen.value = false;
};

const handleEditedFileChange = (e) => {
    if (e.target.files.length > 0) {
        editedForm.edited_photo = e.target.files[0];
    }
};

const submitEditedUpload = () => {
    editedForm.post(`/admin/bookings/gallery/${activePhotoForEdit.value.id}/upload-edited`, {
        onSuccess: () => closeEditUploadModal(),
    });
};

// Delete Photo Form
const deleteForm = useForm({});
const confirmDeletePhoto = (photoId) => {
    if (confirm('Apakah Anda yakin ingin menghapus foto ini dari galeri?')) {
        deleteForm.delete(`/admin/bookings/gallery/${photoId}`, {
            preserveScroll: true,
        });
    }
};

// Tabs state
const activeTab = ref('all'); // all, selected, edited

const filteredPhotos = computed(() => {
    const photos = props.booking.photos || [];
    if (activeTab.value === 'selected') {
        return photos.filter(p => p.is_selected && p.status !== 'edited');
    }
    if (activeTab.value === 'edited') {
        return photos.filter(p => p.status === 'edited');
    }
    return photos;
});
</script>

<template>
    <AdminLayout>
        <Head title="Kelola Galeri Foto" />

        <template #title>
            Kelola Galeri Sesi Foto
        </template>

        <!-- Floating Notifications -->
        <div class="fixed top-6 right-6 z-50 space-y-3">
            <div v-if="showSuccessNotification && flashSuccess"
                class="flex items-center p-4 bg-emerald-50 dark:bg-emerald-955/85 border border-emerald-250 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-lg shadow-lg max-w-sm transition-all animate-in fade-in"
            >
                <span class="text-lg mr-3">✅</span>
                <div class="text-sm font-medium mr-8">{{ flashSuccess }}</div>
                <button @click="showSuccessNotification = false" class="text-emerald-500 hover:text-emerald-700 ml-auto">✕</button>
            </div>

            <div v-if="showErrorNotification && flashError"
                class="flex items-center p-4 bg-red-50 dark:bg-red-955/85 border border-red-255 dark:border-red-800 text-red-800 dark:text-red-300 rounded-lg shadow-lg max-w-sm transition-all animate-in fade-in"
            >
                <span class="text-lg mr-3">⚠️</span>
                <div class="text-sm font-medium mr-8">{{ flashError }}</div>
                <button @click="showErrorNotification = false" class="text-red-500 hover:text-red-700 ml-auto">✕</button>
            </div>
        </div>

        <!-- Back Button & Secure link info -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <Link 
                    href="/admin/bookings"
                    class="text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 flex items-center space-x-1"
                >
                    <span>← Kembali ke Pemesanan</span>
                </Link>
                <h3 class="text-base text-slate-500 dark:text-slate-400 mt-1">
                    Klien: <span class="font-bold text-slate-700 dark:text-slate-200">{{ booking.client_name }}</span> — Paket: <span class="font-bold text-slate-700 dark:text-slate-200">{{ booking.package?.name }}</span>
                </h3>
            </div>
            
            <!-- Secure Link Card -->
            <div class="flex items-center space-x-2 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-2.5 rounded-xl">
                <div class="min-w-0">
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Secure Client Link</p>
                    <p class="text-xs font-semibold truncate text-slate-650 dark:text-slate-350 max-w-[200px]" :title="clientGalleryUrl">
                        {{ clientGalleryUrl }}
                    </p>
                </div>
                <button 
                    @click="copyLinkToClipboard"
                    class="px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-all cursor-pointer whitespace-nowrap"
                >
                    {{ showCopiedLabel ? 'Copied! ✓' : '📋 Salin Link' }}
                </button>
            </div>
        </div>

        <!-- Split Grid: Upload Panel & Gallery Listing -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            
            <!-- Upload Panel -->
            <div class="lg:col-span-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-xl shadow-sm self-start">
                <h4 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">UNGAH FOTO MENTAH (RAW)</h4>
                
                <form @submit.prevent="submitUpload" class="space-y-4">
                    <div class="border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-xl p-4 text-center hover:border-violet-500 transition-colors relative cursor-pointer group">
                        <input 
                            type="file" 
                            id="photo-upload-input"
                            multiple
                            accept="image/*"
                            @change="handleFileChange"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                        />
                        <div class="space-y-1">
                            <span class="text-2xl block group-hover:scale-110 transition-transform">📤</span>
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 block">Pilih File Foto</span>
                            <span class="text-[10px] text-slate-400 block">Format JPEG/PNG, Max 12MB</span>
                        </div>
                    </div>

                    <!-- Selected Files Count -->
                    <div v-if="uploadForm.photos.length > 0" class="p-2 bg-slate-55/40 dark:bg-slate-950/40 border border-slate-150 dark:border-slate-800/80 rounded-lg text-xs">
                        <p class="font-semibold text-slate-700 dark:text-slate-300">File siap diunggah:</p>
                        <p class="text-slate-500 font-mono mt-0.5">{{ uploadForm.photos.length }} foto terpilih.</p>
                    </div>

                    <button 
                        type="submit"
                        :disabled="uploadForm.photos.length === 0 || isUploading"
                        class="w-full py-2.5 bg-violet-600 hover:bg-violet-700 text-white text-xs font-semibold rounded-lg shadow-sm disabled:opacity-40 disabled:cursor-not-allowed transition-all cursor-pointer flex items-center justify-center space-x-1.5"
                    >
                        <span>{{ isUploading ? 'Mengunggah...' : '🚀 Mulai Unggah' }}</span>
                    </button>
                </form>
            </div>

            <!-- Gallery Listing Panel -->
            <div class="lg:col-span-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-xl shadow-sm">
                
                <!-- Tab Controls -->
                <div class="flex items-center justify-between pb-4 border-b border-slate-150 dark:border-slate-800 mb-6 gap-3 flex-wrap">
                    <div class="flex bg-slate-100 dark:bg-slate-950 p-1 rounded-lg">
                        <button 
                            @click="activeTab = 'all'"
                            class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all cursor-pointer"
                            :class="activeTab === 'all' 
                                ? 'bg-white dark:bg-slate-850 text-violet-600 dark:text-violet-400 shadow-sm' 
                                : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                        >
                            Semua Foto ({{ booking.photos?.length || 0 }})
                        </button>
                        <button 
                            @click="activeTab = 'selected'"
                            class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all cursor-pointer"
                            :class="activeTab === 'selected' 
                                ? 'bg-white dark:bg-slate-850 text-violet-600 dark:text-violet-400 shadow-sm' 
                                : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                        >
                            Dipilih Klien ({{ booking.photos?.filter(p => p.is_selected && p.status !== 'edited').length || 0 }})
                        </button>
                        <button 
                            @click="activeTab = 'edited'"
                            class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all cursor-pointer"
                            :class="activeTab === 'edited' 
                                ? 'bg-white dark:bg-slate-850 text-violet-600 dark:text-violet-400 shadow-sm' 
                                : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                        >
                            Selesai Diedit ({{ booking.photos?.filter(p => p.status === 'edited').length || 0 }})
                        </button>
                    </div>
                </div>

                <!-- Photos Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4" v-if="filteredPhotos.length > 0">
                    <div 
                        v-for="photo in filteredPhotos" 
                        :key="photo.id"
                        class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm flex flex-col justify-between group"
                    >
                        <!-- Image View -->
                        <div class="aspect-square relative overflow-hidden bg-slate-100 dark:bg-slate-900 flex items-center justify-center">
                            <img 
                                :src="photo.raw_url" 
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                alt="Gallery Preview"
                            />
                            
                            <!-- Badges -->
                            <div class="absolute top-2 left-2 space-y-1">
                                <span v-if="photo.is_selected" class="inline-flex px-1.5 py-0.5 rounded text-[8px] font-bold bg-amber-500 text-white shadow-sm">
                                    DIPILIH
                                </span>
                                <span v-if="photo.status === 'edited'" class="inline-flex px-1.5 py-0.5 rounded text-[8px] font-bold bg-emerald-500 text-white shadow-sm">
                                    EDITED
                                </span>
                            </div>
                        </div>

                        <!-- Card Action footer -->
                        <div class="p-2 border-t border-slate-150 dark:border-slate-850/80 flex items-center justify-between bg-white dark:bg-slate-900">
                            <!-- Hapus -->
                            <button 
                                @click="confirmDeletePhoto(photo.id)"
                                class="text-[10px] font-semibold text-red-500 hover:text-red-700 dark:hover:text-red-400 p-1 cursor-pointer transition-colors"
                            >
                                🗑️ Hapus
                            </button>

                            <!-- Upload Edited Button -->
                            <button 
                                v-if="photo.is_selected"
                                @click="openEditUploadModal(photo)"
                                class="px-2 py-0.5 bg-violet-50 hover:bg-violet-100 dark:bg-violet-900/20 dark:hover:bg-violet-905/40 text-violet-600 dark:text-violet-400 text-[10px] font-bold rounded cursor-pointer transition-all border border-violet-100 dark:border-violet-800/80"
                            >
                                {{ photo.status === 'edited' ? '✏️ Edit Ulang' : '📤 Upload Edit' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-20 text-slate-400">
                    <p class="text-4xl mb-3">🖼️</p>
                    <p class="text-xs">Belum ada foto dalam kategori ini.</p>
                </div>
            </div>
        </div>

        <!-- Upload Edited Photo Modal -->
        <div v-if="isEditUploadModalOpen && activePhotoForEdit" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/60 dark:bg-slate-950/80 backdrop-blur-sm" @click="closeEditUploadModal"></div>

            <div class="bg-white dark:bg-slate-900 w-full max-w-[420px] rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl z-10 overflow-hidden flex flex-col transform transition-all animate-in fade-in zoom-in-95 duration-200">
                <div class="px-6 py-4 border-b border-slate-150 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">
                        📤 Unggah Foto Hasil Edit Final
                    </h3>
                    <button @click="closeEditUploadModal" class="text-slate-400 hover:text-slate-650 dark:hover:text-slate-200 text-sm">✕</button>
                </div>

                <form @submit.prevent="submitEditedUpload" class="p-6 space-y-4">
                    <div class="flex items-center space-x-4 mb-4">
                        <img :src="activePhotoForEdit.raw_url" class="w-16 h-16 object-cover rounded-lg border border-slate-200 dark:border-slate-800" />
                        <div>
                            <p class="text-xs font-bold text-slate-400">FOTO ASAL (RAW)</p>
                            <p class="text-[10px] text-slate-500 font-mono mt-0.5">ID Foto: {{ activePhotoForEdit.id }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-450 dark:text-slate-500 uppercase tracking-wider mb-2">PILIH FILE EDITED</label>
                        <input 
                            type="file" 
                            accept="image/*"
                            required
                            @change="handleEditedFileChange"
                            class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-violet-50 file:text-violet-700 dark:file:bg-slate-800 dark:file:text-slate-300 hover:file:bg-violet-100 cursor-pointer"
                        />
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex space-x-3 pt-4 border-t border-slate-150 dark:border-slate-800/80">
                        <button 
                            type="button" 
                            @click="closeEditUploadModal"
                            class="flex-1 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-semibold rounded-lg transition-all cursor-pointer"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            :disabled="editedForm.processing || !editedForm.edited_photo"
                            class="flex-1 py-2 bg-violet-600 hover:bg-violet-700 text-white text-xs font-semibold rounded-lg shadow-sm hover:shadow transition-all cursor-pointer flex items-center justify-center"
                        >
                            <span>{{ editedForm.processing ? 'Mengunggah...' : 'Upload Hasil Edit' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
