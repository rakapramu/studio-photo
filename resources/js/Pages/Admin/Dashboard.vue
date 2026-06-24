<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '../../Layouts/AdminLayout.vue';

const props = defineProps({
    stats: Object,
    recentBookings: Array,
    allBookings: Array,
});

// View mode state: 'list' or 'calendar'
const activeTab = ref('calendar');

// Calendar state
const currentYear = ref(new Date().getFullYear());
const currentMonth = ref(new Date().getMonth()); // 0-indexed

const monthNames = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const nextMonth = () => {
    if (currentMonth.value === 11) {
        currentMonth.value = 0;
        currentYear.value++;
    } else {
        currentMonth.value++;
    }
};

const prevMonth = () => {
    if (currentMonth.value === 0) {
        currentMonth.value = 11;
        currentYear.value--;
    } else {
        currentMonth.value--;
    }
};

const goToToday = () => {
    currentYear.value = new Date().getFullYear();
    currentMonth.value = new Date().getMonth();
};

// Generate calendar cells (exactly 42 cells representing 6 rows)
const calendarCells = computed(() => {
    const year = currentYear.value;
    const month = currentMonth.value;

    // First day of the month (e.g. Wednesday -> index 3)
    const firstDayIndex = new Date(year, month, 1).getDay();
    
    // Days in current month
    const daysInCurrentMonth = new Date(year, month + 1, 0).getDate();
    
    // Days in previous month
    const daysInPrevMonth = new Date(year, month, 0).getDate();
    
    const cells = [];
    
    // Previous month padding cells
    for (let i = firstDayIndex - 1; i >= 0; i--) {
        const dayVal = daysInPrevMonth - i;
        const prevMonthIndex = month === 0 ? 11 : month - 1;
        const prevYearVal = month === 0 ? year - 1 : year;
        cells.push({
            day: dayVal,
            month: prevMonthIndex,
            year: prevYearVal,
            isCurrentMonth: false,
            dateString: `${prevYearVal}-${String(prevMonthIndex + 1).padStart(2, '0')}-${String(dayVal).padStart(2, '0')}`
        });
    }
    
    // Current month cells
    for (let dayVal = 1; dayVal <= daysInCurrentMonth; dayVal++) {
        cells.push({
            day: dayVal,
            month: month,
            year: year,
            isCurrentMonth: true,
            dateString: `${year}-${String(month + 1).padStart(2, '0')}-${String(dayVal).padStart(2, '0')}`
        });
    }
    
    // Next month padding cells to make 42 items
    const remainingCells = 42 - cells.length;
    for (let dayVal = 1; dayVal <= remainingCells; dayVal++) {
        const nextMonthIndex = month === 11 ? 0 : month + 1;
        const nextYearVal = month === 11 ? year + 1 : year;
        cells.push({
            day: dayVal,
            month: nextMonthIndex,
            year: nextYearVal,
            isCurrentMonth: false,
            dateString: `${nextYearVal}-${String(nextMonthIndex + 1).padStart(2, '0')}-${String(dayVal).padStart(2, '0')}`
        });
    }
    
    return cells;
});

// Match bookings to date
const getBookingsForDate = (dateString) => {
    return props.allBookings.filter(b => {
        if (!b.booking_date) return false;
        const dateObj = new Date(b.booking_date);
        const y = dateObj.getFullYear();
        const m = String(dateObj.getMonth() + 1).padStart(2, '0');
        const d = String(dateObj.getDate()).padStart(2, '0');
        const formattedBDate = `${y}-${m}-${d}`;
        return formattedBDate === dateString;
    });
};

