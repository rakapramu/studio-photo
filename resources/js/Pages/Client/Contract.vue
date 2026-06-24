<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';

const props = defineProps({
    booking: Object,
    contract: Object,
    hash: String,
});

// Form submission setup
const form = useForm({
    signature_image: '',
});

// Canvas DOM reference
const canvasRef = ref(null);
let isDrawing = false;
let ctx = null;

// Initialize canvas 2D context
const initCanvas = () => {
    if (props.contract.signed_at || !canvasRef.value) return;

    const canvas = canvasRef.value;
    ctx = canvas.getContext('2d');
    
    // Setup stroke style
    ctx.strokeStyle = '#6366f1'; // Indigo stroke
    ctx.lineWidth = 3;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
};

// Start drawing
const startDrawing = (e) => {
    isDrawing = true;
    draw(e);
};

// Stop drawing
const stopDrawing = () => {
    isDrawing = false;
    if (ctx) ctx.beginPath(); // reset path
};

// Get position relative to canvas
const getMousePos = (e) => {
    const canvas = canvasRef.value;
    const rect = canvas.getBoundingClientRect();
    
    // Handle touch events
    if (e.touches && e.touches.length > 0) {
        return {
            x: e.touches[0].clientX - rect.left,
            y: e.touches[0].clientY - rect.top
        };
    }
    
    // Handle mouse events
    return {
        x: e.clientX - rect.left,
        y: e.clientY - rect.top
    };
};

// Draw logic
const draw = (e) => {
    if (!isDrawing || !ctx) return;
    
    // Prevent scrolling on touch screens when drawing
    e.preventDefault();

    const pos = getMousePos(e);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
    ctx.beginPath();
    ctx.moveTo(pos.x, pos.y);
};

// Clear canvas
const clearCanvas = () => {
    if (!ctx || !canvasRef.value) return;
    ctx.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height);
    ctx.beginPath();
};

// Submit signed SPK
const submitSignature = () => {
    if (!canvasRef.value) return;

    // Cek apakah canvas kosong (kita bisa cek data pixelnya)
    const canvas = canvasRef.value;
    const blank = document.createElement('canvas');
    blank.width = canvas.width;
    blank.height = canvas.height;
    
    if (canvas.toDataURL() === blank.toDataURL()) {
        alert('Silakan bubuhkan tanda tangan Anda terlebih dahulu pada area canvas.');
        return;
    }

    // Convert drawing to base64 image data URI
    form.signature_image = canvas.toDataURL('image/png');

    form.post(`/contract/${props.booking.id}/${props.hash}/sign`, {
        onSuccess: () => {
            clearCanvas();
        }
    });
};

onMounted(() => {
    // Beri sedikit delay agar DOM selesai merender lebar canvas
    setTimeout(initCanvas, 300);
});
</script>

<template>
    <GuestLayout>
        <Head title="Tanda Tangan Kontrak SPK" />

        <div class="mb-4">
            <span 
                v-if="contract.signed_at" 
                class="mb-3 inline-flex items-center px-3 py-1 bg-emerald-100 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-400 text-xs font-semibold rounded-full"
            >
                ● SPK Sudah Ditandatangani
            </span>
            <span 
                v-else 
                class="mb-3 inline-flex items-center px-3 py-1 bg-amber-100 dark:bg-amber-950/40 text-amber-800 dark:text-amber-400 text-xs font-semibold rounded-full"
            >
                ● Menunggu Tanda Tangan Anda
            </span>
            <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Surat Perjanjian Kerja (SPK)</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Pemesanan Sesi #{{ booking.id }} - {{ booking.package.name }}</p>
        </div>

        <div class="space-y-4">
            <!-- Contract Text Display (Scrollable) -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Isi Pasal Perjanjian
                </label>
                <div class="h-60 overflow-y-auto px-4 py-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-xs leading-relaxed text-slate-600 dark:text-slate-300 font-mono whitespace-pre-line shadow-inner">
                    {{ contract.contract_text }}
                </div>
            </div>

            <!-- Signature Display (If Signed) -->
            <div v-if="contract.signed_at" class="p-4 bg-emerald-50/50 dark:bg-emerald-950/10 border border-emerald-200/60 dark:border-emerald-900 rounded-lg space-y-3">
                <p class="text-xs font-bold text-emerald-800 dark:text-emerald-400">Bukti Tanda Tangan Digital Klien:</p>
                <div class="bg-white dark:bg-slate-900 p-2 border border-slate-200 dark:border-slate-800 rounded-lg w-fit mx-auto shadow-sm">
                    <img :src="contract.signature_path" alt="Tanda Tangan Klien" class="h-24 dark:invert transition-all" />
                </div>
                <div class="text-[10px] text-slate-500 dark:text-slate-400 leading-tight">
                    <p>Ditandatangani pada: {{ new Date(contract.signed_at).toLocaleString('id-ID') }} WIB</p>
                    <p>Log Alamat IP Klien: {{ contract.ip_address || 'Tidak terekam' }}</p>
                </div>
            </div>

            <!-- Canvas Drawing Area (If Pending) -->
            <div v-else>
                <div class="flex justify-between items-center mb-1.5">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Bubuhkan Tanda Tangan <span class="text-red-500">*</span>
                    </label>
                    <button 
                        type="button" 
                        @click="clearCanvas"
                        class="text-[10px] text-violet-600 dark:text-violet-400 font-bold hover:underline"
                    >
                        Reset / Bersihkan Canvas
                    </button>
                </div>

                <!-- Canvas Grid -->
                <div class="bg-slate-50 dark:bg-slate-950 border-2 border-dashed border-slate-350 dark:border-slate-800 rounded-lg overflow-hidden shadow-inner flex justify-center">
                    <canvas 
                        ref="canvasRef"
                        width="380"
                        height="150"
                        class="cursor-crosshair w-full max-w-[380px] bg-slate-50 dark:bg-slate-950"
                        @mousedown="startDrawing"
                        @mousemove="draw"
                        @mouseup="stopDrawing"
                        @mouseleave="stopDrawing"
                        
                        @touchstart="startDrawing"
                        @touchmove="draw"
                        @touchend="stopDrawing"
                    ></canvas>
                </div>
                <p class="text-[10px] text-slate-400 mt-1 text-center">Gunakan mouse atau coret dengan jari (touchscreen) pada area di atas.</p>
                <span v-if="form.errors.signature_image" class="text-xs text-red-500 dark:text-red-400 mt-1 block">
                    {{ form.errors.signature_image }}
                </span>
            </div>

            <!-- Action Button (If Pending) -->
            <div v-if="!contract.signed_at">
                <button 
                    type="button" 
                    @click="submitSignature"
                    :disabled="form.processing"
                    class="w-full py-2.5 bg-violet-600 hover:bg-violet-700 text-white font-medium rounded-lg shadow-lg shadow-violet-500/10 hover:shadow-violet-500/20 text-sm transition-all disabled:opacity-50"
                >
                    <span v-if="form.processing">Mengirim Persetujuan...</span>
                    <span v-else>Setujui & Tanda Tangani Kontrak SPK</span>
                </button>
            </div>
        </div>

        <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800/80 text-center">
            <Link href="/" class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                ← Batal & Kembali ke Halaman Utama
            </Link>
        </div>
    </GuestLayout>
</template>
