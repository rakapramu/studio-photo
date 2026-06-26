<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, nextTick } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    crews: Array,
    bookings: Array,
});

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

// Filters state
const selectedCrewId = ref('all');
const selectedRole = ref('all');
const searchQuery = ref('');

// Format Currency
const formatIDR = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(value);
};

// Format LocalDate
const formatLocalDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};

// Compute all crew assignments
const assignments = computed(() => {
    const list = [];
    const now = new Date();
    // Get YYYY-MM-DD in local time
    const todayStr = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
    
    props.bookings.forEach(booking => {
        if (!booking.crews || booking.crews.length === 0) return;
        
        booking.crews.forEach(crew => {
            let status = 'upcoming'; // default
            
            if (booking.status === 'completed') {
                status = 'completed';
            } else if (booking.status === 'cancelled') {
                return; // ignore cancelled
            } else {
                const bDateStr = booking.booking_date; // "YYYY-MM-DD"
                
                if (bDateStr < todayStr) {
                    status = 'completed';
                } else if (bDateStr > todayStr) {
                    status = 'upcoming';
                } else {
                    // Booking is today
                    const startTime = booking.start_time; // "HH:MM:SS"
                    const endTime = booking.end_time; // "HH:MM:SS"
                    
                    const currentH = now.getHours();
                    const currentM = now.getMinutes();
                    const currentStr = `${String(currentH).padStart(2, '0')}:${String(currentM).padStart(2, '0')}:00`;
                    
                    if (currentStr >= startTime && currentStr <= endTime) {
                        status = 'on_duty';
                    } else if (currentStr > endTime) {
                        status = 'completed';
                    } else {
                        status = 'upcoming';
                    }
                }
            }
            
            list.push({
                id: `${booking.id}-${crew.id}`,
                booking_id: booking.id,
                booking_status: booking.status,
                client_name: booking.client_name,
                package_name: booking.package?.name || 'Paket Kustom',
                booking_date: booking.booking_date,
                start_time: booking.start_time,
                end_time: booking.end_time,
                location: booking.location,
                is_outdoor: booking.is_outdoor,
                travel_surcharge: booking.travel_surcharge,
                travel_distance: booking.travel_distance,
                location_latitude: booking.location_latitude,
                location_longitude: booking.location_longitude,
                crew: crew,
                status: status
            });
        });
    });
    return list;
});

// Filter assignments
const filteredAssignments = computed(() => {
    return assignments.value.filter(item => {
        const matchesCrew = selectedCrewId.value === 'all' || item.crew.id === parseInt(selectedCrewId.value);
        const matchesRole = selectedRole.value === 'all' || item.crew.role === selectedRole.value;
        const matchesSearch = !searchQuery.value.trim() || 
            item.client_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            item.package_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            item.location.toLowerCase().includes(searchQuery.value.toLowerCase());
            
        return matchesCrew && matchesRole && matchesSearch;
    });
});

// Columns setup
const columns = [
    { title: 'Akan Bertugas', status: 'upcoming', bgHeader: 'border-l-4 border-amber-500 bg-amber-500/5 dark:bg-amber-950/10', badgeColor: 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400' },
    { title: 'Sedang Bertugas', status: 'on_duty', bgHeader: 'border-l-4 border-violet-600 bg-violet-600/5 dark:bg-violet-950/10', badgeColor: 'bg-violet-100 text-violet-850 dark:bg-violet-955/40 dark:text-violet-400', isLive: true },
    { title: 'Selesai Bertugas', status: 'completed', bgHeader: 'border-l-4 border-emerald-500 bg-emerald-500/5 dark:bg-emerald-950/10', badgeColor: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400' },
];

const getColumnAssignments = (status) => {
    return filteredAssignments.value.filter(item => item.status === status);
};

// HTML5 Drag and Drop State
const draggingCardId = ref(null);
const activeDragColumn = ref(null);

const handleDragStart = (e, card) => {
    draggingCardId.value = card.id;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', JSON.stringify({
        id: card.id,
        booking_id: card.booking_id,
        booking_status: card.booking_status
    }));
};

