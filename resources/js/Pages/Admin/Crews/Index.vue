<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import Pagination from '../../../Components/Pagination.vue';

const props = defineProps({
    crews: Array,
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
const editingCrewId = ref(null);

const form = useForm({
    name: '',
    role: 'fotografer',
    phone: '',
    email: '',
    is_active: true,
    fee_per_session: 150000,
});

// Modal open/close actions
const openAddModal = () => {
    isEditing.value = false;
    editingCrewId.value = null;
    form.reset();
    isFormModalOpen.value = true;
};

const openEditModal = (crew) => {
    isEditing.value = true;
    editingCrewId.value = crew.id;
    form.name = crew.name;
    form.role = crew.role;
    form.phone = crew.phone;
    form.email = crew.email || '';
    form.is_active = crew.is_active;
    form.fee_per_session = crew.fee_per_session;
    isFormModalOpen.value = true;
};

const closeFormModal = () => {
    isFormModalOpen.value = false;
    form.reset();
};

// Form submit action
const submitForm = () => {
    if (isEditing.value) {
        form.put(`/admin/crews/${editingCrewId.value}`, {
            onSuccess: () => closeFormModal(),
        });
    } else {
        form.post('/admin/crews', {
            onSuccess: () => closeFormModal(),
        });
    }
};

// Delete action
const isDeleteModalOpen = ref(false);
const deleteTargetId = ref(null);
const deleteTargetName = ref('');

const confirmDelete = (crew) => {
    deleteTargetId.value = crew.id;
    deleteTargetName.value = crew.name;
    isDeleteModalOpen.value = true;
};

const executeDelete = () => {
    form.delete(`/admin/crews/${deleteTargetId.value}`, {
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            deleteTargetId.value = null;
        }
    });
};

// Filter state
const filterRole = ref('all');
const filterStatus = ref('all');

const filteredCrews = computed(() => {
    return props.crews.filter(c => {
        const roleMatch = filterRole.value === 'all' || c.role === filterRole.value;
        const statusMatch = filterStatus.value === 'all' || 
                            (filterStatus.value === 'active' && c.is_active) || 
                            (filterStatus.value === 'inactive' && !c.is_active);
        return roleMatch && statusMatch;
    });
});

// Pagination state
const currentPage = ref(1);
const itemsPerPage = 5;

// Reset page when filters change
watch([filterRole, filterStatus], () => {
    currentPage.value = 1;
});

const paginatedCrews = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredCrews.value.slice(start, end);
});

const getRoleLabel = (role) => {
    const labels = {
        fotografer: '📸 Fotografer',
        videografer: '🎥 Videografer',
        editor: '💻 Editor',
        mua: '💄 MUA',
        asisten: '🙋‍♂️ Asisten',
    };
    return labels[role] || role;
};

const formatIDR = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(value);
};
</script>

