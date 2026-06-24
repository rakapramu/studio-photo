<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    filters: Object,
    periodLabel: String,
    summary: Object,
    revenueList: Array,
    expensesList: Array,
    generatedAt: String,
    adminName: String,
});

// Filter state
const filterType = ref(props.filters.filter_type || 'all');
const startDate = ref(props.filters.start_date || new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0]);
const endDate = ref(props.filters.end_date || new Date().toISOString().split('T')[0]);
const selectedMonth = ref(props.filters.month ? parseInt(props.filters.month) : new Date().getMonth() + 1);
const selectedYear = ref(props.filters.year ? parseInt(props.filters.year) : new Date().getFullYear());

// Helper Lists
const months = [
    { value: 1, name: 'Januari' },
    { value: 2, name: 'Februari' },
    { value: 3, name: 'Maret' },
    { value: 4, name: 'April' },
    { value: 5, name: 'Mei' },
    { value: 6, name: 'Juni' },
    { value: 7, name: 'Juli' },
    { value: 8, name: 'Agustus' },
    { value: 9, name: 'September' },
    { value: 10, name: 'Oktober' },
    { value: 11, name: 'November' },
    { value: 12, name: 'Desember' },
];

const years = [];
const currentYear = new Date().getFullYear();
for (let y = currentYear - 3; y <= currentYear + 3; y++) {
    years.push(y);
}

// Format Currency IDR
const formatIDR = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(value);
};

// Format Date
const formatDate = (dateStr) => {
    if (!dateStr || dateStr === '-') return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        dateStyle: 'medium'
    });
};

// Apply Filter
const applyFilter = () => {
    const params = {
        filter_type: filterType.value,
    };

    if (filterType.value === 'date_range') {
        params.start_date = startDate.value;
        params.end_date = endDate.value;
    } else if (filterType.value === 'month') {
        params.month = selectedMonth.value;
        params.year = selectedYear.value;
    } else if (filterType.value === 'year') {
        params.year = selectedYear.value;
    }

    router.get('/admin/analytics/report', params, {
        preserveState: true,
        replace: true,
    });
};

// Print Action
const triggerPrint = () => {
    window.print();
};

// Go Back
const goBack = () => {
    router.get('/admin/analytics');
};
</script>

