<script setup>
import { Head, Link, router, usePage, useForm } from "@inertiajs/vue3";
import { ref, computed, watch, nextTick } from "vue";
import AdminLayout from "../../../Layouts/AdminLayout.vue";
import Pagination from "../../../Components/Pagination.vue";

const props = defineProps({
    bookings: Array,
    allCrews: Array,
    allEquipments: Array,
});

// Pagination state
const currentPage = ref(1);
const itemsPerPage = 5;

const paginatedBookings = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return props.bookings.slice(start, end);
});

// Notifications props handling
const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const showSuccessNotification = ref(false);
const showErrorNotification = ref(false);

watch(
    flashSuccess,
    (newVal) => {
        if (newVal) {
            showSuccessNotification.value = true;
            setTimeout(() => (showSuccessNotification.value = false), 4000);
        }
    },
    { immediate: true },
);

watch(
    flashError,
    (newVal) => {
        if (newVal) {
            showErrorNotification.value = true;
            setTimeout(() => (showErrorNotification.value = false), 4000);
        }
    },
    { immediate: true },
);

// Format Currency
const formatIDR = (value) => {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(value);
};

// Modal state for custom confirmation
const isConfirmModalOpen = ref(false);
const confirmModalType = ref("status"); // 'status' or 'delete'
const targetBookingObj = ref(null);
const targetStatusVal = ref(null);

// Trigger Modal open
const openConfirmModal = (booking, type, status = null) => {
    targetBookingObj.value = booking;
    confirmModalType.value = type;
    targetStatusVal.value = status;
    isConfirmModalOpen.value = true;
};

// Close Modal
const closeConfirmModal = () => {
    isConfirmModalOpen.value = false;
    targetBookingObj.value = null;
    targetStatusVal.value = null;
};

// Execute action
const executeConfirmedAction = () => {
    if (confirmModalType.value === "status") {
        router.patch(
            `/admin/bookings/${targetBookingObj.value.id}/status`,
            {
                status: targetStatusVal.value,
            },
            {
                preserveScroll: true,
                onSuccess: () => closeConfirmModal(),
            },
        );
    } else if (confirmModalType.value === "delete") {
        router.delete(`/admin/bookings/${targetBookingObj.value.id}`, {
            preserveScroll: true,
            onSuccess: () => closeConfirmModal(),
        });
    }
};

// Assignment modal state & form
const isAssignModalOpen = ref(false);
const assignTargetBooking = ref(null);

const assignForm = useForm({
    crew_ids: [],
    equipment_ids: [],
});

const openAssignModal = (booking) => {
    assignTargetBooking.value = booking;
    assignForm.crew_ids = booking.crews?.map((c) => c.id) || [];
    assignForm.equipment_ids = booking.equipments?.map((e) => e.id) || [];
    isAssignModalOpen.value = true;
};

const closeAssignModal = () => {
    isAssignModalOpen.value = false;
    assignTargetBooking.value = null;
    assignForm.reset();
};

const submitAssignment = () => {
    assignForm.post(`/admin/bookings/${assignTargetBooking.value.id}/assign`, {
        preserveScroll: true,
        onSuccess: () => closeAssignModal(),
    });
};

// Check if a crew is already assigned to an overlapping booking
const isCrewBusy = (crewId) => {
    if (!assignTargetBooking.value) return false;
    const target = assignTargetBooking.value;

    return props.bookings.some((b) => {
        if (b.id === target.id) return false; // Ignore current booking
        if (b.booking_date !== target.booking_date) return false; // Different date is safe
        if (b.status === "cancelled") return false; // Cancelled is safe

        // Overlap check
        const overlap =
            b.start_time < target.end_time && b.end_time > target.start_time;
        if (!overlap) return false;

        return b.crews?.some((c) => c.id === crewId);
    });
};

// Check if an equipment is already allocated to an overlapping booking
const isEquipmentBusy = (equipmentId) => {
    if (!assignTargetBooking.value) return false;
    const target = assignTargetBooking.value;

    return props.bookings.some((b) => {
        if (b.id === target.id) return false;
        if (b.booking_date !== target.booking_date) return false;
        if (b.status === "cancelled") return false;

        // Overlap check
        const overlap =
            b.start_time < target.end_time && b.end_time > target.start_time;
        if (!overlap) return false;

        return b.equipments?.some((e) => e.id === equipmentId);
    });
};

// Admin Map Modal State & Logic
const isMapModalOpen = ref(false);
const mapTargetBooking = ref(null);
let adminMap = null;
let adminStudioMarker = null;
let adminDestMarker = null;
let adminRoutePolyline = null;

const openMapModal = async (booking) => {
    mapTargetBooking.value = booking;
    isMapModalOpen.value = true;

    await nextTick();
    initAdminMap();
};

