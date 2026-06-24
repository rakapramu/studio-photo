<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import Pagination from '../../../Components/Pagination.vue';

const props = defineProps({
    expenses: Array,
    totalAmount: Number,
    thisMonthAmount: Number,
    thisMonthLabel: String,
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
const editingExpenseId = ref(null);

const form = useForm({
    title: '',
    amount: '',
    expense_date: new Date().toISOString().split('T')[0],
    notes: '',
});

// Modal actions
const openAddModal = () => {
    isEditing.value = false;
    editingExpenseId.value = null;
    form.reset();
    form.expense_date = new Date().toISOString().split('T')[0];
    isFormModalOpen.value = true;
};

const openEditModal = (expense) => {
    isEditing.value = true;
    editingExpenseId.value = expense.id;
    form.title = expense.title;
    form.amount = expense.amount;
    form.expense_date = expense.expense_date;
    form.notes = expense.notes || '';
    isFormModalOpen.value = true;
};

const closeFormModal = () => {
    isFormModalOpen.value = false;
    form.reset();
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(`/admin/expenses/${editingExpenseId.value}`, {
            onSuccess: () => closeFormModal(),
        });
    } else {
        form.post('/admin/expenses', {
            onSuccess: () => closeFormModal(),
        });
    }
};

// Delete state
const isDeleteModalOpen = ref(false);
const deleteTargetId = ref(null);
const deleteTargetTitle = ref('');

const confirmDelete = (expense) => {
    deleteTargetId.value = expense.id;
    deleteTargetTitle.value = expense.title;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    deleteTargetId.value = null;
};

const executeDelete = () => {
    form.delete(`/admin/expenses/${deleteTargetId.value}`, {
        onSuccess: () => closeDeleteModal(),
    });
};

// Filter & Search state
const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = 5;

// Reset page when search query changes
watch(searchQuery, () => {
    currentPage.value = 1;
});

const filteredExpenses = computed(() => {
    if (!searchQuery.value) return props.expenses;
    return props.expenses.filter(e => 
        e.title.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
        (e.notes && e.notes.toLowerCase().includes(searchQuery.value.toLowerCase()))
    );
});

const paginatedExpenses = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredExpenses.value.slice(start, end);
});

// Format currency
const formatIDR = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(value);
};

