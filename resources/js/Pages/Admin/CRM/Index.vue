<script setup>
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    rules: Array,
    packages: Array,
    schedules: Array,
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const showSuccessNotification = ref(false);
const showErrorNotification = ref(false);
const notificationMessage = ref('');

watch(flashSuccess, (newVal) => {
    if (newVal) {
        notificationMessage.value = newVal;
        showSuccessNotification.value = true;
        setTimeout(() => showSuccessNotification.value = false, 4000);
    }
}, { immediate: true });

watch(flashError, (newVal) => {
    if (newVal) {
        notificationMessage.value = newVal;
        showErrorNotification.value = true;
        setTimeout(() => showErrorNotification.value = false, 4000);
    }
}, { immediate: true });

// Tabs state
const activeTab = ref('rules'); // 'rules' or 'logs'

// Search & Filtering logs
const logSearch = ref('');
const logStatusFilter = ref('all');

const filteredSchedules = computed(() => {
    return props.schedules.filter(item => {
        const matchesSearch = item.client_name.toLowerCase().includes(logSearch.value.toLowerCase()) || 
                             item.client_phone.includes(logSearch.value);
        const matchesStatus = logStatusFilter.value === 'all' || item.status === logStatusFilter.value;
        return matchesSearch && matchesStatus;
    });
});

// Modals State
const isRuleModalOpen = ref(false);
const isEditMode = ref(false);
const currentRuleId = ref(null);

const ruleForm = useForm({
    name: '',
    source_package_id: '',
    target_package_id: '',
    delay_days: '',
    message_template: '',
    is_active: true,
});

// Helper for opening new rule modal
const openCreateModal = () => {
    isEditMode.value = false;
    currentRuleId.value = null;
    ruleForm.reset();
    ruleForm.name = '';
    ruleForm.source_package_id = props.packages[0]?.id || '';
    ruleForm.target_package_id = props.packages[1]?.id || props.packages[0]?.id || '';
    ruleForm.delay_days = 300;
    ruleForm.message_template = 'Halo {client_name},\n\nTerima kasih telah mempercayakan dokumentasi {source_package} Anda kepada kami.\n\nApakah Anda berencana mengabadikan momen berikutnya? Kami memiliki penawaran spesial untuk paket {target_package} seharga {target_price}.\n\nHubungi kami untuk melakukan booking kembali!\n\nSalam hangat,\nPhoto Studio Team';
    ruleForm.is_active = true;
    isRuleModalOpen.value = true;
};

// Helper for opening edit rule modal
const openEditModal = (rule) => {
    isEditMode.value = true;
    currentRuleId.value = rule.id;
    ruleForm.name = rule.name;
    ruleForm.source_package_id = rule.source_package_id;
    ruleForm.target_package_id = rule.target_package_id;
    ruleForm.delay_days = rule.delay_days;
    ruleForm.message_template = rule.message_template;
    ruleForm.is_active = rule.is_active;
    isRuleModalOpen.value = true;
};

// Save rule submit handler
const submitRule = () => {
    if (isEditMode.value) {
        ruleForm.put('/admin/crm/rules/' + currentRuleId.value, {
            onSuccess: () => {
                isRuleModalOpen.value = false;
                ruleForm.reset();
            }
        });
    } else {
        ruleForm.post('/admin/crm/rules', {
            onSuccess: () => {
                isRuleModalOpen.value = false;
                ruleForm.reset();
            }
        });
    }
};

// Delete rule confirmation
const deleteRule = (ruleId) => {
    if (confirm('Apakah Anda yakin ingin menghapus aturan lifecycle ini?')) {
        router.delete('/admin/crm/rules/' + ruleId);
    }
};

// Dispatch individual schedule message
const isSending = ref({});
const sendScheduleNow = (scheduleId) => {
    isSending.value[scheduleId] = true;
    router.post('/admin/crm/schedules/' + scheduleId + '/send', {}, {
        preserveScroll: true,
        onFinish: () => {
            isSending.value[scheduleId] = false;
        }
    });
};

