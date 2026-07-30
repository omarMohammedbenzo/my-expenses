<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    modelValue: {
        type: [File, null],
        default: null,
    },
    currentUrl: {
        type: String,
        default: null,
    },
    rounded: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue', 'remove']);

const isDragging = ref(false);
const inputRef = ref(null);

const previewUrl = computed(() => {
    if (props.modelValue) {
        return URL.createObjectURL(props.modelValue);
    }
    return props.currentUrl;
});

function openPicker() {
    inputRef.value?.click();
}

function handleFiles(fileList) {
    const file = fileList?.[0];
    if (file && file.type.startsWith('image/')) {
        emit('update:modelValue', file);
    }
}

function onInputChange(event) {
    handleFiles(event.target.files);
    event.target.value = '';
}

function onDrop(event) {
    isDragging.value = false;
    handleFiles(event.dataTransfer.files);
}

function remove() {
    emit('update:modelValue', null);
    emit('remove');
    if (inputRef.value) inputRef.value.value = '';
}
</script>

<template>
    <div
        @click="openPicker"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="onDrop"
        class="relative flex cursor-pointer items-center gap-4 rounded-lg border-2 border-dashed p-4 transition"
        :class="isDragging
            ? 'border-vue-500 bg-vue-50 dark:bg-vue-950/30'
            : 'border-gray-300 hover:border-vue-400 dark:border-gray-600 dark:hover:border-vue-600'"
    >
        <img
            v-if="previewUrl"
            :src="previewUrl"
            alt=""
            class="h-16 w-16 shrink-0 object-cover ring-1 ring-gray-200 dark:ring-gray-700"
            :class="rounded ? 'rounded-full' : 'rounded-md'"
        />
        <div
            v-else
            class="flex h-16 w-16 shrink-0 items-center justify-center bg-gray-100 text-gray-400 dark:bg-gray-700"
            :class="rounded ? 'rounded-full' : 'rounded-md'"
        >
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M18 3.75a.75.75 0 01.75.75v.75m0 0h.75a.75.75 0 01.75.75m-1.5-1.5h-.75a.75.75 0 00-.75.75v.75m0 0V3.75m-13.5 15h13.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v11.25a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
            </svg>
        </div>

        <div class="text-sm text-gray-600 dark:text-gray-400">
            <span class="font-medium text-vue-600 dark:text-vue-400">{{ t('common.clickToUpload') }}</span>
            {{ t('common.orDragDrop') }}
        </div>

        <button
            v-if="previewUrl"
            type="button"
            @click.stop="remove"
            class="absolute -end-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-red-600 text-white shadow hover:bg-red-500"
        >
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <input ref="inputRef" type="file" accept="image/*" class="hidden" @change="onInputChange" />
    </div>
</template>
