<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmDeleteModal from '@/Components/ConfirmDeleteModal.vue';
import ColorDot from '@/Components/ColorDot.vue';
import MonthPicker from '@/Components/MonthPicker.vue';
import DatePicker from '@/Components/DatePicker.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import SelectInput from '@/Components/SelectInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/composables/useToast';

const { t } = useI18n();
const toast = useToast();

const props = defineProps({
    expenses: Object,
    categories: Array,
    accounts: Array,
    totals: Array,
    currency: String,
    enabledCurrencies: Array,
    month: String,
    from: String,
    to: String,
});

const today = new Date().toISOString().slice(0, 10);

// Date filter: either a single month, or a from/to range
const filterMode = ref(props.from || props.to ? 'range' : 'month');
const monthFilter = ref(props.month ?? today.slice(0, 7));
const fromFilter = ref(props.from ?? '');
const toFilter = ref(props.to ?? '');

// A null currency means "all currencies".
const currencyFilter = ref(props.currency ?? null);

function dateParams() {
    return filterMode.value === 'range'
        ? { from: fromFilter.value || undefined, to: toFilter.value || undefined }
        : { month: monthFilter.value };
}

function reload() {
    router.get(
        route('expenses.index'),
        { ...dateParams(), currency: currencyFilter.value || undefined },
        { preserveState: true },
    );
}

function applyMonthFilter() {
    filterMode.value = 'month';
    reload();
}

function applyRangeFilter() {
    filterMode.value = 'range';
    reload();
}

function applyCurrencyFilter(value) {
    currencyFilter.value = value;
    reload();
}

function switchFilterMode(mode) {
    filterMode.value = mode;
    if (mode === 'month') {
        applyMonthFilter();
    }
}

// Expense modal
const showModal = ref(false);
const editing = ref(null);
const form = useForm({
    category_id: props.categories[0]?.id ?? null,
    account_id: props.accounts[0]?.id ?? null,
    amount: '',
    date: today,
    note: '',
    location: '',
    affects_balance: true,
});

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.category_id = props.categories[0]?.id ?? null;
    form.account_id = props.accounts[0]?.id ?? null;
    form.date = today;
    form.affects_balance = true;
    showModal.value = true;
}

function openEdit(expense) {
    editing.value = expense;
    form.clearErrors();
    form.category_id = expense.category_id;
    form.account_id = expense.account_id;
    form.amount = expense.amount;
    form.date = expense.date;
    form.note = expense.note ?? '';
    form.location = expense.location ?? '';
    form.affects_balance = expense.affects_balance;
    showModal.value = true;
}

