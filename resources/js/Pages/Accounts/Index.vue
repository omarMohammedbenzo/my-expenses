<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmDeleteModal from '@/Components/ConfirmDeleteModal.vue';
import ColorDot from '@/Components/ColorDot.vue';
import FileDropzone from '@/Components/FileDropzone.vue';
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
    institutions: Array,
    enabledCurrencies: Array,
});

const institutionTypes = computed(() => [
    { value: 'bank', label: t('accounts.types.bank') },
    { value: 'cash', label: t('accounts.types.cash') },
    { value: 'wallet', label: t('accounts.types.wallet') },
]);

// Institution modal state
const showInstitutionModal = ref(false);
const editingInstitution = ref(null);
const DEFAULT_COLOR = '#42b883';

const institutionForm = useForm({
    name: '',
    type: 'bank',
    color: DEFAULT_COLOR,
    logo: null,
    remove_logo: false,
});

const currentLogoUrl = ref(null);

function openCreateInstitution() {
    editingInstitution.value = null;
    institutionForm.reset();
    institutionForm.clearErrors();
    institutionForm.type = 'bank';
    institutionForm.color = DEFAULT_COLOR;
    currentLogoUrl.value = null;
    showInstitutionModal.value = true;
}

function openEditInstitution(institution) {
    editingInstitution.value = institution;
    institutionForm.clearErrors();
    institutionForm.name = institution.name;
    institutionForm.type = institution.type;
    institutionForm.color = institution.color ?? DEFAULT_COLOR;
    institutionForm.logo = null;
    institutionForm.remove_logo = false;
    currentLogoUrl.value = institution.logo_url ?? null;
    showInstitutionModal.value = true;
}

function onLogoUpdate(file) {
    institutionForm.logo = file;
    institutionForm.remove_logo = false;
}

function removeLogo() {
    institutionForm.remove_logo = true;
    currentLogoUrl.value = null;
}

function submitInstitution() {
    if (editingInstitution.value) {
        institutionForm.put(route('institutions.update', editingInstitution.value.id), {
            onSuccess: () => {
                showInstitutionModal.value = false;
                toast.success(t('toast.updated'));
            },
            onError: () => toast.error(t('toast.error')),
        });
    } else {
        institutionForm.post(route('institutions.store'), {
            onSuccess: () => {
                showInstitutionModal.value = false;
                institutionForm.reset();
                toast.success(t('toast.created'));
            },
            onError: () => toast.error(t('toast.error')),
        });
    }
}

const confirmingDeleteInstitution = ref(null);
const deleteInstitutionForm = useForm({});

function askDeleteInstitution(institution) {
    confirmingDeleteInstitution.value = institution;
}

function cancelDeleteInstitution() {
    confirmingDeleteInstitution.value = null;
}

function confirmDeleteInstitution() {
    deleteInstitutionForm.delete(route('institutions.destroy', confirmingDeleteInstitution.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingDeleteInstitution.value = null;
            toast.success(t('toast.deleted'));
        },
        onError: () => toast.error(t('toast.error')),
    });
}

// Account modal state
const showAccountModal = ref(false);
const editingAccount = ref(null);
const accountForm = useForm({
    institution_id: null,
    currency: props.enabledCurrencies[0] ?? 'USD',
    nickname: '',
    balance: 0,
});

function openCreateAccount(institution) {
    editingAccount.value = null;
    accountForm.reset();
    accountForm.clearErrors();
    accountForm.institution_id = institution.id;
    accountForm.currency = props.enabledCurrencies[0] ?? 'USD';
    accountForm.balance = 0;
    showAccountModal.value = true;
}

function openEditAccount(account) {
    editingAccount.value = account;
    accountForm.clearErrors();
    accountForm.nickname = account.nickname ?? '';
    showAccountModal.value = true;
}

function submitAccount() {
    if (editingAccount.value) {
        accountForm.put(route('accounts.update', editingAccount.value.id), {
            onSuccess: () => {
                showAccountModal.value = false;
                toast.success(t('toast.updated'));
            },
            onError: () => toast.error(t('toast.error')),
        });
    } else {
        accountForm.post(route('accounts.store'), {
            onSuccess: () => {
                showAccountModal.value = false;
                accountForm.reset();
                toast.success(t('toast.created'));
            },
            onError: () => toast.error(t('toast.error')),
        });
    }
}

const confirmingDeleteAccount = ref(null);
const deleteAccountForm = useForm({});

function askDeleteAccount(account) {
    confirmingDeleteAccount.value = account;
}

function cancelDeleteAccount() {
    confirmingDeleteAccount.value = null;
}

function confirmDeleteAccount() {
    deleteAccountForm.delete(route('accounts.destroy', confirmingDeleteAccount.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingDeleteAccount.value = null;
            toast.success(t('toast.deleted'));
        },
        onError: () => toast.error(t('toast.error')),
    });
}
</script>

