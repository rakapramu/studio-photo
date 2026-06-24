<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '../../Layouts/GuestLayout.vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Admin Login" />

        <div class="mb-4">
            <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100 text-center">Masuk ke Dasbor</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 text-center">Gunakan akun admin untuk mengelola studio foto Anda</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Alamat Email
                </label>
                <input 
                    id="email" 
                    type="email" 
                    v-model="form.email" 
                    required 
                    autofocus 
                    autocomplete="username"
                    class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 dark:focus:ring-violet-400/50 dark:focus:border-violet-400 transition-all text-slate-900 dark:text-white"
                    placeholder="nama@email.com"
                />
                <span v-if="form.errors.email" class="text-xs text-red-500 dark:text-red-400 mt-1 block">
                    {{ form.errors.email }}
                </span>
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Kata Sandi (Password)
                </label>
                <input 
                    id="password" 
                    type="password" 
                    v-model="form.password" 
                    required 
                    autocomplete="current-password"
                    class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 dark:focus:ring-violet-400/50 dark:focus:border-violet-400 transition-all text-slate-900 dark:text-white"
                    placeholder="••••••••"
                />
                <span v-if="form.errors.password" class="text-xs text-red-500 dark:text-red-400 mt-1 block">
                    {{ form.errors.password }}
                </span>
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <input 
                        type="checkbox" 
                        v-model="form.remember" 
                        class="rounded border-slate-300 dark:border-slate-800 text-violet-600 focus:ring-violet-500 bg-slate-50 dark:bg-slate-950" 
                    />
                    <span class="ml-2 text-xs text-slate-500 dark:text-slate-400">Ingat saya di perangkat ini</span>
                </label>
            </div>

            <!-- Submit Button -->
            <div>
                <button 
                    type="submit" 
                    :disabled="form.processing"
                    class="w-full py-2.5 bg-violet-600 hover:bg-violet-700 text-white font-medium rounded-lg shadow-lg shadow-violet-500/10 hover:shadow-violet-500/20 text-sm transition-all focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 dark:focus:ring-offset-slate-950 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="form.processing">Menghubungkan...</span>
                    <span v-else>Masuk Sekarang</span>
                </button>
            </div>
        </form>

        <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800/80 text-center">
            <a href="/" class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                ← Kembali ke Halaman Utama
            </a>
        </div>
    </GuestLayout>
</template>
