<script setup>
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import Pagination from '../../../Components/Pagination.vue';

const props = defineProps({
    packages: Array,
});

// Pagination state
const currentPage = ref(1);
const itemsPerPage = 5;

const paginatedPackages = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return props.packages.slice(start, end);
});

// Access Inertia page props for flash messages
const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

// Notification visibility state
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

// Modal states
const isModalOpen = ref(false);
const isEditMode = ref(false);
const activePackageId = ref(null);

// Form init using Inertia useForm
const form = useForm({
    _method: 'POST',
    name: '',
    description: '',
    price: '',
    duration_minutes: '',
    is_active: true,
    image: null,
});

// Image preview and input references
const imagePreviewUrl = ref(null);
const existingImageUrl = ref(null);
const fileInputRef = ref(null);

const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.image = file;
        imagePreviewUrl.value = URL.createObjectURL(file);
    } else {
        form.image = null;
        imagePreviewUrl.value = null;
    }
};

// Currency formatting utility
const formatIDR = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(value);
};

// Open modal for Create
const openCreateModal = () => {
    isEditMode.value = false;
    activePackageId.value = null;
    form.reset();
    form.clearErrors();
    form._method = 'POST';
    form.image = null;
    imagePreviewUrl.value = null;
    existingImageUrl.value = null;
    if (fileInputRef.value) fileInputRef.value.value = '';
    isModalOpen.value = true;
};

// Open modal for Edit
const openEditModal = (pkg) => {
    isEditMode.value = true;
    activePackageId.value = pkg.id;
    form.clearErrors();
    form._method = 'PUT';
    form.name = pkg.name;
    form.description = pkg.description || '';
    form.price = pkg.price;
    form.duration_minutes = pkg.duration_minutes;
    form.is_active = pkg.is_active;
    form.image = null;
    imagePreviewUrl.value = null;
    existingImageUrl.value = pkg.image_path ? '/storage/' + pkg.image_path : null;
    if (fileInputRef.value) fileInputRef.value.value = '';
    isModalOpen.value = true;
};

