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
import ColorDot from '@/Components/ColorDot.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/composables/useToast';

const { t } = useI18n();
const toast = useToast();

const props = defineProps({
    transfers: Array,
    accounts: Array,
});

function accountLabel(account) {
    return `${account.nickname || account.institution.name} (${account.currency})`;
}

const toAccountOptions = computed(() => props.accounts.filter((a) => a.id !== form.from_account_id));

const sentAmountError = computed(() => {
    if (form.errors.sent_amount === 'insufficient_balance') return t('transfers.insufficientBalance');
    return form.errors.sent_amount;
});

const showModal = ref(false);
const editing = ref(null);

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    from_account_id: props.accounts[0]?.id ?? null,
    to_account_id: props.accounts[1]?.id ?? props.accounts[0]?.id ?? null,
    sent_amount: '',
    received_amount: '',
    date: today,
    note: '',
});

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.from_account_id = props.accounts[0]?.id ?? null;
    form.to_account_id = props.accounts[1]?.id ?? props.accounts[0]?.id ?? null;
    form.date = today;
    showModal.value = true;
}

function openEdit(transfer) {
    editing.value = transfer;
    form.clearErrors();
    form.from_account_id = transfer.from_account_id;
    form.to_account_id = transfer.to_account_id;
    form.sent_amount = transfer.sent_amount;
    form.received_amount = transfer.received_amount;
    form.date = transfer.date;
    form.note = transfer.note ?? '';
    showModal.value = true;
}

function submit() {
    if (editing.value) {
        form.put(route('transfers.update', editing.value.id), {
            onSuccess: () => {
                showModal.value = false;
                toast.success(t('toast.updated'));
            },
            onError: () => toast.error(t('toast.error')),
        });
    } else {
        form.post(route('transfers.store'), {
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

function askDelete(transfer) {
    confirmingDelete.value = transfer;
}

function cancelDelete() {
    confirmingDelete.value = null;
}

function confirmDelete() {
    deleteForm.delete(route('transfers.destroy', confirmingDelete.value.id), {
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
    <Head :title="t('transfers.title')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ t('transfers.title') }}
                </h2>
                <PrimaryButton :disabled="accounts.length < 2" @click="openCreate">{{ t('transfers.addTransfer') }}</PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div v-if="accounts.length < 2" class="mb-6 rounded-md bg-yellow-50 p-4 text-sm text-yellow-700 dark:bg-yellow-950/50 dark:text-yellow-400">
                    {{ t('transfers.needTwoAccounts') }}
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div
                            v-for="transfer in transfers"
                            :key="transfer.id"
                            class="flex items-center justify-between p-4"
                        >
                            <div>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center gap-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <ColorDot :color="transfer.from_account?.institution?.color" />
                                        {{ transfer.from_account?.nickname || transfer.from_account?.institution?.name }}
                                    </span>
                                    <span class="text-red-600 dark:text-red-400">-{{ transfer.sent_amount }} {{ transfer.from_account?.currency }}</span>
                                    <span class="text-gray-400 rtl:rotate-180 dark:text-gray-500">&rarr;</span>
                                    <span class="inline-flex items-center gap-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <ColorDot :color="transfer.to_account?.institution?.color" />
                                        {{ transfer.to_account?.nickname || transfer.to_account?.institution?.name }}
                                    </span>
                                    <span class="text-vue-600 dark:text-vue-400">+{{ transfer.received_amount }} {{ transfer.to_account?.currency }}</span>
                                </div>
                                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ transfer.date }}<span v-if="transfer.note"> &middot; {{ transfer.note }}</span>
                                </div>
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <SecondaryButton @click="openEdit(transfer)">{{ t('common.edit') }}</SecondaryButton>
                                <DangerButton @click="askDelete(transfer)">{{ t('common.delete') }}</DangerButton>
                            </div>
                        </div>
                        <div v-if="transfers.length === 0" class="p-6 text-center text-gray-500 dark:text-gray-400">
                            {{ t('transfers.noTransfers') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <form @submit.prevent="submit" class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ editing ? t('transfers.editTransfer') : t('transfers.newTransfer') }}
                </h2>

                <div class="mt-4">
                    <InputLabel :value="t('transfers.fromAccount')" />
                    <SelectInput v-model="form.from_account_id" class="mt-1 block w-full">
                        <option v-for="a in accounts" :key="a.id" :value="a.id">{{ accountLabel(a) }}</option>
                    </SelectInput>
                    <InputError :message="form.errors.from_account_id" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel :value="t('transfers.sentAmount')" />
                    <TextInput v-model="form.sent_amount" type="number" step="0.01" class="mt-1 block w-full" />
                    <InputError :message="sentAmountError" class="mt-2" />
                </div>

                <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                    <InputLabel :value="t('transfers.toAccount')" />
                    <SelectInput v-model="form.to_account_id" class="mt-1 block w-full">
                        <option v-for="a in toAccountOptions" :key="a.id" :value="a.id">{{ accountLabel(a) }}</option>
                    </SelectInput>
                    <InputError :message="form.errors.to_account_id" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel :value="t('transfers.receivedAmount')" />
                    <TextInput v-model="form.received_amount" type="number" step="0.01" class="mt-1 block w-full" />
                    <InputError :message="form.errors.received_amount" class="mt-2" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ t('transfers.receivedAmountHint') }}</p>
                </div>

                <div class="mt-4">
                    <InputLabel :value="t('transfers.date')" />
                    <TextInput v-model="form.date" type="date" class="mt-1 block w-full" />
                    <InputError :message="form.errors.date" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel :value="t('transfers.note')" />
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
            :title="t('transfers.deleteTitle')"
            :message="t('transfers.deleteMessage')"
            :processing="deleteForm.processing"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />
    </AuthenticatedLayout>
</template>
