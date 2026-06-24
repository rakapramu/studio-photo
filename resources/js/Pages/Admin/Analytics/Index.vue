<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import Pagination from '../../../Components/Pagination.vue';

const props = defineProps({
    stats: Object,
    monthlyTrends: Array,
    packagePerformance: Array,
    projectProfitability: Array,
    crewLedger: Array,
});

const isReceivablesModalOpen = ref(false);
const openReceivablesModal = () => {
    isReceivablesModalOpen.value = true;
};
const closeReceivablesModal = () => {
    isReceivablesModalOpen.value = false;
};

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

// SVG Graph Scaling Calculations
const graphWidth = 700;
const graphHeight = 240;
const paddingX = 60;
const paddingY = 40;

const maxChartValue = computed(() => {
    const vals = props.monthlyTrends.flatMap(t => [t.revenue, t.expenses]);
    const maxVal = Math.max(...vals, 1000000);
    return maxVal * 1.15; // Add 15% buffer at the top
});

const getX = (index) => {
    const usableWidth = graphWidth - (paddingX * 2);
    const steps = props.monthlyTrends.length - 1;
    return paddingX + (index * (usableWidth / steps));
};

const getY = (value) => {
    const usableHeight = graphHeight - (paddingY * 2);
    const yFromBottom = (value / maxChartValue.value) * usableHeight;
    return graphHeight - paddingY - yFromBottom;
};

// SVG Path generators
const revenuePath = computed(() => {
    if (props.monthlyTrends.length === 0) return '';
    return props.monthlyTrends.map((t, idx) => 
        `${idx === 0 ? 'M' : 'L'} ${getX(idx)} ${getY(t.revenue)}`
    ).join(' ');
});

const revenueAreaPath = computed(() => {
    if (props.monthlyTrends.length === 0) return '';
    const points = props.monthlyTrends.map((t, idx) => 
        `L ${getX(idx)} ${getY(t.revenue)}`
    );
    // Start from bottom-left, draw path, close to bottom-right
    return `M ${getX(0)} ${graphHeight - paddingY} ` + 
           points.join(' ') + 
           ` L ${getX(props.monthlyTrends.length - 1)} ${graphHeight - paddingY} Z`;
});

const expensesPath = computed(() => {
    if (props.monthlyTrends.length === 0) return '';
    return props.monthlyTrends.map((t, idx) => 
        `${idx === 0 ? 'M' : 'L'} ${getX(idx)} ${getY(t.expenses)}`
    ).join(' ');
});

const expensesAreaPath = computed(() => {
    if (props.monthlyTrends.length === 0) return '';
    const points = props.monthlyTrends.map((t, idx) => 
        `L ${getX(idx)} ${getY(t.expenses)}`
    );
    return `M ${getX(0)} ${graphHeight - paddingY} ` + 
           points.join(' ') + 
           ` L ${getX(props.monthlyTrends.length - 1)} ${graphHeight - paddingY} Z`;
});

// Grid line helper
const yGridLines = computed(() => {
    const lines = [];
    const count = 4;
    for (let i = 0; i <= count; i++) {
        const val = (maxChartValue.value / count) * i;
        lines.push({
            value: val,
            y: getY(val),
        });
    }
    return lines;
});

// Table 1 Pagination: Project Profitability
const projCurrentPage = ref(1);
const projItemsPerPage = 5;
const paginatedProjects = computed(() => {
    const start = (projCurrentPage.value - 1) * projItemsPerPage;
    const end = start + projItemsPerPage;
    return props.projectProfitability.slice(start, end);
});

// Table 2 Pagination: Crew Ledger
const crewCurrentPage = ref(1);
const crewItemsPerPage = 5;
const paginatedCrews = computed(() => {
    const start = (crewCurrentPage.value - 1) * crewItemsPerPage;
    const end = start + crewItemsPerPage;
    return props.crewLedger.slice(start, end);
});

const getRoleEmojiLabel = (role) => {
    const labels = {
        fotografer: '📸 Fotografer',
        videografer: '🎥 Videografer',
        editor: '💻 Editor',
        mua: '💄 MUA',
        asisten: '🙋‍♂️ Asisten',
    };
    return labels[role] || role;
};
</script>

