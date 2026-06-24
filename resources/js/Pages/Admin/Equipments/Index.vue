<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import Pagination from '../../../Components/Pagination.vue';

const props = defineProps({
    equipments: Array,
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

// Form state
const isFormModalOpen = ref(false);
const isEditing = ref(false);
const editingEquipmentId = ref(null);

const form = useForm({
    name: '',
    type: 'kamera',
    serial_number: '',
    status: 'active',
    notes: '',
});

// Modal open/close actions
const openAddModal = () => {
    isEditing.value = false;
    editingEquipmentId.value = null;
    form.reset();
    isFormModalOpen.value = true;
};

const openEditModal = (equipment) => {
    isEditing.value = true;
    editingEquipmentId.value = equipment.id;
    form.name = equipment.name;
    form.type = equipment.type;
    form.serial_number = equipment.serial_number || '';
    form.status = equipment.status;
    form.notes = equipment.notes || '';
    isFormModalOpen.value = true;
};

const closeFormModal = () => {
    isFormModalOpen.value = false;
    form.reset();
};

// Form submit action
const submitForm = () => {
    if (isEditing.value) {
        form.put(`/admin/equipments/${editingEquipmentId.value}`, {
            onSuccess: () => closeFormModal(),
        });
    } else {
        form.post('/admin/equipments', {
            onSuccess: () => closeFormModal(),
        });
    }
};

// Delete action
const isDeleteModalOpen = ref(false);
const deleteTargetId = ref(null);
const deleteTargetName = ref('');

const confirmDelete = (equipment) => {
    deleteTargetId.value = equipment.id;
    deleteTargetName.value = equipment.name;
    isDeleteModalOpen.value = true;
};

const executeDelete = () => {
    form.delete(`/admin/equipments/${deleteTargetId.value}`, {
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            deleteTargetId.value = null;
        }
    });
};

// Filter state
const filterType = ref('all');
const filterStatus = ref('all');

const filteredEquipments = computed(() => {
    return props.equipments.filter(e => {
        const typeMatch = filterType.value === 'all' || e.type === filterType.value;
        const statusMatch = filterStatus.value === 'all' || e.status === filterStatus.value;
        return typeMatch && statusMatch;
    });
});

// Pagination state
const currentPage = ref(1);
const itemsPerPage = 5;

// Reset page when filters change
watch([filterType, filterStatus], () => {
    currentPage.value = 1;
});

const paginatedEquipments = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredEquipments.value.slice(start, end);
});

const getTypeLabel = (type) => {
    const labels = {
        kamera: '📷 Kamera',
        lensa: '🔍 Lensa',
        lighting: '💡 Lighting',
        aksesoris: '🔋 Aksesoris',
        properti: '🎭 Properti',
    };
    return labels[type] || type;
};
</script>

