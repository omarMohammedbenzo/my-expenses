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
    gifts: Array,
    accounts: Array,
});

function accountLabel(account) {
    return `${account.nickname || account.institution.name} (${account.currency})`;
}

const activeFilter = ref('all');
const filteredGifts = computed(() => {
    if (activeFilter.value === 'all') return props.gifts;
    return props.gifts.filter((gift) => gift.type === activeFilter.value);
});

const showModal = ref(false);
const editing = ref(null);

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    account_id: props.accounts[0]?.id ?? null,
    type: 'received',
    amount: '',
    source_name: '',
    date: today,
    note: '',
});

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.account_id = props.accounts[0]?.id ?? null;
    form.type = 'received';
    form.date = today;
    showModal.value = true;
}

function openEdit(gift) {
    editing.value = gift;
    form.clearErrors();
    form.account_id = gift.account_id;
    form.type = gift.type;
    form.amount = gift.amount;
    form.source_name = gift.source_name;
    form.date = gift.date;
    form.note = gift.note ?? '';
    showModal.value = true;
}

function submit() {
    if (editing.value) {
        form.put(route('gifts.update', editing.value.id), {
            onSuccess: () => {
                showModal.value = false;
                toast.success(t('toast.updated'));
            },
            onError: () => toast.error(t('toast.error')),
        });
    } else {
        form.post(route('gifts.store'), {
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

function askDelete(gift) {
    confirmingDelete.value = gift;
}

function cancelDelete() {
    confirmingDelete.value = null;
}

function confirmDelete() {
    deleteForm.delete(route('gifts.destroy', confirmingDelete.value.id), {
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
    <Head :title="t('gifts.title')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ t('gifts.title') }}
                </h2>
                <PrimaryButton @click="openCreate">{{ t('gifts.addGift') }}</PrimaryButton>
            </div>

            <div class="mt-4 flex rounded-md shadow-sm">
                <button
                    type="button"
                    @click="activeFilter = 'all'"
                    class="rounded-s-md border px-3 py-1.5 text-sm font-medium"
                    :class="activeFilter === 'all'
                        ? 'border-vue-500 bg-vue-500 text-white'
                        : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'"
                >
                    {{ t('gifts.filterAll') }}
                </button>
                <button
                    type="button"
                    @click="activeFilter = 'received'"
                    class="border border-s-0 px-3 py-1.5 text-sm font-medium"
                    :class="activeFilter === 'received'
                        ? 'border-vue-500 bg-vue-500 text-white'
                        : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'"
                >
                    {{ t('gifts.typeReceived') }}
                </button>
                <button
                    type="button"
                    @click="activeFilter = 'given'"
                    class="rounded-e-md border border-s-0 px-3 py-1.5 text-sm font-medium"
                    :class="activeFilter === 'given'
                        ? 'border-vue-500 bg-vue-500 text-white'
                        : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'"
                >
                    {{ t('gifts.typeGiven') }}
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div
                            v-for="gift in filteredGifts"
                            :key="gift.id"
                            class="flex items-center justify-between p-4"
                        >
                            <div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="gift.type === 'received'
                                            ? 'bg-vue-100 text-vue-700 dark:bg-vue-950/50 dark:text-vue-400'
                                            : 'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-400'"
                                    >
                                        {{ gift.type === 'received' ? t('gifts.typeReceived') : t('gifts.typeGiven') }}
                                    </span>
                                    <span
                                        class="text-lg font-semibold"
                                        :class="gift.type === 'received'
                                            ? 'text-vue-600 dark:text-vue-400'
                                            : 'text-red-600 dark:text-red-400'"
                                    >
                                        {{ gift.type === 'received' ? '+' : '-' }}{{ gift.amount }} {{ gift.account?.currency }}
                                    </span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        &middot; {{ gift.source_name }}
                                    </span>
                                </div>
                                <div class="mt-1 flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                                    <ColorDot :color="gift.account?.institution?.color" />
                                    {{ gift.account?.nickname || gift.account?.institution?.name }}
                                    <span v-if="gift.account?.currency" class="text-gray-400 dark:text-gray-500">({{ gift.account.currency }})</span>
                                    &middot; {{ gift.date }}<span v-if="gift.note"> &middot; {{ gift.note }}</span>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <SecondaryButton @click="openEdit(gift)">{{ t('common.edit') }}</SecondaryButton>
                                <DangerButton @click="askDelete(gift)">{{ t('common.delete') }}</DangerButton>
                            </div>
                        </div>
                        <div v-if="filteredGifts.length === 0" class="p-6 text-center text-gray-500 dark:text-gray-400">
                            {{ t('gifts.noGifts') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <form @submit.prevent="submit" class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ editing ? t('gifts.editGift') : t('gifts.newGift') }}
                </h2>

                <div class="mt-4">
                    <InputLabel :value="t('gifts.type')" />
                    <div class="mt-1 flex rounded-md shadow-sm">
                        <button
                            type="button"
                            @click="form.type = 'received'"
                            class="flex-1 rounded-s-md border px-3 py-2 text-sm font-medium"
                            :class="form.type === 'received'
                                ? 'border-vue-500 bg-vue-500 text-white'
                                : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-700'"
                        >
                            {{ t('gifts.typeReceived') }}
                        </button>
                        <button
                            type="button"
                            @click="form.type = 'given'"
                            class="flex-1 rounded-e-md border border-s-0 px-3 py-2 text-sm font-medium"
                            :class="form.type === 'given'
                                ? 'border-vue-500 bg-vue-500 text-white'
                                : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-700'"
                        >
                            {{ t('gifts.typeGiven') }}
                        </button>
                    </div>
                    <InputError :message="form.errors.type" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel :value="form.type === 'received' ? t('gifts.sourceFrom') : t('gifts.sourceTo')" />
                    <TextInput v-model="form.source_name" class="mt-1 block w-full" :placeholder="t('gifts.sourcePlaceholder')" />
                    <InputError :message="form.errors.source_name" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel :value="t('gifts.account')" />
                    <SelectInput v-model="form.account_id" class="mt-1 block w-full">
                        <option v-for="a in accounts" :key="a.id" :value="a.id">{{ accountLabel(a) }}</option>
                    </SelectInput>
                    <InputError :message="form.errors.account_id" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel :value="t('gifts.amount')" />
                    <TextInput v-model="form.amount" type="number" step="0.01" class="mt-1 block w-full" />
                    <InputError :message="form.errors.amount" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel :value="t('gifts.date')" />
                    <TextInput v-model="form.date" type="date" class="mt-1 block w-full" />
                    <InputError :message="form.errors.date" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel :value="t('gifts.note')" />
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
            :title="t('gifts.deleteTitle')"
            :message="t('gifts.deleteMessage')"
            :processing="deleteForm.processing"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />
    </AuthenticatedLayout>
</template>
