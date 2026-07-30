<script setup>
defineProps({
    color: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <span
        class="inline-block h-2.5 w-2.5 shrink-0 rounded-full ring-1 ring-black/10 dark:ring-white/10"
        :style="{ backgroundColor: color || '#9ca3af' }"
    ></span>
</template>
