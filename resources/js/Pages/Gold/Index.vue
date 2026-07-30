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
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/composables/useToast';

const { t } = useI18n();
const toast = useToast();

const props = defineProps({
    holdings: Array,
    goldPricePerGramUsd: {
        type: Number,
        default: null,
    },
    goldEgpPricesByKarat: {
        type: Object,
        default: () => ({}),
    },
});

const karats = [9, 14, 18, 21, 22, 24];

function formatMoney(value, currency) {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(value);
}

function holdingValueUsd(holding) {
    if (props.goldPricePerGramUsd === null) {
        return null;
    }

    return holding.grams * props.goldPricePerGramUsd * (holding.karat / 24);
}

function holdingGoldValueEgp(holding) {
    const pricePerGram = props.goldEgpPricesByKarat[holding.karat];

    if (pricePerGram == null) {
        return null;
    }

    return holding.grams * pricePerGram;
}

function holdingManufacturingCost(holding) {
    return holding.grams * Number(holding.manufacturing_cost_per_gram ?? 0);
}

function holdingValueEgp(holding) {
    const goldValue = holdingGoldValueEgp(holding);

    if (goldValue === null) {
        return null;
    }

    return goldValue + holdingManufacturingCost(holding);
}

const totalValueUsd = computed(() => {
    if (props.goldPricePerGramUsd === null) {
        return null;
    }

    return props.holdings.reduce((sum, holding) => sum + holdingValueUsd(holding), 0);
});

const totalValueEgp = computed(() => {
    if (props.holdings.some((holding) => holdingValueEgp(holding) === null)) {
        return null;
    }

    return props.holdings.reduce((sum, holding) => sum + holdingValueEgp(holding), 0);
});

const totalManufacturingCost = computed(() =>
    props.holdings.reduce((sum, holding) => sum + holdingManufacturingCost(holding), 0),
);

function holdingPurchasePrice(holding) {
    return holding.purchase_price != null ? Number(holding.purchase_price) : null;
}

function holdingNetProfitEgp(holding) {
    const purchasePrice = holdingPurchasePrice(holding);
    const currentValue = holdingValueEgp(holding);

    if (purchasePrice === null || currentValue === null) {
        return null;
    }

    return currentValue - purchasePrice;
}

const holdingsWithPurchasePrice = computed(() =>
    props.holdings.filter((holding) => holdingPurchasePrice(holding) !== null),
);

const totalInvested = computed(() =>
    holdingsWithPurchasePrice.value.reduce((sum, holding) => sum + holdingPurchasePrice(holding), 0),
);

const totalNetProfit = computed(() => {
    if (holdingsWithPurchasePrice.value.some((holding) => holdingValueEgp(holding) === null)) {
        return null;
    }

    return holdingsWithPurchasePrice.value.reduce((sum, holding) => sum + holdingNetProfitEgp(holding), 0);
});

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
            <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="flex items-center justify-between p-4">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ t('gold.totalValue') }}</div>
                            <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ totalValueUsd !== null ? formatMoney(totalValueUsd, 'USD') : t('gold.priceUnavailable') }}
                            </div>
                            <div v-if="totalValueEgp !== null" class="text-lg text-gray-600 dark:text-gray-300">
                                {{ formatMoney(totalValueEgp, 'EGP') }}
                            </div>
                            <div v-if="totalManufacturingCost > 0" class="text-xs text-gray-400 dark:text-gray-500">
                                {{ t('gold.includesManufacturing', { amount: formatMoney(totalManufacturingCost, 'EGP') }) }}
                            </div>
                        </div>
                        <div v-if="goldPricePerGramUsd !== null || goldEgpPricesByKarat[24] != null" class="text-right text-sm text-gray-500 dark:text-gray-400">
                            {{ t('gold.pricePerGram24k') }}<br />
                            <template v-if="goldPricePerGramUsd !== null">{{ formatMoney(goldPricePerGramUsd, 'USD') }}<br /></template>
                            <template v-if="goldEgpPricesByKarat[24] != null">{{ formatMoney(goldEgpPricesByKarat[24], 'EGP') }}</template>
                        </div>
                    </div>
                    <div v-if="totalNetProfit !== null" class="flex items-center justify-between border-t border-gray-100 p-4 dark:border-gray-700">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ t('gold.totalInvested') }}</div>
                            <div class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ formatMoney(totalInvested, 'EGP') }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ t('gold.netProfit') }}</div>
                            <div
                                class="text-lg font-medium"
                                :class="totalNetProfit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                            >
                                {{ totalNetProfit >= 0 ? '+' : '' }}{{ formatMoney(totalNetProfit, 'EGP') }}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 border-t border-gray-100 px-4 py-2 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-green-500"></span>
                        </span>
                        {{ t('gold.liveMarketValue') }}
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div
                            v-for="holding in holdings"
                            :key="holding.id"
                            class="flex items-start justify-between gap-4 p-4"
                        >
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                                    <span class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                        <template v-if="holdingValueEgp(holding) !== null">{{ formatMoney(holdingValueEgp(holding), 'EGP') }}</template>
                                        <template v-else-if="holdingValueUsd(holding) !== null">{{ formatMoney(holdingValueUsd(holding), 'USD') }}</template>
                                        <template v-else>{{ t('gold.priceUnavailable') }}</template>
                                    </span>
                                    <span v-if="holdingValueEgp(holding) !== null && holdingValueUsd(holding) !== null" class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatMoney(holdingValueUsd(holding), 'USD') }}
                                    </span>
                                </div>

                                <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-gray-100 px-3 py-1 text-base font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                        {{ holding.grams }}g &middot; {{ holding.karat }}K
                                    </span>
                                    <span v-if="holdingManufacturingCost(holding) > 0" class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ t('gold.includesManufacturing', { amount: formatMoney(holdingManufacturingCost(holding), 'EGP') }) }}
                                    </span>
                                </div>

                                <div v-if="holding.note" class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">
                                    {{ holding.note }}
                                </div>

                                <div v-if="holdingPurchasePrice(holding) !== null" class="mt-3 flex flex-wrap gap-x-6 gap-y-2">
                                    <div>
                                        <div class="text-xs text-gray-400 dark:text-gray-500">{{ t('gold.boughtFor') }}</div>
                                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                            {{ formatMoney(holdingPurchasePrice(holding), 'EGP') }}
                                        </div>
                                    </div>
                                    <div v-if="holdingNetProfitEgp(holding) !== null">
                                        <div class="text-xs text-gray-400 dark:text-gray-500">{{ t('gold.netProfit') }}</div>
                                        <div
                                            class="text-sm font-semibold"
                                            :class="holdingNetProfitEgp(holding) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                                        >
                                            {{ holdingNetProfitEgp(holding) >= 0 ? '+' : '' }}{{ formatMoney(holdingNetProfitEgp(holding), 'EGP') }}
                                        </div>
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