function submit() {
    if (editing.value) {
        form.put(route('expenses.update', editing.value.id), {
            onSuccess: () => {
                showModal.value = false;
                toast.success(t('toast.updated'));
            },
            onError: () => toast.error(t('toast.error')),
        });
    } else {
        form.post(route('expenses.store'), {
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

function askDelete(expense) {
    confirmingDelete.value = expense;
}

function cancelDelete() {
    confirmingDelete.value = null;
}

function confirmDelete() {
    deleteForm.delete(route('expenses.destroy', confirmingDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingDelete.value = null;
            toast.success(t('toast.deleted'));
        },
        onError: () => toast.error(t('toast.error')),
    });
}

// Category modal
const showCategoryModal = ref(false);
const categoryForm = useForm({ name: '', icon: '' });
function submitCategory() {
    categoryForm.post(route('expense-categories.store'), {
        onSuccess: () => {
            categoryForm.reset();
            toast.success(t('toast.created'));
        },
        onError: () => toast.error(t('toast.error')),
    });
}

function accountLabel(account) {
    return `${account.nickname || account.institution.name} (${account.currency})`;
}
</script>

<template>
    <Head :title="t('expenses.title')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ t('expenses.title') }}
                </h2>
                <div class="flex flex-wrap items-center gap-2">
                    <SecondaryButton @click="showCategoryModal = true">{{ t('expenses.manageCategories') }}</SecondaryButton>
                    <PrimaryButton @click="openCreate">{{ t('expenses.addExpense') }}</PrimaryButton>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <div class="flex rounded-md shadow-sm">
                    <button
                        type="button"
                        @click="switchFilterMode('month')"
                        class="rounded-s-md border px-3 py-1.5 text-sm font-medium"
                        :class="filterMode === 'month'
                            ? 'border-vue-500 bg-vue-500 text-white'
                            : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'"
                    >
                        {{ t('expenses.filterByMonth') }}
                    </button>
                    <button
                        type="button"
                        @click="switchFilterMode('range')"
                        class="rounded-e-md border border-s-0 px-3 py-1.5 text-sm font-medium"
                        :class="filterMode === 'range'
                            ? 'border-vue-500 bg-vue-500 text-white'
                            : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'"
                    >
                        {{ t('expenses.filterByRange') }}
                    </button>
                </div>

                <MonthPicker
                    v-if="filterMode === 'month'"
                    v-model="monthFilter"
                    @change="applyMonthFilter"
                />

                <template v-else>
                    <InputLabel :value="t('expenses.from')" class="sr-only" />
                    <DatePicker v-model="fromFilter" @change="applyRangeFilter" />
                    <InputLabel :value="t('expenses.to')" class="sr-only" />
                    <DatePicker v-model="toFilter" @change="applyRangeFilter" />
                </template>

                <div v-if="enabledCurrencies.length > 1" class="flex gap-2 ms-auto">
                    <button
                        type="button"
                        @click="applyCurrencyFilter(null)"
                        class="rounded-md px-3 py-1.5 text-sm font-medium"
                        :class="!currency
                            ? 'bg-gray-800 text-white dark:bg-gray-200 dark:text-gray-800'
                            : 'bg-white text-gray-700 shadow-sm dark:bg-gray-800 dark:text-gray-300'"
                    >
                        {{ t('common.all') }}
                    </button>
                    <button
                        v-for="c in enabledCurrencies"
                        :key="c"
                        type="button"
                        @click="applyCurrencyFilter(c)"
                        class="rounded-md px-3 py-1.5 text-sm font-medium"
                        :class="c === currency
                            ? 'bg-gray-800 text-white dark:bg-gray-200 dark:text-gray-800'
                            : 'bg-white text-gray-700 shadow-sm dark:bg-gray-800 dark:text-gray-300'"
                    >
                        {{ c }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-start text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ t('expenses.table.row') }}</th>
                                <th class="px-4 py-2 text-start text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ t('expenses.table.date') }}</th>
                                <th class="px-4 py-2 text-start text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ t('expenses.table.category') }}</th>
                                <th class="px-4 py-2 text-start text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ t('expenses.table.account') }}</th>
                                <th class="px-4 py-2 text-start text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ t('expenses.table.note') }}</th>
                                <th class="px-4 py-2 text-end text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ t('expenses.table.amount') }}</th>
                                <th class="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="(expense, index) in expenses.data" :key="expense.id">
                                <td class="px-4 py-2 text-sm text-gray-400 dark:text-gray-500">{{ expenses.from + index }}</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ expense.date }}</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
                                    <span v-if="expense.category?.icon">{{ expense.category.icon }}</span>
                                    {{ expense.category?.name }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
                                    <span class="inline-flex items-center gap-1.5">
                                        <ColorDot :color="expense.account?.institution?.color" />
                                        {{ expense.account?.nickname || expense.account?.institution?.name }}
                                        <span class="text-gray-400 dark:text-gray-500">({{ expense.account?.currency }})</span>
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ expense.note }}</td>
                                <td class="px-4 py-2 text-end text-sm font-medium"
                                    :class="expense.affects_balance
                                        ? 'text-red-600 dark:text-red-400'
                                        : 'text-gray-400 dark:text-gray-500'">
                                    <span class="inline-flex items-center justify-end gap-1">
                                        <span
                                            v-if="!expense.affects_balance"
                                            class="group relative inline-flex"
                                            tabindex="0"
                                            :aria-label="t('expenses.notCountedHint')"
                                        >
                                            <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="9" />
                                                <path stroke-linecap="round" d="M12 11v5M12 8h.01" />
                                            </svg>
                                            <span
                                                class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-1 w-56 -translate-x-1/2 rounded bg-gray-900 px-2 py-1 text-center text-xs font-normal leading-snug text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100 group-focus:opacity-100 dark:bg-gray-700"
                                            >
                                                {{ t('expenses.notCountedHint') }}
                                            </span>
                                        </span>
                                        <span>-{{ expense.amount }} {{ expense.account?.currency }}</span>
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-2 text-end text-sm">
                                    <SecondaryButton @click="openEdit(expense)">{{ t('common.edit') }}</SecondaryButton>
                                    <DangerButton class="ms-2" @click="askDelete(expense)">{{ t('common.delete') }}</DangerButton>
                                </td>
                            </tr>
                            <tr v-if="expenses.data.length === 0">
                                <td colspan="7" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                    {{ t('expenses.noExpensesMonth') }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="totals.length > 0" class="border-t-2 border-gray-200 dark:border-gray-600">
                            <tr v-for="row in totals" :key="row.currency">
                                <td colspan="4" class="px-4 py-2 text-end text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ t('expenses.total') }}
                                </td>
                                <td class="px-4 py-2 text-end text-sm font-bold text-red-600 dark:text-red-400">
                                    -{{ row.total }} {{ row.currency }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div v-if="expenses.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
                    <template v-for="link in expenses.links" :key="link.label">
                        <button
                            v-if="link.url"
                            @click="router.get(link.url, {}, { preserveState: true })"
                            v-html="link.label"
                            class="rounded border px-3 py-1 text-sm"
                            :class="link.active ? 'bg-gray-800 text-white dark:bg-gray-200 dark:text-gray-800' : 'bg-white text-gray-700 dark:bg-gray-800 dark:text-gray-300'"
                        />
                        <span
                            v-else
                            v-html="link.label"
                            class="rounded border px-3 py-1 text-sm text-gray-400"
                        />
                    </template>
                </div>
            </div>
        </div>

        <!-- Expense modal -->
        <Modal :show="showModal" @close="showModal = false">
            <form @submit.prevent="submit" class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ editing ? t('expenses.editExpense') : t('expenses.addExpense') }}
                </h2>

                <div class="mt-4">
                    <InputLabel :value="t('expenses.category')" />
                    <SelectInput v-model="form.category_id" class="mt-1 block w-full">
                        <option v-for="c in categories" :key="c.id" :value="c.id">
                            {{ c.icon ? c.icon + ' ' : '' }}{{ c.name }}
                        </option>
                    </SelectInput>
                    <InputError :message="form.errors.category_id" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel :value="t('expenses.account')" />
                    <SelectInput v-model="form.account_id" class="mt-1 block w-full">
                        <option v-for="a in accounts" :key="a.id" :value="a.id">{{ accountLabel(a) }}</option>
                    </SelectInput>
                    <InputError :message="form.errors.account_id" class="mt-2" />
                </div>

                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel :value="t('expenses.amount')" />
                        <TextInput v-model="form.amount" type="number" step="0.01" class="mt-1 block w-full" />
                        <InputError :message="form.errors.amount" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel :value="t('expenses.date')" />
                        <TextInput v-model="form.date" type="date" class="mt-1 block w-full" />
                        <InputError :message="form.errors.date" class="mt-2" />
                    </div>
                </div>

                <div class="mt-4">
                    <InputLabel :value="t('expenses.note')" />
                    <TextInput v-model="form.note" class="mt-1 block w-full" />
                    <InputError :message="form.errors.note" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel :value="t('expenses.location')" />
                    <TextInput v-model="form.location" class="mt-1 block w-full" />
                    <InputError :message="form.errors.location" class="mt-2" />
                </div>

                <div class="mt-4 rounded-md bg-gray-50 p-3 dark:bg-gray-900/40">
                    <label class="flex items-start gap-2">
                        <Checkbox v-model:checked="form.affects_balance" class="mt-0.5" />
                        <span>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ t('expenses.affectsBalance') }}
                            </span>
                            <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">
                                {{ t('expenses.affectsBalanceHint') }}
                            </span>
                        </span>
                    </label>
                    <InputError :message="form.errors.affects_balance" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <SecondaryButton @click="showModal = false">{{ t('common.cancel') }}</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">{{ t('common.save') }}</PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Category modal -->
        <Modal :show="showCategoryModal" @close="showCategoryModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ t('expenses.categoriesTitle') }}</h2>

                <ul class="mt-4 max-h-60 space-y-1 overflow-y-auto">
                    <li v-for="c in categories" :key="c.id" class="text-sm text-gray-700 dark:text-gray-300">
                        {{ c.icon ? c.icon + ' ' : '' }}{{ c.name }}
                        <span v-if="!c.user_id" class="text-xs text-gray-400">{{ t('expenses.default') }}</span>
                    </li>
                </ul>

                <form @submit.prevent="submitCategory" class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                    <InputLabel :value="t('expenses.addCustomCategory')" />
                    <div class="mt-1 flex gap-2">
                        <TextInput v-model="categoryForm.name" class="block w-full" :placeholder="t('expenses.namePlaceholder')" />
                        <TextInput v-model="categoryForm.icon" class="block w-20" :placeholder="t('expenses.iconPlaceholder')" />
                        <PrimaryButton :disabled="categoryForm.processing">{{ t('common.add') }}</PrimaryButton>
                    </div>
                    <InputError :message="categoryForm.errors.name" class="mt-2" />
                </form>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="showCategoryModal = false">{{ t('common.close') }}</SecondaryButton>
                </div>
            </div>
        </Modal>

        <ConfirmDeleteModal
            :show="!!confirmingDelete"
            :title="t('expenses.deleteTitle')"
            :message="t('expenses.deleteMessage')"
            :processing="deleteForm.processing"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />
    </AuthenticatedLayout>
</template>