const handleDragEnd = () => {
    draggingCardId.value = null;
    activeDragColumn.value = null;
};

const handleDragEnter = (e, columnStatus) => {
    activeDragColumn.value = columnStatus;
};

const handleDragOver = (e) => {
    e.preventDefault();
};

const handleDragLeave = () => {
    // only reset if leaving the column layout
};

// Custom Alert / Confirm Modal State
const isCustomModalOpen = ref(false);
const customModalType = ref('confirm'); // 'confirm' or 'alert'
const customModalTitle = ref('');
const customModalMessage = ref('');
const customModalAction = ref(null);

const executeCustomAction = () => {
    if (customModalAction.value) {
        customModalAction.value();
    }
};

const showWarningAlert = (message) => {
    customModalType.value = 'alert';
    customModalTitle.value = 'Tindakan Dibatasi';
    customModalMessage.value = message;
    customModalAction.value = null;
    isCustomModalOpen.value = true;
};

const handleDrop = (e, columnStatus) => {
    e.preventDefault();
    activeDragColumn.value = null;
    try {
        const data = JSON.parse(e.dataTransfer.getData('text/plain'));
        
        // Dragging to "completed" updates booking status
        if (columnStatus === 'completed' && data.booking_status !== 'completed') {
            triggerMarkAsCompleted(data.booking_id);
        } else if (columnStatus !== 'completed') {
            // Schedulers are automated. Cannot drag back to upcoming/on-duty
            showWarningAlert('Status "Akan Bertugas" dan "Sedang Bertugas" dikalkulasi otomatis berdasarkan waktu sesi jadwal. Anda hanya dapat menandai selesai.');
        }
    } catch (err) {
        console.error('Drop error:', err);
    }
};

const triggerMarkAsCompleted = (bookingId) => {
    customModalType.value = 'confirm';
    customModalTitle.value = 'Selesaikan Sesi Foto';
    customModalMessage.value = 'Apakah Anda yakin ingin menyelesaikan sesi pemesanan ini? Status reservasi akan diubah menjadi COMPLETED, kru selesai bertugas, dan status pembayaran ditandai lunas.';
    customModalAction.value = () => {
        router.patch(`/admin/bookings/${bookingId}/status`, {
            status: 'completed'
        }, {
            preserveScroll: true,
            onSuccess: () => {
                isCustomModalOpen.value = false;
            }
        });
    };
    isCustomModalOpen.value = true;
};

// Booking Detail Modal State
const isDetailModalOpen = ref(false);
const activeBooking = ref(null);
let kanbanMap = null;
let kStudioMarker = null;
let kDestMarker = null;
let kRoutePolyline = null;

const openDetailModal = async (bookingId) => {
    const booking = props.bookings.find(b => b.id === bookingId);
    if (!booking) return;
    
    activeBooking.value = booking;
    isDetailModalOpen.value = true;
    
    if (booking.is_outdoor && booking.location_latitude && booking.location_longitude) {
        await nextTick();
        initKanbanMap();
    }
};

const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    activeBooking.value = null;
    if (kanbanMap) {
        kanbanMap.remove();
        kanbanMap = null;
    }
    kStudioMarker = null;
    kDestMarker = null;
    kRoutePolyline = null;
};

