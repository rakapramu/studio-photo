<script setup>
import { computed } from 'vue';

const props = defineProps({
    totalItems: {
        type: Number,
        required: true,
    },
    itemsPerPage: {
        type: Number,
        default: 5,
    },
    currentPage: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(['update:currentPage']);

const totalPages = computed(() => Math.ceil(props.totalItems / props.itemsPerPage));

const startItem = computed(() => {
    if (props.totalItems === 0) return 0;
    return (props.currentPage - 1) * props.itemsPerPage + 1;
});

const endItem = computed(() => {
    return Math.min(props.currentPage * props.itemsPerPage, props.totalItems);
});

const pages = computed(() => {
    const pageNumbers = [];
    const maxVisiblePages = 5;
    
    if (totalPages.value <= maxVisiblePages) {
        for (let i = 1; i <= totalPages.value; i++) {
            pageNumbers.push(i);
        }
    } else {
        // Simple sliding window
        let start = Math.max(props.currentPage - 2, 1);
        let end = Math.min(start + maxVisiblePages - 1, totalPages.value);
        
        if (end === totalPages.value) {
            start = Math.max(end - maxVisiblePages + 1, 1);
        }
        
        for (let i = start; i <= end; i++) {
            pageNumbers.push(i);
        }
    }
    
    return pageNumbers;
});

const changePage = (page) => {
    if (page >= 1 && page <= totalPages.value && page !== props.currentPage) {
        emit('update:currentPage', page);
    }
};
</script>

<template>
    <div v-if="totalPages > 1" class="flex flex-col sm:flex-row items-center justify-between px-6 py-4 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 gap-4">
        <!-- Text details -->
        <div class="text-xs text-slate-500 dark:text-slate-400">
            Menampilkan <span class="font-bold text-slate-850 dark:text-slate-200">{{ startItem }}</span> hingga <span class="font-bold text-slate-850 dark:text-slate-200">{{ endItem }}</span> dari <span class="font-bold text-slate-850 dark:text-slate-200">{{ totalItems }}</span> entri
        </div>

        <!-- Pagination Controls -->
        <nav class="inline-flex -space-x-px rounded-xl shadow-sm bg-slate-50 dark:bg-slate-950 p-1 border border-slate-200/60 dark:border-slate-800" aria-label="Pagination">
            <!-- Prev -->
            <button
                @click="changePage(currentPage - 1)"
                :disabled="currentPage === 1"
                class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer"
                :class="currentPage === 1 
                    ? 'text-slate-300 dark:text-slate-700 cursor-not-allowed' 
                    : 'text-slate-650 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white'"
            >
                Sebelumnya
            </button>

            <!-- Page numbers -->
            <button
                v-for="page in pages"
                :key="page"
                @click="changePage(page)"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer"
                :class="page === currentPage
                    ? 'bg-violet-600 text-white shadow-sm'
                    : 'text-slate-650 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white'"
            >
                {{ page }}
            </button>

            <!-- Next -->
            <button
                @click="changePage(currentPage + 1)"
                :disabled="currentPage === totalPages"
                class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer"
                :class="currentPage === totalPages 
                    ? 'text-slate-300 dark:text-slate-700 cursor-not-allowed' 
                    : 'text-slate-650 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white'"
            >
                Selanjutnya
            </button>
        </nav>
    </div>
</template>
