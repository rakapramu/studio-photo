import { ref, onMounted, watch } from 'vue';

export function useDarkMode() {
    const theme = ref('system');

    const applyTheme = (newTheme) => {
        const root = document.documentElement;
        
        if (newTheme === 'dark') {
            root.classList.add('dark');
        } else if (newTheme === 'light') {
            root.classList.remove('dark');
        } else {
            // System preference
            const isSystemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (isSystemDark) {
                root.classList.add('dark');
            } else {
                root.classList.remove('dark');
            }
        }
    };

    const setTheme = (newTheme) => {
        theme.value = newTheme;
        localStorage.setItem('theme', newTheme);
        applyTheme(newTheme);
    };

    onMounted(() => {
        // Load preference from localStorage or default to 'system'
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            theme.value = savedTheme;
        } else {
            theme.value = 'system';
        }
        applyTheme(theme.value);

        // Listen for system changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (theme.value === 'system') {
                if (e.matches) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }
        });
    });

    return {
        theme,
        setTheme,
    };
}