<template>
    <div class="min-h-screen bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-100 print:bg-white print:text-black transition-colors duration-300 font-sans pb-12 print:pb-0">
        <Head title="Cetak Laporan Keuangan & Audit" />

        <!-- Print-Hidden Toolbar Controller -->
        <div class="print:hidden sticky top-0 z-50 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 shadow-sm p-4 backdrop-blur-md bg-opacity-95">
            <div class="max-w-6xl mx-auto flex flex-col md:flex-row gap-4 items-center justify-between">
                <!-- Navigation -->
                <div class="flex items-center space-x-3 self-start md:self-center">
                    <button 
                        @click="goBack"
                        class="px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl transition cursor-pointer"
                    >
                        ⬅️ Kembali
                    </button>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Laporan Audit & Keuangan</h2>
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                    <!-- Filter Type Dropdown -->
                    <select 
                        v-model="filterType"
                        class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-600"
                    >
                        <option value="all">Semua Periode</option>
                        <option value="date_range">Rentang Tanggal</option>
                        <option value="month">Per Bulan</option>
                        <option value="year">Per Tahun</option>
                    </select>

                    <!-- Date Range Inputs -->
                    <div v-if="filterType === 'date_range'" class="flex items-center space-x-2">
                        <input 
                            type="date" 
                            v-model="startDate"
                            class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-2 py-1.5 text-xs text-slate-700 dark:text-slate-200 focus:outline-none font-mono"
                        />
                        <span class="text-xs text-slate-400">s/d</span>
                        <input 
                            type="date" 
                            v-model="endDate"
                            class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-2 py-1.5 text-xs text-slate-700 dark:text-slate-200 focus:outline-none font-mono"
                        />
                    </div>

                    <!-- Month Selectors -->
                    <div v-if="filterType === 'month'" class="flex items-center space-x-2">
                        <select 
                            v-model="selectedMonth"
                            class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-700 dark:text-slate-200 focus:outline-none"
                        >
                            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.name }}</option>
                        </select>
                        <select 
                            v-model="selectedYear"
                            class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-700 dark:text-slate-200 focus:outline-none font-mono"
                        >
                            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>

                    <!-- Year Selector Only -->
                    <div v-if="filterType === 'year'" class="flex items-center space-x-2">
                        <select 
                            v-model="selectedYear"
                            class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-700 dark:text-slate-200 focus:outline-none font-mono"
                        >
                            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>

                    <!-- Filter Button -->
                    <button 
                        @click="applyFilter"
                        class="px-4 py-1.5 bg-violet-600 hover:bg-violet-700 text-white font-bold text-xs rounded-xl shadow-sm transition-all cursor-pointer"
                    >
                        🔄 Tampilkan
                    </button>

                    <!-- Print Button -->
                    <button 
                        @click="triggerPrint"
                        class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition-all cursor-pointer flex items-center gap-1"
                    >
                        🖨️ Cetak PDF
                    </button>
                </div>
            </div>
        </div>

        <!-- Document Report Body (A4 Styled Container) -->
        <div class="max-w-4xl mx-auto my-6 print:my-0 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 print:border-none rounded-2xl print:rounded-none shadow-lg print:shadow-none p-8 sm:p-12 print:p-0 transition-colors duration-300">
            
            <!-- Corporate Header -->
            <div class="flex justify-between items-start border-b-2 border-slate-900 dark:border-slate-750 pb-6 mb-8 flex-col sm:flex-row gap-4">
                <div>
                    <h1 class="text-2xl font-black bg-gradient-to-r from-violet-600 to-indigo-600 dark:from-violet-400 dark:to-indigo-400 bg-clip-text text-transparent print:text-slate-900">
                        📷 RAKA PHOTO STUDIO
                    </h1>
                    <p class="text-[10px] text-slate-450 dark:text-slate-500 font-medium mt-1 uppercase tracking-wide">
                        Sistem Informasi Manajemen Usaha & Penjadwalan Sesi Foto
                    </p>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm">
                        Jl. Raka Raya No. 45, Kota Malang, Jawa Timur | Telp: +62 812-3456-7890
                    </p>
                </div>
                <div class="text-right sm:text-right self-start sm:self-auto font-mono text-xs text-slate-500 space-y-1">
                    <p class="font-bold text-slate-800 dark:text-white uppercase tracking-wider text-sm">LAPORAN AUDIT KEUANGAN</p>
                    <p>Periode: <span class="font-bold text-slate-900 dark:text-slate-200">{{ periodLabel }}</span></p>
                    <p>Dibuat: {{ generatedAt }}</p>
                </div>
            </div>

            <!-- Metadata Box -->
            <div class="bg-slate-50 dark:bg-slate-800/40 print:bg-slate-50 border border-slate-200 dark:border-slate-800/80 rounded-xl p-4 mb-8 grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                <div>
                    <span class="block font-bold text-slate-400 uppercase tracking-wider text-[9px] mb-0.5">Auditor / Admin</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200 print:text-slate-900">{{ adminName }}</span>
                </div>
                <div>
                    <span class="block font-bold text-slate-400 uppercase tracking-wider text-[9px] mb-0.5">Metode Filter</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200 print:text-slate-900">
                        {{ filterType === 'all' ? 'Semua Periode' : filterType === 'date_range' ? 'Rentang Tanggal' : filterType === 'month' ? 'Bulanan' : 'Tahunan' }}
                    </span>
                </div>
                <div>
                    <span class="block font-bold text-slate-400 uppercase tracking-wider text-[9px] mb-0.5">Total Transaksi Selesai</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200 print:text-slate-900 font-mono">{{ revenueList.length }} Pemasukan</span>
                </div>
                <div>
                    <span class="block font-bold text-slate-400 uppercase tracking-wider text-[9px] mb-0.5">Total Entri Biaya</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200 print:text-slate-900 font-mono">{{ expensesList.length }} Pengeluaran</span>
                </div>
            </div>

            <!-- Financial Summary Matrix -->
            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-3">1. Matriks Laba/Rugi Bersih</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 border border-slate-200 dark:border-slate-850 rounded-2xl overflow-hidden mb-8 shadow-sm print-block-avoid">
                <!-- Total Omset -->
                <div class="p-5 bg-slate-50/40 dark:bg-slate-900 print:bg-white border-b md:border-b-0 md:border-r border-slate-200 dark:border-slate-850">
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Omset Pemasukan</span>
                    <span class="block text-xl font-black text-violet-600 dark:text-violet-400 print:text-violet-700 font-mono mt-1">
                        {{ formatIDR(summary.total_revenue) }}
                    </span>
                    <span class="text-[9px] text-slate-455 block mt-1">Dari pembayaran Midtrans berstatus paid</span>
                </div>

                <!-- Total Pengeluaran -->
                <div class="p-5 bg-slate-50/40 dark:bg-slate-900 print:bg-white border-b md:border-b-0 md:border-r border-slate-200 dark:border-slate-850">
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Pengeluaran</span>
                    <span class="block text-xl font-black text-rose-600 dark:text-rose-455 print:text-rose-650 font-mono mt-1">
                        {{ formatIDR(summary.total_expenses) }}
                    </span>
                    <div class="text-[9px] text-slate-455 block mt-1 flex flex-col">
                        <span>Operasional: {{ formatIDR(summary.total_operational_expenses) }}</span>
                        <span>Komisi Kru: {{ formatIDR(summary.total_crew_commissions) }}</span>
                    </div>
                </div>

                <!-- Laba Bersih -->
                <div class="p-5 bg-emerald-50/10 dark:bg-emerald-950/5 print:bg-emerald-50/10">
                    <span class="block text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Laba / Rugi Bersih</span>
                    <span class="block text-xl font-black font-mono mt-1"
                        :class="summary.net_profit >= 0 ? 'text-emerald-650 dark:text-emerald-400 print:text-emerald-700' : 'text-rose-650 dark:text-rose-400 print:text-rose-700'"
                    >
                        {{ formatIDR(summary.net_profit) }}
                    </span>
                    <span class="text-[9px] block mt-1 font-semibold"
                        :class="summary.net_profit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-455'"
                    >
                        {{ summary.net_profit >= 0 ? '🟢 Surplus Profit' : '🔴 Defisit Rugi' }}
                    </span>
                </div>
            </div>

            <!-- Revenue Detailed List Table -->
            <div class="mb-8">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-3">2. Rincian Pemasukan (Omset)</h3>
                <div class="border border-slate-250 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm print:border-none print:shadow-none print:rounded-none">
                    <table class="w-full text-[11px] text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-805 print:bg-slate-100 border-b border-slate-200 dark:border-slate-850 font-bold uppercase tracking-wider text-slate-450 text-[9px] print:text-slate-700">
                                <th class="px-4 py-3 w-[15%]">Tanggal</th>
                                <th class="px-4 py-3 w-[18%]">ID Invoice</th>
                                <th class="px-4 py-3 w-[20%]">Klien</th>
                                <th class="px-4 py-3 w-[32%]">Deskripsi</th>
                                <th class="px-4 py-3 w-[15%] text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            <tr v-if="revenueList.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-slate-400 italic">
                                    Tidak ada data pemasukan tercatat pada periode ini.
                                </td>
                            </tr>
                            <tr 
                                v-for="item in revenueList" 
                                :key="item.invoice"
                                class="hover:bg-slate-50/40 dark:hover:bg-slate-800/10 print:hover:bg-transparent border-b border-slate-100 dark:border-slate-800/50 print:border-slate-200"
                            >
                                <td class="px-4 py-2.5 font-mono">{{ formatDate(item.date) }}</td>
                                <td class="px-4 py-2.5 font-bold font-mono text-slate-650 dark:text-slate-350 print:text-slate-700">{{ item.invoice }}</td>
                                <td class="px-4 py-2.5 font-semibold">{{ item.client_name }}</td>
                                <td class="px-4 py-2.5 text-slate-500 dark:text-slate-400 print:text-slate-600">{{ item.description }}</td>
                                <td class="px-4 py-2.5 font-bold font-mono text-right text-emerald-650 print:text-emerald-700">{{ formatIDR(item.amount) }}</td>
                            </tr>
                            <tr class="bg-slate-50/50 dark:bg-slate-900/50 print:bg-slate-50 font-bold border-t border-slate-200 dark:border-slate-800">
                                <td colspan="4" class="px-4 py-3 text-right text-xs uppercase tracking-wider text-slate-450 print:text-slate-600">Subtotal Pemasukan:</td>
                                <td class="px-4 py-3 text-right font-mono text-emerald-650 print:text-emerald-700 text-xs">{{ formatIDR(summary.total_revenue) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Expenses Detailed List Table -->
            <div class="mb-10">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-3">3. Rincian Pengeluaran Operasional & Komisi</h3>
                <div class="border border-slate-250 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm print:border-none print:shadow-none print:rounded-none">
                    <table class="w-full text-[11px] text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-805 print:bg-slate-100 border-b border-slate-200 dark:border-slate-850 font-bold uppercase tracking-wider text-slate-450 text-[9px] print:text-slate-700">
                                <th class="px-4 py-3 w-[15%]">Tanggal</th>
                                <th class="px-4 py-3 w-[18%]">Kategori</th>
                                <th class="px-4 py-3 w-[25%]">Keterangan</th>
                                <th class="px-4 py-3 w-[27%]">Rincian Catatan</th>
                                <th class="px-4 py-3 w-[15%] text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            <tr v-if="expensesList.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-slate-400 italic">
                                    Tidak ada data pengeluaran tercatat pada periode ini.
                                </td>
                            </tr>
                            <tr 
                                v-for="item in expensesList" 
                                :key="item.title + item.date + item.amount"
                                class="hover:bg-slate-50/40 dark:hover:bg-slate-800/10 print:hover:bg-transparent border-b border-slate-100 dark:border-slate-800/50 print:border-slate-200"
                            >
                                <td class="px-4 py-2.5 font-mono">{{ formatDate(item.date) }}</td>
                                <td class="px-4 py-2.5">
                                    <span class="inline-block px-2 py-0.5 rounded text-[9px] font-bold"
                                        :class="item.category === 'Operasional' 
                                            ? 'bg-rose-50 dark:bg-rose-950/20 text-rose-800 dark:text-rose-455 print:bg-rose-50 print:text-rose-800' 
                                            : 'bg-indigo-50 dark:bg-indigo-950/20 text-indigo-800 dark:text-indigo-455 print:bg-indigo-50 print:text-indigo-800'"
                                    >
                                        {{ item.category.toUpperCase() }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 font-semibold">{{ item.title }}</td>
                                <td class="px-4 py-2.5 text-slate-500 dark:text-slate-400 print:text-slate-600">{{ item.description }}</td>
                                <td class="px-4 py-2.5 font-bold font-mono text-right text-rose-650 print:text-rose-700">-{{ formatIDR(item.amount) }}</td>
                            </tr>
                            <tr class="bg-slate-50/50 dark:bg-slate-900/50 print:bg-slate-50 font-bold border-t border-slate-200 dark:border-slate-800">
                                <td colspan="4" class="px-4 py-3 text-right text-xs uppercase tracking-wider text-slate-450 print:text-slate-600">Subtotal Pengeluaran:</td>
                                <td class="px-4 py-3 text-right font-mono text-rose-650 print:text-rose-700 text-xs">{{ formatIDR(summary.total_expenses) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Signatures Section -->
            <div class="mt-16 pt-8 border-t border-dashed border-slate-200 dark:border-slate-800 flex justify-between text-xs print:mt-12 print-block-avoid">
                <div class="text-center w-[200px]">
                    <p class="text-slate-450 uppercase tracking-wider text-[9px] mb-12">Dibuat Oleh,</p>
                    <div class="border-b border-slate-300 dark:border-slate-700 mx-auto w-[150px] mb-1"></div>
                    <p class="font-bold text-slate-800 dark:text-white print:text-slate-900">{{ adminName }}</p>
                    <p class="text-[9px] text-slate-450">Administrator Studio</p>
                </div>

                <div class="text-center w-[200px]">
                    <p class="text-slate-450 uppercase tracking-wider text-[9px] mb-12">Disetujui & Diadit Oleh,</p>
                    <div class="border-b border-slate-300 dark:border-slate-700 mx-auto w-[150px] mb-1"></div>
                    <p class="font-bold text-slate-800 dark:text-white print:text-slate-900">Owner Raka Photo</p>
                    <p class="text-[9px] text-slate-450">Pemilik Usaha</p>
                </div>
            </div>

            <!-- Document Audit footer -->
            <div class="mt-12 text-center text-[9px] text-slate-400 dark:text-slate-500 border-t border-slate-100 dark:border-slate-800/80 pt-4 font-mono">
                Laporan ini dibuat otomatis oleh sistem manajemen internal Raka Photo Studio. Seluruh data keuangan, invoice, dan pengeluaran bersifat rahasia dan sah secara hukum sebagai basis audit pajak & keuangan internal.
            </div>
        </div>
    </div>
</template>

<style>
/* CSS custom for page break during printing */
@media print {
    body {
        background-color: white !important;
        color: black !important;
    }
    .page-break-before {
        page-break-before: always !important;
        break-before: always !important;
    }
    .print-block-avoid {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
    table {
        page-break-inside: auto;
    }
    tr {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
    thead {
        display: table-header-group !important;
    }
    tfoot {
        display: table-footer-group !important;
    }
    /* Hide scrollbars */
    ::-webkit-scrollbar {
        display: none;
    }
}
</style>
