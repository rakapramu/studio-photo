<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch, nextTick } from 'vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';

const props = defineProps({
    packages: Array,
    studioConfig: Object,
});

const form = useForm({
    package_id: '',
    client_name: '',
    client_email: '',
    client_phone: '',
    booking_date: '',
    start_time: '',
    location: '',
    notes: '',
    is_outdoor: false,
    travel_distance: 0,
    fuel_cost: 0,
    toll_cost: 0,
    accommodation_cost: 0,
    travel_surcharge: 0,
    is_overnight: false,
    location_latitude: null,
    location_longitude: null,
});

// Preset jam mulai pemotretan untuk kemudahan pilih slot waktu
const timeSlots = [
    '08:00', '09:00', '10:00', '11:00', '13:00', 
    '14:00', '15:00', '16:00', '17:00', '19:00', '20:00'
];

let map = null;
let studioMarker = null;
let destinationMarker = null;
let routePolyline = null;

const studioCoords = [
    props.studioConfig?.latitude || -6.597629,
    props.studioConfig?.longitude || 106.799568
];

const initMap = () => {
    if (map) {
        map.remove();
        map = null;
    }
    
    // Create map
    map = L.map('map').setView(studioCoords, 12);
    
    // Add OpenStreetMap layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    // Studio Icon (Violet marker)
    const studioIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-violet.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    // Add Studio Marker
    const studioName = props.studioConfig?.studio_name || 'Studio Photo Raka';
    studioMarker = L.marker(studioCoords, { icon: studioIcon }).addTo(map)
        .bindPopup(`<b>${studioName}</b><br>Titik Keberangkatan`).openPopup();
        
    // Click on map to place destination marker
    map.on('click', (e) => {
        setDestination(e.latlng.lat, e.latlng.lng);
    });
};

const setDestination = (lat, lng) => {
    if (!map) return;
    
    form.location_latitude = lat;
    form.location_longitude = lng;
    
    if (destinationMarker) {
        destinationMarker.setLatLng([lat, lng]);
    } else {
        const destIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
        destinationMarker = L.marker([lat, lng], { icon: destIcon }).addTo(map);
    }
    
    calculateRouteAndCosts(lat, lng);
};

const searchQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);

const searchLocation = async () => {
    if (!searchQuery.value) return;
    isSearching.value = true;
    searchResults.value = [];
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery.value)}&limit=5`);
        const data = await response.json();
        searchResults.value = data;
    } catch (e) {
        console.error('Error searching location:', e);
    } finally {
        isSearching.value = false;
    }
};

const selectSearchResult = (item) => {
    const lat = parseFloat(item.lat);
    const lng = parseFloat(item.lon);
    
    // Update location name input automatically
    form.location = item.display_name;
    
    // Center map and set destination
    if (map) {
        map.setView([lat, lng], 13);
    }
    setDestination(lat, lng);
    searchResults.value = [];
};

// Calculate route via OSRM
const calculateRouteAndCosts = async (lat, lng) => {
    try {
        const url = `https://router.project-osrm.org/route/v1/driving/${studioCoords[1]},${studioCoords[0]};${lng},${lat}?overview=full&geometries=geojson`;
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
            const route = data.routes[0];
            const distanceMeters = route.distance; // in meters
            const distanceKm = distanceMeters / 1000;
            form.travel_distance = Math.round(distanceKm * 100) / 100;
            
            // Draw route on map
            const coordinates = route.geometry.coordinates.map(c => [c[1], c[0]]); // GeoJSON is [lng, lat] so reverse it
            if (routePolyline) {
                routePolyline.setLatLngs(coordinates);
            } else {
                routePolyline = L.polyline(coordinates, { color: '#6366f1', weight: 5, opacity: 0.7 }).addTo(map);
            }
            
            // Fit bounds to show both markers
            const bounds = L.latLngBounds([studioCoords, [lat, lng]]);
            map.fitBounds(bounds, { padding: [40, 40] });
            
            // Update pricing
            updatePricingBreakdown();
        } else {
            console.warn('OSRM Route not found, falling back to Haversine straight line');
            // Straight-line fallback
            const straightDistance = calculateHaversine(studioCoords[0], studioCoords[1], lat, lng);
            form.travel_distance = Math.round(straightDistance * 100) / 100;
            
            if (routePolyline) {
                routePolyline.setLatLngs([studioCoords, [lat, lng]]);
            } else {
                routePolyline = L.polyline([studioCoords, [lat, lng]], { color: '#ef4444', weight: 4, dashArray: '5, 10' }).addTo(map);
            }
            updatePricingBreakdown();
        }
    } catch (err) {
        console.error('Error fetching OSRM routing:', err);
    }
};