<template>
    <AdminLayout>
        <Head title="Manajemen Kru & Staf" />

        <template #title>
            Tim Staf & Kru Studio
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
                    Kelola data tim fotografer, videografer, editor, MUA, dan asisten lapangan untuk penugasan jadwal sesi foto klien tanpa bentrok kerja.
                </h3>
            </div>
            
            <button 
                @click="openAddModal"
                class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow transition-all shrink-0 cursor-pointer"
            >
                ➕ Tambah Kru / Staf
            </button>
        </div>

        <!-- Filter Controls -->
        <div class="mb-5 flex flex-wrap gap-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-xl shadow-sm transition-all">
            <div class="flex flex-col space-y-1">
                <label class="text-xs text-slate-400 font-bold uppercase tracking-wider">Filter Peran</label>
                <select v-model="filterRole" class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm rounded-lg p-2 focus:outline-none focus:border-violet-500 transition-all text-slate-700 dark:text-slate-200">
                    <option value="all">Semua Peran</option>
                    <option value="fotografer">Fotografer</option>
                    <option value="videografer">Videografer</option>
                    <option value="editor">Editor</option>
                    <option value="mua">MUA</option>
                    <option value="asisten">Asisten</option>
                </select>
            </div>

            <div class="flex flex-col space-y-1">
                <label class="text-xs text-slate-400 font-bold uppercase tracking-wider">Filter Status</label>
                <select v-model="filterStatus" class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm rounded-lg p-2 focus:outline-none focus:border-violet-500 transition-all text-slate-700 dark:text-slate-200">
                    <option value="all">Semua Status</option>
                    <option value="active">Aktif (Tersedia)</option>
                    <option value="inactive">Non-Aktif (Cuti/Resign)</option>
                </select>
            </div>
        </div>

        <!-- Table Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm overflow-hidden transition-all duration-300">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse table-fixed">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/40 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <th class="px-6 py-4 w-[30%]">Nama Staf</th>
                            <th class="px-6 py-4 w-[18%]">Peran / Tugas</th>
                            <th class="px-6 py-4 w-[15%]">Fee / Sesi</th>
                            <th class="px-6 py-4 w-[15%]">No. WhatsApp</th>
                            <th class="px-6 py-4 w-[12%] text-center">Status</th>
                            <th class="px-6 py-4 w-[10%] text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-150 dark:divide-slate-800/60">
                        <tr 
                            v-for="crew in paginatedCrews" 
                            :key="crew.id"
                            class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors"
                        >
                            <!-- Name & Email -->
                            <td class="px-6 py-4">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-855 dark:text-slate-100 truncate" :title="crew.name">
                                        {{ crew.name }}
                                    </p>
                                    <p class="text-xs text-slate-450 truncate mt-0.5">
                                        {{ crew.email || 'Tidak ada alamat email.' }}
                                    </p>
                                </div>
                            </td>

                            <!-- Role -->
                            <td class="px-6 py-4 text-slate-650 dark:text-slate-400 capitalize">
                                {{ getRoleLabel(crew.role) }}
                            </td>

                            <!-- Fee Per Session -->
                            <td class="px-6 py-4 text-slate-900 dark:text-white font-bold font-mono text-xs">
                                {{ formatIDR(crew.fee_per_session) }}
                            </td>

                            <!-- Phone -->
                            <td class="px-6 py-4 text-slate-650 dark:text-slate-400">
                                {{ crew.phone }}
                            </td>

                            <!-- Status Active Badge -->
                            <td class="px-6 py-4 text-center">
                                <span 
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="crew.is_active
                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400'
                                        : 'bg-slate-100 text-slate-500 dark:bg-slate-855 dark:text-slate-400'"
                                >
                                    {{ crew.is_active ? 'AKTIF' : 'NON-AKTIF' }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-right space-x-1.5 whitespace-nowrap">
                                <button 
                                    @click="openEditModal(crew)"
                                    class="px-2 py-0.5 bg-violet-50 dark:bg-violet-950/20 hover:bg-violet-100 text-violet-700 dark:text-violet-400 text-xs font-semibold rounded transition-all cursor-pointer"
                                >
                                    ✏️ Edit
                                </button>
                                <button 
                                    @click="confirmDelete(crew)"
                                    class="px-2 py-0.5 bg-red-50 dark:bg-red-950/10 hover:bg-red-100 text-red-600 dark:text-red-400 text-xs font-semibold rounded transition-all cursor-pointer"
                                >
                                    🗑️ Hapus
                                </button>
                            </td>
                        </tr>
                        
                        <!-- Empty State -->
                        <tr v-if="filteredCrews.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                <p class="text-3xl mb-2">👥</p>
                                Tidak ada data staf/kru yang cocok dengan filter.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <Pagination 
                :total-items="filteredCrews.length"
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
                        {{ isEditing ? 'Edit Data Staf / Kru' : 'Tambah Staf / Kru Baru' }}
                    </h3>
                    <button @click="closeFormModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm">✕</button>
                </div>

                <form @submit.prevent="submitForm" class="p-6 space-y-4">
                    <!-- Name -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                        <input 
                            type="text" 
                            v-model="form.name" 
                            required
                            placeholder="Contoh: Andi Pratama"
                            class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-sm focus:outline-none focus:border-violet-500 text-slate-800 dark:text-slate-100 transition-all"
                        />
                        <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Role -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Tugas / Peran</label>
                            <select 
                                v-model="form.role" 
                                class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-sm focus:outline-none focus:border-violet-500 text-slate-800 dark:text-slate-100 transition-all"
                            >
                                <option value="fotografer">📸 Fotografer</option>
                                <option value="videografer">🎥 Videografer</option>
                                <option value="editor">💻 Editor</option>
                                <option value="mua">💄 MUA</option>
                                <option value="asisten">🙋‍♂️ Asisten Lapangan</option>
                            </select>
                        </div>

                        <!-- Is Active -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Status Keaktifan</label>
                            <select 
                                v-model="form.is_active" 
                                class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-sm focus:outline-none focus:border-violet-500 text-slate-800 dark:text-slate-100 transition-all"
                            >
                                <option :value="true">Aktif (Tersedia)</option>
                                <option :value="false">Non-Aktif (Cuti/Resign)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Phone (WhatsApp) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">No. WhatsApp (Aktif)</label>
                        <input 
                            type="text" 
                            v-model="form.phone" 
                            required
                            placeholder="Contoh: 08123456789"
                            class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-sm focus:outline-none focus:border-violet-500 text-slate-800 dark:text-slate-100 transition-all font-mono"
                        />
                        <p v-if="form.errors.phone" class="text-xs text-red-500 mt-1">{{ form.errors.phone }}</p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Alamat Email (Opsional)</label>
                        <input 
                            type="email" 
                            v-model="form.email" 
                            placeholder="Contoh: andi@example.com"
                            class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-sm focus:outline-none focus:border-violet-500 text-slate-800 dark:text-slate-100 transition-all"
                        />
                    </div>

                    <!-- Fee Per Session -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Fee / Komisi Per Sesi (Rp)</label>
                        <input 
                            type="number" 
                            v-model="form.fee_per_session" 
                            required
                            min="0"
                            placeholder="Contoh: 150000"
                            class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-sm focus:outline-none focus:border-violet-500 text-slate-850 dark:text-slate-100 transition-all font-mono"
                        />
                        <p v-if="form.errors.fee_per_session" class="text-xs text-red-500 mt-1">{{ form.errors.fee_per_session }}</p>
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
                        Apakah Anda yakin ingin menghapus data staf <strong>{{ deleteTargetName }}</strong> secara permanen dari sistem?
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
