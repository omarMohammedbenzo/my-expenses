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
    sources: Array,
    pending: Array,
    confirmed: Array,
    accounts: Array,
    enabledCurrencies: Array,
});

const today = new Date().toISOString().slice(0, 10);

// Income source modal
const showSourceModal = ref(false);
const editingSource = ref(null);
const sourceForm = useForm({
    name: '',
    amount: '',
    currency: props.enabledCurrencies[0] ?? 'USD',
    frequency: 'monthly',
    expected_day: 1,
    account_id: props.accounts[0]?.id ?? null,
});

function openCreateSource() {
    editingSource.value = null;
    sourceForm.reset();
    sourceForm.clearErrors();
    sourceForm.currency = props.enabledCurrencies[0] ?? 'USD';
    sourceForm.frequency = 'monthly';
    sourceForm.expected_day = 1;
    sourceForm.account_id = props.accounts[0]?.id ?? null;
    showSourceModal.value = true;
}

function openEditSource(source) {
    editingSource.value = source;
    sourceForm.clearErrors();
    sourceForm.name = source.name;
    sourceForm.amount = source.amount;
    sourceForm.currency = source.currency;
    sourceForm.frequency = source.frequency;
    sourceForm.expected_day = source.expected_day;
    sourceForm.account_id = source.account_id;
    showSourceModal.value = true;
}

function submitSource() {
    if (editingSource.value) {
        sourceForm.put(route('income-sources.update', editingSource.value.id), {
            onSuccess: () => {
                showSourceModal.value = false;
                toast.success(t('toast.updated'));
            },
            onError: () => toast.error(t('toast.error')),
        });
    } else {
        sourceForm.post(route('income-sources.store'), {
            onSuccess: () => {
                showSourceModal.value = false;
                sourceForm.reset();
                toast.success(t('toast.created'));
            },
            onError: () => toast.error(t('toast.error')),
        });
    }
}

const confirmingDeleteSource = ref(null);
const deleteSourceForm = useForm({});

function askDeleteSource(source) {
    confirmingDeleteSource.value = source;
}

function cancelDeleteSource() {
    confirmingDeleteSource.value = null;
}

function confirmDeleteSource() {
    deleteSourceForm.delete(route('income-sources.destroy', confirmingDeleteSource.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingDeleteSource.value = null;
            toast.success(t('toast.deleted'));
        },
        onError: () => toast.error(t('toast.error')),
    });
}

// Confirm modal — either confirming an existing pending transaction, or
// manually confirming this period's income directly from its source.
const showConfirmModal = ref(false);
const confirming = ref(null);
const confirmingSource = ref(null);
const confirmForm = useForm({
    confirmed_date: today,
    account_id: null,
});

function openConfirm(transaction) {
    confirming.value = transaction;
    confirmingSource.value = null;
    confirmForm.clearErrors();
    confirmForm.confirmed_date = today;
    confirmForm.account_id = transaction.account_id ?? props.accounts[0]?.id ?? null;
    showConfirmModal.value = true;
}

function openConfirmSource(source) {
    confirming.value = null;
    confirmingSource.value = source;
    confirmForm.clearErrors();
    confirmForm.confirmed_date = today;
    confirmForm.account_id = source.account_id ?? props.accounts[0]?.id ?? null;
    showConfirmModal.value = true;
}

// Known backend validation keys get translated; anything else (e.g. Laravel's
// default "required"/"date" messages) is shown through as-is.
const KNOWN_ERROR_KEYS = ['income.alreadyConfirmed'];
function translateError(message) {
    if (!message) return message;
    return KNOWN_ERROR_KEYS.includes(message) ? t(message) : message;
}

const confirmDateError = computed(() => translateError(confirmForm.errors.confirmed_date));

function submitConfirm() {
    const url = confirming.value
        ? route('income-transactions.confirm', confirming.value.id)
        : route('income-sources.confirm', confirmingSource.value.id);

    confirmForm.post(url, {
        onSuccess: () => {
            showConfirmModal.value = false;
            toast.success(t('toast.confirmed'));
        },
        onError: (errors) => toast.error(translateError(errors.confirmed_date) ?? t('toast.error')),
    });
}