const isToday = (dateString) => {
    const today = new Date();
    const y = today.getFullYear();
    const m = String(today.getMonth() + 1).padStart(2, '0');
    const d = String(today.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}` === dateString;
};

// Details Modal State
const isDetailModalOpen = ref(false);
const activeBooking = ref(null);

const openDetailModal = (booking) => {
    activeBooking.value = booking;
    isDetailModalOpen.value = true;
};

const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    activeBooking.value = null;
};

// Helpers for modal details
const formatIDR = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(value);
};

const formatLocalDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};
</script>

<template>
    <AdminLayout>
        <Head title="Admin Dashboard" />

        <template #title>
            Dashboard Ringkasan
        </template>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Stat card: Total Booking -->
            <div class="p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl transition-all shadow-sm hover:shadow-md flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Pemesanan</p>
                    <p class="text-3xl font-extrabold mt-1 text-slate-800 dark:text-slate-100">{{ stats.totalBookings }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-violet-50 dark:bg-violet-955/40 text-violet-600 dark:text-violet-400 flex items-center justify-center text-2xl">
                    📅
                </div>
            </div>

            <!-- Stat card: Total Packages -->
            <div class="p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl transition-all shadow-sm hover:shadow-md flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Paket Foto</p>
                    <p class="text-3xl font-extrabold mt-1 text-slate-800 dark:text-slate-100">{{ stats.totalPackages }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-2xl">
                    🏷️
                </div>
            </div>

            <!-- Stat card: Pending Booking -->
            <div class="p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl transition-all shadow-sm hover:shadow-md flex items-center justify-between border-l-4 border-l-amber-500">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Menunggu Review</p>
                    <p class="text-3xl font-extrabold mt-1 text-slate-800 dark:text-slate-100">{{ stats.pendingBookings }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-amber-50 dark:bg-amber-955/40 text-amber-600 dark:text-amber-400 flex items-center justify-center text-2xl">
                    ⏳
                </div>
            </div>

            <!-- Stat card: Confirmed Booking -->
            <div class="p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl transition-all shadow-sm hover:shadow-md flex items-center justify-between border-l-4 border-l-emerald-500">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Terkonfirmasi</p>
                    <p class="text-3xl font-extrabold mt-1 text-slate-800 dark:text-slate-100">{{ stats.confirmedBookings }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-2xl">
                    ✅
                </div>
            </div>
        </div>

        <!-- Dashboard Content Grid -->
        <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
            
            <!-- Left 3 columns: Interactive Calendar or List view -->
            <div class="xl:col-span-3 p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm transition-all duration-300">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-5 border-b border-slate-150 dark:border-slate-800/80 mb-5 gap-3">
                    <div class="flex items-center space-x-2">
                        <span class="text-xl">📅</span>
                        <h3 class="text-base font-bold text-slate-850 dark:text-slate-100">Jadwal & Reservasi Studio</h3>
                    </div>
                    
                    <!-- Tab Switcher -->
                    <div class="flex bg-slate-100 dark:bg-slate-950 p-1 rounded-lg self-start sm:self-center">
                        <button 
                            @click="activeTab = 'calendar'"
                            class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all cursor-pointer"
                            :class="activeTab === 'calendar' 
                                ? 'bg-white dark:bg-slate-850 text-violet-600 dark:text-violet-400 shadow-sm' 
                                : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                        >
                            📅 Kalender Bulanan
                        </button>
                        <button 
                            @click="activeTab = 'list'"
                            class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all cursor-pointer"
                            :class="activeTab === 'list' 
                                ? 'bg-white dark:bg-slate-850 text-violet-600 dark:text-violet-400 shadow-sm' 
                                : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                        >
                            📋 Daftar Sesi Terbaru
                        </button>
                    </div>
                </div>

                <!-- View 1: Calendar View -->
                <div v-show="activeTab === 'calendar'">
                    <!-- Calendar Header Controls -->
                    <div class="flex items-center justify-between mb-5">
                        <div class="flex items-center space-x-3">
                            <h4 class="text-base font-bold text-slate-800 dark:text-slate-100">
                                {{ monthNames[currentMonth] }} {{ currentYear }}
                            </h4>
                            <button 
                                @click="goToToday"
                                class="px-2.5 py-1 text-[10px] font-bold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded transition-all cursor-pointer"
                            >
                                Hari Ini
                            </button>
                        </div>
                        <div class="flex space-x-1.5">
                            <button 
                                @click="prevMonth"
                                class="p-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs rounded-lg transition-all cursor-pointer"
                            >
                                ◀
                            </button>
                            <button 
                                @click="nextMonth"
                                class="p-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs rounded-lg transition-all cursor-pointer"
                            >
                                ▶
                            </button>
                        </div>
                    </div>

                    <!-- Weekdays Header -->
                    <div class="grid grid-cols-7 gap-px bg-slate-200 dark:bg-slate-855 rounded-t-lg overflow-hidden border-t border-x border-slate-200 dark:border-slate-800">
                        <div 
                            v-for="day in ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']" 
                            :key="day" 
                            class="bg-slate-50 dark:bg-slate-850/60 py-2 text-center text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-550"
                        >
                            {{ day.substring(0, 3) }}
                        </div>
                    </div>

                    <!-- Calendar Grid -->
                    <div class="grid grid-cols-7 gap-px bg-slate-200 dark:bg-slate-800 border border-slate-200 dark:border-slate-800 rounded-b-lg overflow-hidden">
                        <div 
                            v-for="(cell, idx) in calendarCells" 
                            :key="idx" 
                            class="bg-white dark:bg-slate-900 min-h-[90px] p-2 flex flex-col justify-between transition-colors group relative"
                            :class="[
                                !cell.isCurrentMonth ? 'bg-slate-50/50 dark:bg-slate-950/20 opacity-40' : '',
                                isToday(cell.dateString) ? 'bg-violet-50/20 dark:bg-violet-900/15 border-2 border-violet-500' : ''
                            ]"
                        >
                            <!-- Date Number -->
                            <div class="flex items-center justify-between mb-1.5">
                                <span 
                                    class="text-xs font-semibold"
                                    :class="[
                                        isToday(cell.dateString) ? 'text-violet-600 dark:text-violet-400 font-bold' : 'text-slate-600 dark:text-slate-400',
                                    ]"
                                >
                                    {{ cell.day }}
                                </span>
                                
                                <span v-if="getBookingsForDate(cell.dateString).length > 0" class="text-[9px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-450 px-1 rounded-full">
                                    {{ getBookingsForDate(cell.dateString).length }}
                                </span>
                            </div>

                            <!-- Events List inside cell -->
                            <div class="flex-1 space-y-1 overflow-y-auto max-h-[60px] custom-scrollbar">
                                <div 
                                    v-for="booking in getBookingsForDate(cell.dateString)" 
                                    :key="booking.id"
                                    @click.stop="openDetailModal(booking)"
                                    class="text-[9px] px-1.5 py-0.5 rounded cursor-pointer transition-all hover:scale-[1.02] flex items-center justify-between font-medium tracking-tight truncate border"
                                    :class="{
                                        'bg-amber-50 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400 border-amber-100 dark:border-amber-900/50': booking.status === 'pending',
                                        'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400 border-emerald-100 dark:border-emerald-900/50': booking.status === 'confirmed',
                                        'bg-blue-50 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 border-blue-100 dark:border-blue-900/50': booking.status === 'completed',
                                        'bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-400 border-red-100 dark:border-red-900/50': booking.status === 'cancelled',
                                    }"
                                    :title="`${booking.start_time.substring(0, 5)} - ${booking.client_name} (${booking.package?.name})`"
                                >
                                    <span class="truncate">{{ booking.start_time.substring(0, 5) }} {{ booking.client_name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- View 2: List View -->
                <div v-show="activeTab === 'list'">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="text-xs font-semibold uppercase tracking-wider text-slate-400 border-b border-slate-150 dark:border-slate-800 pb-3">
                                    <th class="pb-3">Klien & Paket</th>
                                    <th class="pb-3">Tanggal & Waktu</th>
                                    <th class="pb-3">Lokasi</th>
                                    <th class="pb-3 text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr 
                                    v-for="booking in recentBookings" 
                                    :key="booking.id"
                                    class="border-b border-slate-100 dark:border-slate-800/80 hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors"
                                >
                                    <td class="py-4">
                                        <p class="font-semibold text-slate-800 dark:text-slate-100">{{ booking.client_name }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ booking.package?.name || 'Paket Kustom' }}</p>
                                    </td>
                                    <td class="py-4">
                                        <p class="text-slate-800 dark:text-slate-100">{{ new Date(booking.booking_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ booking.start_time.substring(0, 5) }} - {{ booking.end_time.substring(0, 5) }} WIB</p>
                                    </td>
                                    <td class="py-4">
                                        <p class="text-slate-800 dark:text-slate-100">📍 {{ booking.location }}</p>
                                    </td>
                                    <td class="py-4 text-right">
                                        <span 
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                            :class="{
                                                'bg-amber-100 text-amber-800 dark:bg-amber-955/40 dark:text-amber-400': booking.status === 'pending',
                                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-955/40 dark:text-emerald-400': booking.status === 'confirmed',
                                                'bg-blue-100 text-blue-800 dark:bg-blue-955/40 dark:text-blue-400': booking.status === 'completed',
                                                'bg-red-100 text-red-800 dark:bg-red-955/40 dark:text-red-400': booking.status === 'cancelled',
                                            }"
                                        >
                                            {{ booking.status.toUpperCase() }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="recentBookings.length === 0">
                                    <td colspan="4" class="py-8 text-center text-slate-400">
                                        <p class="text-2xl mb-2">📭</p>
                                        Belum ada pemesanan jadwal pemotretan terbaru.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right 1 column: Quick actions & System Info -->
            <div class="space-y-6">
                <!-- Card: Quick actions -->
                <div class="p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm transition-all">
                    <h3 class="text-base font-bold mb-4 text-slate-800 dark:text-slate-100">Aksi Pintar</h3>
                    <div class="space-y-3">
                        <Link 
                            href="/admin/packages" 
                            class="w-full flex items-center justify-center px-4 py-2.5 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow transition-all cursor-pointer"
                        >
                            ➕ Tambah Paket Foto Baru
                        </Link>
                        <Link 
                            href="/admin/bookings" 
                            class="w-full flex items-center justify-center px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm font-medium rounded-lg transition-all cursor-pointer"
                        >
                            📅 Buka Halaman Sesi
                        </Link>
                    </div>
                </div>

                <!-- Card: System config info -->
                <div class="p-6 bg-slate-900/40 dark:bg-slate-900/10 backdrop-blur-md border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm">
                    <h3 class="text-sm font-bold text-slate-500 dark:text-slate-400 mb-3 uppercase tracking-wider">Info Sistem</h3>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Database Driver:</span>
                            <span class="font-mono text-slate-800 dark:text-slate-200">PostgreSQL (pgsql)</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Storage API:</span>
                            <span class="font-mono text-slate-800 dark:text-slate-200">Cloudflare R2 Ready</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Server Caching:</span>
                            <span class="font-mono text-slate-800 dark:text-slate-200">Redis Cache Ready</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Interactive Booking Detail Modal -->
        <div v-if="isDetailModalOpen && activeBooking" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-950/60 dark:bg-slate-950/80 backdrop-blur-sm" @click="closeDetailModal"></div>

            <!-- Modal Panel -->
            <div class="bg-white dark:bg-slate-900 w-full max-w-[480px] rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl z-10 overflow-hidden flex flex-col justify-between transform transition-all animate-in fade-in zoom-in-95 duration-200 relative">
                <!-- Close Button -->
                <button @click="closeDetailModal" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm focus:outline-none transition-colors">✕</button>
                
                <div class="px-6 py-4 border-b border-slate-150 dark:border-slate-800 bg-slate-50 dark:bg-slate-855">
                    <div class="flex items-center space-x-2.5">
                        <span 
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold"
                            :class="{
                                'bg-amber-100 text-amber-800 dark:bg-amber-955/40 dark:text-amber-400': activeBooking.status === 'pending',
                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-955/40 dark:text-emerald-400': activeBooking.status === 'confirmed',
                                'bg-blue-100 text-blue-800 dark:bg-blue-955/40 dark:text-blue-400': activeBooking.status === 'completed',
                                'bg-red-100 text-red-800 dark:bg-red-955/40 dark:text-red-400': activeBooking.status === 'cancelled',
                            }"
                        >
                            {{ activeBooking.status.toUpperCase() }}
                        </span>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 font-sans">Detail Sesi Reservasi</h3>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    <!-- Client and Package -->
                    <div>
                        <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-0.5">KLIEN & LAYANAN</p>
                        <p class="font-bold text-slate-800 dark:text-slate-100 text-sm">{{ activeBooking.client_name }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ activeBooking.package?.name || 'Paket Kustom' }} — {{ formatIDR(activeBooking.total_price) }}</p>
                    </div>

                    <!-- Date, Time and Location -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-0.5">JADWAL</p>
                            <p class="text-xs text-slate-805 dark:text-slate-200 font-semibold">{{ formatLocalDate(activeBooking.booking_date) }}</p>
                            <p class="text-[10px] text-slate-500 font-mono font-bold">{{ activeBooking.start_time.substring(0, 5) }} - {{ activeBooking.end_time.substring(0, 5) }} WIB</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-0.5">LOKASI</p>
                            <p class="text-xs text-slate-805 dark:text-slate-200 font-semibold">📍 {{ activeBooking.location }}</p>
                        </div>
                    </div>

                    <!-- Assigned Crews -->
                    <div>
                        <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">👥 STAF & KRU YANG DITUGASKAN</p>
                        <div class="flex flex-wrap gap-1.5" v-if="activeBooking.crews?.length > 0">
                            <span 
                                v-for="crew in activeBooking.crews" 
                                :key="crew.id"
                                class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-violet-50 dark:bg-violet-900/35 text-violet-700 dark:text-violet-300 border border-violet-100 dark:border-violet-900/50"
                            >
                                {{ crew.name }} ({{ crew.role.toUpperCase() }})
                            </span>
                        </div>
                        <p v-else class="text-xs text-slate-400 italic">Belum ada staf yang dialokasikan.</p>
                    </div>

                    <!-- Assigned Equipments -->
                    <div>
                        <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">🛠️ PERALATAN & PROPERTI DIALOKASIKAN</p>
                        <div class="flex flex-wrap gap-1.5" v-if="activeBooking.equipments?.length > 0">
                            <span 
                                v-for="eq in activeBooking.equipments" 
                                :key="eq.id"
                                class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700"
                            >
                                {{ eq.name }} ({{ eq.type.toUpperCase() }})
                            </span>
                        </div>
                        <p v-else class="text-xs text-slate-400 italic">Belum ada peralatan yang dialokasikan.</p>
                    </div>

                    <!-- Notes -->
                    <div v-if="activeBooking.notes">
                        <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-0.5">CATATAN KHUSUS</p>
                        <p class="text-xs text-slate-605 dark:text-slate-405 italic leading-relaxed">"{{ activeBooking.notes }}"</p>
                    </div>
                </div>

                <!-- Footer Action Buttons -->
                <div class="px-6 py-4 border-t border-slate-150 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/20 flex space-x-3">
                    <button 
                        @click="closeDetailModal"
                        class="flex-1 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-semibold rounded-lg transition-all cursor-pointer"
                    >
                        Tutup
                    </button>
                    <Link 
                        href="/admin/bookings"
                        class="flex-1 py-2 bg-violet-600 hover:bg-violet-700 text-white text-xs font-semibold rounded-lg shadow-sm hover:shadow transition-all text-center flex items-center justify-center cursor-pointer"
                    >
                        💼 Kelola Sesi / Alokasi
                    </Link>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Custom mini-scrollbar style for calendar grid day cells */
.custom-scrollbar::-webkit-scrollbar {
    width: 3px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(139, 92, 246, 0.3);
    border-radius: 9px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(139, 92, 246, 0.6);
}
</style>