const closeMapModal = () => {
    isMapModalOpen.value = false;
    mapTargetBooking.value = null;
    if (adminMap) {
        adminMap.remove();
        adminMap = null;
    }
    adminStudioMarker = null;
    adminDestMarker = null;
    adminRoutePolyline = null;
};

const initAdminMap = () => {
    if (!mapTargetBooking.value) return;
    const booking = mapTargetBooking.value;

    // Default studio location
    const pageObj = usePage();
    const studioLat = parseFloat(pageObj.props.settings?.studio_latitude || -6.597629);
    const studioLng = parseFloat(pageObj.props.settings?.studio_longitude || 106.799568);
    const studioName = pageObj.props.settings?.studio_name || 'Studio Photo Raka';
    const studioCoords = [studioLat, studioLng];

    const destLat = parseFloat(booking.location_latitude);
    const destLng = parseFloat(booking.location_longitude);

    if (!destLat || !destLng) return;

    // Create map
    adminMap = L.map("admin-map").setView(studioCoords, 12);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(adminMap);

    // Studio Icon (Violet)
    const studioIcon = L.icon({
        iconUrl:
            "https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-violet.png",
        shadowUrl:
            "https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png",
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41],
    });

    // Destination Icon (Red)
    const destIcon = L.icon({
        iconUrl:
            "https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png",
        shadowUrl:
            "https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png",
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41],
    });

    // Add Markers
    adminStudioMarker = L.marker(studioCoords, { icon: studioIcon })
        .addTo(adminMap)
        .bindPopup(`<b>${studioName}</b><br>Titik Keberangkatan`);

    adminDestMarker = L.marker([destLat, destLng], { icon: destIcon })
        .addTo(adminMap)
        .bindPopup(`<b>Lokasi Foto Klien</b><br>${booking.location}`)
        .openPopup();

    // Calculate and draw route
    drawAdminRoute(studioCoords, [destLat, destLng]);
};

