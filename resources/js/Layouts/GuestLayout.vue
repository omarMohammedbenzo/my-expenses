<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import AppearanceToggleDropdown from '@/Components/AppearanceToggleDropdown.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
    <div class="flex min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Branding panel -->
        <div
            class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-gradient-to-br from-vue-500 to-vue-900 p-12 text-white lg:flex"
        >
            <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute -bottom-32 -left-16 h-96 w-96 rounded-full bg-black/10 blur-3xl"></div>

            <Link href="/" class="relative z-10 flex items-center gap-3">
                <div class="rounded-xl bg-white/15 p-2 backdrop-blur">
                    <ApplicationLogo class="h-8 w-8" />
                </div>
                <span class="text-lg font-semibold tracking-tight">Expense Tracker</span>
            </Link>

            <div class="relative z-10 max-w-md">
                <h1 class="text-4xl font-bold leading-tight">
                    {{ t('welcome.hero.title1') }} {{ t('welcome.hero.title2') }}
                </h1>
                <p class="mt-4 text-white/80">
                    {{ t('welcome.hero.subtitle') }}
                </p>
            </div>

            <p class="relative z-10 text-sm text-white/60">
                {{ t('welcome.footer', { year: new Date().getFullYear() }) }}
            </p>
        </div>

        <!-- Form panel -->
        <div class="flex w-full flex-col items-center justify-center px-6 py-12 lg:w-1/2">
            <div class="mb-6 flex w-full max-w-sm items-center justify-between lg:justify-end">
                <Link href="/" class="flex items-center gap-2 lg:hidden">
                    <ApplicationLogo class="h-10 w-10" />
                    <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Expense Tracker
                    </span>
                </Link>

                <div class="flex items-center gap-2">
                    <LanguageSwitcher />
                    <AppearanceToggleDropdown />
                </div>
            </div>

            <div class="w-full max-w-sm">
                <div
                    class="overflow-hidden rounded-2xl bg-white px-8 py-8 shadow-xl ring-1 ring-gray-900/5 dark:bg-gray-800 dark:ring-white/10"
                >
                    <slot />
                </div>
            </div>
        </div>

        <ToastContainer />
    </div>
</template>