<template>
    <AdminLayout>
        <Head title="Manajemen Inventaris Alat" />

        <template #title>
            Inventaris Alat & Properti Studio
        </template>

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
                class="flex items-center p-4 bg-red-50 dark:bg-red-950/85 border border-red-255 dark:border-red-800 text-red-800 dark:text-red-300 rounded-lg shadow-lg max-w-sm transition-all animate-in fade-in"
            >
                <span class="text-lg mr-3">⚠️</span>
                <div class="text-sm font-medium mr-8">{{ flashError }}</div>
                <button @click="showErrorNotification = false" class="text-red-500 hover:text-red-700 ml-auto">✕</button>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="max-w-xl">
                <h3 class="text-base text-slate-500 dark:text-slate-400">
                    Kelola ketersediaan kamera, lensa, lighting, aksesoris, serta properti studio untuk kelancaran sesi pemotretan klien.
                </h3>
            </div>
            
            <button 
                @click="openAddModal"
                class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow transition-all shrink-0 cursor-pointer"
            >
                ➕ Tambah Alat / Properti
            </button>
        </div>

        <!-- Filter Controls -->
        <div class="mb-5 flex flex-wrap gap-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-xl shadow-sm transition-all">
            <div class="flex flex-col space-y-1">
                <label class="text-xs text-slate-400 font-bold uppercase tracking-wider">Filter Kategori</label>
                <select v-model="filterType" class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm rounded-lg p-2 focus:outline-none focus:border-violet-500 transition-all text-slate-700 dark:text-slate-200">
                    <option value="all">Semua Kategori</option>
                    <option value="kamera">Kamera</option>
                    <option value="lensa">Lensa</option>
                    <option value="lighting">Lighting</option>
                    <option value="aksesoris">Aksesoris</option>
                    <option value="properti">Properti</option>
                </select>
            </div>

            <div class="flex flex-col space-y-1">
                <label class="text-xs text-slate-400 font-bold uppercase tracking-wider">Filter Status</label>
                <select v-model="filterStatus" class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm rounded-lg p-2 focus:outline-none focus:border-violet-500 transition-all text-slate-700 dark:text-slate-200">
                    <option value="all">Semua Status</option>
                    <option value="active">Active (Siap Pakai)</option>
                    <option value="maintenance">Maintenance (Servis)</option>
                    <option value="broken">Broken (Rusak)</option>
                </select>
            </div>
        </div>

        <!-- Table Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm overflow-hidden transition-all duration-300">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse table-fixed">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/40 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <th class="px-6 py-4 w-[35%]">Nama Alat</th>
                            <th class="px-6 py-4 w-[20%]">Kategori</th>
                            <th class="px-6 py-4 w-[18%]">No. Serial (S/N)</th>
                            <th class="px-6 py-4 w-[15%] text-center">Status</th>
                            <th class="px-6 py-4 w-[12%] text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-150 dark:divide-slate-800/60">
                        <tr 
                            v-for="equipment in paginatedEquipments" 
                            :key="equipment.id"
                            class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors"
                        >
                            <!-- Name & Notes -->
                            <td class="px-6 py-4">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-850 dark:text-slate-100 truncate" :title="equipment.name">
                                        {{ equipment.name }}
                                    </p>
                                    <p class="text-xs text-slate-450 dark:text-slate-550 truncate mt-0.5" :title="equipment.notes">
                                        {{ equipment.notes || 'Tidak ada catatan khusus.' }}
                                    </p>
                                </div>
                            </td>

                            <!-- Category -->
                            <td class="px-6 py-4 text-slate-700 dark:text-slate-300 font-medium">
                                {{ getTypeLabel(equipment.type) }}
                            </td>

                            <!-- Serial Number -->
                            <td class="px-6 py-4 text-slate-500 font-mono text-xs">
                                {{ equipment.serial_number || '-' }}
                            </td>

                            <!-- Status Badge -->
                            <td class="px-6 py-4 text-center">
                                <span 
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="{
                                        'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400': equipment.status === 'active',
                                        'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400': equipment.status === 'maintenance',
                                        'bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-400': equipment.status === 'broken',
                                    }"
                                >
                                    {{ equipment.status.toUpperCase() }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-right space-x-1.5 whitespace-nowrap">
                                <button 
                                    @click="openEditModal(equipment)"
                                    class="px-2 py-0.5 bg-violet-50 dark:bg-violet-950/20 hover:bg-violet-100 text-violet-700 dark:text-violet-400 text-xs font-semibold rounded transition-all cursor-pointer"
                                >
                                    ✏️ Edit
                                </button>
                                <button 
                                    @click="confirmDelete(equipment)"
                                    class="px-2 py-0.5 bg-red-50 dark:bg-red-950/10 hover:bg-red-100 text-red-600 dark:text-red-400 text-xs font-semibold rounded transition-all cursor-pointer"
                                >
                                    🗑️ Hapus
                                </button>
                            </td>
                        </tr>
                        
                        <!-- Empty State -->
                        <tr v-if="filteredEquipments.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                <p class="text-3xl mb-2">📷</p>
                                Tidak ada data inventaris alat yang cocok dengan filter.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <Pagination 
                :total-items="filteredEquipments.length"
                :items-per-page="itemsPerPage"
                v-model:current-page="currentPage"
            />
        </div>

        <!-- Add/Edit Form Modal -->
        <div v-if="isFormModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/60 dark:bg-slate-950/80 backdrop-blur-sm" @click="closeFormModal"></div>

            <div class="bg-white dark:bg-slate-900 w-full max-w-[480px] rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl z-10 overflow-hidden flex flex-col transform transition-all animate-in fade-in zoom-in-95 duration-200">
                <div class="px-6 py-4 border-b border-slate-150 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-800/40">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">
                        {{ isEditing ? 'Edit Data Alat / Properti' : 'Tambah Alat / Properti Baru' }}
                    </h3>
                    <button @click="closeFormModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm">✕</button>
                </div>

                <form @submit.prevent="submitForm" class="p-6 space-y-4">
                    <!-- Name -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Nama Alat / Properti</label>
                        <input 
                            type="text" 
                            v-model="form.name" 
                            required
                            placeholder="Contoh: Sony A7 IV Body"
                            class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-sm focus:outline-none focus:border-violet-500 text-slate-800 dark:text-slate-100 transition-all"
                        />
                        <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Category -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Kategori</label>
                            <select 
                                v-model="form.type" 
                                class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-sm focus:outline-none focus:border-violet-500 text-slate-800 dark:text-slate-100 transition-all"
                            >
                                <option value="kamera">Kamera</option>
                                <option value="lensa">Lensa</option>
                                <option value="lighting">Lighting</option>
                                <option value="aksesoris">Aksesoris (Baterai/Tripod/dll)</option>
                                <option value="properti">Properti / Dekorasi</option>
                            </select>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Status Alat</label>
                            <select 
                                v-model="form.status" 
                                class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-sm focus:outline-none focus:border-violet-500 text-slate-800 dark:text-slate-100 transition-all"
                            >
                                <option value="active">Active (Siap Pakai)</option>
                                <option value="maintenance">Maintenance (Servis)</option>
                                <option value="broken">Broken (Rusak)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Serial Number -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Nomor Serial (S/N)</label>
                        <input 
                            type="text" 
                            v-model="form.serial_number" 
                            placeholder="Contoh: S/N 4839210-BC"
                            class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-sm focus:outline-none focus:border-violet-500 text-slate-800 dark:text-slate-100 transition-all font-mono"
                        />
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Catatan Kondisi / Penempatan</label>
                        <textarea 
                            v-model="form.notes" 
                            rows="3"
                            placeholder="Kondisi sensor bersih, disimpan di drybox A..."
                            class="w-full bg-slate-50 dark:bg-slate-955 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-sm focus:outline-none focus:border-violet-500 text-slate-800 dark:text-slate-100 transition-all"
                        ></textarea>
                    </div>

                    <!-- Buttons -->
                    <div class="flex space-x-3 pt-3 border-t border-slate-100 dark:border-slate-800/80 mt-4">
                        <button 
                            type="button" 
                            @click="closeFormModal"
                            class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm font-medium rounded-lg transition-all cursor-pointer"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="flex-1 py-2.5 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow transition-all cursor-pointer"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Data' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="isDeleteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/60 dark:bg-slate-950/80 backdrop-blur-sm" @click="isDeleteModalOpen = false"></div>

            <div class="bg-white dark:bg-slate-900 w-full max-w-[360px] rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl z-10 overflow-hidden flex flex-col justify-between transform transition-all animate-in fade-in zoom-in-95 duration-200 relative">
                <!-- Close Button -->
                <button @click="isDeleteModalOpen = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm focus:outline-none transition-colors">✕</button>
                <div class="p-6 text-center">
                    <div class="w-12 h-12 rounded-full bg-red-50 dark:bg-red-950/20 text-red-500 flex items-center justify-center text-xl mx-auto mb-4 shadow-sm">
                        ⚠️
                    </div>
                    <h3 class="text-base font-bold text-slate-850 dark:text-slate-100 mb-2">Konfirmasi Hapus</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-405 leading-relaxed mb-6">
                        Apakah Anda yakin ingin menghapus data alat <strong>{{ deleteTargetName }}</strong> secara permanen dari sistem?
                    </p>
                    <div class="flex space-x-3">
                        <button 
                            type="button" 
                            @click="isDeleteModalOpen = false"
                            class="flex-1 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-850 dark:text-slate-200 text-xs font-semibold rounded-lg transition-all cursor-pointer"
                        >
                            Batal
                        </button>
                        <button 
                            type="button" 
                            @click="executeDelete"
                            class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-lg transition-all cursor-pointer"
                        >
                            Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
