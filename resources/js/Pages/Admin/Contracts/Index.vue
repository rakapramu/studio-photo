<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import Pagination from '../../../Components/Pagination.vue';

const props = defineProps({
    contracts: Array,
    stats: Object,
});

// Search & Filter state
const searchQuery = ref('');
const statusFilter = ref('all');

// Pagination state
const currentPage = ref(1);
const itemsPerPage = 5;

// Reset page when filters change
watch([searchQuery, statusFilter], () => {
    currentPage.value = 1;
});

// Modal state
const selectedContract = ref(null);
const isDocumentModalOpen = ref(false);

// Format DateTime
const formatDateTime = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short'
    }) + ' WIB';
};

// Filtered Contracts
const filteredContracts = computed(() => {
    return props.contracts.filter(contract => {
        const matchesSearch = contract.booking?.client_name
            ?.toLowerCase()
            .includes(searchQuery.value.toLowerCase()) || 
            contract.booking?.client_email
            ?.toLowerCase()
            .includes(searchQuery.value.toLowerCase());

        const isSigned = !!contract.signed_at;
        const matchesStatus = statusFilter.value === 'all' || 
            (statusFilter.value === 'signed' && isSigned) || 
            (statusFilter.value === 'draft' && !isSigned);

        return matchesSearch && matchesStatus;
    });
});

const paginatedContracts = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredContracts.value.slice(start, end);
});

const openDocumentModal = (contract) => {
    selectedContract.value = contract;
    isDocumentModalOpen.value = true;
};

const closeDocumentModal = () => {
    isDocumentModalOpen.value = false;
    selectedContract.value = null;
};
</script>

