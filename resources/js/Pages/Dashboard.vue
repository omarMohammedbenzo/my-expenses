<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ColorDot from '@/Components/ColorDot.vue';
import MonthPicker from '@/Components/MonthPicker.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    month: String,
    currency: String,
    enabledCurrencies: Array,
    totalSpent: [Number, String],
    topCategory: Object,
    categoryBreakdown: Array,
    accountBalances: Array,
    totalBalance: [Number, String],
    pendingIncome: Array,
});

function goTo(params) {
    router.get(
        route('dashboard'),
        { month: props.month, currency: props.currency, ...params },
        { preserveState: true },
    );
}

const monthValue = computed({
    get: () => props.month,
    set: (value) => goTo({ month: value }),
});

const chartSeries = computed(() => props.categoryBreakdown.map((c) => Number(c.total)));
const chartLabels = computed(() =>
    props.categoryBreakdown.map((c) => `${c.icon ? c.icon + ' ' : ''}${c.name}`),
);
const chartOptions = computed(() => ({
    labels: chartLabels.value,
    legend: { position: 'bottom' },
    chart: { type: 'donut' },
    dataLabels: { enabled: true },
    theme: { mode: window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light' },
}));
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ t('dashboard.title') }}
                </h2>
                <MonthPicker v-model="monthValue" />
            </div>
        </template>

        <div class="space-y-6 py-12">
            <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
                <div v-if="pendingIncome.length > 0" class="mb-6 overflow-hidden bg-yellow-50 shadow-sm sm:rounded-lg dark:bg-yellow-900/20">
                    <div class="p-4">
                        <div v-for="t2 in pendingIncome" :key="t2.id" class="text-sm text-yellow-800 dark:text-yellow-200">
                            🔔 {{ t('dashboard.pendingReminder', { name: t2.income_source?.name, date: t2.expected_date }) }}
                            <Link :href="route('income-sources.index')" class="underline">{{ t('dashboard.confirmNow') }}</Link>
                        </div>
                    </div>
                </div>

                <!-- Currency tabs -->
                <div class="mb-6 flex gap-2">
                    <button
                        v-for="c in enabledCurrencies"
                        :key="c"
                        @click="goTo({ currency: c })"
                        class="rounded-md px-4 py-2 text-sm font-medium"
                        :class="c === currency
                            ? 'bg-gray-800 text-white dark:bg-gray-200 dark:text-gray-800'
                            : 'bg-white text-gray-700 shadow-sm dark:bg-gray-800 dark:text-gray-300'"
                    >
                        {{ c }}
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ t('dashboard.totalSpent') }}</div>
                        <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-gray-100">
                            {{ totalSpent }} {{ currency }}
                        </div>
                    </div>
                    <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ t('dashboard.topCategory') }}</div>
                        <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-gray-100">
                            <template v-if="topCategory">
                                {{ topCategory.icon ? topCategory.icon + ' ' : '' }}{{ topCategory.name }}
                            </template>
                            <template v-else>&mdash;</template>
                        </div>
                    </div>
                    <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ t('dashboard.currentBalance', { currency }) }}</div>
                        <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-gray-100">
                            {{ totalBalance }} {{ currency }}
                        </div>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <h3 class="mb-4 font-medium text-gray-800 dark:text-gray-200">{{ t('dashboard.spendingByCategory') }}</h3>
                        <apexchart
                            v-if="categoryBreakdown.length > 0"
                            type="donut"
                            :options="chartOptions"
                            :series="chartSeries"
                        />
                        <p v-else class="text-sm text-gray-500 dark:text-gray-400">
                            {{ t('dashboard.noExpenses') }}
                        </p>
                    </div>

                    <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <h3 class="mb-4 font-medium text-gray-800 dark:text-gray-200">
                             {{ t('dashboard.balanceDistribution', { currency }) }}
                        </h3>
                        <div class="space-y-2">
                            <Link
                                v-for="a in accountBalances"
                                :key="a.id"
                                :href="route('accounts.ledger', a.id)"
                                class="flex justify-between border-b border-gray-100 py-1 text-sm transition hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/50"
                            >
                                <span class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                    <ColorDot :color="a.color" />
                                    {{ a.label }}
                                </span>
                                <span class="font-medium text-vue-600 dark:text-vue-400">{{ a.balance }}</span>
                            </Link>
                            <div v-if="accountBalances.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
                                {{ t('dashboard.noAccountsInCurrency', { currency }) }}
                            </div>
                            <div class="flex justify-between border-t border-gray-300 pt-2 text-sm font-semibold text-gray-900 dark:border-gray-600 dark:text-gray-100">
                                <span>{{ t('dashboard.total') }}</span>
                                <span class="text-vue-600 dark:text-vue-400">{{ totalBalance }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
