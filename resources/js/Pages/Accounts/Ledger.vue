<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmDeleteModal from '@/Components/ConfirmDeleteModal.vue';
import ColorDot from '@/Components/ColorDot.vue';
import MonthPicker from '@/Components/MonthPicker.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/composables/useToast';

const { t } = useI18n();
const toast = useToast();

const props = defineProps({
    account: Object,
    transactions: Array,
    month: String,
    total: [Number, String],
});

const accountLabel = props.account.nickname || props.account.institution.name;

const today = new Date().toISOString().slice(0, 10);

const monthFilter = ref(props.month ?? '');

function applyMonthFilter() {
    router.get(route('accounts.ledger', props.account.id), { month: monthFilter.value || undefined }, { preserveState: true });
}

const showModal = ref(false);
const editing = ref(null);

const form = useForm({
    account_id: props.account.id,
    amount: '',
    date: today,
    source: '',
    note: '',
});

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.date = today;
    showModal.value = true;
}

function openEdit(transaction) {
    editing.value = transaction;
    form.clearErrors();
    form.amount = transaction.amount;
    form.date = transaction.date;
    form.source = transaction.label ?? '';
    form.note = transaction.note ?? '';
    showModal.value = true;
}

function submit() {
    if (editing.value) {
        form.put(route('deposits.update', editing.value.raw_id), {
            onSuccess: () => {
                showModal.value = false;
                toast.success(t('toast.updated'));
            },
            onError: () => toast.error(t('toast.error')),
        });
    } else {
        form.post(route('deposits.store'), {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
                toast.success(t('toast.created'));
            },
            onError: () => toast.error(t('toast.error')),
        });
    }
}

const confirmingDelete = ref(null);
const deleteForm = useForm({});

function askDelete(transaction) {
    confirmingDelete.value = transaction;
}

function cancelDelete() {
    confirmingDelete.value = null;
}

function confirmDelete() {
    deleteForm.delete(route('deposits.destroy', confirmingDelete.value.raw_id), {
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
    <Head :title="t('ledger.title')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <Link :href="route('institutions.index')" class="text-sm text-vue-600 hover:text-vue-700 dark:text-vue-400 dark:hover:text-vue-300">
                        &larr; {{ t('ledger.back') }}
                    </Link>
                    <h2 class="mt-1 flex items-center gap-2 text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        <ColorDot :color="account.institution.color" />
                        {{ accountLabel }} ({{ account.currency }})
                    </h2>
                </div>
                <div class="flex items-center gap-2">
                    <MonthPicker v-model="monthFilter" @change="applyMonthFilter" />
                    <PrimaryButton @click="openCreate">{{ t('ledger.addMoney') }}</PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="mb-6 overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ t('ledger.currentBalance') }}</div>
                    <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-gray-100">
                        {{ account.balance }} {{ account.currency }}
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-start text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ t('ledger.table.date') }}</th>
                                <th class="px-4 py-2 text-start text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ t('ledger.table.type') }}</th>
                                <th class="px-4 py-2 text-start text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ t('ledger.table.description') }}</th>
                                <th class="px-4 py-2 text-end text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ t('ledger.table.amount') }}</th>
                                <th class="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="transaction in transactions" :key="transaction.id">
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ transaction.date }}</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="transaction.direction === 'in'
                                            ? 'bg-vue-100 text-vue-700 dark:bg-vue-950/50 dark:text-vue-400'
                                            : 'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-400'"
                                    >
                                        {{ t(`ledger.type.${transaction.type}`) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                    {{ transaction.label }}<span v-if="transaction.note"> &middot; {{ transaction.note }}</span>
                                </td>
                                <td
                                    class="px-4 py-2 text-end text-sm font-medium"
                                    :class="transaction.direction === 'in'
                                        ? 'text-vue-600 dark:text-vue-400'
                                        : 'text-red-600 dark:text-red-400'"
                                >
                                    {{ transaction.direction === 'in' ? '+' : '-' }}{{ transaction.amount }} {{ account.currency }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-2 text-end text-sm">
                                    <template v-if="transaction.type === 'deposit'">
                                        <SecondaryButton @click="openEdit(transaction)">{{ t('common.edit') }}</SecondaryButton>
                                        <DangerButton class="ms-2" @click="askDelete(transaction)">{{ t('common.delete') }}</DangerButton>
                                    </template>
                                </td>
                            </tr>
                            <tr v-if="transactions.length === 0">
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                    {{ t('ledger.noTransactions') }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="transactions.length > 0" class="border-t-2 border-gray-200 dark:border-gray-600">
                            <tr>
                                <td colspan="3" class="px-4 py-2 text-end text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ t('ledger.total') }}
                                </td>
                                <td
                                    class="px-4 py-2 text-end text-sm font-bold"
                                    :class="total >= 0 ? 'text-vue-600 dark:text-vue-400' : 'text-red-600 dark:text-red-400'"
                                >
                                    {{ total >= 0 ? '+' : '' }}{{ total }} {{ account.currency }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <form @submit.prevent="submit" class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ editing ? t('ledger.editDeposit') : t('ledger.newDeposit') }}
                </h2>

                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel :value="t('ledger.amount')" />
                        <TextInput v-model="form.amount" type="number" step="0.01" class="mt-1 block w-full" />
                        <InputError :message="form.errors.amount" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel :value="t('ledger.date')" />
                        <TextInput v-model="form.date" type="date" class="mt-1 block w-full" />
                        <InputError :message="form.errors.date" class="mt-2" />
                    </div>
                </div>

                <div class="mt-4">
                    <InputLabel :value="t('ledger.source')" />
                    <TextInput v-model="form.source" class="mt-1 block w-full" :placeholder="t('ledger.sourcePlaceholder')" />
                    <InputError :message="form.errors.source" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel :value="t('ledger.note')" />
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
            :title="t('ledger.deleteTitle')"
            :message="t('ledger.deleteMessage')"
            :processing="deleteForm.processing"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />
    </AuthenticatedLayout>
</template>
