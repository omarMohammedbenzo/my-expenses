<script setup>
import { nextTick, ref, watch } from 'vue';
import { useToast } from '@/composables/useToast';

const { toasts, dismiss } = useToast();
const container = ref(null);

// The toast container is a native popover so it lives in the same browser
// "top layer" as our <dialog>-based Modal. Top-layer elements always paint
// above normally z-indexed content, but stack among themselves in the order
// they were shown — so every time a new toast arrives we re-show the
// popover, bumping it above any dialog that may have opened since.
watch(
    () => toasts.length,
    async (count) => {
        await nextTick();
        if (!container.value) return;

        if (count > 0) {
            if (container.value.matches(':popover-open')) {
                container.value.hidePopover();
            }
            container.value.showPopover();
        } else if (container.value.matches(':popover-open')) {
            container.value.hidePopover();
        }
    },
);
</script>

<template>
    <Teleport to="body">
        <div
            ref="container"
            popover="manual"
            class="pointer-events-none fixed inset-x-4 top-4 z-[100] m-0 flex flex-col items-center gap-2 border-0 bg-transparent p-0 sm:inset-x-auto sm:end-4 sm:items-end"
        >
            <TransitionGroup
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg bg-white p-4 shadow-lg ring-1 ring-gray-900/5 dark:bg-gray-800 dark:ring-white/10"
                >
                    <span
                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full"
                        :class="toast.type === 'success'
                            ? 'bg-vue-100 text-vue-600 dark:bg-vue-950/60 dark:text-vue-400'
                            : 'bg-red-100 text-red-600 dark:bg-red-950/60 dark:text-red-400'"
                    >
                        <svg v-if="toast.type === 'success'" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        <svg v-else class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </span>
                    <p class="flex-1 text-sm text-gray-700 dark:text-gray-200">{{ toast.message }}</p>
                    <button
                        type="button"
                        @click="dismiss(toast.id)"
                        class="shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>
