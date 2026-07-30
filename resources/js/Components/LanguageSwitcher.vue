<script setup>
import { useLocale } from '@/composables/useLocale';

const { locale, setLocale } = useLocale();

function toggleLocale() {
    setLocale(locale.value === 'ar' ? 'en' : 'ar');
}
</script>

<template>
    <button
        type="button"
        @click="toggleLocale"
        class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none dark:bg-gray-800 dark:text-gray-400 dark:hover:text-gray-300"
    >
        {{ locale === 'ar' ? 'EN' : 'AR' }}
    </button>
</template>
