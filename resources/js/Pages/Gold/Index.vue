<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmDeleteModal from '@/Components/ConfirmDeleteModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/composables/useToast';

const { t } = useI18n();
const toast = useToast();

const props = defineProps({
    holdings: Array,
    market: {
        type: Object,
        default: () => ({ available: false, is_stale: false, karats: {} }),
    },
});

const karats = [9, 14, 18, 21, 22, 24];

function formatMoney(value, currency) {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(value);
}

function formatSignedMoney(value, currency) {
    return (value > 0 ? '+' : '') + formatMoney(value, currency);
}

function formatAmount(value, digits = 2) {
    return new Intl.NumberFormat(undefined, { maximumFractionDigits: digits }).format(value);
}

function formatSignedAmount(value, digits = 2) {
    return (value > 0 ? '+' : '') + formatAmount(value, digits);
}

function formatPercent(value) {
    return `${value > 0 ? '+' : ''}${formatAmount(value, 2)}%`;
}

function formatTime(iso) {
    if (!iso) {
        return '';
    }

    return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(iso));
}

function moveClass(value) {
    if (value === null || value === 0) {
        return 'text-gray-500 dark:text-gray-400';
    }

    return value > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
}

// --- Market -------------------------------------------------------------

const marketAvailable = computed(() => props.market.available === true);

/** The iSagha row for a karat: sell, buy, spread, change and the gap vs the global price. */
function karatRow(karat) {
    return props.market.karats?.[karat] ?? null;
}

const heldKarats = computed(() => new Set(props.holdings.map((holding) => holding.karat)));

/**
 * Only the karats actually sitting in the portfolio — the rest is noise. With
 * nothing recorded yet there is no portfolio to narrow to, so the whole board
 * shows instead of an empty table.
 */
const karatRows = computed(() => {
    const wanted = heldKarats.value.size > 0 ? karats.filter((karat) => heldKarats.value.has(karat)) : karats;

    return wanted.map((karat) => ({ karat, ...karatRow(karat) })).filter((row) => row.sell != null);
});

/**
 * The global spot price for a gram of that purity. It carries no local premium,
 * so it sits beside the Egyptian figures rather than converting them.
 */
function usdPerGram(karat) {
    const pureGram = props.market.usd_per_gram_24k;

    return pureGram == null ? null : (pureGram * karat) / 24;
}

function gapLabel(gap) {
    if (!gap) {
        return null;
    }

    return t(gap.above_global ? 'gold.market.aboveGlobal' : 'gold.market.belowGlobal', {
        amount: formatAmount(gap.amount),
    });
}

// --- Price history ------------------------------------------------------

const charts = computed(() => props.market.charts ?? []);

/** The karats iSagha publishes a chart for, which is fewer than it prices. */
const chartKarats = computed(() => [...new Set(charts.value.map((chart) => chart.karat))].sort((a, b) => a - b));

const historyRanges = ['one-year', 'two-year'];

const historyRange = ref('two-year');

// Open on a karat the portfolio actually holds, so the first chart is the
// relevant one; 21 is the Egyptian default when nothing matches.
const historyKarat = ref(null);

const selectedHistoryKarat = computed(
    () =>
        historyKarat.value ??
        chartKarats.value.find((karat) => heldKarats.value.has(karat)) ??
        (chartKarats.value.includes(21) ? 21 : chartKarats.value[0]),
);

const currentChart = computed(
    () =>
        charts.value.find(
            (chart) => chart.range === historyRange.value && chart.karat === selectedHistoryKarat.value,
        ) ?? null,
);

function rangeLabel(range) {
    return t(range === 'one-year' ? 'gold.history.oneYear' : 'gold.history.twoYear');
}

/**
 * Always the English rendering, in either language. iSagha mirrors its Arabic
 * charts to run right-to-left, which reads as a falling line when the price
 * rose. A chart's time axis is worth more than its labels being translated.
 */
function chartUrl(chart) {
    return chart.url_en;
}

const growthLabel = computed(() => {
    const growth = currentChart.value?.growth;

    if (growth == null) {
        return null;
    }

    return t(growth >= 0 ? 'gold.history.growthUp' : 'gold.history.growthDown', {
        percent: `${formatAmount(Math.abs(growth))}%`,
        range: rangeLabel(historyRange.value),
    });
});

// --- Calculator ---------------------------------------------------------

