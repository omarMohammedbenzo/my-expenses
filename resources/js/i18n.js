import { createI18n } from 'vue-i18n';
import en from '@/lang/en.json';
import ar from '@/lang/ar.json';

export const i18n = createI18n({
    legacy: false,
    locale: localStorage.getItem('locale') || 'en',
    fallbackLocale: 'en',
    messages: { en, ar },
});