<template>
    <Head :title="t('accounts.title')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ t('accounts.title') }}
                </h2>
                <PrimaryButton @click="openCreateInstitution">{{ t('accounts.addInstitution') }}</PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                <div
                    v-for="institution in institutions"
                    :key="institution.id"
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800"
                >
                    <div class="flex items-center justify-between border-b border-gray-200 p-4 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <ColorDot :color="institution.color" />
                            <img
                                v-if="institution.logo_url"
                                :src="institution.logo_url"
                                :alt="institution.name"
                                class="h-7 w-7 rounded object-cover"
                            />
                            <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ institution.name }}
                            </span>
                            <span class="rounded bg-gray-100 px-2 py-0.5 text-xs uppercase text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                {{ t(`accounts.types.${institution.type}`) }}
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <SecondaryButton @click="openCreateAccount(institution)">{{ t('accounts.addAccount') }}</SecondaryButton>
                            <SecondaryButton @click="openEditInstitution(institution)">{{ t('common.edit') }}</SecondaryButton>
                            <DangerButton @click="askDeleteInstitution(institution)">{{ t('common.delete') }}</DangerButton>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div
                            v-for="account in institution.accounts"
                            :key="account.id"
                            class="flex items-center justify-between p-4"
                        >
                            <div>
                                <div class="flex items-center gap-2 font-medium text-gray-900 dark:text-gray-100">
                                    <ColorDot :color="institution.color" />
                                    {{ account.nickname || institution.name }}
                                    <span class="text-sm text-gray-500 dark:text-gray-400">({{ account.currency }})</span>
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ t('accounts.balance') }}: {{ account.balance }} {{ account.currency }}
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <Link
                                    :href="route('accounts.ledger', account.id)"
                                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition duration-150 ease-in-out hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-vue-500 focus:ring-offset-2 dark:border-gray-500 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-offset-gray-800"
                                >
                                    {{ t('accounts.viewHistory') }}
                                </Link>
                                <SecondaryButton @click="openEditAccount(account)">{{ t('common.edit') }}</SecondaryButton>
                                <DangerButton @click="askDeleteAccount(account)">{{ t('common.delete') }}</DangerButton>
                            </div>
                        </div>
                        <div v-if="institution.accounts.length === 0" class="p-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ t('accounts.noAccountsYet') }}
                        </div>
                    </div>
                </div>

                <div v-if="institutions.length === 0" class="overflow-hidden bg-white p-6 text-center text-gray-500 shadow-sm sm:rounded-lg dark:bg-gray-800 dark:text-gray-400">
                    {{ t('accounts.noInstitutions') }}
                </div>
            </div>
        </div>

        <!-- Institution modal -->
        <Modal :show="showInstitutionModal" @close="showInstitutionModal = false">
            <form @submit.prevent="submitInstitution" class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ editingInstitution ? t('accounts.editInstitution') : t('accounts.newInstitution') }}
                </h2>

                <div class="mt-4">
                    <InputLabel :value="t('accounts.name')" />
                    <TextInput v-model="institutionForm.name" class="mt-1 block w-full" :placeholder="t('accounts.namePlaceholder')" />
                    <InputError :message="institutionForm.errors.name" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel :value="t('accounts.type')" />
                    <SelectInput v-model="institutionForm.type" class="mt-1 block w-full">
                        <option v-for="opt in institutionTypes" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </SelectInput>
                    <InputError :message="institutionForm.errors.type" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel :value="t('accounts.color')" />
                    <div class="mt-1 flex items-center gap-3">
                        <input
                            type="color"
                            v-model="institutionForm.color"
                            class="h-10 w-14 cursor-pointer rounded-md border border-gray-300 dark:border-gray-600"
                        />
                        <TextInput v-model="institutionForm.color" class="block w-32" placeholder="#42b883" />
                    </div>
                    <InputError :message="institutionForm.errors.color" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel :value="t('accounts.logo')" />
                    <div class="mt-1">
                        <FileDropzone
                            :model-value="institutionForm.logo"
                            :current-url="currentLogoUrl"
                            @update:model-value="onLogoUpdate"
                            @remove="removeLogo"
                        />
                    </div>
                    <InputError :message="institutionForm.errors.logo" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <SecondaryButton @click="showInstitutionModal = false">{{ t('common.cancel') }}</SecondaryButton>
                    <PrimaryButton :disabled="institutionForm.processing">{{ t('common.save') }}</PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Account modal -->
        <Modal :show="showAccountModal" @close="showAccountModal = false">
            <form @submit.prevent="submitAccount" class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ editingAccount ? t('accounts.editAccount') : t('accounts.newAccount') }}
                </h2>

                <template v-if="!editingAccount">
                    <div class="mt-4">
                        <InputLabel :value="t('accounts.currency')" />
                        <SelectInput v-model="accountForm.currency" class="mt-1 block w-full">
                            <option v-for="c in enabledCurrencies" :key="c" :value="c">{{ c }}</option>
                        </SelectInput>
                        <InputError :message="accountForm.errors.currency" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <InputLabel :value="t('accounts.startingBalance')" />
                        <TextInput v-model="accountForm.balance" type="number" step="0.01" class="mt-1 block w-full" />
                        <InputError :message="accountForm.errors.balance" class="mt-2" />
                    </div>
                </template>

                <div class="mt-4">
                    <InputLabel :value="t('accounts.nickname')" />
                    <TextInput v-model="accountForm.nickname" class="mt-1 block w-full" :placeholder="t('accounts.nicknamePlaceholder')" />
                    <InputError :message="accountForm.errors.nickname" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <SecondaryButton @click="showAccountModal = false">{{ t('common.cancel') }}</SecondaryButton>
                    <PrimaryButton :disabled="accountForm.processing">{{ t('common.save') }}</PrimaryButton>
                </div>
            </form>
        </Modal>

        <ConfirmDeleteModal
            :show="!!confirmingDeleteInstitution"
            :title="confirmingDeleteInstitution ? t('accounts.deleteInstitutionTitle', { name: confirmingDeleteInstitution.name }) : ''"
            :message="t('accounts.deleteInstitutionMessage')"
            :processing="deleteInstitutionForm.processing"
            @confirm="confirmDeleteInstitution"
            @cancel="cancelDeleteInstitution"
        />

        <ConfirmDeleteModal
            :show="!!confirmingDeleteAccount"
            :title="t('accounts.deleteAccountTitle')"
            :message="t('accounts.deleteAccountMessage')"
            :processing="deleteAccountForm.processing"
            @confirm="confirmDeleteAccount"
            @cancel="cancelDeleteAccount"
        />
    </AuthenticatedLayout>
</template>