const calculator = ref({ grams: 8, karat: 21, manufacturing: 0, paid: '' });

/**
 * A scratch pad over today's prices: nothing here is saved, it just answers
 * "what would this piece cost, and what would it fetch back".
 */
const calculation = computed(() => {
    const karat = Number(calculator.value.karat);
    const grams = Number(calculator.value.grams);
    const row = karatRow(karat);

    if (!row || !(grams > 0)) {
        return null;
    }

    const manufacturing = grams * (Number(calculator.value.manufacturing) || 0);
    const buyCost = grams * row.sell + manufacturing;
    const sellValue = grams * row.buy;
    const paid = calculator.value.paid === '' ? null : Number(calculator.value.paid);
    const pureGram = usdPerGram(karat);

    return {
        buyCost,
        sellValue,
        manufacturing,
        spread: grams * row.spread,
        // What the counter costs you on a same-day round trip: the dealer's
        // margin plus the manufacturing that never comes back.
        spreadLoss: buyCost - sellValue,
        usdValue: pureGram === null ? null : grams * pureGram,
        profit: paid === null || Number.isNaN(paid) ? null : sellValue - paid,
        profitPercent: paid ? ((sellValue - paid) / paid) * 100 : null,
    };
});

// --- Per holding --------------------------------------------------------

function holdingGrams(holding) {
    return Number(holding.grams);
}

/** Grams restated as pure gold, so mixed karats can be added up. */
function holdingPureGrams(holding) {
    return (holdingGrams(holding) * holding.karat) / 24;
}

function holdingManufacturingCost(holding) {
    return holdingGrams(holding) * Number(holding.manufacturing_cost_per_gram ?? 0);
}

/** Metal value at the price a dealer sells that karat for today. */
function holdingMetalValue(holding) {
    const row = karatRow(holding.karat);

    return row ? holdingGrams(holding) * row.sell : null;
}

/** What buying this same piece would cost today, manufacturing included. */
function holdingReplacementValue(holding) {
    const metalValue = holdingMetalValue(holding);

    return metalValue === null ? null : metalValue + holdingManufacturingCost(holding);
}

/**
 * What a dealer would actually hand over for it today. Gold is bought back by
 * weight at the buy price, so the manufacturing you paid for is not part of it.
 */
function holdingResaleValue(holding) {
    const row = karatRow(holding.karat);

    return row ? holdingGrams(holding) * row.buy : null;
}

function holdingValueUsd(holding) {
    const usdPerGram = props.market.usd_per_gram_24k;

    if (usdPerGram == null) {
        return null;
    }

    return holdingPureGrams(holding) * usdPerGram;
}

function holdingTodayChange(holding) {
    const row = karatRow(holding.karat);

    if (!row || row.change == null) {
        return null;
    }

    return holdingGrams(holding) * row.change;
}

function holdingPurchasePrice(holding) {
    return holding.purchase_price != null ? Number(holding.purchase_price) : null;
}

/** Profit if sold today: what a dealer pays, less what the piece cost you. */
function holdingNetProfitEgp(holding) {
    const purchasePrice = holdingPurchasePrice(holding);
    const resaleValue = holdingResaleValue(holding);

    if (purchasePrice === null || resaleValue === null) {
        return null;
    }

    return resaleValue - purchasePrice;
}

function holdingProfitPercent(holding) {
    const purchasePrice = holdingPurchasePrice(holding);
    const profit = holdingNetProfitEgp(holding);

    if (profit === null || !purchasePrice) {
        return null;
    }

    return (profit / purchasePrice) * 100;
}

// --- Totals -------------------------------------------------------------

function sum(items, fn) {
    return items.reduce((total, item) => total + fn(item), 0);
}

const totalGrams = computed(() => sum(props.holdings, holdingGrams));

const totalResaleValue = computed(() =>
    marketAvailable.value ? sum(props.holdings, (holding) => holdingResaleValue(holding) ?? 0) : null,
);

const totalValueUsd = computed(() =>
    props.market.usd_per_gram_24k == null ? null : sum(props.holdings, (holding) => holdingValueUsd(holding) ?? 0),
);

const totalTodayChange = computed(() =>
    marketAvailable.value ? sum(props.holdings, (holding) => holdingTodayChange(holding) ?? 0) : null,
);

