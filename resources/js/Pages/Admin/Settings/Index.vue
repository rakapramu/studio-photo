<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch, nextTick } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    settings: Object,
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

const form = useForm({
    studio_name: props.settings.studio_name || '',
    studio_address: props.settings.studio_address || '',
    studio_latitude: props.settings.studio_latitude || '-6.597629',
    studio_longitude: props.settings.studio_longitude || '106.799568',
    fuel_cost_per_km: props.settings.fuel_cost_per_km || 2000,
    accommodation_cost_per_night: props.settings.accommodation_cost_per_night || 500000,
});

// Map picker states
let map = null;
let marker = null;
const searchLocationQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);

onMounted(() => {
    initMap();
});

const initMap = () => {
    const lat = parseFloat(form.studio_latitude);
    const lng = parseFloat(form.studio_longitude);
    
    // Create Map centered at current coordinates
    map = L.map('studio-settings-map').setView([lat, lng], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    const studioIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-violet.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });
    
    marker = L.marker([lat, lng], {
        icon: studioIcon,
        draggable: true
    }).addTo(map);
    
    // Listen for marker drag event
    marker.on('dragend', (e) => {
        const position = marker.getLatLng();
        form.studio_latitude = position.lat.toFixed(6);
        form.studio_longitude = position.lng.toFixed(6);
    });
    
    // Listen for map click event to place marker
    map.on('click', (e) => {
        marker.setLatLng(e.latlng);
        form.studio_latitude = e.latlng.lat.toFixed(6);
        form.studio_longitude = e.latlng.lng.toFixed(6);
    });
};

