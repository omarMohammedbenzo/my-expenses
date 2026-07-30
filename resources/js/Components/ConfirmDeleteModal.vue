<script setup>
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
    message: {
        type: String,
        default: '',
    },
    confirmLabel: {
        type: String,
        default: '',
    },
    processing: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['confirm', 'cancel']);
</script>

<template>
    <Modal :show="show" max-width="md" @close="emit('cancel')">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ title || t('common.delete') }}
            </h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ message }}
            </p>

            <div class="mt-6 flex justify-end gap-2">
                <SecondaryButton @click="emit('cancel')">{{ t('common.cancel') }}</SecondaryButton>
                <DangerButton :disabled="processing" @click="emit('confirm')">
                    {{ confirmLabel || t('common.delete') }}
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>