const holdingsWithPurchasePrice = computed(() =>
    props.holdings.filter((holding) => holdingPurchasePrice(holding) !== null),
);

const totalInvested = computed(() => sum(holdingsWithPurchasePrice.value, holdingPurchasePrice));

const totalNetProfit = computed(() =>
    marketAvailable.value && holdingsWithPurchasePrice.value.length > 0
        ? sum(holdingsWithPurchasePrice.value, (holding) => holdingNetProfitEgp(holding) ?? 0)
        : null,
);

const totalProfitPercent = computed(() => {
    if (totalNetProfit.value === null || !totalInvested.value) {
        return null;
    }

    return (totalNetProfit.value / totalInvested.value) * 100;
});

// --- Form ---------------------------------------------------------------

const showModal = ref(false);
const editing = ref(null);

const form = useForm({
    grams: '',
    karat: 21,
    manufacturing_cost_per_gram: 0,
    purchase_price: '',
    note: '',
});

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.karat = 21;
    form.manufacturing_cost_per_gram = 0;
    showModal.value = true;
}

function openEdit(holding) {
    editing.value = holding;
    form.clearErrors();
    form.grams = holding.grams;
    form.karat = holding.karat;
    form.manufacturing_cost_per_gram = holding.manufacturing_cost_per_gram ?? 0;
    form.purchase_price = holding.purchase_price ?? '';
    form.note = holding.note ?? '';
    showModal.value = true;
}

function submit() {
    if (editing.value) {
        form.put(route('gold-holdings.update', editing.value.id), {
            onSuccess: () => {
                showModal.value = false;
                toast.success(t('toast.updated'));
            },
            onError: () => toast.error(t('toast.error')),
        });
    } else {
        form.post(route('gold-holdings.store'), {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
                toast.success(t('toast.created'));
            },
            onError: () => toast.error(t('toast.error')),
        });
    }
}

// Delete confirmation
const confirmingDelete = ref(null);
const deleteForm = useForm({});

function askDelete(holding) {
    confirmingDelete.value = holding;
}

function cancelDelete() {
    confirmingDelete.value = null;
}

function confirmDelete() {
    deleteForm.delete(route('gold-holdings.destroy', confirmingDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingDelete.value = null;
            toast.success(t('toast.deleted'));
        },
        onError: () => toast.error(t('toast.error')),
    });
}
</script>

