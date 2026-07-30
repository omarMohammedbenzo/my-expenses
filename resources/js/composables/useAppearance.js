import { onMounted, ref } from 'vue';

const STORAGE_KEY = 'appearance';

function prefersDark() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

function applyAppearance(value) {
    const isDark = value === 'dark' || (value === 'system' && prefersDark());
    document.documentElement.classList.toggle('dark', isDark);
}

const appearance = ref(localStorage.getItem(STORAGE_KEY) || 'system');

function updateAppearance(value) {
    appearance.value = value;
    localStorage.setItem(STORAGE_KEY, value);
    applyAppearance(value);
}

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (appearance.value === 'system') {
        applyAppearance('system');
    }
});

export function useAppearance() {
    onMounted(() => applyAppearance(appearance.value));

    return { appearance, updateAppearance };
}
