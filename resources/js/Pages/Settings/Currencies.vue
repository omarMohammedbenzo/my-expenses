<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/composables/useToast';

const { t } = useI18n();
const toast = useToast();

const props = defineProps({
    currencies: Array,
    defaultCurrency: String,
});

const form = useForm({
    enabled: props.currencies.filter((c) => c.is_enabled).map((c) => c.currency),
    default_currency: props.defaultCurrency,
});

const enabledOptions = computed(() => form.enabled);

function submit() {
    form.patch(route('currency-settings.update'), {
        onSuccess: () => toast.success(t('toast.updated')),
        onError: () => toast.error(t('toast.error')),
    });
}
</script>

<template>
    <Head :title="t('currencySettings.title')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ t('currencySettings.title') }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ t('currencySettings.description') }}
                    </p>

                    <form @submit.prevent="submit" class="mt-6 space-y-4">
                        <label
                            v-for="c in currencies"
                            :key="c.currency"
                            class="flex items-center gap-3"
                        >
                            <input
                                type="checkbox"
                                :value="c.currency"
                                v-model="form.enabled"
                                class="rounded border-gray-300 text-vue-600 shadow-sm focus:ring-vue-500 dark:border-gray-700 dark:bg-gray-900"
                            />
                            <span class="text-gray-900 dark:text-gray-100">{{ c.currency }}</span>
                        </label>
                        <InputError :message="form.errors.enabled" />

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ t('currencySettings.defaultCurrency') }}
                            </label>
                            <select
                                v-model="form.default_currency"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-vue-500 focus:ring-vue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            >
                                <option v-for="c in enabledOptions" :key="c" :value="c">{{ c }}</option>
                            </select>
                            <InputError :message="form.errors.default_currency" class="mt-2" />
                        </div>

                        <div class="flex justify-end">
                            <PrimaryButton :disabled="form.processing">{{ t('common.save') }}</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
