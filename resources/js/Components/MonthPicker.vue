<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const { t, locale } = useI18n();

function parseValue(value) {
    if (!value) {
        return null;
    }

    const [year, month] = value.split('-').map(Number);

    return year && month ? { year, month } : null;
}

const today = new Date();
const parsed = computed(() => parseValue(props.modelValue));
const viewYear = ref(parsed.value?.year ?? today.getFullYear());

watch(
    () => props.modelValue,
    (value) => {
        const next = parseValue(value);
        if (next) {
            viewYear.value = next.year;
        }
    },
);

const open = ref(false);
const root = ref(null);

function handleClickOutside(event) {
    if (root.value && !root.value.contains(event.target)) {
        open.value = false;
    }
}

function handleKeydown(event) {
    if (event.key === 'Escape') {
        open.value = false;
    }
}

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
    document.removeEventListener('keydown', handleKeydown);
});

function toggle() {
    open.value = !open.value;
    if (open.value) {
        viewYear.value = parsed.value?.year ?? today.getFullYear();
    }
}

const dateFormatterLocale = computed(() => (locale.value === 'ar' ? 'ar-EG-u-nu-latn' : 'en-US'));

const monthNames = computed(() => {
    const formatter = new Intl.DateTimeFormat(dateFormatterLocale.value, { month: 'short' });

    return Array.from({ length: 12 }, (_, i) => formatter.format(new Date(2000, i, 1)));
});

const displayLabel = computed(() => {
    if (!parsed.value) {
        return t('common.selectMonth');
    }

    const formatter = new Intl.DateTimeFormat(dateFormatterLocale.value, { month: 'long', year: 'numeric' });

    return formatter.format(new Date(parsed.value.year, parsed.value.month - 1, 1));
});

function isSelected(monthIndex) {
    return parsed.value?.year === viewYear.value && parsed.value?.month === monthIndex + 1;
}

function isCurrentMonth(monthIndex) {
    return viewYear.value === today.getFullYear() && monthIndex === today.getMonth();
}

function emitValue(value) {
    emit('update:modelValue', value);
    emit('change', value);
}

function selectMonth(monthIndex) {
    emitValue(`${viewYear.value}-${String(monthIndex + 1).padStart(2, '0')}`);
    open.value = false;
}

function selectThisMonth() {
    viewYear.value = today.getFullYear();
    selectMonth(today.getMonth());
}

function clear() {
    emitValue('');
    open.value = false;
}

function prevYear() {
    viewYear.value -= 1;
}

function nextYear() {
    viewYear.value += 1;
}
</script>

<template>
    <div ref="root" class="relative inline-block">
        <button
            type="button"
            class="flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 shadow-sm hover:bg-gray-50 focus:border-vue-500 focus:outline-none focus:ring-1 focus:ring-vue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
            @click="toggle"
        >
            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"
                />
            </svg>
            <span>{{ displayLabel }}</span>
        </button>

        <div
            v-show="open"
            class="absolute z-50 mt-2 w-64 rounded-md border border-gray-200 bg-white p-3 shadow-lg ltr:origin-top-left ltr:start-0 rtl:origin-top-right rtl:end-0 dark:border-gray-700 dark:bg-gray-800"
        >
            <div class="mb-3 flex items-center justify-between">
                <button
                    type="button"
                    class="rounded p-1 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                    :aria-label="t('common.previousYear')"
                    @click="prevYear"
                >
                    <svg class="h-4 w-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </button>
                <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ viewYear }}</span>
                <button
                    type="button"
                    class="rounded p-1 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                    :aria-label="t('common.nextYear')"
                    @click="nextYear"
                >
                    <svg class="h-4 w-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-4 gap-1.5">
                <button
                    v-for="(name, index) in monthNames"
                    :key="index"
                    type="button"
                    class="rounded-md px-2 py-1.5 text-sm transition"
                    :class="
                        isSelected(index)
                            ? 'bg-vue-500 font-semibold text-white'
                            : isCurrentMonth(index)
                              ? 'font-semibold text-vue-600 dark:text-vue-400'
                              : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                    "
                    @click="selectMonth(index)"
                >
                    {{ name }}
                </button>
            </div>

            <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-2 dark:border-gray-700">
                <button
                    type="button"
                    class="text-sm text-gray-500 hover:text-gray-700 hover:underline dark:text-gray-400 dark:hover:text-gray-200"
                    @click="clear"
                >
                    {{ t('common.clear') }}
                </button>
                <button
                    type="button"
                    class="text-sm text-vue-600 hover:underline dark:text-vue-400"
                    @click="selectThisMonth"
                >
                    {{ t('common.thisMonth') }}
                </button>
            </div>
        </div>
    </div>
</template>