const calculateHaversine = (lat1, lon1, lat2, lon2) => {
    const R = 6371; // km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
};

const updatePricingBreakdown = () => {
    const fuelRate = props.studioConfig?.fuel_cost_per_km || 2000;
    const accommodationRate = props.studioConfig?.accommodation_per_night || 500000;
    
    form.fuel_cost = form.travel_distance * 2 * fuelRate;
    form.accommodation_cost = form.is_overnight ? accommodationRate : 0;
    form.travel_surcharge = form.fuel_cost + form.toll_cost + form.accommodation_cost;
};

const selectedPackage = computed(() => {
    return props.packages.find(pkg => pkg.id === form.package_id) || null;
});

const packagePrice = computed(() => {
    return selectedPackage.value ? parseFloat(selectedPackage.value.price) : 0;
});

const totalPrice = computed(() => {
    return packagePrice.value + (form.is_outdoor ? form.travel_surcharge : 0);
});

// Format Currency
const formatIDR = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(value);
};

// Watch changes to is_outdoor to initialize map
watch(() => form.is_outdoor, async (newVal) => {
    if (newVal) {
        await nextTick();
        initMap();
    } else {
        // Clear all fields
        form.travel_distance = 0;
        form.fuel_cost = 0;
        form.toll_cost = 0;
        form.accommodation_cost = 0;
        form.travel_surcharge = 0;
        form.is_overnight = false;
        form.location_latitude = null;
        form.location_longitude = null;
        if (map) {
            map.remove();
            map = null;
        }
    }
});

// Watch is_overnight or toll_cost changes
watch(() => form.is_overnight, () => {
    updatePricingBreakdown();
});
watch(() => form.toll_cost, () => {
    updatePricingBreakdown();
});

const submit = () => {
    form.post('/booking', {
        onSuccess: () => form.reset(),
    });
};

// Dapatkan tanggal hari ini dalam format YYYY-MM-DD untuk batas minimum date picker
const todayDate = new Date().toISOString().split('T')[0];

onMounted(() => {
    // Auto-select package if passed in URL query
    const urlParams = new URLSearchParams(window.location.search);
    const packageParam = urlParams.get('package');
    if (packageParam) {
        const matchingPackage = props.packages.find(p => String(p.id) === String(packageParam));
        if (matchingPackage) {
            form.package_id = matchingPackage.id;
        }
    }
});
</script>

