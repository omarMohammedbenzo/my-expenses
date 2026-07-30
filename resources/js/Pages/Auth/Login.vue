<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="t('auth.login.submit')" />

        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ t('auth.login.title') }}</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ t('auth.login.subtitle') }}
        </p>

        <div
            v-if="status"
            class="mt-6 rounded-lg bg-vue-50 px-4 py-3 text-sm font-medium text-vue-800 dark:bg-vue-950/40 dark:text-vue-300"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="mt-6">
            <div>
                <InputLabel for="email" :value="t('auth.login.email')" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" :value="t('auth.login.password')" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 flex items-center justify-between">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-400"
                        >{{ t('auth.login.rememberMe') }}</span
                    >
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-vue-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                >
                    {{ t('auth.login.forgotPassword') }}
                </Link>
            </div>

            <PrimaryButton
                class="mt-6 w-full justify-center py-2.5"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                {{ t('auth.login.submit') }}
            </PrimaryButton>

            <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ t('auth.login.noAccount') }}
                <Link
                    :href="route('register')"
                    class="font-semibold text-vue-600 hover:text-vue-700 dark:text-vue-400 dark:hover:text-vue-300"
                >
                    {{ t('auth.login.signUp') }}
                </Link>
            </p>
        </form>
    </GuestLayout>
</template>