// Trigger simulated cron job
const isRunningCron = ref(false);
const runCronSimulation = () => {
    isRunningCron.value = true;
    router.post('/admin/crm/schedules/run-cron', {}, {
        preserveScroll: true,
        onFinish: () => {
            isRunningCron.value = false;
        }
    });
};

// Format Date
const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="CRM & Lifecycle Marketing" />

        <template #title>
            CRM & Client Lifecycle Marketing
        </template>

        <!-- Floating Notifications -->
        <div class="fixed top-6 right-6 z-50 space-y-3">
            <Transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showSuccessNotification" class="max-w-sm w-full bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 p-4 rounded-xl shadow-lg flex items-start space-x-3">
                    <span class="text-xl">✅</span>
                    <div>
                        <p class="font-semibold text-sm">Berhasil</p>
                        <p class="text-xs opacity-90">{{ notificationMessage }}</p>
                    </div>
                </div>
            </Transition>

            <Transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showErrorNotification" class="max-w-sm w-full bg-rose-50 dark:bg-rose-950/80 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 p-4 rounded-xl shadow-lg flex items-start space-x-3">
                    <span class="text-xl">⚠️</span>
                    <div>
                        <p class="font-semibold text-sm">Gagal</p>
                        <p class="text-xs opacity-90">{{ notificationMessage }}</p>
                    </div>
                </div>
            </Transition>
        </div>

        <div class="space-y-6">
            <!-- Header Card with Summary -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100">Repeat Order Otomatis (Lifecycle Marketing)</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Sistem secara otomatis menjadwalkan promosi WhatsApp personal pasca-pemotretan berdasarkan timeline siklus hidup pelanggan (Wedding → Maternity → Newborn → Family).
                        </p>
                    </div>
                    <div class="flex items-center space-x-3 shrink-0">
                        <button
                            @click="runCronSimulation"
                            :disabled="isRunningCron"
                            class="px-4 py-2.5 bg-violet-600 hover:bg-violet-700 disabled:bg-violet-400 text-white text-sm font-semibold rounded-xl transition shadow-sm flex items-center space-x-2"
                        >
                            <span>{{ isRunningCron ? 'Memproses...' : '⚙️ Simulasikan Cron' }}</span>
                        </button>
                        <button
                            @click="openCreateModal"
                            class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 dark:bg-slate-100 dark:hover:bg-slate-200 text-white dark:text-slate-900 text-sm font-semibold rounded-xl transition shadow-sm flex items-center space-x-2"
                        >
                            <span>➕ Tambah Aturan</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tab Buttons -->
            <div class="border-b border-slate-200 dark:border-slate-800 flex space-x-6">
                <button
                    @click="activeTab = 'rules'"
                    class="pb-4 text-sm font-bold border-b-2 transition-all"
                    :class="activeTab === 'rules' 
                        ? 'border-violet-600 text-violet-600 dark:border-violet-400 dark:text-violet-400' 
                        : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                >
                    📣 Aturan Siklus Hidup (Rules)
                </button>
                <button
                    @click="activeTab = 'logs'"
                    class="pb-4 text-sm font-bold border-b-2 transition-all"
                    :class="activeTab === 'logs' 
                        ? 'border-violet-600 text-violet-600 dark:border-violet-400 dark:text-violet-400' 
                        : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                >
                    📅 Antrean Pemasaran & Log
                </button>
            </div>

            <!-- Tab Content: Rules -->
            <div v-if="activeTab === 'rules'" class="space-y-4">
                <div v-if="rules.length === 0" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-12 text-center">
                    <p class="text-2xl">📣</p>
                    <h4 class="text-base font-semibold text-slate-800 dark:text-slate-200 mt-3">Belum Ada Aturan Siklus Hidup</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-md mx-auto">
                        Mulai dengan menambahkan aturan baru untuk menjadwalkan pesan WhatsApp promosi otomatis setelah pemotretan klien selesai.
                    </p>
                    <button
                        @click="openCreateModal"
                        class="mt-4 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold rounded-xl transition"
                    >
                        Tambah Aturan Pertama
                    </button>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div 
                        v-for="rule in rules" 
                        :key="rule.id"
                        class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden flex flex-col justify-between"
                    >
                        <div class="p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <span 
                                    class="px-2.5 py-1 text-xs font-semibold rounded-full"
                                    :class="rule.is_active 
                                        ? 'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800' 
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400'"
                                >
                                    {{ rule.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                                <span class="text-xs font-semibold text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-950/30 px-2.5 py-1 rounded-full border border-violet-100 dark:border-violet-900">
                                    ⏱️ {{ rule.delay_days }} Hari Penundaan
                                </span>
                            </div>

                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-slate-100 text-base">{{ rule.name }}</h4>
                                <div class="flex items-center space-x-2 text-xs text-slate-500 dark:text-slate-400 mt-2 bg-slate-50 dark:bg-slate-800/40 p-2 rounded-lg">
                                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ rule.source_package?.name || 'Semua Paket' }}</span>
                                    <span>➡️</span>
                                    <span class="font-semibold text-violet-700 dark:text-violet-400">{{ rule.target_package?.name || 'Paket Rekomendasi' }}</span>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Template Pesan WhatsApp</p>
                                <p class="text-xs text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-950/40 p-3 rounded-lg border border-slate-100 dark:border-slate-800 whitespace-pre-wrap line-clamp-4">
                                    {{ rule.message_template }}
                                </p>
                            </div>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-900/60 border-t border-slate-100 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                            <button
                                @click="openEditModal(rule)"
                                class="text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-violet-600 dark:hover:text-violet-400 transition"
                            >
                                ✏️ Edit Aturan
                            </button>
                            <button
                                @click="deleteRule(rule.id)"
                                class="text-xs font-bold text-red-600 hover:text-red-700 transition"
                            >
                                🗑️ Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Content: Marketing Schedules & Logs -->
            <div v-if="activeTab === 'logs'" class="space-y-4">
                <!-- Filters & Statistics -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        <input
                            v-model="logSearch"
                            type="text"
                            placeholder="Cari nama klien / WhatsApp..."
                            class="px-4 py-2 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-600 dark:focus:ring-violet-400 focus:border-transparent min-w-[240px]"
                        />
                        <select
                            v-model="logStatusFilter"
                            class="px-4 py-2 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-600 dark:focus:ring-violet-400 focus:border-transparent"
                        >
                            <option value="all">Semua Status</option>
                            <option value="pending">⏳ Pending (Menunggu Jadwal)</option>
                            <option value="sent">✅ Sent (Terkirim)</option>
                            <option value="failed">❌ Failed (Gagal)</option>
                        </select>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 font-medium self-end sm:self-center">
                        Menampilkan <span class="font-bold text-slate-700 dark:text-slate-300">{{ filteredSchedules.length }}</span> entri antrean
                    </div>
                </div>

                <!-- Log Table -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider text-xs">
                                    <th class="p-4">Klien</th>
                                    <th class="p-4">WhatsApp</th>
                                    <th class="p-4">Rekomendasi Paket</th>
                                    <th class="p-4 max-w-xs">Isi Pesan</th>
                                    <th class="p-4">Jadwal Kirim</th>
                                    <th class="p-4">Status</th>
                                    <th class="p-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                                <tr v-if="filteredSchedules.length === 0">
                                    <td colspan="7" class="p-8 text-center text-slate-400 dark:text-slate-500">
                                        Tidak ada data antrean atau log yang sesuai dengan filter.
                                    </td>
                                </tr>
                                <tr 
                                    v-for="item in filteredSchedules" 
                                    :key="item.id"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors"
                                >
                                    <td class="p-4 font-semibold text-slate-900 dark:text-slate-100">
                                        {{ item.client_name }}
                                        <div class="text-xs text-slate-400 font-normal">
                                            Booking: {{ item.booking?.package?.name || 'Paket Selesai' }}
                                        </div>
                                    </td>
                                    <td class="p-4 font-mono text-slate-600 dark:text-slate-400">{{ item.client_phone }}</td>
                                    <td class="p-4">
                                        <span class="px-2 py-1 bg-violet-50 dark:bg-violet-950/30 border border-violet-100 dark:border-violet-900 text-violet-700 dark:text-violet-400 rounded-lg text-xs font-semibold">
                                            🏷️ {{ item.lifecycle_rule?.target_package?.name || 'Paket CRM' }}
                                        </span>
                                    </td>
                                    <td class="p-4 max-w-xs truncate text-xs text-slate-600 dark:text-slate-400" :title="item.message_content">
                                        {{ item.message_content }}
                                    </td>
                                    <td class="p-4 font-medium text-slate-600 dark:text-slate-400">
                                        {{ formatDate(item.scheduled_at) }}
                                    </td>
                                    <td class="p-4">
                                        <div class="flex flex-col">
                                            <span 
                                                class="px-2.5 py-1 text-xs font-bold rounded-full w-fit flex items-center space-x-1"
                                                :class="{
                                                    'bg-amber-50 dark:bg-amber-950/30 text-amber-700 dark:text-amber-400 border border-amber-100 dark:border-amber-900': item.status === 'pending',
                                                    'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900': item.status === 'sent',
                                                    'bg-rose-50 dark:bg-rose-950/30 text-rose-700 dark:text-rose-400 border border-rose-100 dark:border-rose-900': item.status === 'failed',
                                                }"
                                            >
                                                <span v-if="item.status === 'pending'">⏳ Pending</span>
                                                <span v-else-if="item.status === 'sent'">✅ Sent</span>
                                                <span v-else>❌ Failed</span>
                                            </span>
                                            <span v-if="item.status === 'failed'" class="text-[10px] text-red-500 mt-1 max-w-[150px] truncate" :title="item.error_message">
                                                {{ item.error_message }}
                                            </span>
                                            <span v-if="item.status === 'sent' && item.sent_at" class="text-[10px] text-slate-400 mt-1">
                                                {{ formatDate(item.sent_at) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="p-4 text-right">
                                        <button
                                            v-if="item.status !== 'sent'"
                                            @click="sendScheduleNow(item.id)"
                                            :disabled="isSending[item.id]"
                                            class="px-3 py-1.5 bg-violet-600 hover:bg-violet-700 disabled:bg-violet-400 text-white rounded-lg text-xs font-semibold shadow-sm transition flex items-center space-x-1 ml-auto"
                                        >
                                            <span>{{ isSending[item.id] ? 'Kirim...' : '🚀 Kirim Sekarang' }}</span>
                                        </button>
                                        <span v-else class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold">
                                            Selesai
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create & Edit Rule Modal -->
        <div v-if="isRuleModalOpen" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 w-full max-w-lg rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden flex flex-col transition-all duration-300">
                <div class="h-14 border-b border-slate-200 dark:border-slate-800 px-6 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 dark:text-slate-100 text-base">
                        {{ isEditMode ? 'Edit Aturan Lifecycle' : 'Tambah Aturan Lifecycle Baru' }}
                    </h3>
                    <button @click="isRuleModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg transition">
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submitRule" class="p-6 space-y-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Nama Aturan</label>
                        <input
                            v-model="ruleForm.name"
                            type="text"
                            placeholder="Contoh: Wedding to Maternity Promo"
                            required
                            class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-600 dark:focus:ring-violet-400 focus:border-transparent text-slate-800 dark:text-slate-100"
                        />
                        <p v-if="ruleForm.errors.name" class="text-xs text-red-500 mt-1">{{ ruleForm.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Paket Asal (Source)</label>
                            <select
                                v-model="ruleForm.source_package_id"
                                required
                                class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-600 dark:focus:ring-violet-400 focus:border-transparent text-slate-800 dark:text-slate-100"
                            >
                                <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                                    {{ pkg.name }}
                                </option>
                            </select>
                            <p v-if="ruleForm.errors.source_package_id" class="text-xs text-red-500 mt-1">{{ ruleForm.errors.source_package_id }}</p>
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Paket Tujuan (Target)</label>
                            <select
                                v-model="ruleForm.target_package_id"
                                required
                                class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-600 dark:focus:ring-violet-400 focus:border-transparent text-slate-800 dark:text-slate-100"
                            >
                                <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                                    {{ pkg.name }}
                                </option>
                            </select>
                            <p v-if="ruleForm.errors.target_package_id" class="text-xs text-red-500 mt-1">{{ ruleForm.errors.target_package_id }}</p>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Jeda Waktu (Hari)</label>
                        <input
                            v-model="ruleForm.delay_days"
                            type="number"
                            placeholder="Jeda hari pengiriman (misal: 300 untuk 10 bulan)"
                            required
                            min="0"
                            class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-600 dark:focus:ring-violet-400 focus:border-transparent text-slate-800 dark:text-slate-100"
                        />
                        <p v-if="ruleForm.errors.delay_days" class="text-xs text-red-500 mt-1">{{ ruleForm.errors.delay_days }}</p>
                    </div>

                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Template Pesan WhatsApp</label>
                            <span class="text-[10px] text-slate-400 font-semibold bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">Fonnte API</span>
                        </div>
                        <textarea
                            v-model="ruleForm.message_template"
                            rows="5"
                            placeholder="Tulis pesan promosi WhatsApp..."
                            required
                            class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-600 dark:focus:ring-violet-400 focus:border-transparent text-slate-800 dark:text-slate-100 font-sans"
                        ></textarea>
                        <div class="bg-slate-50 dark:bg-slate-950/40 p-2.5 rounded-lg border border-slate-100 dark:border-slate-800 text-[11px] text-slate-500 dark:text-slate-400 space-y-1">
                            <p class="font-bold text-slate-700 dark:text-slate-300">Variabel Dinamis yang didukung:</p>
                            <ul class="list-disc pl-4 grid grid-cols-2 gap-x-2">
                                <li><code>{client_name}</code> : Nama Klien</li>
                                <li><code>{source_package}</code> : Paket Lama</li>
                                <li><code>{target_package}</code> : Paket Rekomendasi</li>
                                <li><code>{target_price}</code> : Harga Rekomendasi</li>
                            </ul>
                        </div>
                        <p v-if="ruleForm.errors.message_template" class="text-xs text-red-500 mt-1">{{ ruleForm.errors.message_template }}</p>
                    </div>

                    <div class="space-y-1 py-2">
                        <div class="flex items-center space-x-2">
                            <input
                                type="checkbox"
                                id="is_active"
                                v-model="ruleForm.is_active"
                                class="rounded border-slate-300 text-violet-600 focus:ring-violet-500 dark:bg-slate-950 dark:border-slate-800"
                            />
                            <label for="is_active" class="text-sm font-semibold text-slate-700 dark:text-slate-300 cursor-pointer selection:bg-transparent">
                                Aturan Aktif & Berlaku
                            </label>
                        </div>
                        <p v-if="ruleForm.errors.is_active" class="text-xs text-red-500 mt-1">{{ ruleForm.errors.is_active }}</p>
                    </div>

                    <div class="h-14 border-t border-slate-200 dark:border-slate-800 pt-4 flex items-center justify-end space-x-3 shrink-0">
                        <button
                            type="button"
                            @click="isRuleModalOpen = false"
                            class="px-4 py-2 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="ruleForm.processing"
                            class="px-4 py-2 bg-violet-600 hover:bg-violet-700 disabled:bg-violet-400 text-white text-sm font-semibold rounded-xl shadow-sm transition"
                        >
                            {{ ruleForm.processing ? 'Menyimpan...' : 'Simpan Aturan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
