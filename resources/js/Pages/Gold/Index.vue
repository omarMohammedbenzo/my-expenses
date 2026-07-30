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

function holdingValueEgp(holding) {
    const pricePerGram = props.goldEgpPricesByKarat[holding.karat];

    if (pricePerGram == null) {
        return null;
    }

    return holding.grams * pricePerGram;
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

const showModal = ref(false);
const editing = ref(null);

const form = useForm({
    grams: '',
    karat: 21,
    note: '',
});

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.karat = 21;
    showModal.value = true;
}

function openEdit(holding) {
    editing.value = holding;
    form.clearErrors();
    form.grams = holding.grams;
    form.karat = holding.karat;
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
                        </div>
                        <div v-if="goldPricePerGramUsd !== null || goldEgpPricesByKarat[24] != null" class="text-right text-sm text-gray-500 dark:text-gray-400">
                            {{ t('gold.pricePerGram24k') }}<br />
                            <template v-if="goldPricePerGramUsd !== null">{{ formatMoney(goldPricePerGramUsd, 'USD') }}<br /></template>
                            <template v-if="goldEgpPricesByKarat[24] != null">{{ formatMoney(goldEgpPricesByKarat[24], 'EGP') }}</template>
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
                            class="flex items-center justify-between p-4"
                        >
                            <div>
                                <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ holding.grams }}g &middot; {{ holding.karat }}K
                                </div>
                                <div v-if="holding.note" class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ holding.note }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    <template v-if="holdingValueUsd(holding) !== null">
                                        {{ formatMoney(holdingValueUsd(holding), 'USD') }}
                                        <span v-if="holdingValueEgp(holding) !== null">
                                            &middot; {{ formatMoney(holdingValueEgp(holding), 'EGP') }}
                                        </span>
                                    </template>
                                    <template v-else>{{ t('gold.priceUnavailable') }}</template>
                                </div>
                            </div>
                            <div class="flex gap-2">
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
