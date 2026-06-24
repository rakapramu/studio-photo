<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';

defineProps({
    packages: Array,
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
});

// Preset jam mulai pemotretan untuk kemudahan pilih slot waktu
const timeSlots = [
    '08:00', '09:00', '10:00', '11:00', '13:00', 
    '14:00', '15:00', '16:00', '17:00', '19:00', '20:00'
];

const submit = () => {
    form.post('/booking', {
        onSuccess: () => form.reset(),
    });
};

// Dapatkan tanggal hari ini dalam format YYYY-MM-DD untuk batas minimum date picker
const todayDate = new Date().toISOString().split('T')[0];
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

            <!-- Location -->
            <div>
                <label for="location" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Lokasi Pemotretan <span class="text-red-500">*</span>
                </label>
                <input 
                    id="location" 
                    type="text" 
                    v-model="form.location" 
                    required 
                    class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 dark:focus:ring-violet-400/50 dark:focus:border-violet-400 transition-all text-slate-900 dark:text-white"
                    placeholder="Contoh: Studio A (Indoor) / Kebun Raya Bogor (Outdoor)"
                />
                <span v-if="form.errors.location" class="text-xs text-red-500 dark:text-red-400 mt-1 block">
                    {{ form.errors.location }}
                </span>
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

            <!-- Submit Button -->
            <div>
                <button 
                    type="submit" 
                    :disabled="form.processing"
                    class="w-full py-2.5 bg-violet-600 hover:bg-violet-700 text-white font-medium rounded-lg shadow-lg shadow-violet-500/10 hover:shadow-violet-500/20 text-sm transition-all focus:outline-none focus:ring-2 focus:ring-violet-500 disabled:opacity-50"
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