const drawAdminRoute = async (start, end) => {
    try {
        const url = `https://router.project-osrm.org/route/v1/driving/${start[1]},${start[0]};${end[1]},${end[0]}?overview=full&geometries=geojson`;
        const response = await fetch(url);
        const data = await response.json();

        if (data.code === "Ok" && data.routes && data.routes.length > 0) {
            const route = data.routes[0];
            const coordinates = route.geometry.coordinates.map((c) => [
                c[1],
                c[0],
            ]);

            adminRoutePolyline = L.polyline(coordinates, {
                color: "#6366f1",
                weight: 5,
                opacity: 0.7,
            }).addTo(adminMap);

            const bounds = L.latLngBounds([start, end]);
            adminMap.fitBounds(bounds, { padding: [40, 40] });
        } else {
            adminRoutePolyline = L.polyline([start, end], {
                color: "#ef4444",
                weight: 4,
                dashArray: "5, 10",
            }).addTo(adminMap);
        }
    } catch (e) {
        console.error("Error drawing admin map route:", e);
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Kelola Pemesanan" />

        <template #title> Manajemen Pemesanan & Sesi </template>

        <!-- Floating Notifications -->
        <div class="fixed top-6 right-6 z-50 space-y-3">
            <div
                v-if="showSuccessNotification && flashSuccess"
                class="flex items-center p-4 bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-lg shadow-lg max-w-sm transition-all"
            >
                <span class="text-lg mr-3">✅</span>
                <div class="text-sm font-medium mr-8">{{ flashSuccess }}</div>
                <button
                    @click="showSuccessNotification = false"
                    class="text-emerald-500 hover:text-emerald-700 ml-auto"
                >
                    ✕
                </button>
            </div>

            <div
                v-if="showErrorNotification && flashError"
                class="flex items-center p-4 bg-red-50 dark:bg-red-950/80 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 rounded-lg shadow-lg max-w-sm transition-all"
            >
                <span class="text-lg mr-3">⚠️</span>
                <div class="text-sm font-medium mr-8">{{ flashError }}</div>
                <button
                    @click="showErrorNotification = false"
                    class="text-red-500 hover:text-red-700 ml-auto"
                >
                    ✕
                </button>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="mb-6">
            <h3 class="text-base text-slate-500 dark:text-slate-400">
                Tinjau reservasi masuk, konfirmasi slot waktu sesi foto, dan
                perbarui alur kerja pascaproduksi.
            </h3>
        </div>

        <!-- Table Card -->
        <div
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm overflow-hidden transition-all duration-300"
        >
            <div class="overflow-x-auto">
                <table
                    class="w-full text-left text-sm border-collapse table-fixed"
                >
                    <thead>
                        <tr
                            class="bg-slate-50 dark:bg-slate-800/40 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800"
                        >
                            <th class="px-6 py-4 w-[20%]">Klien</th>
                            <th class="px-6 py-4 w-[18%]">Paket & Harga</th>
                            <th class="px-6 py-4 w-[18%]">Tanggal & Waktu</th>
                            <th class="px-6 py-4 w-[18%]">Lokasi & Notes</th>
                            <th class="px-6 py-4 w-[10%] text-center">
                                Status
                            </th>
                            <th class="px-6 py-4 w-[16%] text-right">
                                Tindakan
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-slate-150 dark:divide-slate-800/60"
                    >
                        <tr
                            v-for="booking in paginatedBookings"
                            :key="booking.id"
                            class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors"
                        >
                            <!-- Client Info -->
                            <td class="px-6 py-4">
                                <div class="min-w-0">
                                    <div class="flex items-center space-x-2">
                                        <p
                                            class="font-semibold text-slate-850 dark:text-slate-100 truncate"
                                            :title="booking.client_name"
                                        >
                                            {{ booking.client_name }}
                                        </p>
                                        <!-- Contract Badge -->
                                        <span
                                            v-if="booking.contract?.signed_at"
                                            class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400"
                                            title="SPK telah ditandatangani"
                                        >
                                            SPK Signed
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400"
                                            title="SPK belum ditandatangani"
                                        >
                                            SPK Draft
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-400 truncate">
                                        {{ booking.client_email }}
                                    </p>
                                    <p class="text-xs text-slate-500 font-mono">
                                        {{ booking.client_phone }}
                                    </p>
                                    <div
                                        class="flex items-center space-x-2 mt-1.5"
                                        v-if="
                                            booking.status === 'confirmed' ||
                                            booking.status === 'completed'
                                        "
                                    >
                                        <a
                                            :href="booking.contract_url"
                                            target="_blank"
                                            class="text-[10px] text-violet-600 hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300 font-bold tracking-wide"
                                            >SPK LINK ↗</a
                                        >
                                        <span
                                            class="text-slate-300 dark:text-slate-700 text-[10px]"
                                            >•</span
                                        >
                                        <a
                                            :href="booking.payment_url"
                                            target="_blank"
                                            class="text-[10px] text-violet-600 hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300 font-bold tracking-wide"
                                            >PORTAL BAYAR ↗</a
                                        >
                                    </div>
                                </div>
                            </td>

                            <!-- Package Info -->
                            <td class="px-6 py-4">
                                <div class="min-w-0">
                                    <p
                                        class="font-medium text-slate-800 dark:text-slate-200 truncate"
                                        :title="booking.package?.name"
                                    >
                                        {{
                                            booking.package?.name ||
                                            "Paket Kustom"
                                        }}
                                    </p>
                                    <p
                                        class="text-xs font-semibold text-violet-600 dark:text-violet-400 mt-0.5"
                                    >
                                        {{ formatIDR(booking.total_price) }}
                                    </p>
                                    <div
                                        v-if="booking.is_outdoor"
                                        class="text-[10px] text-violet-600 dark:text-violet-400 mt-1 space-y-0.5"
                                    >
                                        <span
                                            class="inline-flex px-1.5 py-0.5 rounded bg-violet-100 dark:bg-violet-950/40 text-[9px] font-bold"
                                            >🌳 OUTDOOR</span
                                        >
                                        <p class="text-slate-400">
                                            Surcharge:
                                            <span class="font-semibold">{{
                                                formatIDR(
                                                    booking.travel_surcharge,
                                                )
                                            }}</span>
                                            <span
                                                class="block text-[9px] text-slate-400 leading-tight"
                                            >
                                                (Dist:
                                                {{ booking.travel_distance }}km,
                                                Bensin:
                                                {{
                                                    formatIDR(
                                                        booking.fuel_cost,
                                                    )
                                                }}, Tol:
                                                {{
                                                    formatIDR(
                                                        booking.toll_cost,
                                                    )
                                                }}, Akom:
                                                {{
                                                    formatIDR(
                                                        booking.accommodation_cost,
                                                    )
                                                }})
                                            </span>
                                        </p>
                                    </div>
                                    <p
                                        class="text-[10px] font-medium text-slate-400 dark:text-slate-500 mt-1"
                                        v-if="
                                            parseFloat(booking.paid_amount) > 0
                                        "
                                    >
                                        Terbayar:
                                        <span
                                            class="text-emerald-600 dark:text-emerald-400 font-bold"
                                            >{{
                                                formatIDR(booking.paid_amount)
                                            }}</span
                                        >
                                    </p>
                                </div>
                            </td>

                            <!-- Date & Time -->
                            <td
                                class="px-6 py-4 text-slate-700 dark:text-slate-300"
                            >
                                <div class="min-w-0">
                                    <p class="font-medium">
                                        {{
                                            new Date(
                                                booking.booking_date,
                                            ).toLocaleDateString("id-ID", {
                                                day: "numeric",
                                                month: "short",
                                                year: "numeric",
                                            })
                                        }}
                                    </p>
                                    <p class="text-xs text-slate-400 font-mono">
                                        {{
                                            booking.start_time.substring(0, 5)
                                        }}
                                        -
                                        {{
                                            booking.end_time.substring(0, 5)
                                        }}
                                        WIB
                                    </p>
                                </div>
                            </td>

                            <!-- Location & Notes -->
                            <td
                                class="px-6 py-4 text-slate-600 dark:text-slate-400"
                            >
                                <div class="min-w-0">
                                    <p
                                        class="truncate"
                                        :title="booking.location"
                                    >
                                        📍 {{ booking.location }}
                                    </p>
                                    <div
                                        class="flex items-center space-x-2 mt-1"
                                        v-if="
                                            booking.is_outdoor &&
                                            booking.location_latitude
                                        "
                                    >
                                        <button
                                            @click="openMapModal(booking)"
                                            class="text-[10px] text-violet-600 hover:text-violet-750 dark:text-violet-400 dark:hover:text-violet-300 font-bold tracking-wide flex items-center space-x-1 cursor-pointer"
                                        >
                                            <span
                                                >🗺️ Rute & Peta Rincian ↗</span
                                            >
                                        </button>
                                    </div>
                                    <p
                                        class="text-xs truncate text-slate-400 italic"
                                        :title="booking.notes"
                                    >
                                        {{
                                            booking.notes ||
                                            "Tidak ada catatan."
                                        }}
                                    </p>
                                    <!-- Assigned Crews & Equipments -->
                                    <div
                                        class="mt-2 text-[10px] space-y-0.5 border-t border-slate-100 dark:border-slate-800/80 pt-1.5"
                                        v-if="
                                            booking.crews?.length > 0 ||
                                            booking.equipments?.length > 0
                                        "
                                    >
                                        <p
                                            class="text-slate-450 dark:text-slate-500 font-bold"
                                            v-if="booking.crews?.length > 0"
                                        >
                                            👥 Kru:
                                            <span
                                                class="text-slate-700 dark:text-slate-300 font-medium"
                                                >{{
                                                    booking.crews
                                                        .map((c) => c.name)
                                                        .join(", ")
                                                }}</span
                                            >
                                        </p>
                                        <p
                                            class="text-slate-450 dark:text-slate-500 font-bold"
                                            v-if="
                                                booking.equipments?.length > 0
                                            "
                                        >
                                            🛠️ Alat:
                                            <span
                                                class="text-slate-700 dark:text-slate-300 font-medium"
                                                >{{
                                                    booking.equipments
                                                        .map((e) => e.name)
                                                        .join(", ")
                                                }}</span
                                            >
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- Status Badge -->
                            <td class="px-6 py-4 text-center">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="{
                                        'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400':
                                            booking.status === 'pending',
                                        'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400':
                                            booking.status === 'confirmed',
                                        'bg-blue-100 text-blue-800 dark:bg-blue-950/40 dark:text-blue-400':
                                            booking.status === 'completed',
                                        'bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-400':
                                            booking.status === 'cancelled',
                                    }"
                                >
                                    {{ booking.status.toUpperCase() }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td
                                class="px-6 py-4 text-right space-x-1.5 whitespace-nowrap"
                            >
                                <!-- Confirm -->
                                <button
                                    v-if="booking.status === 'pending'"
                                    @click="
                                        openConfirmModal(
                                            booking,
                                            'status',
                                            'confirmed',
                                        )
                                    "
                                    class="px-2 py-0.5 bg-emerald-50 dark:bg-emerald-950/30 hover:bg-emerald-100 text-emerald-700 dark:text-emerald-400 text-xs font-semibold rounded transition-all"
                                    title="Konfirmasi Booking"
                                >
                                    ✓ Konfirmasi
                                </button>
                                <!-- Complete -->
                                <button
                                    v-if="booking.status === 'confirmed'"
                                    @click="
                                        openConfirmModal(
                                            booking,
                                            'status',
                                            'completed',
                                        )
                                    "
                                    class="px-2 py-0.5 bg-blue-50 dark:bg-blue-950/30 hover:bg-blue-100 text-blue-700 dark:text-blue-400 text-xs font-semibold rounded transition-all"
                                    title="Tandai Selesai"
                                >
                                    ✓ Selesai
                                </button>
                                <!-- Tugaskan -->
                                <button
                                    v-if="booking.status === 'confirmed'"
                                    @click="openAssignModal(booking)"
                                    class="px-2 py-0.5 bg-violet-50 dark:bg-violet-950/30 hover:bg-violet-100 text-violet-700 dark:text-violet-400 text-xs font-semibold rounded transition-all cursor-pointer"
                                    title="Tugaskan Kru & Alat"
                                >
                                    💼 Tugaskan
                                </button>
                                <!-- Galeri -->
                                <Link
                                    v-if="
                                        booking.status === 'confirmed' ||
                                        booking.status === 'completed'
                                    "
                                    :href="`/admin/bookings/${booking.id}/gallery`"
                                    class="inline-block px-2 py-0.5 bg-violet-50 dark:bg-violet-950/30 hover:bg-violet-100 text-violet-700 dark:text-violet-400 text-xs font-semibold rounded transition-all cursor-pointer"
                                    title="Kelola Galeri Foto Sesi"
                                >
                                    🖼️ Galeri
                                </Link>
                                <!-- Cancel -->
                                <button
                                    v-if="
                                        booking.status === 'pending' ||
                                        booking.status === 'confirmed'
                                    "
                                    @click="
                                        openConfirmModal(
                                            booking,
                                            'status',
                                            'cancelled',
                                        )
                                    "
                                    class="px-2 py-0.5 bg-red-50 dark:bg-red-950/20 hover:bg-red-100 text-red-600 dark:text-red-400 text-xs font-semibold rounded transition-all"
                                    title="Batalkan Booking"
                                >
                                    ✕ Batal
                                </button>
                                <!-- Delete -->
                                <button
                                    v-if="
                                        booking.status === 'cancelled' ||
                                        booking.status === 'completed'
                                    "
                                    @click="openConfirmModal(booking, 'delete')"
                                    class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded transition-all"
                                    title="Hapus Record"
                                >
                                    🗑️ Hapus
                                </button>
                            </td>
                        </tr>
                        <!-- Empty State -->
                        <tr v-if="bookings.length === 0">
                            <td
                                colspan="6"
                                class="px-6 py-12 text-center text-slate-400"
                            >
                                <p class="text-3xl mb-2">📅</p>
                                Belum ada sesi pemesanan klien yang terdaftar.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <Pagination
                :total-items="bookings.length"
                :items-per-page="itemsPerPage"
                v-model:current-page="currentPage"
            />
        </div>

        <!-- Custom Confirmation Modal -->
        <div
            v-if="isConfirmModalOpen && targetBookingObj"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <!-- Backdrop -->
            <div
                class="fixed inset-0 bg-slate-950/60 dark:bg-slate-950/80 backdrop-blur-sm"
                @click="closeConfirmModal"
            ></div>

            <!-- Modal Panel -->
            <div
                class="bg-white dark:bg-slate-900 w-full max-w-[400px] rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl z-10 overflow-hidden flex flex-col justify-between transform transition-all animate-in fade-in zoom-in-95 duration-200 relative"
            >
                <!-- Close Button -->
                <button
                    @click="closeConfirmModal"
                    class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm focus:outline-none transition-colors"
                >
                    ✕
                </button>
                <div class="p-6 text-center">
                    <div
                        class="w-12 h-12 rounded-full flex items-center justify-center text-2xl mx-auto mb-4 shadow-sm"
                        :class="{
                            'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500':
                                confirmModalType === 'status' &&
                                targetStatusVal === 'confirmed',
                            'bg-blue-50 dark:bg-blue-950/40 text-blue-500':
                                confirmModalType === 'status' &&
                                targetStatusVal === 'completed',
                            'bg-red-50 dark:bg-red-950/20 text-red-500':
                                (confirmModalType === 'status' &&
                                    targetStatusVal === 'cancelled') ||
                                confirmModalType === 'delete',
                        }"
                    >
                        <span
                            v-if="
                                confirmModalType === 'status' &&
                                targetStatusVal === 'confirmed'
                            "
                            >✓</span
                        >
                        <span
                            v-else-if="
                                confirmModalType === 'status' &&
                                targetStatusVal === 'completed'
                            "
                            >🏆</span
                        >
                        <span
                            v-else-if="
                                confirmModalType === 'status' &&
                                targetStatusVal === 'cancelled'
                            "
                            >✕</span
                        >
                        <span v-else-if="confirmModalType === 'delete'"
                            >⚠️</span
                        >
                    </div>

                    <h3
                        class="text-base font-bold text-slate-800 dark:text-slate-100 mb-2"
                    >
                        Konfirmasi Tindakan
                    </h3>

                    <p
                        class="text-sm text-slate-500 dark:text-slate-400 mb-6 leading-relaxed"
                    >
                        <span
                            v-if="
                                confirmModalType === 'status' &&
                                targetStatusVal === 'confirmed'
                            "
                        >
                            Apakah Anda yakin ingin
                            <strong>menyetujui</strong> pemesanan jadwal atas
                            nama
                            <strong>{{ targetBookingObj.client_name }}</strong
                            >? Sistem otomatis akan mengirimkan WhatsApp
                            konfirmasi.
                        </span>
                        <span
                            v-else-if="
                                confirmModalType === 'status' &&
                                targetStatusVal === 'completed'
                            "
                        >
                            Apakah Anda yakin ingin menandai sesi foto atas nama
                            <strong>{{ targetBookingObj.client_name }}</strong>
                            sebagai <strong>selesai</strong>? Status pembayaran
                            akan ditandai lunas.
                        </span>
                        <span
                            v-else-if="
                                confirmModalType === 'status' &&
                                targetStatusVal === 'cancelled'
                            "
                        >
                            Apakah Anda yakin ingin
                            <strong>membatalkan</strong> pemesanan jadwal atas
                            nama
                            <strong>{{ targetBookingObj.client_name }}</strong
                            >? Sistem otomatis akan mengirimkan WhatsApp
                            pembatalan.
                        </span>
                        <span v-else-if="confirmModalType === 'delete'">
                            Apakah Anda yakin ingin
                            <strong>menghapus</strong> reservasi jadwal atas
                            nama
                            <strong>{{ targetBookingObj.client_name }}</strong>
                            secara permanen? Tindakan ini tidak dapat
                            dibatalkan.
                        </span>
                    </p>

                    <!-- Buttons -->
                    <div class="flex space-x-3">
                        <button
                            type="button"
                            @click="closeConfirmModal"
                            class="flex-1 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm font-medium rounded-lg transition-all"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="executeConfirmedAction"
                            class="flex-1 py-2 text-white text-sm font-medium rounded-lg shadow-sm transition-all"
                            :class="{
                                'bg-emerald-600 hover:bg-emerald-700':
                                    confirmModalType === 'status' &&
                                    targetStatusVal === 'confirmed',
                                'bg-blue-600 hover:bg-blue-700':
                                    confirmModalType === 'status' &&
                                    targetStatusVal === 'completed',
                                'bg-red-600 hover:bg-red-700':
                                    (confirmModalType === 'status' &&
                                        targetStatusVal === 'cancelled') ||
                                    confirmModalType === 'delete',
                            }"
                        >
                            Ya, Lanjutkan
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Resource Assignment Modal -->
        <div
            v-if="isAssignModalOpen && assignTargetBooking"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div
                class="fixed inset-0 bg-slate-950/60 dark:bg-slate-950/80 backdrop-blur-sm"
                @click="closeAssignModal"
            ></div>

            <div
                class="bg-white dark:bg-slate-900 w-full max-w-[480px] rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl z-10 overflow-hidden flex flex-col transform transition-all animate-in fade-in zoom-in-95 duration-200"
            >
                <div
                    class="px-6 py-4 border-b border-slate-150 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-800/40"
                >
                    <h3
                        class="text-sm font-bold text-slate-850 dark:text-slate-100"
                    >
                        💼 Alokasi Kru & Alat Penunjang
                    </h3>
                    <button
                        @click="closeAssignModal"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm"
                    >
                        ✕
                    </button>
                </div>

                <form
                    @submit.prevent="submitAssignment"
                    class="p-6 space-y-5 overflow-y-auto max-h-[75vh]"
                >
                    <div
                        class="p-3 bg-slate-50 dark:bg-slate-950/50 border border-slate-150 dark:border-slate-850 rounded-xl text-xs text-slate-550 dark:text-slate-400 space-y-1"
                    >
                        <p class="font-bold text-slate-700 dark:text-slate-300">
                            Detail Sesi:
                        </p>
                        <p>
                            Klien:
                            <span
                                class="font-semibold text-slate-800 dark:text-slate-200"
                                >{{ assignTargetBooking.client_name }}</span
                            >
                        </p>
                        <p>
                            Paket:
                            <span
                                class="font-semibold text-slate-800 dark:text-slate-200"
                                >{{ assignTargetBooking.package?.name }}</span
                            >
                        </p>
                        <p>
                            Tipe Sesi:
                            <span
                                class="font-semibold"
                                :class="
                                    assignTargetBooking.is_outdoor
                                        ? 'text-violet-600 dark:text-violet-400'
                                        : 'text-slate-700 dark:text-slate-300'
                                "
                                >{{
                                    assignTargetBooking.is_outdoor
                                        ? "🌳 Outdoor (Luar Studio)"
                                        : "🏠 Indoor (Studio)"
                                }}</span
                            >
                        </p>
                        <p v-if="assignTargetBooking.is_outdoor">
                            Surcharge:
                            <span
                                class="font-semibold text-slate-800 dark:text-slate-200"
                                >{{
                                    formatIDR(
                                        assignTargetBooking.travel_surcharge,
                                    )
                                }}</span
                            >
                        </p>
                        <p>
                            Jadwal:
                            <span
                                class="font-semibold text-slate-800 dark:text-slate-200"
                                >{{
                                    new Date(
                                        assignTargetBooking.booking_date,
                                    ).toLocaleDateString("id-ID", {
                                        day: "numeric",
                                        month: "short",
                                        year: "numeric",
                                    })
                                }}
                                ({{
                                    assignTargetBooking.start_time.substring(
                                        0,
                                        5,
                                    )
                                }}
                                -
                                {{
                                    assignTargetBooking.end_time.substring(0, 5)
                                }}
                                WIB)</span
                            >
                        </p>
                    </div>

                    <!-- Crews Section -->
                    <div>
                        <h4
                            class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2"
                        >
                            PILIH STAF & KRU
                        </h4>
                        <div
                            class="max-h-40 overflow-y-auto border border-slate-150 dark:border-slate-800 rounded-xl p-3 bg-slate-50/50 dark:bg-slate-950/20 space-y-2"
                        >
                            <label
                                v-for="crew in allCrews"
                                :key="crew.id"
                                class="flex items-center justify-between text-xs p-2 rounded-lg transition-colors"
                                :class="
                                    isCrewBusy(crew.id) &&
                                    !assignForm.crew_ids.includes(crew.id)
                                        ? 'opacity-40 bg-slate-100/50 dark:bg-slate-800/10 cursor-not-allowed'
                                        : 'hover:bg-slate-100/50 dark:hover:bg-slate-850/30 cursor-pointer'
                                "
                            >
                                <div class="flex items-center space-x-2">
                                    <input
                                        type="checkbox"
                                        :value="crew.id"
                                        v-model="assignForm.crew_ids"
                                        :disabled="
                                            isCrewBusy(crew.id) &&
                                            !assignForm.crew_ids.includes(
                                                crew.id,
                                            )
                                        "
                                        class="rounded border-slate-300 text-violet-600 focus:ring-violet-500 w-4 h-4"
                                    />
                                    <span
                                        class="font-semibold text-slate-700 dark:text-slate-200"
                                        >{{ crew.name }}</span
                                    >
                                    <span class="text-slate-400 font-medium"
                                        >({{ crew.role.toUpperCase() }})</span
                                    >
                                </div>
                                <span
                                    v-if="
                                        isCrewBusy(crew.id) &&
                                        !assignForm.crew_ids.includes(crew.id)
                                    "
                                    class="text-[9px] font-bold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-400 px-1.5 py-0.5 rounded"
                                >
                                    ⚠️ BENTROK JADWAL
                                </span>
                            </label>
                            <p
                                v-if="allCrews.length === 0"
                                class="text-xs text-slate-400 italic text-center py-4"
                            >
                                Belum ada data kru aktif.
                            </p>
                        </div>
                    </div>

                    <!-- Equipments Section -->
                    <div>
                        <h4
                            class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2"
                        >
                            PILIH ALAT & PROPERTI
                        </h4>
                        <div
                            class="max-h-40 overflow-y-auto border border-slate-150 dark:border-slate-800 rounded-xl p-3 bg-slate-50/50 dark:bg-slate-950/20 space-y-2"
                        >
                            <label
                                v-for="eq in allEquipments"
                                :key="eq.id"
                                class="flex items-center justify-between text-xs p-2 rounded-lg transition-colors"
                                :class="
                                    isEquipmentBusy(eq.id) &&
                                    !assignForm.equipment_ids.includes(eq.id)
                                        ? 'opacity-40 bg-slate-100/50 dark:bg-slate-800/10 cursor-not-allowed'
                                        : 'hover:bg-slate-100/50 dark:hover:bg-slate-800/30 cursor-pointer'
                                "
                            >
                                <div class="flex items-center space-x-2">
                                    <input
                                        type="checkbox"
                                        :value="eq.id"
                                        v-model="assignForm.equipment_ids"
                                        :disabled="
                                            isEquipmentBusy(eq.id) &&
                                            !assignForm.equipment_ids.includes(
                                                eq.id,
                                            )
                                        "
                                        class="rounded border-slate-300 text-violet-600 focus:ring-violet-500 w-4 h-4"
                                    />
                                    <span
                                        class="font-semibold text-slate-700 dark:text-slate-200"
                                        >{{ eq.name }}</span
                                    >
                                    <span class="text-slate-400 font-medium"
                                        >({{ eq.type.toUpperCase() }})</span
                                    >
                                </div>
                                <span
                                    v-if="
                                        isEquipmentBusy(eq.id) &&
                                        !assignForm.equipment_ids.includes(
                                            eq.id,
                                        )
                                    "
                                    class="text-[9px] font-bold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-400 px-1.5 py-0.5 rounded"
                                >
                                    ⚠️ SEDANG DIPAKAI
                                </span>
                            </label>
                            <p
                                v-if="allEquipments.length === 0"
                                class="text-xs text-slate-450 italic text-center py-4"
                            >
                                Belum ada data alat aktif.
                            </p>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div
                        class="flex space-x-3 pt-4 border-t border-slate-100 dark:border-slate-800/80"
                    >
                        <button
                            type="button"
                            @click="closeAssignModal"
                            class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm font-medium rounded-lg transition-all cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="assignForm.processing"
                            class="flex-1 py-2.5 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow transition-all cursor-pointer"
                        >
                            {{
                                assignForm.processing
                                    ? "Menyimpan..."
                                    : "Simpan Alokasi"
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Location Map Modal -->
        <div
            v-if="isMapModalOpen && mapTargetBooking"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div
                class="fixed inset-0 bg-slate-950/60 dark:bg-slate-950/80 backdrop-blur-sm"
                @click="closeMapModal"
            ></div>

            <div
                class="bg-white dark:bg-slate-900 w-full max-w-[540px] rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl z-10 overflow-hidden flex flex-col transform transition-all animate-in fade-in zoom-in-95 duration-200"
            >
                <div
                    class="px-6 py-4 border-b border-slate-150 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-800/40"
                >
                    <h3
                        class="text-sm font-bold text-slate-850 dark:text-slate-100 flex items-center space-x-1"
                    >
                        <span>🗺️ Detail Surcharge & Rute Perjalanan</span>
                    </h3>
                    <button
                        @click="closeMapModal"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm"
                    >
                        ✕
                    </button>
                </div>

                <div class="p-6 space-y-4 overflow-y-auto max-h-[75vh]">
                    <!-- Information Header -->
                    <div
                        class="p-3 bg-slate-50 dark:bg-slate-950/50 border border-slate-150 dark:border-slate-850 rounded-xl text-xs space-y-1.5"
                    >
                        <div
                            class="grid grid-cols-2 gap-2 text-slate-650 dark:text-slate-400"
                        >
                            <div>
                                Klien:
                                <span
                                    class="font-bold text-slate-800 dark:text-slate-200"
                                    >{{ mapTargetBooking.client_name }}</span
                                >
                            </div>
                            <div>
                                Tanggal Sesi:
                                <span
                                    class="font-bold text-slate-800 dark:text-slate-200"
                                    >{{
                                        new Date(
                                            mapTargetBooking.booking_date,
                                        ).toLocaleDateString("id-ID", {
                                            day: "numeric",
                                            month: "short",
                                            year: "numeric",
                                        })
                                    }}</span
                                >
                            </div>
                            <div class="col-span-2">
                                Lokasi Pemotretan:
                                <span
                                    class="font-bold text-slate-800 dark:text-slate-200"
                                    >📍 {{ mapTargetBooking.location }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Map Container -->
                    <div>
                        <div
                            id="admin-map"
                            class="h-60 rounded-xl border border-slate-200 dark:border-slate-800 relative z-10"
                        ></div>
                        <p class="text-[10px] text-slate-450 mt-1">
                            🔴 Pin Merah: Lokasi Sesi | 🟣 Pin Ungu: Studio
                        </p>
                    </div>

                    <!-- Surcharge Cost Breakdown Grid -->
                    <div
                        class="p-4 rounded-xl bg-violet-500/5 dark:bg-violet-950/20 border border-violet-500/10 dark:border-violet-800/20 text-xs text-slate-650 dark:text-slate-400 space-y-2"
                    >
                        <p
                            class="font-bold text-slate-800 dark:text-slate-200 border-b border-slate-100 dark:border-slate-800/80 pb-1.5 mb-1.5 uppercase tracking-wide"
                        >
                            Rincian Perhitungan Transportasi & Akomodasi
                        </p>
                        <div class="flex justify-between">
                            <span>Jarak Tempuh Rute (Satu Arah)</span>
                            <span
                                class="font-semibold text-slate-800 dark:text-slate-200"
                                >{{ mapTargetBooking.travel_distance }} KM</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span
                                >Bensin (Pulang Pergi:
                                {{
                                    mapTargetBooking.travel_distance * 2
                                }}
                                KM)</span
                            >
                            <span
                                class="font-semibold text-slate-800 dark:text-slate-200"
                                >{{
                                    formatIDR(mapTargetBooking.fuel_cost)
                                }}</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span>Biaya Tol</span>
                            <span
                                class="font-semibold text-slate-800 dark:text-slate-200"
                                >{{
                                    formatIDR(mapTargetBooking.toll_cost)
                                }}</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span>Biaya Akomodasi Staf/Kru (Overnight)</span>
                            <span
                                class="font-semibold text-slate-800 dark:text-slate-200"
                                >{{
                                    formatIDR(
                                        mapTargetBooking.accommodation_cost,
                                    )
                                }}</span
                            >
                        </div>
                        <div
                            class="flex justify-between border-t border-dashed border-slate-200 dark:border-slate-800 pt-2 font-extrabold text-slate-900 dark:text-white text-sm"
                        >
                            <span>Total Surcharge Perjalanan</span>
                            <span
                                class="text-violet-600 dark:text-violet-400"
                                >{{
                                    formatIDR(mapTargetBooking.travel_surcharge)
                                }}</span
                            >
                        </div>
                    </div>
                </div>

                <div
                    class="px-6 py-4 border-t border-slate-150 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 flex justify-end"
                >
                    <button
                        type="button"
                        @click="closeMapModal"
                        class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-semibold rounded-lg transition-all cursor-pointer"
                    >
                        Tutup Detail
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
