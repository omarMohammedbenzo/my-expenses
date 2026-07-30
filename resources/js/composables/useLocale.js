import { ref } from 'vue';
import { i18n } from '@/i18n';

const locale = ref(i18n.global.locale.value);

function applyLocale(value) {
    document.documentElement.lang = value;
    document.documentElement.dir = value === 'ar' ? 'rtl' : 'ltr';
}

applyLocale(locale.value);

function setLocale(value) {
    locale.value = value;
    i18n.global.locale.value = value;
    localStorage.setItem('locale', value);
    applyLocale(value);
}

export function useLocale() {
    return { locale, setLocale };
}