// Search address using OSM Nominatim API
const searchStudioAddress = async () => {
    if (!searchLocationQuery.value.trim()) return;
    
    isSearching.value = true;
    try {
        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchLocationQuery.value)}&limit=5`;
        const res = await fetch(url, {
            headers: {
                'Accept-Language': 'id-ID,id;q=0.9,en;q=0.8'
            }
        });
        searchResults.value = await res.json();
    } catch (e) {
        console.error('Nominatim search error:', e);
    } finally {
        isSearching.value = false;
    }
};

const selectSearchResult = (result) => {
    const lat = parseFloat(result.lat);
    const lon = parseFloat(result.lon);
    
    form.studio_latitude = lat.toFixed(6);
    form.studio_longitude = lon.toFixed(6);
    form.studio_address = result.display_name;
    
    if (map && marker) {
        map.setView([lat, lon], 15);
        marker.setLatLng([lat, lon]);
    }
    
    searchResults.value = [];
    searchLocationQuery.value = '';
};

const clearSearchResults = () => {
    setTimeout(() => {
        searchResults.value = [];
    }, 200);
};

// Save form
const submit = () => {
    form.put('/admin/settings', {
        preserveScroll: true,
        onSuccess: () => {
            // Refresh map position
            const lat = parseFloat(form.studio_latitude);
            const lng = parseFloat(form.studio_longitude);
            if (map && marker) {
                map.setView([lat, lng], 13);
                marker.setLatLng([lat, lng]);
            }
        }
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Pengaturan Usaha" />

        <template #title>
            Pengaturan Profil Usaha
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
                Kelola informasi identitas studio, tarif dasar kalkulasi sesi outdoor, serta koordinat titik awal keberangkatan armada & kru.
            </h3>
        </div>

        <form @submit.prevent="submit" class="grid grid-cols-1 xl:grid-cols-5 gap-6 items-start">
            
            <!-- Left Panel: Profile & Pricing Info (3 Columns) -->
            <div class="xl:col-span-3 space-y-6">
                <!-- Identitas Usaha Card -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm p-6 space-y-4">
                    <h3 class="text-sm font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800/80 pb-2">
                        🏢 Profil & Identitas Studio
                    </h3>
                    
                    <div>
                        <label for="studio_name" class="block text-xs font-bold text-slate-500 dark:text-slate-450 uppercase mb-1.5">Nama Usaha / Studio</label>
                        <input 
                            id="studio_name"
                            type="text" 
                            v-model="form.studio_name"
                            required
                            placeholder="Contoh: Raka Photo Studio"
                            class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-sm text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-600 transition-all font-medium"
                        />
                        <p v-if="form.errors.studio_name" class="text-xs text-red-500 mt-1">{{ form.errors.studio_name }}</p>
                    </div>

                    <div>
                        <label for="studio_address" class="block text-xs font-bold text-slate-500 dark:text-slate-450 uppercase mb-1.5">Alamat Fisik Lengkap</label>
                        <textarea 
                            id="studio_address"
                            rows="3"
                            v-model="form.studio_address"
                            required
                            placeholder="Tulis alamat studio lengkap..."
                            class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-sm text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-600 transition-all font-medium"
                        ></textarea>
                        <p v-if="form.errors.studio_address" class="text-xs text-red-500 mt-1">{{ form.errors.studio_address }}</p>
                    </div>
                </div>

                <!-- Tarif & Akomodasi Sesi Outdoor Card -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm p-6 space-y-4">
                    <h3 class="text-sm font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800/80 pb-2">
                        🌳 Tarif Dasar Operasional Outdoor
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="fuel_cost_per_km" class="block text-xs font-bold text-slate-500 dark:text-slate-450 uppercase mb-1.5">Biaya Bensin (per KM)</label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-2.5 text-xs text-slate-400 font-bold">Rp</span>
                                <input 
                                    id="fuel_cost_per_km"
                                    type="number" 
                                    v-model="form.fuel_cost_per_km"
                                    required
                                    min="0"
                                    class="w-full pl-9 pr-16 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-sm text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-600 transition-all font-mono font-bold"
                                />
                                <span class="absolute right-3.5 top-2.5 text-xs text-slate-400 font-medium">/ KM</span>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1">Biaya dikalikan PP (2x jarak rute) untuk estimasi bensin.</p>
                            <p v-if="form.errors.fuel_cost_per_km" class="text-xs text-red-500 mt-1">{{ form.errors.fuel_cost_per_km }}</p>
                        </div>

                        <div>
                            <label for="accommodation_cost_per_night" class="block text-xs font-bold text-slate-500 dark:text-slate-450 uppercase mb-1.5">Akomodasi Menginap (per Malam)</label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-2.5 text-xs text-slate-400 font-bold">Rp</span>
                                <input 
                                    id="accommodation_cost_per_night"
                                    type="number" 
                                    v-model="form.accommodation_cost_per_night"
                                    required
                                    min="0"
                                    class="w-full pl-9 pr-20 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-sm text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-600 transition-all font-mono font-bold"
                                />
                                <span class="absolute right-3.5 top-2.5 text-xs text-slate-400 font-medium">/ Malam</span>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1">Biaya penginapan crew jika sesi foto berdurasi menginap.</p>
                            <p v-if="form.errors.accommodation_cost_per_night" class="text-xs text-red-500 mt-1">{{ form.errors.accommodation_cost_per_night }}</p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button Desktop -->
                <div class="hidden xl:flex justify-end">
                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="px-6 py-3 bg-violet-600 hover:bg-violet-700 disabled:opacity-50 text-white font-semibold text-sm rounded-lg shadow-sm hover:shadow transition-all flex items-center space-x-2 cursor-pointer"
                    >
                        <span>{{ form.processing ? 'Menyimpan...' : '💾 Simpan Perubahan Pengaturan' }}</span>
                    </button>
                </div>
            </div>

            <!-- Right Panel: Map Location & Coordinates (2 Columns) -->
            <div class="xl:col-span-2 space-y-6">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm p-6 space-y-4">
                    <h3 class="text-sm font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800/80 pb-2">
                        📍 Koordinat & Peta Lokasi Studio
                    </h3>

                    <!-- Search Address Bar -->
                    <div class="relative">
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-450 uppercase mb-1.5">Cari & Pin Lokasi</label>
                        <div class="flex space-x-2">
                            <input 
                                type="text"
                                v-model="searchLocationQuery"
                                placeholder="Cari nama gedung, jalan, atau kota..."
                                @keydown.enter.prevent="searchStudioAddress"
                                @blur="clearSearchResults"
                                class="flex-1 px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-sm text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-600 transition-all"
                            />
                            <button 
                                type="button"
                                @click="searchStudioAddress"
                                :disabled="isSearching"
                                class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white font-medium text-sm rounded-lg transition-all cursor-pointer flex items-center justify-center shrink-0"
                            >
                                <span v-if="isSearching">Cari...</span>
                                <span v-else>🔍 Cari</span>
                            </button>
                        </div>

                        <!-- Autocomplete Suggestions -->
                        <div 
                            v-if="searchResults.length > 0" 
                            class="absolute left-0 right-0 mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg shadow-xl z-[2000] overflow-hidden divide-y divide-slate-100 dark:divide-slate-800"
                        >
                            <button
                                v-for="result in searchResults"
                                :key="result.place_id"
                                type="button"
                                @click="selectSearchResult(result)"
                                class="w-full text-left px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs text-slate-700 dark:text-slate-300 transition-colors block truncate"
                            >
                                📍 {{ result.display_name }}
                            </button>
                        </div>
                    </div>

                    <!-- Lat / Long Display -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="studio_latitude" class="block text-xs font-bold text-slate-500 dark:text-slate-450 uppercase mb-1.5">Latitude</label>
                            <input 
                                id="studio_latitude"
                                type="text" 
                                v-model="form.studio_latitude"
                                required
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-xs text-slate-850 dark:text-slate-205 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-600 transition-all font-mono"
                            />
                            <p v-if="form.errors.studio_latitude" class="text-xs text-red-500 mt-1">{{ form.errors.studio_latitude }}</p>
                        </div>

                        <div>
                            <label for="studio_longitude" class="block text-xs font-bold text-slate-500 dark:text-slate-450 uppercase mb-1.5">Longitude</label>
                            <input 
                                id="studio_longitude"
                                type="text" 
                                v-model="form.studio_longitude"
                                required
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-xs text-slate-850 dark:text-slate-205 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-600 transition-all font-mono"
                            />
                            <p v-if="form.errors.studio_longitude" class="text-xs text-red-500 mt-1">{{ form.errors.studio_longitude }}</p>
                        </div>
                    </div>

                    <!-- Map Container -->
                    <div>
                        <div id="studio-settings-map" class="h-64 rounded-xl border border-slate-200 dark:border-slate-800 relative z-10"></div>
                        <p class="text-[10px] text-slate-400 mt-1.5">💡 Anda dapat menggeser pin (drag) atau mengklik lokasi manapun di peta untuk menentukan koordinat.</p>
                    </div>
                </div>

                <!-- Submit Button Mobile -->
                <div class="xl:hidden">
                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="w-full py-3 bg-violet-600 hover:bg-violet-700 disabled:opacity-50 text-white font-semibold text-sm rounded-lg shadow-sm hover:shadow transition-all flex items-center justify-center space-x-2 cursor-pointer"
                    >
                        <span>{{ form.processing ? 'Menyimpan...' : '💾 Simpan Perubahan Pengaturan' }}</span>
                    </button>
                </div>
            </div>
            
        </form>
    </AdminLayout>
</template>

<style scoped>
/* Styling adjustment for map */
#studio-settings-map {
    width: 100%;
}
</style>