<template>
    <Head :title="t('gold.title')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ t('gold.title') }}
                </h2>
                <PrimaryButton @click="openCreate">{{ t('gold.addGold') }}</PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                <!-- Portfolio -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="flex flex-wrap items-start justify-between gap-4 p-4">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ t('gold.resaleValue') }}</div>
                            <div class="text-3xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ totalResaleValue !== null ? formatMoney(totalResaleValue, 'EGP') : t('gold.priceUnavailable') }}
                            </div>
                            <div class="text-xs text-gray-400 dark:text-gray-500">{{ t('gold.resaleValueHint') }}</div>
                        </div>
                        <div v-if="totalTodayChange !== null" class="text-end">
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ t('gold.todayChange') }}</div>
                            <div class="text-xl font-semibold" :class="moveClass(totalTodayChange)">
                                <bdi>{{ formatSignedMoney(totalTodayChange, 'EGP') }}</bdi>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="totalNetProfit !== null"
                        class="flex flex-wrap items-center justify-between gap-4 border-t border-gray-100 p-4 dark:border-gray-700"
                    >
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ t('gold.totalInvested') }}</div>
                            <div class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ formatMoney(totalInvested, 'EGP') }}</div>
                        </div>
                        <div class="text-end">
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ t('gold.netProfit') }}</div>
                            <div class="text-lg font-medium" :class="moveClass(totalNetProfit)">
                                <bdi>{{ formatSignedMoney(totalNetProfit, 'EGP') }}</bdi>
                                <span v-if="totalProfitPercent !== null" class="text-sm"><bdi>({{ formatPercent(totalProfitPercent) }})</bdi></span>
                            </div>
                        </div>
                    </div>

                    <dl class="grid grid-cols-2 gap-px border-t border-gray-100 bg-gray-100 dark:border-gray-700 dark:bg-gray-700">
                        <div class="bg-white p-4 dark:bg-gray-800">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ t('gold.totalGrams') }}</dt>
                            <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ formatAmount(totalGrams) }} g</dd>
                        </div>
                        <div class="bg-white p-4 dark:bg-gray-800">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ t('gold.globalValueUsd') }}</dt>
                            <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ totalValueUsd !== null ? formatMoney(totalValueUsd, 'USD') : '—' }}
                            </dd>
                        </div>
                    </dl>

                    <div
                        class="flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-gray-100 px-4 py-2 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400"
                    >
                        <span class="flex items-center gap-1.5">
                            <span class="relative flex h-2 w-2">
                                <span
                                    class="absolute inline-flex h-full w-full rounded-full opacity-75"
                                    :class="market.is_stale ? 'bg-amber-400' : 'animate-ping bg-green-400'"
                                ></span>
                                <span
                                    class="relative inline-flex h-2 w-2 rounded-full"
                                    :class="market.is_stale ? 'bg-amber-500' : 'bg-green-500'"
                                ></span>
                            </span>
                            {{ t('gold.liveMarketValue') }}
                        </span>
                        <span v-if="market.updated_at">{{ t('gold.market.updatedAt', { time: formatTime(market.updated_at) }) }}</span>
                        <span v-if="market.is_stale" class="text-amber-600 dark:text-amber-400">{{ t('gold.market.stale') }}</span>
                        <a
                            v-if="market.source_url"
                            :href="market.source_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="underline hover:text-gray-700 dark:hover:text-gray-200"
                        >
                            {{ t('gold.market.source') }}
                        </a>
                        <Link
                            :href="route('gold-holdings.index', { refresh: 1 })"
                            class="underline hover:text-gray-700 dark:hover:text-gray-200"
                        >
                            {{ t('gold.market.refresh') }}
                        </Link>
                    </div>
                </div>

                <!-- Live market -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="flex flex-wrap items-baseline justify-between gap-2 px-4 py-3">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200">{{ t('gold.market.title') }}</h3>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ t('gold.market.egpPerGram') }}</span>
                    </div>

                    <div v-if="!marketAvailable" class="px-4 pb-4 text-sm text-gray-500 dark:text-gray-400">
                        {{ t('gold.market.unavailable') }}
                    </div>

                    <template v-else>
                        <div class="overflow-x-auto">
                            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2 text-start text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                                            {{ t('gold.market.karat') }}
                                        </th>
                                        <th
                                            class="px-4 py-2 text-end text-xs font-medium uppercase text-gray-500 dark:text-gray-400"
                                            :title="t('gold.market.sellHint')"
                                        >
                                            {{ t('gold.market.sell') }}
                                        </th>
                                        <th
                                            class="px-4 py-2 text-end text-xs font-medium uppercase text-gray-500 dark:text-gray-400"
                                            :title="t('gold.market.buyHint')"
                                        >
                                            {{ t('gold.market.buy') }}
                                        </th>
                                        <th
                                            v-if="market.usd_per_gram_24k != null"
                                            class="px-4 py-2 text-end text-xs font-medium uppercase text-gray-500 dark:text-gray-400"
                                            :title="t('gold.market.gramUsdHint')"
                                        >
                                            {{ t('gold.market.gramUsd') }}
                                        </th>
                                        <th class="px-4 py-2 text-end text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                                            {{ t('gold.market.spread') }}
                                        </th>
                                        <th class="px-4 py-2 text-end text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                                            {{ t('gold.market.change') }}
                                        </th>
                                        <th class="px-4 py-2 text-end text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                                            {{ t('gold.market.changePercent') }}
                                        </th>
                                        <th class="px-4 py-2 text-end text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                                            {{ t('gold.market.gapVsGlobal') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <tr v-for="row in karatRows" :key="row.karat">
                                        <td class="whitespace-nowrap px-4 py-2 text-sm font-semibold text-gray-800 dark:text-gray-200">
                                            {{ row.karat }}K
                                            <span
                                                v-if="row.derived"
                                                class="ms-1 text-[10px] font-normal text-gray-400 dark:text-gray-500"
                                                :title="t('gold.market.derived')"
                                            >
                                                *
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2 text-end text-sm tabular-nums text-gray-700 dark:text-gray-300">
                                            {{ formatAmount(row.sell) }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2 text-end text-sm tabular-nums text-gray-700 dark:text-gray-300">
                                            {{ formatAmount(row.buy) }}
                                        </td>
                                        <td
                                            v-if="market.usd_per_gram_24k != null"
                                            class="whitespace-nowrap px-4 py-2 text-end text-sm tabular-nums text-gray-500 dark:text-gray-400"
                                        >
                                            {{ formatMoney(usdPerGram(row.karat), 'USD') }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2 text-end text-sm tabular-nums text-gray-500 dark:text-gray-400">
                                            {{ formatAmount(row.spread) }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2 text-end text-sm tabular-nums" :class="moveClass(row.change)">
                                            <bdi>{{ row.change == null ? '—' : formatSignedAmount(row.change) }}</bdi>
                                        </td>
                                        <td
                                            class="whitespace-nowrap px-4 py-2 text-end text-sm tabular-nums"
                                            :class="moveClass(row.change_percent)"
                                        >
                                            <bdi>{{ row.change_percent == null ? '—' : formatPercent(row.change_percent) }}</bdi>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2 text-end text-xs text-gray-500 dark:text-gray-400">
                                            {{ gapLabel(row.sell_gap) ?? '—' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <dl class="grid grid-cols-1 gap-px border-t border-gray-100 bg-gray-100 dark:border-gray-700 dark:bg-gray-700">
                            <div v-if="market.ounce" class="bg-white p-4 dark:bg-gray-800">
                                <dt class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ t('gold.market.ounce') }}
                                    <span class="text-gray-400 dark:text-gray-500">· {{ t('gold.market.ounceHint') }}</span>
                                </dt>
                                <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ formatMoney(market.ounce.sell, 'USD') }}
                                </dd>
                                <dd class="text-xs" :class="moveClass(market.ounce.change_percent)">
                                    <bdi>{{ market.ounce.change_percent == null ? '' : formatPercent(market.ounce.change_percent) }}</bdi>
                                </dd>
                            </div>
                        </dl>
                    </template>
                </div>

                <!-- Price history -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200">{{ t('gold.history.title') }}</h3>
                        <div v-if="growthLabel" class="text-sm font-medium" :class="moveClass(currentChart.growth)">
                            <bdi>{{ growthLabel }}</bdi>
                        </div>
                    </div>

                    <div v-if="charts.length === 0" class="px-4 pb-4 text-sm text-gray-500 dark:text-gray-400">
                        {{ t('gold.history.unavailable') }}
                    </div>

                    <template v-else>
                        <div class="flex flex-wrap items-center gap-2 px-4 pb-3">
                            <button
                                v-for="range in historyRanges"
                                :key="range"
                                type="button"
                                @click="historyRange = range"
                                class="rounded-md px-3 py-1.5 text-sm font-medium"
                                :class="range === historyRange
                                    ? 'bg-gray-800 text-white dark:bg-gray-200 dark:text-gray-800'
                                    : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'"
                            >
                                {{ rangeLabel(range) }}
                            </button>

                            <span class="mx-1 h-5 w-px bg-gray-200 dark:bg-gray-700"></span>

                            <button
                                v-for="karat in chartKarats"
                                :key="karat"
                                type="button"
                                @click="historyKarat = karat"
                                class="rounded-md px-3 py-1.5 text-sm font-medium"
                                :class="karat === selectedHistoryKarat
                                    ? 'bg-gray-800 text-white dark:bg-gray-200 dark:text-gray-800'
                                    : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'"
                            >
                                {{ karat }}K
                            </button>
                        </div>

                        <!-- iSagha renders these server side as images, on a light
                             backing that has to stay light to remain readable. -->
                        <div v-if="currentChart" class="px-4 pb-4">
                            <div class="overflow-hidden rounded-lg bg-white p-2">
                                <img
                                    :src="chartUrl(currentChart)"
                                    :alt="t('gold.history.chartAlt', { karat: currentChart.karat, range: rangeLabel(currentChart.range) })"
                                    class="w-full"
                                    loading="lazy"
                                />
                            </div>
                            <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">{{ t('gold.history.source') }}</p>
                        </div>
                    </template>
                </div>

                <!-- Calculator -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="px-4 py-3">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200">{{ t('gold.calculator.title') }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('gold.calculator.subtitle') }}</p>
                    </div>

                    <div v-if="!marketAvailable" class="px-4 pb-4 text-sm text-gray-500 dark:text-gray-400">
                        {{ t('gold.calculator.needsPrices') }}
                    </div>

                    <template v-else>
                        <div class="grid grid-cols-1 gap-4 px-4 pb-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <InputLabel :value="t('gold.calculator.grams')" />
                                <TextInput v-model="calculator.grams" type="number" step="0.01" min="0" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel :value="t('gold.calculator.karat')" />
                                <SelectInput v-model="calculator.karat" class="mt-1 block w-full">
                                    <option v-for="k in karats" :key="k" :value="k">{{ k }}K</option>
                                </SelectInput>
                            </div>
                            <div>
                                <InputLabel :value="t('gold.calculator.manufacturing')" />
                                <TextInput v-model="calculator.manufacturing" type="number" step="0.01" min="0" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel :value="t('gold.calculator.paid')" />
                                <TextInput
                                    v-model="calculator.paid"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    :placeholder="t('gold.calculator.paidPlaceholder')"
                                    class="mt-1 block w-full"
                                />
                            </div>
                        </div>

                        <dl
                            v-if="calculation"
                            class="grid grid-cols-2 gap-px border-t border-gray-100 bg-gray-100 lg:grid-cols-4 dark:border-gray-700 dark:bg-gray-700"
                        >
                            <div class="bg-white p-4 dark:bg-gray-800">
                                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ t('gold.calculator.buyCost') }}</dt>
                                <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ formatMoney(calculation.buyCost, 'EGP') }}
                                </dd>
                                <!-- Without it the figure is the bare metal, which reads
                                     the same as a piece whose manufacturing was forgotten. -->
                                <dd class="text-xs text-gray-400 dark:text-gray-500">
                                    {{
                                        calculation.manufacturing > 0
                                            ? t('gold.includesManufacturing', { amount: formatMoney(calculation.manufacturing, 'EGP') })
                                            : t('gold.calculator.noManufacturing')
                                    }}
                                </dd>
                            </div>
                            <div class="bg-white p-4 dark:bg-gray-800">
                                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ t('gold.calculator.sellValue') }}</dt>
                                <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ formatMoney(calculation.sellValue, 'EGP') }}
                                </dd>
                            </div>
                            <div class="bg-white p-4 dark:bg-gray-800">
                                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ t('gold.calculator.spreadLoss') }}</dt>
                                <dd class="mt-1 text-lg font-semibold text-red-600 dark:text-red-400">
                                    <bdi>{{ formatSignedMoney(-calculation.spreadLoss, 'EGP') }}</bdi>
                                </dd>
                                <dd class="text-xs text-gray-400 dark:text-gray-500">
                                    {{
                                        t('gold.calculator.spreadBreakdown', {
                                            spread: formatMoney(calculation.spread, 'EGP'),
                                            manufacturing: formatMoney(calculation.manufacturing, 'EGP'),
                                        })
                                    }}
                                </dd>
                            </div>
                            <div class="bg-white p-4 dark:bg-gray-800">
                                <dt class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ calculation.profit === null ? t('gold.calculator.usdValue') : t('gold.calculator.profit') }}
                                </dt>
                                <dd v-if="calculation.profit === null" class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ calculation.usdValue === null ? '—' : formatMoney(calculation.usdValue, 'USD') }}
                                </dd>
                                <dd v-else class="mt-1 text-lg font-semibold" :class="moveClass(calculation.profit)">
                                    <bdi>{{ formatSignedMoney(calculation.profit, 'EGP') }}</bdi>
                                    <span v-if="calculation.profitPercent !== null" class="text-sm">
                                        <bdi>({{ formatPercent(calculation.profitPercent) }})</bdi>
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </template>
                </div>

                <!-- Holdings -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div v-for="holding in holdings" :key="holding.id" class="flex items-start justify-between gap-4 p-4">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                                    <span class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                        <template v-if="holdingResaleValue(holding) !== null">
                                            {{ formatMoney(holdingResaleValue(holding), 'EGP') }}
                                        </template>
                                        <template v-else>{{ t('gold.priceUnavailable') }}</template>
                                    </span>
                                    <span v-if="holdingValueUsd(holding) !== null" class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatMoney(holdingValueUsd(holding), 'USD') }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-400 dark:text-gray-500">{{ t('gold.resaleValueHint') }}</div>

                                <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                    <span
                                        class="rounded-full bg-gray-100 px-3 py-1 text-base font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200"
                                    >
                                        {{ holding.grams }}g &middot; {{ holding.karat }}K
                                    </span>
                                    <span
                                        v-if="holdingTodayChange(holding) !== null"
                                        class="text-xs font-medium"
                                        :class="moveClass(holdingTodayChange(holding))"
                                    >
                                        <bdi>{{ formatSignedMoney(holdingTodayChange(holding), 'EGP') }}</bdi> {{ t('gold.todayChange') }}
                                    </span>
                                </div>

                                <div v-if="holding.note" class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">
                                    {{ holding.note }}
                                </div>

                                <div class="mt-3 flex flex-wrap gap-x-6 gap-y-2">
                                    <div v-if="holdingReplacementValue(holding) !== null">
                                        <div class="text-xs text-gray-400 dark:text-gray-500">{{ t('gold.replacementValue') }}</div>
                                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                            {{ formatMoney(holdingReplacementValue(holding), 'EGP') }}
                                            <span v-if="holdingManufacturingCost(holding) > 0" class="font-normal text-gray-400 dark:text-gray-500">
                                                ({{ t('gold.includesManufacturing', { amount: formatMoney(holdingManufacturingCost(holding), 'EGP') }) }})
                                            </span>
                                        </div>
                                    </div>
                                    <div v-if="holdingPurchasePrice(holding) !== null">
                                        <div class="text-xs text-gray-400 dark:text-gray-500">{{ t('gold.boughtFor') }}</div>
                                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                            {{ formatMoney(holdingPurchasePrice(holding), 'EGP') }}
                                        </div>
                                    </div>
                                    <div v-if="holdingNetProfitEgp(holding) !== null">
                                        <div class="text-xs text-gray-400 dark:text-gray-500">{{ t('gold.netProfit') }}</div>
                                        <div class="text-sm font-semibold" :class="moveClass(holdingNetProfitEgp(holding))">
                                            <bdi>{{ formatSignedMoney(holdingNetProfitEgp(holding), 'EGP') }}</bdi>
                                            <span v-if="holdingProfitPercent(holding) !== null">
                                                <bdi>({{ formatPercent(holdingProfitPercent(holding)) }})</bdi>
                                            </span>
                                        </div>
                                    </div>
                                    <div v-else-if="holdingPurchasePrice(holding) === null" class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ t('gold.noPurchasePrice') }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <SecondaryButton @click="openEdit(holding)">{{ t('common.edit') }}</SecondaryButton>
                                <DangerButton @click="askDelete(holding)">{{ t('common.delete') }}</DangerButton>
                            </div>
                        </div>
                        <div v-if="holdings.length === 0" class="p-6 text-center text-gray-500 dark:text-gray-400">
                            {{ t('gold.noHoldings') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <form @submit.prevent="submit" class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ editing ? t('gold.editGold') : t('gold.newGold') }}
                </h2>

                <div class="mt-4">
                    <InputLabel :value="t('gold.grams')" />
                    <TextInput v-model="form.grams" type="number" step="1" min="1" class="mt-1 block w-full" />
                    <InputError :message="form.errors.grams" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel :value="t('gold.karat')" />
                    <SelectInput v-model="form.karat" class="mt-1 block w-full">
                        <option v-for="k in karats" :key="k" :value="k">{{ k }}K</option>
                    </SelectInput>
                    <InputError :message="form.errors.karat" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel :value="t('gold.manufacturingCostPerGram')" />
                    <TextInput
                        v-model="form.manufacturing_cost_per_gram"
                        type="number"
                        step="0.01"
                        min="0"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.manufacturing_cost_per_gram" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel :value="t('gold.purchasePrice')" />
                    <TextInput
                        v-model="form.purchase_price"
                        type="number"
                        step="0.01"
                        min="0"
                        :placeholder="t('gold.purchasePricePlaceholder')"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.purchase_price" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel :value="t('gold.note')" />
                    <TextInput v-model="form.note" class="mt-1 block w-full" />
                    <InputError :message="form.errors.note" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <SecondaryButton @click="showModal = false">{{ t('common.cancel') }}</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">{{ t('common.save') }}</PrimaryButton>
                </div>
            </form>
        </Modal>

        <ConfirmDeleteModal
            :show="!!confirmingDelete"
            :title="t('gold.deleteTitle')"
            :message="t('gold.deleteMessage')"
            :processing="deleteForm.processing"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />
    </AuthenticatedLayout>
</template>
