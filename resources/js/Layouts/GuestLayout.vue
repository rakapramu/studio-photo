<script setup>
import ThemeToggle from "../Components/ThemeToggle.vue";
</script>

<template>
    <div
        class="min-h-screen flex flex-col justify-center items-center bg-slate-50 dark:bg-slate-950 transition-colors duration-300 relative px-4 py-8"
    >
        <!-- Floating Theme Toggle -->
        <div class="absolute top-6 right-6">
            <ThemeToggle />
        </div>

        <!-- Decorative background glows -->
        <div
            class="absolute top-10 left-10 w-72 h-72 bg-violet-500/5 dark:bg-violet-500/10 rounded-full blur-3xl pointer-events-none"
        ></div>
        <div
            class="absolute bottom-10 right-10 w-72 h-72 bg-indigo-500/5 dark:bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"
        ></div>

        <div
            class="w-full sm:max-w-xl mt-6 px-8 py-8 bg-white dark:bg-slate-900/60 dark:backdrop-blur-xl shadow-xl hover:shadow-2xl border border-slate-200/60 dark:border-slate-800/80 rounded-2xl transition-all duration-300 z-10"
        >
            <div class="flex flex-col items-center mb-6">
                <span
                    class="text-3xl font-extrabold bg-gradient-to-r from-violet-600 to-indigo-600 dark:from-violet-400 dark:to-indigo-400 bg-clip-text text-transparent"
                >
                    📷 PhotoStudio
                </span>
                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
                    Management Portal
                </p>
            </div>

            <slot />
        </div>
    </div>
</template>
