<script setup>
const model = defineModel({
    required: true,
});
</script>

<template>
    <select
        class="rounded-md border-gray-300 shadow-sm focus:border-vue-500 focus:ring-vue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-vue-600 dark:focus:ring-vue-600"
        v-model="model"
    >
        <slot />
    </select>
</template>
