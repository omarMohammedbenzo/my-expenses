<script setup>
import { computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import AppearanceToggleDropdown from '@/Components/AppearanceToggleDropdown.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});

const features = computed(() => [
    { icon: '🏦', key: 'multiCurrency' },
    { icon: '🧾', key: 'expenses' },
    { icon: '🔔', key: 'income' },
    { icon: '💰', key: 'goldGifts' },
    { icon: '📊', key: 'stats' },
    { icon: '🔒', key: 'private' },
]);
</script>

<template>
    <Head :title="t('welcome.hero.title1')" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <header class="mx-auto flex max-w-6xl items-center justify-between px-6 py-6">
            <div class="flex items-center gap-2">
                <ApplicationLogo class="h-8 w-8" />
                <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Expense Tracker
                </span>
            </div>

            <nav class="flex items-center gap-3">
                <LanguageSwitcher />
                <AppearanceToggleDropdown />

                <template v-if="canLogin">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="rounded-md bg-vue-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-vue-600"
                    >
                        {{ t('nav.dashboard') }}
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                        >
                            {{ t('welcome.nav.login') }}
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="rounded-md bg-vue-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-vue-600"
                        >
                            {{ t('welcome.nav.getStarted') }}
                        </Link>
                    </template>
                </template>
            </nav>
        </header>

        <!-- Hero -->
        <section class="relative overflow-hidden">
            <div class="absolute inset-0 -z-10 bg-gradient-to-br from-vue-50 via-transparent to-transparent dark:from-vue-950/30"></div>

            <div class="mx-auto max-w-4xl px-6 py-20 text-center sm:py-28">
                <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-gray-100 sm:text-5xl">
                    {{ t('welcome.hero.title1') }}
                    <span class="text-vue-500">{{ t('welcome.hero.title2') }}</span>
                </h1>
                <p class="mx-auto mt-6 max-w-2xl text-lg text-gray-600 dark:text-gray-400">
                    {{ t('welcome.hero.subtitle') }}
                </p>

                <div class="mt-10 flex items-center justify-center gap-4">
                    <Link
                        v-if="canRegister && !$page.props.auth.user"
                        :href="route('register')"
                        class="rounded-md bg-vue-500 px-6 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-vue-600"
                    >
                        {{ t('welcome.hero.createAccount') }}
                    </Link>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="rounded-md bg-vue-500 px-6 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-vue-600"
                    >
                        {{ t('welcome.hero.goToDashboard') }}
                    </Link>
                    <Link
                        v-if="canLogin && !$page.props.auth.user"
                        :href="route('login')"
                        class="rounded-md bg-white px-6 py-3 text-base font-semibold text-gray-900 shadow-sm ring-1 ring-gray-200 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-100 dark:ring-gray-700 dark:hover:bg-gray-700"
                    >
                        {{ t('welcome.hero.logIn') }}
                    </Link>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="mx-auto max-w-6xl px-6 pb-24">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="feature in features"
                    :key="feature.key"
                    class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5 dark:bg-gray-800 dark:ring-white/10"
                >
                    <div class="flex size-11 items-center justify-center rounded-full bg-vue-50 text-2xl dark:bg-vue-950/40">
                        {{ feature.icon }}
                    </div>
                    <h3 class="mt-4 font-semibold text-gray-900 dark:text-gray-100">
                        {{ t(`welcome.features.${feature.key}.title`) }}
                    </h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ t(`welcome.features.${feature.key}.description`) }}
                    </p>
                </div>
            </div>
        </section>

        <footer class="border-t border-gray-200 py-8 text-center text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
            {{ t('welcome.footer', { year: new Date().getFullYear() }) }}
        </footer>
    </div>

    <ToastContainer />
</template>