const initKanbanMap = () => {
    if (!activeBooking.value) return;
    const booking = activeBooking.value;
    
    const pageObj = usePage();
    const studioLat = parseFloat(pageObj.props.settings?.studio_latitude || -6.597629);
    const studioLng = parseFloat(pageObj.props.settings?.studio_longitude || 106.799568);
    const studioName = pageObj.props.settings?.studio_name || 'Studio Photo Raka';
    const studioCoords = [studioLat, studioLng];
    
    const destLat = parseFloat(booking.location_latitude);
    const destLng = parseFloat(booking.location_longitude);
    
    if (!destLat || !destLng) return;
    
    kanbanMap = L.map('kanban-detail-map').setView(studioCoords, 12);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(kanbanMap);
    
    const studioIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-violet.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    const destIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    kStudioMarker = L.marker(studioCoords, { icon: studioIcon }).addTo(kanbanMap)
        .bindPopup(`<b>${studioName}</b><br>Titik Keberangkatan`);
        
    kDestMarker = L.marker([destLat, destLng], { icon: destIcon }).addTo(kanbanMap)
        .bindPopup(`<b>Lokasi Foto Klien</b><br>${booking.location}`).openPopup();
        
    drawKanbanRoute(studioCoords, [destLat, destLng]);
};

const drawKanbanRoute = async (start, end) => {
    try {
        const url = `https://router.project-osrm.org/route/v1/driving/${start[1]},${start[0]};${end[1]},${end[0]}?overview=full&geometries=geojson`;
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
            const route = data.routes[0];
            const coordinates = route.geometry.coordinates.map(c => [c[1], c[0]]);
            
            kRoutePolyline = L.polyline(coordinates, { color: '#6366f1', weight: 5, opacity: 0.7 }).addTo(kanbanMap);
            
            const bounds = L.latLngBounds([start, end]);
            kanbanMap.fitBounds(bounds, { padding: [40, 40] });
        } else {
            kRoutePolyline = L.polyline([start, end], { color: '#ef4444', weight: 4, dashArray: '5, 10' }).addTo(kanbanMap);
        }
    } catch (e) {
        console.error('Error drawing kanban route:', e);
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Kanban Jadwal Kru" />

        <template #title>
            Kanban Penugasan Jadwal Kru
        </template>

        <!-- Floating Notifications -->
        <div class="fixed top-6 right-6 z-50 space-y-3">
            <div 
                v-if="showSuccessNotification && flashSuccess"
                class="flex items-center p-4 bg-emerald-50 dark:bg-emerald-955/80 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-lg shadow-lg max-w-sm transition-all"
            >
                <span class="text-lg mr-3">✅</span>
                <div class="text-sm font-medium mr-8">{{ flashSuccess }}</div>
                <button @click="showSuccessNotification = false" class="text-emerald-500 hover:text-emerald-750 ml-auto">✕</button>
            </div>

            <div 
                v-if="showErrorNotification && flashError"
                class="flex items-center p-4 bg-red-50 dark:bg-red-955/80 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 rounded-lg shadow-lg max-w-sm transition-all"
            >
                <span class="text-lg mr-3">⚠️</span>
                <div class="text-sm font-medium mr-8">{{ flashError }}</div>
                <button @click="showErrorNotification = false" class="text-red-500 hover:text-red-750 ml-auto">✕</button>
            </div>
        </div>

        <!-- Toolbar Description -->
        <div class="mb-6">
            <h3 class="text-base text-slate-500 dark:text-slate-400">
                Tinjau, alokasikan, dan pantau status kru lapangan yang sedang aktif bertugas hari ini atau dijadwalkan bertugas pada sesi mendatang.
            </h3>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm p-4 mb-6 flex flex-col md:flex-row gap-4 items-center justify-between">
            <div class="flex flex-wrap gap-3 items-center w-full md:w-auto">
                
                <!-- Crew Filter -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">STAF / KRU</label>
                    <select 
                        v-model="selectedCrewId"
                        class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-3 py-1.5 text-xs text-slate-700 dark:text-slate-200 font-semibold focus:ring-1 focus:ring-violet-500 focus:border-violet-600"
                    >
                        <option value="all">👥 Semua Staf / Kru</option>
                        <option v-for="c in crews" :key="c.id" :value="c.id">{{ c.name }} ({{ c.role.toUpperCase() }})</option>
                    </select>
                </div>

                <!-- Role Filter -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">PERAN</label>
                    <select 
                        v-model="selectedRole"
                        class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-3 py-1.5 text-xs text-slate-700 dark:text-slate-200 font-semibold focus:ring-1 focus:ring-violet-500 focus:border-violet-600"
                    >
                        <option value="all">💼 Semua Peran</option>
                        <option value="fotografer">📸 Fotografer</option>
                        <option value="videografer">🎥 Videografer</option>
                        <option value="editor">🖥️ Editor</option>
                        <option value="mua">💄 MUA</option>
                        <option value="asisten">🙋 Asisten</option>
                    </select>
                </div>
            </div>

            <!-- Search input -->
            <div class="w-full md:w-64">
                <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">CARI RESERVASI</label>
                <input 
                    type="text" 
                    v-model="searchQuery"
                    placeholder="Nama klien, paket, atau lokasi..."
                    class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-3 py-1.5 text-xs text-slate-700 dark:text-slate-200 focus:ring-1 focus:ring-violet-500 focus:border-violet-600"
                />
            </div>
        </div>

        <!-- Kanban Board Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <div 
                v-for="col in columns" 
                :key="col.status"
                @dragover="handleDragOver"
                @drop="handleDrop($event, col.status)"
                @dragenter="handleDragEnter($event, col.status)"
                @dragleave="handleDragLeave"
                class="bg-slate-100/50 dark:bg-slate-900/30 border-2 border-dashed border-transparent rounded-2xl p-4 flex flex-col min-h-[600px] transition-colors"
                :class="[
                    activeDragColumn === col.status 
                        ? 'border-violet-500 bg-violet-50/20 dark:bg-violet-955/10' 
                        : 'border-transparent'
                ]"
            >
                <!-- Column Header -->
                <div class="flex items-center justify-between mb-4 p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm" :class="col.bgHeader">
                    <div class="flex items-center space-x-2">
                        <span class="text-xs font-bold text-slate-850 dark:text-slate-100 tracking-wide uppercase">{{ col.title }}</span>
                        <!-- Heartbeat animation for Live indicator -->
                        <span v-if="col.isLive" class="flex h-2 w-2 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-violet-600 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-violet-600"></span>
                        </span>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full font-extrabold" :class="col.badgeColor">
                        {{ getColumnAssignments(col.status).length }}
                    </span>
                </div>

                <!-- Cards list -->
                <div class="flex-1 space-y-3 overflow-y-auto max-h-[70vh] custom-scrollbar">
                    <div 
                        v-for="card in getColumnAssignments(col.status)"
                        :key="card.id"
                        draggable="true"
                        @dragstart="handleDragStart($event, card)"
                        @dragend="handleDragEnd"
                        @click="openDetailModal(card.booking_id)"
                        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm hover:shadow-md transition-all hover:scale-[1.01] cursor-pointer flex flex-col justify-between group relative"
                        :class="[
                            draggingCardId === card.id ? 'opacity-30' : '',
                            card.is_outdoor ? 'hover:border-violet-400' : 'hover:border-slate-400'
                        ]"
                    >
                        <div>
                            <!-- Header: Crew Info -->
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate max-w-[70%]">👤 {{ card.crew.name }}</span>
                                <span 
                                    class="text-[9px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wide"
                                    :class="{
                                        'bg-blue-100 text-blue-800 dark:bg-blue-950/40 dark:text-blue-400': card.crew.role === 'fotografer',
                                        'bg-purple-100 text-purple-800 dark:bg-purple-950/40 dark:text-purple-400': card.crew.role === 'videografer',
                                        'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400': card.crew.role === 'mua',
                                        'bg-teal-100 text-teal-800 dark:bg-teal-955/40 dark:text-teal-400': card.crew.role === 'editor',
                                        'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-400': card.crew.role === 'asisten',
                                    }"
                                >
                                    {{ card.crew.role }}
                                </span>
                            </div>

                            <!-- Session info -->
                            <div class="space-y-1.5 border-t border-slate-100 dark:border-slate-800/80 pt-2 mb-2">
                                <p class="text-xs font-extrabold text-slate-800 dark:text-slate-200 truncate">{{ card.client_name }}</p>
                                <p class="text-[10px] text-slate-400 font-semibold truncate">{{ card.package_name }}</p>
                                
                                <div class="text-[10px] text-slate-500 flex items-center space-x-1">
                                    <span>📅</span>
                                    <span class="font-medium">{{ new Date(card.booking_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}</span>
                                    <span>•</span>
                                    <span class="font-mono">{{ card.start_time.substring(0, 5) }}-{{ card.end_time.substring(0, 5) }}</span>
                                </div>

                                <p class="text-[10px] text-slate-500 truncate" :title="card.location">📍 {{ card.location }}</p>
                            </div>
                        </div>

                        <!-- Footer badges and fast actions -->
                        <div class="flex items-center justify-between border-t border-slate-100 dark:border-slate-800/80 pt-2.5 mt-1.5">
                            <span 
                                v-if="card.is_outdoor" 
                                class="inline-flex px-1.5 py-0.5 rounded bg-violet-100 dark:bg-violet-950/40 text-[9px] font-bold text-violet-600 dark:text-violet-400"
                                :title="`Surcharge: ${formatIDR(card.travel_surcharge)}`"
                            >
                                🌳 OUTDOOR
                            </span>
                            <span 
                                v-else 
                                class="inline-flex px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-[9px] font-bold text-slate-550"
                            >
                                🏠 INDOOR
                            </span>

                            <button 
                                v-if="card.status !== 'completed'"
                                @click.stop="triggerMarkAsCompleted(card.booking_id)"
                                class="text-[9px] px-2 py-0.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:hover:bg-emerald-950/70 dark:text-emerald-400 font-bold rounded transition-colors cursor-pointer"
                                title="Tandai Sesi Selesai"
                            >
                                ✓ Selesai
                            </button>
                            <span v-else class="text-[9px] text-emerald-600 font-bold">✅ Selesai</span>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div 
                    v-if="getColumnAssignments(col.status).length === 0" 
                    class="flex-1 flex flex-col items-center justify-center border border-dashed border-slate-200 dark:border-slate-800 rounded-2xl py-12 text-slate-400 bg-white/20 dark:bg-slate-900/10"
                >
                    <p class="text-3xl mb-1">📭</p>
                    <p class="text-xs">Tidak ada jadwal.</p>
                </div>
            </div>
        </div>

        <!-- Interactive Booking Detail Modal -->
        <div v-if="isDetailModalOpen && activeBooking" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/60 dark:bg-slate-950/80 backdrop-blur-sm" @click="closeDetailModal"></div>

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
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 font-sans">Detail Jadwal & Sesi Foto</h3>
                    </div>
                </div>

                <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto custom-scrollbar">
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
                            <p class="text-xs text-slate-805 dark:text-slate-205 font-semibold">{{ formatLocalDate(activeBooking.booking_date) }}</p>
                            <p class="text-[10px] text-slate-500 font-mono font-bold">{{ activeBooking.start_time.substring(0, 5) }} - {{ activeBooking.end_time.substring(0, 5) }} WIB</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-0.5">LOKASI</p>
                            <p class="text-xs text-slate-805 dark:text-slate-205 font-semibold">📍 {{ activeBooking.location }}</p>
                        </div>
                    </div>

                    <!-- Assigned Crews -->
                    <div>
                        <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1.5">👥 STAF & KRU PENUH YANG DITUGASKAN</p>
                        <div class="flex flex-wrap gap-1.5" v-if="activeBooking.crews?.length > 0">
                            <span 
                                v-for="crew in activeBooking.crews" 
                                :key="crew.id"
                                class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-violet-50 dark:bg-violet-900/35 text-violet-750 dark:text-violet-300 border border-violet-100 dark:border-violet-900/50"
                            >
                                {{ crew.name }} ({{ crew.role.toUpperCase() }})
                            </span>
                        </div>
                        <p v-else class="text-xs text-slate-400 italic">Belum ada staf yang dialokasikan.</p>
                    </div>

                    <!-- Outdoor Details & Map -->
                    <div v-if="activeBooking.is_outdoor" class="space-y-3 pt-2 border-t border-slate-150 dark:border-slate-800/80">
                        <div class="p-3 bg-violet-500/5 dark:bg-violet-950/20 border border-violet-500/10 dark:border-violet-800/20 rounded-xl text-xs space-y-1.5">
                            <p class="font-bold text-slate-805 dark:text-slate-200 border-b border-slate-150 dark:border-slate-800/80 pb-1 mb-1.5 uppercase tracking-wide">🌳 LAYANAN OUTDOOR & SURCHARGE PERJALANAN</p>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Jarak Tempuh</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ activeBooking.travel_distance }} KM</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Total Surcharge</span>
                                <span class="font-bold text-violet-600 dark:text-violet-400">{{ formatIDR(activeBooking.travel_surcharge) }}</span>
                            </div>
                        </div>

                        <!-- Map Container -->
                        <div v-if="activeBooking.location_latitude">
                            <div id="kanban-detail-map" class="h-40 rounded-xl border border-slate-200 dark:border-slate-800 relative z-10"></div>
                        </div>
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
                </div>
            </div>
        </div>

        <!-- Custom Alert/Confirm Modal -->
        <div v-if="isCustomModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-950/60 dark:bg-slate-950/80 backdrop-blur-sm" @click="isCustomModalOpen = false"></div>

            <!-- Modal Panel -->
            <div class="bg-white dark:bg-slate-900 w-full max-w-[400px] rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl z-10 overflow-hidden flex flex-col justify-between transform transition-all animate-in fade-in zoom-in-95 duration-200 relative animate-in fade-in zoom-in-95 duration-200">
                <!-- Close Button -->
                <button @click="isCustomModalOpen = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm focus:outline-none transition-colors">✕</button>
                
                <div class="p-6 text-center">
                    <!-- Icon Area -->
                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-2xl mx-auto mb-4 shadow-sm"
                        :class="{
                            'bg-amber-50 dark:bg-amber-955/40 text-amber-500': customModalType === 'alert',
                            'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500': customModalType === 'confirm'
                        }"
                    >
                        <span v-if="customModalType === 'alert'">⚠️</span>
                        <span v-else-if="customModalType === 'confirm'">✓</span>
                    </div>

                    <h3 class="text-base font-bold text-slate-850 dark:text-slate-100 mb-2 font-sans">
                        {{ customModalTitle }}
                    </h3>
                    
                    <p class="text-sm text-slate-550 dark:text-slate-400 mb-6 leading-relaxed">
                        {{ customModalMessage }}
                    </p>

                    <!-- Buttons -->
                    <div class="flex space-x-3">
                        <button 
                            type="button" 
                            @click="isCustomModalOpen = false"
                            class="flex-1 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm font-semibold rounded-lg transition-all cursor-pointer"
                        >
                            {{ customModalType === 'confirm' ? 'Batal' : 'Tutup' }}
                        </button>
                        <button 
                            v-if="customModalType === 'confirm'"
                            type="button" 
                            @click="executeCustomAction"
                            class="flex-1 py-2 text-white text-sm font-semibold rounded-lg shadow-sm transition-all cursor-pointer bg-emerald-600 hover:bg-emerald-700"
                        >
                            Ya, Lanjutkan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Custom Scrollbar for Kanban cards list */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(139, 92, 246, 0.2);
    border-radius: 9px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(139, 92, 246, 0.4);
}
</style>
