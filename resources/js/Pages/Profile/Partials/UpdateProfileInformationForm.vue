<script setup>
import { ref } from 'vue';
import FileDropzone from '@/Components/FileDropzone.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/composables/useToast';

const { t } = useI18n();
const toast = useToast();

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
    avatar: null,
    remove_avatar: false,
});

const currentAvatarUrl = ref(user.avatar_url ?? null);

function onAvatarUpdate(file) {
    form.avatar = file;
    form.remove_avatar = false;
}

function removeAvatar() {
    form.remove_avatar = true;
    currentAvatarUrl.value = null;
}
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ t('profile.information.title') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ t('profile.information.description') }}
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'), {
                onSuccess: () => toast.success(t('toast.updated')),
                onError: () => toast.error(t('toast.error')),
            })"
            class="mt-6 space-y-6"
        >
            <div>
                <InputLabel :value="t('profile.information.avatar')" />
                <div class="mt-1">
                    <FileDropzone
                        :model-value="form.avatar"
                        :current-url="currentAvatarUrl"
                        rounded
                        @update:model-value="onAvatarUpdate"
                        @remove="removeAvatar"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.avatar" />
            </div>

            <div>
                <InputLabel for="name" :value="t('profile.information.name')" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" :value="t('profile.information.email')" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800 dark:text-gray-200">
                    {{ t('profile.information.unverified') }}
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-vue-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                    >
                        {{ t('profile.information.resend') }}
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600 dark:text-green-400"
                >
                    {{ t('profile.information.sent') }}
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">{{ t('common.save') }}</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600 dark:text-gray-400"
                    >
                        {{ t('profile.information.saved') }}
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