<template>
    <GuestLayout>
        <Head title="Booking Sesi Foto" />

        <div class="mb-6">
            <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100 text-center">Reservasi Jadwal Foto</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 text-center">Isi formulir di bawah untuk mengamankan slot pemotretan Anda</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Client Name -->
            <div>
                <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Nama Lengkap <span class="text-red-500">*</span>
                </label>
                <input 
                    id="name" 
                    type="text" 
                    v-model="form.client_name" 
                    required 
                    class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 dark:focus:ring-violet-400/50 dark:focus:border-violet-400 transition-all text-slate-900 dark:text-white"
                    placeholder="Contoh: Raka Krisnandi"
                />
                <span v-if="form.errors.client_name" class="text-xs text-red-500 dark:text-red-400 mt-1 block">
                    {{ form.errors.client_name }}
                </span>
            </div>

            <!-- Email & Phone -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                        Alamat Email <span class="text-red-500">*</span>
                    </label>
                    <input 
                        id="email" 
                        type="email" 
                        v-model="form.client_email" 
                        required 
                        class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 dark:focus:ring-violet-400/50 dark:focus:border-violet-400 transition-all text-slate-900 dark:text-white"
                        placeholder="raka@email.com"
                    />
                    <span v-if="form.errors.client_email" class="text-xs text-red-500 dark:text-red-400 mt-1 block">
                        {{ form.errors.client_email }}
                    </span>
                </div>

                <div>
                    <label for="phone" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                        No. WhatsApp / HP <span class="text-red-500">*</span>
                    </label>
                    <input 
                        id="phone" 
                        type="text" 
                        v-model="form.client_phone" 
                        required 
                        class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 dark:focus:ring-violet-400/50 dark:focus:border-violet-400 transition-all text-slate-900 dark:text-white"
                        placeholder="081234567890"
                    />
                    <span v-if="form.errors.client_phone" class="text-xs text-red-500 dark:text-red-400 mt-1 block">
                        {{ form.errors.client_phone }}
                    </span>
                </div>
            </div>

            <!-- Package Select -->
            <div>
                <label for="package" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Pilih Paket Foto <span class="text-red-500">*</span>
                </label>
                <select 
                    id="package" 
                    v-model="form.package_id" 
                    required
                    class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 dark:focus:ring-violet-400/50 dark:focus:border-violet-400 transition-all text-slate-900 dark:text-white"
                >
                    <option value="" disabled>-- Pilih Paket --</option>
                    <option 
                        v-for="pkg in packages" 
                        :key="pkg.id" 
                        :value="pkg.id"
                    >
                        {{ pkg.name }} - {{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(pkg.price) }}
                    </option>
                </select>
                <span v-if="form.errors.package_id" class="text-xs text-red-500 dark:text-red-400 mt-1 block">
                    {{ form.errors.package_id }}
                </span>
            </div>

            <!-- Date & Time Picker -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="date" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                        Pilih Tanggal <span class="text-red-500">*</span>
                    </label>
                    <input 
                        id="date" 
                        type="date" 
                        v-model="form.booking_date" 
                        required 
                        :min="todayDate"
                        class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 dark:focus:ring-violet-400/50 dark:focus:border-violet-400 transition-all text-slate-900 dark:text-white"
                    />
                    <span v-if="form.errors.booking_date" class="text-xs text-red-500 dark:text-red-400 mt-1 block">
                        {{ form.errors.booking_date }}
                    </span>
                </div>

                <div>
                    <label for="time" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                        Pilih Jam Mulai <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="time" 
                        v-model="form.start_time" 
                        required
                        class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 dark:focus:ring-violet-400/50 dark:focus:border-violet-400 transition-all text-slate-900 dark:text-white"
                    >
                        <option value="" disabled>-- Pilih Jam --</option>
                        <option 
                            v-for="time in timeSlots" 
                            :key="time" 
                            :value="time"
                        >
                            {{ time }} WIB
                        </option>
                    </select>
                    <span v-if="form.errors.start_time" class="text-xs text-red-500 dark:text-red-400 mt-1 block">
                        {{ form.errors.start_time }}
                    </span>
                </div>
            </div>

            <!-- Session Type & Location Selection -->
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                        Tipe Sesi Foto <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-4">
                        <button 
                            type="button"
                            @click="form.is_outdoor = false"
                            class="flex flex-col items-center justify-center p-3 border rounded-xl transition-all cursor-pointer"
                            :class="!form.is_outdoor 
                                ? 'border-violet-500 bg-violet-500/10 text-violet-600 dark:text-violet-400 font-semibold' 
                                : 'border-slate-200 dark:border-slate-800 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-900'"
                        >
                            <span class="text-lg mb-1">🏠</span>
                            <span class="text-xs">Indoor (di Studio)</span>
                        </button>
                        <button 
                            type="button"
                            @click="form.is_outdoor = true"
                            class="flex flex-col items-center justify-center p-3 border rounded-xl transition-all cursor-pointer"
                            :class="form.is_outdoor 
                                ? 'border-violet-500 bg-violet-500/10 text-violet-600 dark:text-violet-400 font-semibold' 
                                : 'border-slate-200 dark:border-slate-800 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-900'"
                        >
                            <span class="text-lg mb-1">🌳</span>
                            <span class="text-xs">Outdoor (Luar Studio)</span>
                        </button>
                    </div>
                </div>

                <!-- Location Input (Indoor) -->
                <div v-if="!form.is_outdoor">
                    <label for="location" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                        Lokasi Studio <span class="text-red-500">*</span>
                    </label>
                    <input 
                        id="location" 
                        type="text" 
                        v-model="form.location" 
                        required
                        class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 dark:focus:ring-violet-400/50 dark:focus:border-violet-400 transition-all text-slate-900 dark:text-white"
                        placeholder="Contoh: Studio Utama Photo Raka"
                    />
                    <span v-if="form.errors.location" class="text-xs text-red-500 dark:text-red-400 mt-1 block">
                        {{ form.errors.location }}
                    </span>
                </div>

                <!-- Location / Map Section (Outdoor) -->
                <div v-else class="space-y-4 border border-violet-500/20 dark:border-violet-800/30 rounded-xl p-4 bg-violet-500/[0.02]">
                    <h4 class="text-xs font-bold text-violet-600 dark:text-violet-400 uppercase tracking-wider">Kalkulator Perjalanan Sesi Outdoor</h4>
                    
                    <!-- Search Box -->
                    <div class="relative">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                            Cari & Pilih Lokasi Pemotretan <span class="text-red-500">*</span>
                        </label>
                        <div class="flex space-x-2">
                            <input 
                                type="text" 
                                v-model="searchQuery" 
                                class="flex-1 px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 dark:focus:ring-violet-400/50 dark:focus:border-violet-400 transition-all text-slate-900 dark:text-white"
                                placeholder="Ketik lokasi, misal: Kebun Raya Bogor"
                                @keyup.enter="searchLocation"
                            />
                            <button 
                                type="button" 
                                @click="searchLocation"
                                :disabled="isSearching"
                                class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-lg text-xs font-semibold transition-all cursor-pointer disabled:opacity-50"
                            >
                                {{ isSearching ? 'Mencari...' : 'Cari' }}
                            </button>
                        </div>

                        <!-- Results List -->
                        <div v-if="searchResults.length > 0" class="absolute z-[2000] left-0 right-0 mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg shadow-xl max-h-48 overflow-y-auto">
                            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                                <li 
                                    v-for="item in searchResults" 
                                    :key="item.place_id"
                                    @click="selectSearchResult(item)"
                                    class="px-4 py-2.5 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer transition-colors line-clamp-1"
                                >
                                    {{ item.display_name }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Map Container -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                            Peta Interaktif (Klik pada peta untuk menetapkan pin lokasi)
                        </label>
                        <div id="map" class="h-60 rounded-xl border border-slate-200 dark:border-slate-800 relative z-10"></div>
                        <p class="text-[10px] text-slate-500 mt-1">🔴 Pin Merah: Lokasi Pemotretan | 🟣 Pin Ungu: Studio</p>
                    </div>

                    <!-- Alamat Detail -->
                    <div>
                        <label for="location_outdoor" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                            Alamat Lengkap Lokasi <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            id="location_outdoor" 
                            rows="2"
                            v-model="form.location" 
                            required
                            class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 dark:focus:ring-violet-400/50 dark:focus:border-violet-400 transition-all text-slate-900 dark:text-white resize-none"
                            placeholder="Tuliskan nama tempat dan alamat lengkap..."
                        ></textarea>
                        <span v-if="form.errors.location" class="text-xs text-red-500 dark:text-red-400 mt-1 block">
                            {{ form.errors.location }}
                        </span>
                    </div>

                    <!-- Toll Cost & Overnight -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="toll_cost" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                                Perkiraan Uang Tol Kru (Rp)
                            </label>
                            <input 
                                id="toll_cost" 
                                type="number" 
                                v-model.number="form.toll_cost" 
                                min="0"
                                class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 dark:focus:ring-violet-400/50 dark:focus:border-violet-400 transition-all text-slate-900 dark:text-white"
                                placeholder="0"
                            />
                        </div>
                        <div class="flex items-center pt-5">
                            <label class="relative flex items-center cursor-pointer select-none">
                                <input 
                                    type="checkbox" 
                                    v-model="form.is_overnight" 
                                    class="sr-only peer"
                                />
                                <div class="w-11 h-6 bg-slate-200 dark:bg-slate-800 rounded-full peer peer-focus:ring-2 peer-focus:ring-violet-500/30 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-violet-600"></div>
                                <span class="ml-3 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    Kru Perlu Menginap (Akomodasi)
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Breakdown Surcharge Card -->
                    <div v-if="form.travel_distance > 0" class="p-4 rounded-xl bg-violet-500/5 dark:bg-violet-950/10 border border-violet-500/10 dark:border-violet-900/20 text-xs text-slate-600 dark:text-slate-400 space-y-2">
                        <p class="font-bold text-slate-800 dark:text-slate-200 border-b border-slate-100 dark:border-slate-800 pb-1.5 mb-1.5 uppercase tracking-wide">Rincian Tambahan Biaya Perjalanan</p>
                        <div class="flex justify-between">
                            <span>Jarak Satu Arah (Rute OSRM)</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ form.travel_distance }} KM</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Bensin (Pulang Pergi: {{ form.travel_distance * 2 }} KM)</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ formatIDR(form.fuel_cost) }}</span>
                        </div>
                        <div class="flex justify-between" v-if="form.toll_cost > 0">
                            <span>Uang Tol</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ formatIDR(form.toll_cost) }}</span>
                        </div>
                        <div class="flex justify-between" v-if="form.is_overnight">
                            <span>Akomodasi Kru</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ formatIDR(form.accommodation_cost) }}</span>
                        </div>
                        <div class="flex justify-between border-t border-dashed border-slate-200 dark:border-slate-800 pt-2 font-bold text-slate-800 dark:text-slate-200 text-sm">
                            <span>Total Surcharge Sesi Outdoor</span>
                            <span class="text-violet-600 dark:text-violet-400">{{ formatIDR(form.travel_surcharge) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label for="notes" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Catatan Tambahan
                </label>
                <textarea 
                    id="notes" 
                    rows="3" 
                    v-model="form.notes" 
                    class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 dark:focus:ring-violet-400/50 dark:focus:border-violet-400 transition-all text-slate-900 dark:text-white resize-none"
                    placeholder="Tulis konsep foto, jumlah peserta, atau permintaan khusus lainnya jika ada..."
                ></textarea>
                <span v-if="form.errors.notes" class="text-xs text-red-500 dark:text-red-400 mt-1 block">
                    {{ form.errors.notes }}
                </span>
            </div>

            <!-- Pricing Summary Card -->
            <div v-if="selectedPackage" class="p-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-slate-900/50 space-y-2 text-xs">
                <p class="font-bold text-slate-800 dark:text-slate-200 border-b border-slate-100 dark:border-slate-800 pb-1.5 mb-1.5 uppercase tracking-wide">Ringkasan Biaya Reservasi</p>
                <div class="flex justify-between text-slate-700 dark:text-slate-300">
                    <span>Paket: {{ selectedPackage.name }}</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ formatIDR(packagePrice) }}</span>
                </div>
                <div class="flex justify-between text-violet-600 dark:text-violet-400" v-if="form.is_outdoor && form.travel_surcharge > 0">
                    <span>Tambahan Surcharge Sesi Outdoor</span>
                    <span class="font-semibold">+ {{ formatIDR(form.travel_surcharge) }}</span>
                </div>
                <div class="flex justify-between border-t border-slate-200 dark:border-slate-800 pt-2 font-extrabold text-slate-900 dark:text-white text-sm">
                    <span>Estimasi Total Nilai Kontrak</span>
                    <span class="text-violet-600 dark:text-violet-400">{{ formatIDR(totalPrice) }}</span>
                </div>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 italic mt-1 leading-tight text-center">
                    * Uang Muka (DP 30%) akan dihitung dari total biaya di atas setelah admin mengonfirmasi reservasi.
                </p>
            </div>

            <!-- Submit Button -->
            <div>
                <button 
                    type="submit" 
                    :disabled="form.processing"
                    class="w-full py-2.5 bg-violet-600 hover:bg-violet-700 text-white font-medium rounded-lg shadow-lg shadow-violet-500/10 hover:shadow-violet-500/20 text-sm transition-all focus:outline-none focus:ring-2 focus:ring-violet-500 disabled:opacity-50 cursor-pointer"
                >
                    <span v-if="form.processing">Mengirim Reservasi...</span>
                    <span v-else>Kirim Pemesanan Jadwal</span>
                </button>
            </div>
        </form>

        <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800/80 text-center">
            <Link href="/" class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                ← Batal & Kembali ke Halaman Utama
            </Link>
        </div>
    </GuestLayout>
</template>