<template>
    <AdminLayout>
        <Head title="Analitik Keuangan" />

        <template #title>
            📈 Analitik Keuangan & Komisi
        </template>

        <!-- Action Toolbar -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h4 class="text-sm font-bold text-slate-800 dark:text-white">Aksi Dokumen & Audit</h4>
                <p class="text-xs text-slate-500 mt-0.5">Ekspor data pembukuan keuangan studio Raka Photo ke format cetak PDF resmi.</p>
            </div>
            <Link 
                href="/admin/analytics/report" 
                class="px-4 py-2.5 bg-violet-600 hover:bg-violet-700 text-white font-bold text-xs rounded-xl shadow-sm hover:shadow transition-all flex items-center gap-1.5 cursor-pointer"
            >
                🖨️ Cetak Laporan Keuangan
            </Link>
        </div>

        <!-- Summary Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Omset Kotor -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Omset Terbayar (Pendapatan)</p>
                <p class="text-2xl font-black text-violet-600 dark:text-violet-400 mt-2 font-mono">
                    {{ formatIDR(stats.gross_revenue) }}
                </p>
                <p class="text-xs text-slate-450 mt-1">🟡 Akumulasi transaksi Midtrans sukses</p>
            </div>

            <!-- Total Pengeluaran -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Total Pengeluaran</p>
                <p class="text-2xl font-black text-rose-600 dark:text-rose-450 mt-2 font-mono">
                    {{ formatIDR(stats.total_expenses) }}
                </p>
                <div class="text-xs text-slate-450 mt-1 flex flex-col space-y-0.5">
                    <span>💸 Operasional: {{ formatIDR(stats.operational_expenses) }}</span>
                    <span>👥 Komisi Kru: {{ formatIDR(stats.crew_commissions) }}</span>
                </div>
            </div>

            <!-- Laba Bersih -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm"
                :class="stats.net_profit >= 0 ? 'border-emerald-200 dark:border-emerald-950/40 bg-emerald-50/5 dark:bg-emerald-950/5' : 'border-rose-200 dark:border-rose-950/40 bg-rose-50/5 dark:bg-rose-950/5'"
            >
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Laba Bersih</p>
                <p class="text-2xl font-black mt-2 font-mono"
                    :class="stats.net_profit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                >
                    {{ formatIDR(stats.net_profit) }}
                </p>
                <p class="text-xs text-slate-450 mt-1">
                    {{ stats.net_profit >= 0 ? '🟢 Laba bersih setelah dikurangi pengeluaran' : '🔴 Rugi operasional bersih' }}
                </p>
            </div>

            <!-- Piutang Klien -->
            <div 
                @click="openReceivablesModal"
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm cursor-pointer hover:border-amber-400 dark:hover:border-amber-600/85 hover:shadow-md transition-all group"
            >
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 flex justify-between items-center">
                    <span>Piutang Pemesanan</span>
                    <span class="text-[9px] bg-amber-50 dark:bg-amber-950/40 text-amber-600 px-1.5 py-0.5 rounded font-bold opacity-0 group-hover:opacity-100 transition-opacity">👁️ LIHAT</span>
                </p>
                <p class="text-2xl font-black text-amber-500 mt-2 font-mono">
                    {{ formatIDR(stats.total_receivables) }}
                </p>
                <p class="text-xs text-slate-450 mt-1">⏳ Klik untuk rincian {{ stats.receivables_details?.length || 0 }} klien</p>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Line Chart: Cash Flow Trend (2/3 width) -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm lg:col-span-2">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-wider">Tren Arus Kas (6 Bulan Terakhir)</h3>
                    <div class="flex items-center space-x-3 text-xs">
                        <span class="inline-flex items-center"><span class="w-3 h-1.5 bg-violet-600 rounded mr-1.5"></span>Omset</span>
                        <span class="inline-flex items-center"><span class="w-3 h-1.5 bg-rose-500 rounded mr-1.5"></span>Pengeluaran</span>
                    </div>
                </div>

                <!-- SVG Area -->
                <div class="w-full relative h-[250px]">
                    <svg :viewBox="`0 0 ${graphWidth} ${graphHeight}`" class="w-full h-full overflow-visible">
                        <!-- Gradients definition -->
                        <defs>
                            <linearGradient id="rev-grad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#7c3aed" stop-opacity="0.25"/>
                                <stop offset="100%" stop-color="#7c3aed" stop-opacity="0.0"/>
                            </linearGradient>
                            <linearGradient id="exp-grad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#f43f5e" stop-opacity="0.20"/>
                                <stop offset="100%" stop-color="#f43f5e" stop-opacity="0.0"/>
                            </linearGradient>
                        </defs>

                        <!-- Grid Lines -->
                        <g class="stroke-slate-100 dark:stroke-slate-800/80 stroke-1 stroke-dasharray-[4,4]" stroke-dasharray="4,4">
                            <line 
                                v-for="line in yGridLines" 
                                :key="line.value"
                                :x1="paddingX" 
                                :y1="line.y" 
                                :x2="graphWidth - paddingX" 
                                :y2="line.y"
                            />
                        </g>

                        <!-- Y-Axis Labels -->
                        <g class="fill-slate-400 text-[10px] font-mono text-right" text-anchor="end">
                            <text 
                                v-for="line in yGridLines" 
                                :key="line.value"
                                :x="paddingX - 10" 
                                :y="line.y + 4"
                            >
                                {{ line.value >= 1000000 ? (line.value / 1000000).toFixed(1) + 'M' : formatIDR(line.value) }}
                            </text>
                        </g>

                        <!-- Area Plots -->
                        <path :d="revenueAreaPath" fill="url(#rev-grad)" />
                        <path :d="expensesAreaPath" fill="url(#exp-grad)" />

                        <!-- Line Plots -->
                        <path :d="revenuePath" fill="none" class="stroke-violet-600 dark:stroke-violet-400 stroke-2.5" stroke-linecap="round" stroke-linejoin="round" />
                        <path :d="expensesPath" fill="none" class="stroke-rose-500 dark:stroke-rose-450 stroke-2.5" stroke-linecap="round" stroke-linejoin="round" />

                        <!-- Interaction Nodes -->
                        <g v-for="(t, idx) in monthlyTrends" :key="idx">
                            <!-- Revenue Circle -->
                            <circle :cx="getX(idx)" :cy="getY(t.revenue)" r="4.5" class="fill-white stroke-violet-600 stroke-2" />
                            <!-- Expense Circle -->
                            <circle :cx="getX(idx)" :cy="getY(t.expenses)" r="4.5" class="fill-white stroke-rose-500 stroke-2" />
                        </g>

                        <!-- X-Axis Labels -->
                        <g class="fill-slate-400 text-[10px] text-center" text-anchor="middle">
                            <text 
                                v-for="(t, idx) in monthlyTrends" 
                                :key="idx"
                                :x="getX(idx)" 
                                :y="graphHeight - paddingY + 20"
                            >
                                {{ t.label.split(' ')[0] }}
                            </text>
                        </g>
                    </svg>
                </div>
            </div>

            <!-- Best Selling Packages (1/3 width) -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-wider mb-4">Performa & Popularitas Paket</h3>
                    
                    <div v-if="packagePerformance.length === 0" class="text-xs text-slate-450 italic py-10 text-center">
                        Belum ada data pemesanan paket foto.
                    </div>
                    
                    <div class="space-y-4">
                        <div 
                            v-for="pkg in packagePerformance.slice(0, 4)" 
                            :key="pkg.id"
                        >
                            <div class="flex justify-between items-center text-xs font-semibold mb-1">
                                <span class="text-slate-700 dark:text-slate-350 truncate max-w-[70%]" :title="pkg.name">
                                    {{ pkg.name }}
                                </span>
                                <span class="text-slate-500">
                                    {{ pkg.bookings_count }} Booking
                                </span>
                            </div>
                            
                            <!-- Custom Progress Bar -->
                            <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div 
                                    class="h-full bg-violet-600 rounded-full transition-all"
                                    :style="{ width: `${Math.min(100, Math.max(8, (pkg.bookings_count / (Math.max(...packagePerformance.map(p=>p.bookings_count)) || 1)) * 100))}%` }"
                                ></div>
                            </div>
                            <div class="text-right text-[10px] font-bold text-slate-400 mt-0.5">
                                Omset: {{ formatIDR(pkg.revenue) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-100 dark:border-slate-800/80 pt-3 mt-4 text-center">
                    <Link href="/admin/packages" class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline">
                        🏷️ Kelola Paket Foto
                    </Link>
                </div>
            </div>
        </div>

        <!-- Profitability Table -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden mb-6">
            <div class="p-5 border-b border-slate-100 dark:border-slate-800/80">
                <h3 class="text-sm font-bold text-slate-805 dark:text-white uppercase tracking-wider">Laporan Laba/Rugi Per Proyek (Sesi)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-805 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-450 uppercase tracking-wider">
                            <th class="px-6 py-4">Klien & Tanggal</th>
                            <th class="px-6 py-4">Paket Sesi</th>
                            <th class="px-6 py-4">Pendapatan</th>
                            <th class="px-6 py-4">Biaya Kru (Komisi)</th>
                            <th class="px-6 py-4">Laba Bersih Proyek</th>
                            <th class="px-6 py-4 text-center">Status Sesi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        <tr v-if="projectProfitability.length === 0">
                            <td colspan="6" class="px-6 py-10 text-center text-slate-450 italic">
                                Belum ada transaksi pengerjaan proyek.
                            </td>
                        </tr>
                        <tr 
                            v-for="proj in paginatedProjects" 
                            :key="proj.id"
                            class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-all"
                        >
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-800 dark:text-slate-250">{{ proj.client_name }}</div>
                                <div class="text-xs text-slate-450">{{ formatDate(proj.booking_date) }}</div>
                            </td>
                            <td class="px-6 py-4 text-slate-700 dark:text-slate-350 font-medium">
                                {{ proj.package_name }}
                            </td>
                            <td class="px-6 py-4 font-mono font-bold text-slate-900 dark:text-white">
                                {{ formatIDR(proj.paid_amount) }}
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-500">
                                -{{ formatIDR(proj.crew_cost) }}
                            </td>
                            <td class="px-6 py-4 font-mono font-bold"
                                :class="proj.net_profit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                            >
                                {{ formatIDR(proj.net_profit) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold"
                                    :class="{
                                        'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400': proj.status === 'completed',
                                        'bg-blue-100 text-blue-800 dark:bg-blue-950/40 dark:text-blue-400': proj.status === 'confirmed',
                                        'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400': proj.status === 'pending',
                                        'bg-slate-100 text-slate-550 dark:bg-slate-800 dark:text-slate-400': proj.status === 'cancelled'
                                    }"
                                >
                                    {{ proj.status.toUpperCase() }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <Pagination 
                :total-items="projectProfitability.length"
                :items-per-page="projItemsPerPage"
                v-model:current-page="projCurrentPage"
            />
        </div>

        <!-- Crew Ledger Table -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 dark:border-slate-800/80">
                <h3 class="text-sm font-bold text-slate-805 dark:text-white uppercase tracking-wider">Buku Besar Akumulasi Komisi Kru</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-805 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-450 uppercase tracking-wider">
                            <th class="px-6 py-4">Nama Kru / Staf</th>
                            <th class="px-6 py-4">Peran Utama</th>
                            <th class="px-6 py-4">Tarif Per Sesi</th>
                            <th class="px-6 py-4">Jumlah Penugasan Sesi</th>
                            <th class="px-6 py-4">Total Hak Komisi</th>
                            <th class="px-6 py-4 text-center">Status Pengerjaan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        <tr v-if="crewLedger.length === 0">
                            <td colspan="6" class="px-6 py-10 text-center text-slate-450 italic">
                                Belum ada tim kru terdaftar.
                            </td>
                        </tr>
                        <tr 
                            v-for="crew in paginatedCrews" 
                            :key="crew.id"
                            class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-all text-sm"
                        >
                            <td class="px-6 py-4 font-semibold text-slate-800 dark:text-slate-200">
                                {{ crew.name }}
                            </td>
                            <td class="px-6 py-4 text-slate-650 dark:text-slate-400">
                                {{ getRoleEmojiLabel(crew.role) }}
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-650 dark:text-slate-400">
                                {{ formatIDR(crew.fee_per_session) }}
                            </td>
                            <td class="px-6 py-4 font-bold text-slate-900 dark:text-white text-center sm:text-left">
                                {{ crew.sessions_count }} Sesi Foto
                            </td>
                            <td class="px-6 py-4 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                {{ formatIDR(crew.total_earnings) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400">
                                    ✓ ACC CRON
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <Pagination 
                :total-items="crewLedger.length"
                :items-per-page="crewItemsPerPage"
                v-model:current-page="crewCurrentPage"
            />
        </div>

        <!-- Receivables Detail Modal -->
        <div v-if="isReceivablesModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-950/60 dark:bg-slate-950/80 backdrop-blur-sm" @click="closeReceivablesModal"></div>

            <!-- Modal Panel -->
            <div class="bg-white dark:bg-slate-900 w-full max-w-2xl rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl z-10 overflow-hidden flex flex-col transform transition-all animate-in fade-in zoom-in-95 duration-200 relative">
                <div class="px-6 py-4 border-b border-slate-150 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-800/40">
                    <h3 class="text-sm font-bold text-slate-850 dark:text-slate-100 uppercase tracking-wider flex items-center">
                        ⏳ Detail Klien Piutang Pemesanan
                    </h3>
                    <button @click="closeReceivablesModal" class="text-slate-400 hover:text-slate-650 dark:hover:text-slate-200 text-sm focus:outline-none">✕</button>
                </div>

                <div class="p-6 overflow-y-auto max-h-[450px]">
                    <div v-if="!stats.receivables_details || stats.receivables_details.length === 0" class="text-sm text-slate-450 italic text-center py-8">
                        Tidak ada klien dengan sisa piutang pemesanan saat ini.
                    </div>
                    <div v-else class="space-y-4">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-200 dark:border-slate-800 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                                        <th class="pb-3 text-left">Klien / Detail Kontak</th>
                                        <th class="pb-3 text-left">Sesi & Tanggal</th>
                                        <th class="pb-3 text-right">Total Harga</th>
                                        <th class="pb-3 text-right">Terbayar</th>
                                        <th class="pb-3 text-right text-amber-500 font-bold">Sisa Tagihan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                                    <tr 
                                        v-for="item in stats.receivables_details" 
                                        :key="item.id"
                                        class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10 text-slate-700 dark:text-slate-300"
                                    >
                                        <td class="py-3 pr-2">
                                            <div class="font-bold text-slate-900 dark:text-white">{{ item.client_name }}</div>
                                            <div class="text-[10px] text-slate-450 mt-0.5">{{ item.client_phone }}</div>
                                            <div class="text-[10px] text-slate-450">{{ item.client_email }}</div>
                                        </td>
                                        <td class="py-3 pr-2">
                                            <div class="font-semibold text-slate-800 dark:text-slate-200">{{ item.package_name }}</div>
                                            <div class="text-[10px] text-slate-450 mt-0.5">{{ formatDate(item.booking_date) }}</div>
                                            <span class="inline-block text-[9px] font-bold px-1.5 py-0.2 rounded mt-1"
                                                :class="item.status === 'confirmed' ? 'bg-emerald-150 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400'"
                                            >
                                                {{ item.status.toUpperCase() }}
                                            </span>
                                        </td>
                                        <td class="py-3 text-right font-mono font-semibold">{{ formatIDR(item.total_price) }}</td>
                                        <td class="py-3 text-right font-mono text-slate-500">{{ formatIDR(item.paid_amount) }}</td>
                                        <td class="py-3 text-right font-mono font-bold text-amber-500">{{ formatIDR(item.remaining_balance) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-150 dark:border-slate-800 flex justify-between items-center text-xs">
                    <span class="font-bold text-slate-450 dark:text-slate-500">TOTAL PIUTANG AKTIF:</span>
                    <span class="font-mono font-black text-sm text-amber-505">{{ formatIDR(stats.total_receivables) }}</span>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