// Undo a confirmed transaction — moves it back to pending and reverses the
// balance credit it had applied.
const confirmingUndo = ref(null);
const undoForm = useForm({});

function askUndo(transaction) {
    confirmingUndo.value = transaction;
}

function cancelUndo() {
    confirmingUndo.value = null;
}

function confirmUndo() {
    undoForm.post(route('income-transactions.unconfirm', confirmingUndo.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingUndo.value = null;
            toast.success(t('toast.updated'));
        },
        onError: () => toast.error(t('toast.error')),
    });
}
</script>

<template>
    <Head :title="t('income.title')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ t('income.title') }}
                </h2>
                <PrimaryButton @click="openCreateSource">{{ t('income.addSource') }}</PrimaryButton>
            </div>
        </template>

        <div class="space-y-6 py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div v-if="pending.length > 0" class="overflow-hidden bg-yellow-50 shadow-sm sm:rounded-lg dark:bg-yellow-900/20">
                    <div class="border-b border-yellow-200 p-4 font-medium text-yellow-800 dark:border-yellow-800 dark:text-yellow-200">
                        🔔 {{ t('income.pendingConfirmations') }}
                    </div>
                    <div class="divide-y divide-yellow-100 dark:divide-yellow-900">
                        <div v-for="t2 in pending" :key="t2.id" class="flex items-center justify-between p-4">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ t2.income_source?.name }} &mdash; {{ t2.amount }} {{ t2.currency }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ t('income.expectedOn', { date: t2.expected_date }) }}
                                </div>
                            </div>
                            <PrimaryButton @click="openConfirm(t2)">{{ t('income.confirmReceived') }}</PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="border-b border-gray-200 p-4 font-medium text-gray-800 dark:border-gray-700 dark:text-gray-200">
                        {{ t('income.incomeSources') }}
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div v-for="source in sources" :key="source.id" class="flex items-center justify-between p-4">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ source.name }} &mdash; {{ source.amount }} {{ source.currency }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ source.frequency === 'monthly'
                                        ? t('income.dayOfMonth', { day: source.expected_day })
                                        : t('income.weeklyOnDay', { day: source.expected_day }) }}
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <PrimaryButton @click="openConfirmSource(source)">{{ t('income.confirmThisMonth') }}</PrimaryButton>
                                <SecondaryButton @click="openEditSource(source)">{{ t('common.edit') }}</SecondaryButton>
                                <DangerButton @click="askDeleteSource(source)">{{ t('common.delete') }}</DangerButton>
                            </div>
                        </div>
                        <div v-if="sources.length === 0" class="p-6 text-center text-gray-500 dark:text-gray-400">
                            {{ t('income.noSources') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="border-b border-gray-200 p-4 font-medium text-gray-800 dark:border-gray-700 dark:text-gray-200">
                        {{ t('income.recentlyConfirmed') }}
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div v-for="t2 in confirmed" :key="t2.id" class="flex items-center justify-between p-4">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ t2.income_source?.name }} &mdash; {{ t2.amount }} {{ t2.currency }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ t('income.confirmedOn', { date: t2.confirmed_date }) }}
                                </div>
                            </div>
                            <SecondaryButton @click="askUndo(t2)">{{ t('income.undoConfirmation') }}</SecondaryButton>
                        </div>
                        <div v-if="confirmed.length === 0" class="p-6 text-center text-gray-500 dark:text-gray-400">
                            {{ t('income.noConfirmed') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Income source modal -->
        <Modal :show="showSourceModal" @close="showSourceModal = false">
            <form @submit.prevent="submitSource" class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ editingSource ? t('income.editSource') : t('income.newSource') }}
                </h2>

                <div class="mt-4">
                    <InputLabel :value="t('income.name')" />
                    <TextInput v-model="sourceForm.name" class="mt-1 block w-full" :placeholder="t('income.namePlaceholder')" />
                    <InputError :message="sourceForm.errors.name" class="mt-2" />
                </div>

                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel :value="t('income.amount')" />
                        <TextInput v-model="sourceForm.amount" type="number" step="0.01" class="mt-1 block w-full" />
                        <InputError :message="sourceForm.errors.amount" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel :value="t('income.currency')" />
                        <SelectInput v-model="sourceForm.currency" class="mt-1 block w-full">
                            <option v-for="c in enabledCurrencies" :key="c" :value="c">{{ c }}</option>
                        </SelectInput>
                        <InputError :message="sourceForm.errors.currency" class="mt-2" />
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel :value="t('income.frequency')" />
                        <SelectInput v-model="sourceForm.frequency" class="mt-1 block w-full">
                            <option value="monthly">{{ t('income.frequencyMonthly') }}</option>
                            <option value="weekly">{{ t('income.frequencyWeekly') }}</option>
                        </SelectInput>
                        <InputError :message="sourceForm.errors.frequency" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel :value="sourceForm.frequency === 'monthly' ? t('income.dayOfMonthLabel') : t('income.dayOfWeekLabel')" />
                        <TextInput
                            v-model="sourceForm.expected_day"
                            type="number"
                            min="0"
                            :max="sourceForm.frequency === 'monthly' ? 31 : 6"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="sourceForm.errors.expected_day" class="mt-2" />
                    </div>
                </div>

                <div class="mt-4">
                    <InputLabel :value="t('income.defaultAccount')" />
                    <SelectInput v-model="sourceForm.account_id" class="mt-1 block w-full">
                        <option :value="null">{{ t('income.chooseAtConfirmation') }}</option>
                        <option v-for="a in accounts" :key="a.id" :value="a.id">
                            {{ a.nickname || a.institution.name }} ({{ a.currency }})
                        </option>
                    </SelectInput>
                    <InputError :message="sourceForm.errors.account_id" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <SecondaryButton @click="showSourceModal = false">{{ t('common.cancel') }}</SecondaryButton>
                    <PrimaryButton :disabled="sourceForm.processing">{{ t('common.save') }}</PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Confirm modal -->
        <Modal :show="showConfirmModal" @close="showConfirmModal = false">
            <form @submit.prevent="submitConfirm" class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ t('income.confirmModalTitle') }}</h2>

                <div class="mt-4">
                    <InputLabel :value="t('income.actualDate')" />
                    <TextInput v-model="confirmForm.confirmed_date" type="date" class="mt-1 block w-full" />
                    <InputError :message="confirmDateError" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel :value="t('income.depositTo')" />
                    <SelectInput v-model="confirmForm.account_id" class="mt-1 block w-full">
                        <option v-for="a in accounts" :key="a.id" :value="a.id">
                            {{ a.nickname || a.institution.name }} ({{ a.currency }})
                        </option>
                    </SelectInput>
                    <InputError :message="confirmForm.errors.account_id" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <SecondaryButton @click="showConfirmModal = false">{{ t('common.cancel') }}</SecondaryButton>
                    <PrimaryButton :disabled="confirmForm.processing">{{ t('common.confirm') }}</PrimaryButton>
                </div>
            </form>
        </Modal>

        <ConfirmDeleteModal
            :show="!!confirmingDeleteSource"
            :title="confirmingDeleteSource ? t('income.deleteSourceTitle', { name: confirmingDeleteSource.name }) : ''"
            :message="t('income.deleteSourceMessage')"
            :processing="deleteSourceForm.processing"
            @confirm="confirmDeleteSource"
            @cancel="cancelDeleteSource"
        />

        <ConfirmDeleteModal
            :show="!!confirmingUndo"
            :title="t('income.undoTitle')"
            :message="t('income.undoMessage')"
            :confirm-label="t('income.undoConfirmation')"
            :processing="undoForm.processing"
            @confirm="confirmUndo"
            @cancel="cancelUndo"
        />
    </AuthenticatedLayout>
</template>