// Submit form (Store or Update)
const submit = () => {
    if (isEditMode.value) {
        // Use POST with method spoofing for file uploads in updates
        form.post(`/admin/packages/${activePackageId.value}`, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/admin/packages', {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    imagePreviewUrl.value = null;
    existingImageUrl.value = null;
    if (fileInputRef.value) fileInputRef.value.value = '';
};

// Toggle status active/inactive
const toggleStatus = (pkg) => {
    router.patch(`/admin/packages/${pkg.id}/toggle`, {}, {
        preserveScroll: true,
    });
};

// Delete package action
const deletePackage = (pkg) => {
    if (confirm(`Apakah Anda yakin ingin menghapus paket "${pkg.name}"?`)) {
        router.delete(`/admin/packages/${pkg.id}`, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Kelola Paket Foto" />

        <template #title>
            Manajemen Paket Foto
        </template>

        <!-- Floating Notifications -->
        <div class="fixed top-6 right-6 z-50 space-y-3">
            <div 
                v-if="showSuccessNotification && flashSuccess"
                class="flex items-center p-4 bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-lg shadow-lg max-w-sm transition-all"
            >
                <span class="text-lg mr-3">✅</span>
                <div class="text-sm font-medium mr-8">{{ flashSuccess }}</div>
                <button @click="showSuccessNotification = false" class="text-emerald-500 hover:text-emerald-700 ml-auto">✕</button>
            </div>

            <div 
                v-if="showErrorNotification && flashError"
                class="flex items-center p-4 bg-red-50 dark:bg-red-950/80 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 rounded-lg shadow-lg max-w-sm transition-all"
            >
                <span class="text-lg mr-3">⚠️</span>
                <div class="text-sm font-medium mr-8">{{ flashError }}</div>
                <button @click="showErrorNotification = false" class="text-red-500 hover:text-red-700 ml-auto">✕</button>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 space-y-4 sm:space-y-0">
            <div>
                <h3 class="text-base text-slate-500 dark:text-slate-400">Daftar paket layanan studio yang dapat dipesan oleh klien.</h3>
            </div>
            <button 
                @click="openCreateModal"
                class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white font-medium text-sm rounded-lg shadow-sm hover:shadow transition-all"
            >
                ➕ Tambah Paket Baru
            </button>
        </div>

        <!-- Table Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm overflow-hidden transition-all duration-300">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse table-fixed">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/40 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <th class="px-6 py-4 w-[20%]">Nama Paket</th>
                            <th class="px-6 py-4 w-[40%]">Deskripsi</th>
                            <th class="px-6 py-4 w-[15%]">Harga</th>
                            <th class="px-6 py-4 w-[10%]">Durasi</th>
                            <th class="px-6 py-4 w-[5%] text-center">Status</th>
                            <th class="px-6 py-4 w-[10%] text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-150 dark:divide-slate-800/60">
                        <tr 
                            v-for="pkg in paginatedPackages" 
                            :key="pkg.id"
                            class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors"
                        >
                            <td class="px-6 py-4 font-semibold text-slate-800 dark:text-slate-100">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-850 overflow-hidden flex-shrink-0 border border-slate-200 dark:border-slate-800 flex items-center justify-center">
                                        <img v-if="pkg.image_path" :src="'/storage/' + pkg.image_path" class="w-full h-full object-cover" alt="" />
                                        <span v-else class="text-lg">🏷️</span>
                                    </div>
                                    <div class="truncate text-sm font-semibold" :title="pkg.name">
                                        {{ pkg.name }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400">
                                <div class="w-full line-clamp-2 whitespace-normal break-words" :title="pkg.description">
                                    {{ pkg.description || 'Tidak ada deskripsi.' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-850 dark:text-slate-200">
                                {{ formatIDR(pkg.price) }}
                            </td>
                            <td class="px-6 py-4 text-slate-700 dark:text-slate-300">
                                {{ pkg.duration_minutes >= 60 ? `${Math.floor(pkg.duration_minutes / 60)} Jam` : '' }} 
                                {{ pkg.duration_minutes % 60 > 0 ? `${pkg.duration_minutes % 60} Menit` : '' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button 
                                    @click="toggleStatus(pkg)"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="pkg.is_active ? 'bg-violet-600' : 'bg-slate-200 dark:bg-slate-800'"
                                >
                                    <span 
                                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                        :class="pkg.is_active ? 'translate-x-5' : 'translate-x-0'"
                                    />
                                </button>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                <button 
                                    @click="openEditModal(pkg)"
                                    class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded transition-all"
                                >
                                    ✏️ Edit
                                </button>
                                <button 
                                    @click="deletePackage(pkg)"
                                    class="px-2.5 py-1 bg-red-50 dark:bg-red-950/20 hover:bg-red-100 dark:hover:bg-red-950/40 text-red-650 dark:text-red-400 text-xs font-semibold rounded transition-all"
                                >
                                    🗑️ Hapus
                                </button>
                            </td>
                        </tr>
                        <!-- Empty State -->
                        <tr v-if="packages.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                <p class="text-3xl mb-2">🏷️</p>
                                Belum ada paket foto yang didaftarkan.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <Pagination 
                :total-items="packages.length"
                :items-per-page="itemsPerPage"
                v-model:current-page="currentPage"
            />
        </div>

        <!-- Create/Edit Modal Dialog -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop (Clicking outside does not close) -->
            <div class="fixed inset-0 bg-slate-950/60 dark:bg-slate-950/80 backdrop-blur-sm"></div>

            <!-- Modal Panel (Fixed width 480px) -->
            <div class="bg-white dark:bg-slate-900 w-full max-w-[480px] rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl z-10 overflow-hidden flex flex-col justify-between transform transition-all">
                <div class="px-6 py-4 border-b border-slate-150 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-800/20">
                    <h3 class="text-base font-bold text-slate-800 dark:text-slate-100">
                        {{ isEditMode ? 'Edit Paket Foto' : 'Tambah Paket Baru' }}
                    </h3>
                    <button @click="closeModal" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded">
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-4">
                    <!-- Name -->
                    <div>
                        <label for="pkg-name" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                            Nama Paket <span class="text-red-500">*</span>
                        </label>
                        <input 
                            id="pkg-name" 
                            type="text" 
                            v-model="form.name" 
                            required 
                            class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 dark:focus:ring-violet-400/50 dark:focus:border-violet-400 transition-all text-slate-900 dark:text-white"
                            placeholder="Contoh: Portrait Studio Premium"
                        />
                        <span v-if="form.errors.name" class="text-xs text-red-500 dark:text-red-400 mt-1 block">
                            {{ form.errors.name }}
                        </span>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="pkg-desc" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                            Deskripsi Layanan
                        </label>
                        <textarea 
                            id="pkg-desc" 
                            rows="3" 
                            v-model="form.description" 
                            class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 dark:focus:ring-violet-400/50 dark:focus:border-violet-400 transition-all text-slate-900 dark:text-white resize-none"
                            placeholder="Detail layanan, output cetakan, jumlah kru, dll."
                        ></textarea>
                        <span v-if="form.errors.description" class="text-xs text-red-500 dark:text-red-400 mt-1 block">
                            {{ form.errors.description }}
                        </span>
                    </div>

                    <!-- Package Image -->
                    <div>
                        <label for="pkg-image" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                            Gambar Paket Foto
                        </label>
                        <div class="flex items-center space-x-4">
                            <!-- Image Preview -->
                            <div class="w-16 h-16 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 overflow-hidden flex items-center justify-center flex-shrink-0">
                                <img v-if="imagePreviewUrl" :src="imagePreviewUrl" class="w-full h-full object-cover" alt="" />
                                <img v-else-if="existingImageUrl" :src="existingImageUrl" class="w-full h-full object-cover" alt="" />
                                <span v-else class="text-2xl">📸</span>
                            </div>
                            <!-- File Input -->
                            <div class="flex-grow">
                                <input 
                                    id="pkg-image" 
                                    type="file" 
                                    ref="fileInputRef"
                                    accept="image/*"
                                    @change="handleImageChange"
                                    class="w-full text-xs text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-violet-50 file:text-violet-750 dark:file:bg-violet-950/40 dark:file:text-violet-400 hover:file:bg-violet-100 dark:hover:file:bg-violet-900/40 transition-all cursor-pointer"
                                />
                                <p class="text-[10px] text-slate-450 mt-1">Format: JPG, PNG, WEBP (Max 5MB)</p>
                            </div>
                        </div>
                        <span v-if="form.errors.image" class="text-xs text-red-500 dark:text-red-400 mt-1 block">
                            {{ form.errors.image }}
                        </span>
                    </div>

                    <!-- Grid for Price and Duration -->
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Price -->
                        <div>
                            <label for="pkg-price" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                                Harga (Rupiah) <span class="text-red-500">*</span>
                            </label>
                            <input 
                                id="pkg-price" 
                                type="number" 
                                v-model="form.price" 
                                required 
                                min="0"
                                class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 dark:focus:ring-violet-400/50 dark:focus:border-violet-400 transition-all text-slate-900 dark:text-white"
                                placeholder="350000"
                            />
                            <span v-if="form.errors.price" class="text-xs text-red-500 dark:text-red-400 mt-1 block">
                                {{ form.errors.price }}
                            </span>
                        </div>

                        <!-- Duration -->
                        <div>
                            <label for="pkg-duration" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                                Durasi (Menit) <span class="text-red-500">*</span>
                            </label>
                            <input 
                                id="pkg-duration" 
                                type="number" 
                                v-model="form.duration_minutes" 
                                required 
                                min="1"
                                class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 dark:focus:ring-violet-400/50 dark:focus:border-violet-400 transition-all text-slate-900 dark:text-white"
                                placeholder="60"
                            />
                            <span v-if="form.errors.duration_minutes" class="text-xs text-red-500 dark:text-red-400 mt-1 block">
                                {{ form.errors.duration_minutes }}
                            </span>
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="pt-4 border-t border-slate-150 dark:border-slate-800 flex justify-end space-x-3">
                        <button 
                            type="button" 
                            @click="closeModal"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm font-medium rounded-lg transition-all"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow transition-all disabled:opacity-50"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