// Format Date
const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        dateStyle: 'medium'
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Manajemen Pengeluaran" />

        <template #title>
            💸 Pengeluaran Operasional
        </template>

        <!-- Floating Notifications -->
        <div class="fixed top-6 right-6 z-50 space-y-3">
            <div v-if="showSuccessNotification && flashSuccess"
                class="flex items-center p-4 bg-emerald-50 dark:bg-emerald-950/85 border border-emerald-250 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-lg shadow-lg max-w-sm transition-all"
            >
                <span class="text-lg mr-3">✅</span>
                <div class="text-sm font-medium mr-8">{{ flashSuccess }}</div>
                <button @click="showSuccessNotification = false" class="text-emerald-500 hover:text-emerald-700 ml-auto">✕</button>
            </div>

            <div v-if="showErrorNotification && flashError"
                class="flex items-center p-4 bg-red-50 dark:bg-red-950/85 border border-red-250 dark:border-red-800 text-red-800 dark:text-red-300 rounded-lg shadow-lg max-w-sm transition-all"
            >
                <span class="text-lg mr-3">⚠️</span>
                <div class="text-sm font-medium mr-8">{{ flashError }}</div>
                <button @click="showErrorNotification = false" class="text-red-500 hover:text-red-700 ml-auto">✕</button>
            </div>
        </div>

        <!-- Summary Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Total Pengeluaran Akumulatif -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Total Pengeluaran Operasional</p>
                    <p class="text-2xl font-black text-rose-650 dark:text-rose-450 mt-2 font-mono">
                        {{ formatIDR(totalAmount) }}
                    </p>
                </div>
                <p class="text-[10px] text-slate-450 mt-2">All-time akumulasi pengeluaran non-kru</p>
            </div>

            <!-- Pengeluaran Bulan Ini -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Pengeluaran Bulan Ini</p>
                    <p class="text-2xl font-black text-amber-500 mt-2 font-mono">
                        {{ formatIDR(thisMonthAmount) }}
                    </p>
                </div>
                <p class="text-[10px] text-slate-450 mt-2">📅 Periode berjalan: {{ thisMonthLabel }}</p>
            </div>

            <!-- Action -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm flex flex-col justify-between items-start">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Tindakan Cepat</p>
                    <p class="text-xs text-slate-450 mt-1">Catat biaya operasional studio baru secara instan di database.</p>
                </div>
                <button 
                    @click="openAddModal"
                    class="mt-4 px-4 py-2.5 bg-violet-600 hover:bg-violet-700 text-white font-bold text-xs rounded-xl shadow-sm hover:shadow transition-all cursor-pointer w-full text-center"
                >
                    ➕ Catat Pengeluaran Baru
                </button>
            </div>
        </div>

        <!-- Toolbar Search -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 mb-6 shadow-sm">
            <div class="relative max-w-md">
                <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
                    🔍
                </span>
                <input 
                    v-model="searchQuery"
                    type="text" 
                    placeholder="Cari deskripsi atau catatan pengeluaran..." 
                    class="w-full pl-9 pr-4 py-2 border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-violet-600 transition text-sm text-slate-800 dark:text-slate-200"
                />
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden transition-all">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse table-fixed">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-805 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-450 uppercase tracking-wider">
                            <th class="px-6 py-4 w-[35%]">Deskripsi Pengeluaran</th>
                            <th class="px-6 py-4 w-[18%]">Tanggal Selesai</th>
                            <th class="px-6 py-4 w-[20%]">Nominal</th>
                            <th class="px-6 py-4 w-[15%] text-center">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        <tr v-if="filteredExpenses.length === 0">
                            <td colspan="4" class="px-6 py-12 text-center text-slate-450 italic">
                                Tidak ada catatan pengeluaran yang terdaftar.
                            </td>
                        </tr>
                        <tr 
                            v-for="expense in paginatedExpenses" 
                            :key="expense.id"
                            class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-all text-sm"
                        >
                            <!-- Title & Notes -->
                            <td class="px-6 py-4">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-850 dark:text-slate-100 truncate" :title="expense.title">
                                        {{ expense.title }}
                                    </p>
                                    <p class="text-xs text-slate-450 dark:text-slate-500 truncate mt-0.5" :title="expense.notes">
                                        {{ expense.notes || 'Tidak ada catatan.' }}
                                    </p>
                                </div>
                            </td>

                            <!-- Date -->
                            <td class="px-6 py-4 text-slate-650 dark:text-slate-400">
                                {{ formatDate(expense.expense_date) }}
                            </td>

                            <!-- Amount -->
                            <td class="px-6 py-4 font-bold font-mono text-slate-900 dark:text-white">
                                {{ formatIDR(expense.amount) }}
                            </td>

                            <!-- Action buttons -->
                            <td class="px-6 py-4 text-center space-x-2 whitespace-nowrap">
                                <button 
                                    @click="openEditModal(expense)"
                                    class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-350 text-xs font-semibold rounded-lg border border-slate-200/20 transition cursor-pointer"
                                >
                                    ✏️ Edit
                                </button>
                                <button 
                                    @click="confirmDelete(expense)"
                                    class="px-2.5 py-1 bg-red-50 dark:bg-red-950/10 hover:bg-red-100 text-red-650 dark:text-red-400 text-xs font-semibold rounded-lg transition cursor-pointer"
                                >
                                    🗑️ Hapus
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <Pagination 
                :total-items="filteredExpenses.length"
                :items-per-page="itemsPerPage"
                v-model:current-page="currentPage"
            />
        </div>

        <!-- Add/Edit Modal -->
        <div v-if="isFormModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-950/60 dark:bg-slate-950/80 backdrop-blur-sm" @click="closeFormModal"></div>

            <!-- Modal Panel -->
            <div class="bg-white dark:bg-slate-900 w-full max-w-[440px] rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl z-10 overflow-hidden flex flex-col transform transition-all animate-in fade-in zoom-in-95 duration-200 relative">
                <div class="px-6 py-4 border-b border-slate-150 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-800/40">
                    <h3 class="text-sm font-bold text-slate-850 dark:text-slate-100">
                        {{ isEditing ? 'Edit Catatan Pengeluaran' : 'Catat Pengeluaran Baru' }}
                    </h3>
                    <button @click="closeFormModal" class="text-slate-400 hover:text-slate-650 dark:hover:text-slate-200 text-sm focus:outline-none">✕</button>
                </div>

                <form @submit.prevent="submitForm" class="p-6 space-y-4">
                    <!-- Title -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Deskripsi / Kebutuhan</label>
                        <input 
                            type="text" 
                            v-model="form.title" 
                            required
                            placeholder="Contoh: Sewa Studio Bulanan"
                            class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-sm focus:outline-none focus:border-violet-500 text-slate-850 dark:text-slate-100 transition-all"
                        />
                        <p v-if="form.errors.title" class="text-xs text-red-500 mt-1">{{ form.errors.title }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Amount -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Nominal (Rp)</label>
                            <input 
                                type="number" 
                                v-model="form.amount" 
                                required
                                min="0"
                                placeholder="Contoh: 500000"
                                class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-sm focus:outline-none focus:border-violet-500 text-slate-850 dark:text-slate-100 font-mono transition-all"
                            />
                            <p v-if="form.errors.amount" class="text-xs text-red-500 mt-1">{{ form.errors.amount }}</p>
                        </div>

                        <!-- Date -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Tanggal</label>
                            <input 
                                type="date" 
                                v-model="form.expense_date" 
                                required
                                class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-sm focus:outline-none focus:border-violet-500 text-slate-850 dark:text-slate-100 font-mono transition-all"
                            />
                            <p v-if="form.errors.expense_date" class="text-xs text-red-500 mt-1">{{ form.errors.expense_date }}</p>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">Catatan Tambahan (Opsional)</label>
                        <textarea 
                            v-model="form.notes" 
                            placeholder="Catatan seputar nomor invoice, kwitansi, atau rincian alat..."
                            rows="3"
                            class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 text-sm focus:outline-none focus:border-violet-500 text-slate-850 dark:text-slate-100 transition-all"
                        ></textarea>
                        <p v-if="form.errors.notes" class="text-xs text-red-500 mt-1">{{ form.errors.notes }}</p>
                    </div>

                    <!-- Buttons -->
                    <div class="flex space-x-3 pt-3 border-t border-slate-100 dark:border-slate-800/80 mt-4">
                        <button 
                            type="button" 
                            @click="closeFormModal"
                            class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm font-medium rounded-lg transition"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="flex-1 py-2.5 bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold rounded-lg shadow-sm hover:shadow transition"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Modal -->
        <div v-if="isDeleteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/60 dark:bg-slate-950/80 backdrop-blur-sm" @click="closeDeleteModal"></div>

            <div class="bg-white dark:bg-slate-900 w-full max-w-[360px] rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl z-10 p-6 text-center animate-in fade-in zoom-in-95 duration-200">
                <span class="text-3xl mb-3 block">⚠️</span>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-2">Hapus Catatan Pengeluaran</h3>
                <p class="text-xs text-slate-500 mb-6">
                    Apakah Anda yakin ingin menghapus catatan pengeluaran <span class="font-bold text-slate-850 dark:text-slate-100">"{{ deleteTargetTitle }}"</span>? Tindakan ini tidak dapat dibatalkan.
                </p>

                <div class="flex space-x-3">
                    <button 
                        @click="closeDeleteModal"
                        class="flex-1 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button 
                        @click="executeDelete"
                        class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl transition cursor-pointer shadow"
                    >
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
