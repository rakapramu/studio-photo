<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import ThemeToggle from '../Components/ThemeToggle.vue';

const page = usePage();
const user = ref(page.props.auth?.user || { name: 'Admin Raka' });

const isSidebarOpen = ref(false);

const navItems = [
    { name: 'Dashboard', url: '/admin/dashboard', icon: '📊' },
    { name: 'Analitik Keuangan', url: '/admin/analytics', icon: '📈' },
    { name: 'Paket Foto', url: '/admin/packages', icon: '🏷️' },
    { name: 'Pemesanan', url: '/admin/bookings', icon: '📅' },
    { name: 'Pembayaran', url: '/admin/payments', icon: '💳' },
    { name: 'Kontrak SPK', url: '/admin/contracts', icon: '📝' },
    { name: 'Pengeluaran', url: '/admin/expenses', icon: '💸' },
    { name: 'Inventaris Alat', url: '/admin/equipments', icon: '📷' },
    { name: 'Kru & Staf', url: '/admin/crews', icon: '👥' },
    { name: 'Jadwal Kru (Kanban)', url: '/admin/crews/kanban', icon: '📋' },
    { name: 'CRM & Marketing', url: '/admin/crm', icon: '📣' },
    { name: 'Pengaturan Usaha', url: '/admin/settings', icon: '⚙️' },
];

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 flex transition-colors duration-300 font-sans">
        <!-- Sidebar Desktop -->
        <aside class="hidden md:flex flex-col w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 shrink-0">
            <div class="h-16 flex items-center px-6 border-b border-slate-200 dark:border-slate-800">
                <Link href="/" class="text-xl font-bold bg-gradient-to-r from-violet-600 to-indigo-600 dark:from-violet-400 dark:to-indigo-400 bg-clip-text text-transparent">
                    📷 PhotoStudio
                </Link>
            </div>
            
            <nav class="flex-1 px-4 py-6 space-y-1.5">
                <Link 
                    v-for="item in navItems" 
                    :key="item.name" 
                    :href="item.url"
                    class="flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200"
                    :class="$page.url.startsWith(item.url) 
                        ? 'bg-violet-50 dark:bg-violet-950/40 text-violet-600 dark:text-violet-400 border-l-4 border-violet-600 dark:border-violet-400' 
                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/40 hover:text-slate-900 dark:hover:text-slate-100'"
                >
                    <span class="mr-3 text-lg">{{ item.icon }}</span>
                    {{ item.name }}
                </Link>
            </nav>

            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center min-w-0">
                        <div class="w-9 h-9 rounded-full bg-violet-600 text-white flex items-center justify-center font-bold shrink-0">
                            {{ user.name.charAt(0) }}
                        </div>
                        <div class="ml-3 min-w-0">
                            <p class="text-sm font-semibold truncate">{{ user.name }}</p>
                            <p class="text-xs text-slate-500 truncate">Administrator</p>
                        </div>
                    </div>
                </div>
                <button 
                    @click="logout"
                    class="w-full flex items-center justify-center px-4 py-2 bg-slate-100 hover:bg-red-50 hover:text-red-600 dark:bg-slate-800 dark:hover:bg-red-950/30 dark:hover:text-red-400 text-slate-700 dark:text-slate-300 text-sm font-medium rounded-lg transition-all"
                >
                    🚪 Keluar (Logout)
                </button>
            </div>
        </aside>

        <!-- Sidebar Mobile Drawer -->
        <div v-if="isSidebarOpen" class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm z-50 md:hidden" @click="isSidebarOpen = false"></div>
        <aside 
            class="fixed inset-y-0 left-0 w-64 bg-white dark:bg-slate-900 z-50 flex flex-col md:hidden transform transition-transform duration-300"
            :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-slate-800">
                <span class="text-xl font-bold bg-gradient-to-r from-violet-600 to-indigo-600 dark:from-violet-400 dark:to-indigo-400 bg-clip-text text-transparent">
                    📷 PhotoStudio
                </span>
                <button @click="isSidebarOpen = false" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded">
                    ✕
                </button>
            </div>
            
            <nav class="flex-1 px-4 py-6 space-y-1.5">
                <Link 
                    v-for="item in navItems" 
                    :key="item.name" 
                    :href="item.url"
                    class="flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all"
                    :class="$page.url.startsWith(item.url) 
                        ? 'bg-violet-50 dark:bg-violet-950/40 text-violet-600 dark:text-violet-400 border-l-4 border-violet-600 dark:border-violet-400' 
                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/40 hover:text-slate-900 dark:hover:text-slate-100'"
                    @click="isSidebarOpen = false"
                >
                    <span class="mr-3 text-lg">{{ item.icon }}</span>
                    {{ item.name }}
                </Link>
            </nav>

            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                <div class="flex items-center min-w-0 mb-4">
                    <div class="w-9 h-9 rounded-full bg-violet-600 text-white flex items-center justify-center font-bold shrink-0">
                        {{ user.name.charAt(0) }}
                    </div>
                    <div class="ml-3 min-w-0">
                        <p class="text-sm font-semibold truncate">{{ user.name }}</p>
                        <p class="text-xs text-slate-500 truncate">Administrator</p>
                    </div>
                </div>
                <button 
                    @click="logout"
                    class="w-full flex items-center justify-center px-4 py-2 bg-slate-100 hover:bg-red-50 hover:text-red-600 dark:bg-slate-800 dark:hover:bg-red-950/30 dark:hover:text-red-400 text-slate-700 dark:text-slate-300 text-sm font-medium rounded-lg transition-all"
                >
                    🚪 Keluar (Logout)
                </button>
            </div>
        </aside>

        <!-- Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Header -->
            <header class="h-16 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-6 z-30 transition-colors duration-300">
                <div class="flex items-center">
                    <button 
                        @click="isSidebarOpen = true"
                        class="p-2 -ml-2 mr-3 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg md:hidden"
                    >
                        <!-- Burger Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                    <h2 class="text-lg font-bold tracking-tight">
                        <slot name="title">Dashboard</slot>
                    </h2>
                </div>

                <div class="flex items-center space-x-4">
                    <ThemeToggle />
                </div>
            </header>

            <!-- Main Content Grid -->
            <main class="flex-grow p-6 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