<template>
    <AdminLayout>
        <Head title="Kontrak SPK Digital" />

        <template #title>
            📝 Kontrak SPK Digital
        </template>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                    Total Draf Kontrak SPK
                </p>
                <p class="text-2xl font-black text-slate-900 dark:text-white mt-2">
                    {{ stats.total_count }}
                </p>
                <p class="text-xs text-slate-450 dark:text-slate-450 mt-1">
                    Seluruh dokumen SPK yang terbuat
                </p>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                    Telah Ditandatangani
                </p>
                <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-2">
                    {{ stats.signed_count }}
                </p>
                <p class="text-xs text-emerald-500 font-medium mt-1">
                    ✍️ Kontrak sah terverifikasi
                </p>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                    Menunggu Tanda Tangan
                </p>
                <p class="text-2xl font-black text-slate-900 dark:text-white mt-2">
                    {{ stats.draft_count }}
                </p>
                <p class="text-xs text-amber-500 font-medium mt-1">
                    ⏳ Draf/Belum ditandatangani
                </p>
            </div>
        </div>

        <!-- Search & Filter Controls -->
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
                <div class="flex items-center gap-3">
                    <div class="flex items-center space-x-2">
                        <label class="text-xs font-bold text-slate-450 uppercase">Status Kontrak:</label>
                        <select 
                            v-model="statusFilter"
                            class="px-3 py-1.5 border border-slate-250 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 rounded-lg text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-violet-600"
                        >
                            <option value="all">Semua Status</option>
                            <option value="signed">Telah Ditandatangani</option>
                            <option value="draft">Belum Ditandatangani</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contracts Table -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-955 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-450 uppercase tracking-wider">
                            <th class="px-6 py-4">Klien & Kontak</th>
                            <th class="px-6 py-4">Paket Pemesanan</th>
                            <th class="px-6 py-4">Status Kontrak</th>
                            <th class="px-6 py-4">Tanggal Ditandatangani</th>
                            <th class="px-6 py-4">IP Address</th>
                            <th class="px-6 py-4 text-center">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-sm">
                        <tr v-if="filteredContracts.length === 0">
                            <td colspan="6" class="px-6 py-10 text-center text-slate-450 dark:text-slate-500 italic">
                                Tidak ada dokumen kontrak SPK yang cocok dengan kriteria filter.
                            </td>
                        </tr>
                        <tr 
                            v-for="contract in paginatedContracts" 
                            :key="contract.id"
                            class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-all"
                        >
                            <!-- Klien & Kontak -->
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-800 dark:text-slate-250">
                                    {{ contract.booking?.client_name || 'N/A' }}
                                </div>
                                <div class="text-xs text-slate-450">
                                    {{ contract.booking?.client_email || 'N/A' }}
                                </div>
                            </td>

                            <!-- Paket Pemesanan -->
                            <td class="px-6 py-4 font-medium text-slate-700 dark:text-slate-350">
                                {{ contract.booking?.package?.name || 'Paket Kustom' }}
                            </td>

                            <!-- Status Kontrak -->
                            <td class="px-6 py-4">
                                <span 
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                    :class="contract.signed_at 
                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400' 
                                        : 'bg-slate-100 text-slate-650 dark:bg-slate-800 dark:text-slate-450'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5"
                                        :class="contract.signed_at ? 'bg-emerald-500' : 'bg-slate-400'"
                                    ></span>
                                    {{ contract.signed_at ? 'DITANDATANGANI' : 'DRAF' }}
                                </span>
                            </td>

                            <!-- Tanggal Ditandatangani -->
                            <td class="px-6 py-4 text-xs text-slate-500 dark:text-slate-400">
                                {{ contract.signed_at ? formatDateTime(contract.signed_at) : '-' }}
                            </td>

                            <!-- IP Address -->
                            <td class="px-6 py-4 font-mono text-xs text-slate-650 dark:text-slate-400">
                                {{ contract.ip_address || '-' }}
                            </td>

                            <!-- Tindakan -->
                            <td class="px-6 py-4 text-center">
                                <button 
                                    @click="openDocumentModal(contract)"
                                    class="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-350 text-xs font-semibold rounded-lg border border-slate-250/20 dark:border-slate-700 transition"
                                >
                                    📄 Lihat Dokumen
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <Pagination 
                :total-items="filteredContracts.length"
                :items-per-page="itemsPerPage"
                v-model:current-page="currentPage"
            />
        </div>

        <!-- Document Modal (View Contract) -->
        <div v-if="isDocumentModalOpen && selectedContract" class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-2xl shadow-2xl p-6 relative flex flex-col max-h-[90vh]">
                <!-- Close Button -->
                <button 
                    @click="closeDocumentModal"
                    class="absolute top-4 right-4 text-slate-400 hover:text-slate-650 dark:hover:text-slate-200 p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition z-10"
                >
                    ✕
                </button>

                <!-- Modal Title -->
                <div class="mb-4">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                        Dokumen Kontrak SPK Digital
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Klien: {{ selectedContract.booking?.client_name }} | Paket: {{ selectedContract.booking?.package?.name }}
                    </p>
                </div>

                <!-- Scrollable Contract Paper -->
                <div class="flex-grow overflow-y-auto bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-850 rounded-xl p-5 md:p-8 mb-5 shadow-inner">
                    <!-- Contract Paper Content -->
                    <div class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line leading-relaxed font-serif max-w-none select-text">
                        {{ selectedContract.contract_text }}
                    </div>

                    <!-- Signatures Area inside the Document -->
                    <div class="mt-8 border-t border-slate-200/60 dark:border-slate-800 pt-6 grid grid-cols-2 gap-4 text-xs font-sans">
                        <div class="text-center">
                            <p class="font-bold text-slate-400 uppercase tracking-wide mb-8">Pihak Pertama<br>(Photo Studio)</p>
                            <p class="font-bold text-slate-800 dark:text-slate-200">Management Team</p>
                        </div>
                        
                        <div class="text-center">
                            <p class="font-bold text-slate-400 uppercase tracking-wide mb-2">Pihak Kedua<br>(Klien)</p>
                            
                            <!-- Signature image preview -->
                            <div class="h-16 flex items-center justify-center mb-2">
                                <img 
                                    v-if="selectedContract.signature_url" 
                                    :src="selectedContract.signature_url" 
                                    alt="Coretan Tanda Tangan Klien" 
                                    class="max-h-full border border-slate-250 dark:border-slate-800 rounded p-1 bg-white" 
                                />
                                <span v-else class="text-slate-400 dark:text-slate-500 italic text-[11px] block p-2 bg-slate-100 dark:bg-slate-900 rounded border border-dashed border-slate-200 dark:border-slate-850">
                                    Belum ditandatangani
                                </span>
                            </div>
                            
                            <p class="font-bold text-slate-800 dark:text-slate-200">
                                {{ selectedContract.booking?.client_name }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Footer details & Actions -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-t border-slate-100 dark:border-slate-800/80 pt-4 text-xs text-slate-500">
                    <div v-if="selectedContract.signed_at">
                        <p class="font-semibold text-emerald-500">✅ Ditandatangani secara sah</p>
                        <p class="mt-0.5">Waktu: {{ formatDateTime(selectedContract.signed_at) }}</p>
                        <p class="font-mono mt-0.5">IP: {{ selectedContract.ip_address }}</p>
                    </div>
                    <div v-else>
                        <p class="font-semibold text-amber-500">⏳ Dokumen berstatus DRAF</p>
                        <p class="mt-0.5">Menunggu respon tanda tangan klien.</p>
                    </div>

                    <div class="flex items-center justify-end space-x-3 self-end">
                        <button 
                            @click="closeDocumentModal"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-750 dark:text-slate-300 font-bold rounded-xl transition"
                        >
                            Tutup
                        </button>
                        <Link 
                            :href="`/admin/bookings`"
                            class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white font-bold rounded-xl transition"
                        >
                            Detail Reservasi
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
