<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
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

    const [year, month, day] = value.split('-').map(Number);

    return year && month && day ? { year, month, day } : null;
}

function toDate({ year, month, day }) {
    return new Date(year, month - 1, day);
}

function toValue(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function isSameDay(a, b) {
    return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
}

const today = new Date();
const parsed = computed(() => parseValue(props.modelValue));
const parsedDate = computed(() => (parsed.value ? toDate(parsed.value) : null));

const viewYear = ref(parsed.value?.year ?? today.getFullYear());
const viewMonth = ref((parsed.value?.month ?? today.getMonth() + 1) - 1);

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
        const base = parsedDate.value ?? today;
        viewYear.value = base.getFullYear();
        viewMonth.value = base.getMonth();
    }
}

const dateFormatterLocale = computed(() => (locale.value === 'ar' ? 'ar-EG-u-nu-latn' : 'en-US'));
const weekStartsOn = computed(() => (locale.value === 'ar' ? 6 : 0));

const weekdayLabels = computed(() => {
    const formatter = new Intl.DateTimeFormat(dateFormatterLocale.value, { weekday: 'short' });
    const start = new Date(2023, 0, 1 + weekStartsOn.value);

    return Array.from({ length: 7 }, (_, i) => {
        const d = new Date(start);
        d.setDate(start.getDate() + i);

        return formatter.format(d);
    });
});

const monthYearLabel = computed(() => {
    const formatter = new Intl.DateTimeFormat(dateFormatterLocale.value, { month: 'long', year: 'numeric' });

    return formatter.format(new Date(viewYear.value, viewMonth.value, 1));
});

const calendarDays = computed(() => {
    const firstOfMonth = new Date(viewYear.value, viewMonth.value, 1);
    const offset = (firstOfMonth.getDay() - weekStartsOn.value + 7) % 7;
    const start = new Date(viewYear.value, viewMonth.value, 1 - offset);

    return Array.from({ length: 42 }, (_, i) => {
        const d = new Date(start);
        d.setDate(start.getDate() + i);

        return d;
    });
});

const displayLabel = computed(() => {
    if (!parsedDate.value) {
        return t('common.selectDate');
    }

    const formatter = new Intl.DateTimeFormat(dateFormatterLocale.value, { day: 'numeric', month: 'short', year: 'numeric' });

    return formatter.format(parsedDate.value);
});

function isCurrentViewMonth(date) {
    return date.getMonth() === viewMonth.value && date.getFullYear() === viewYear.value;
}

function isSelected(date) {
    return parsedDate.value ? isSameDay(date, parsedDate.value) : false;
}

function isToday(date) {
    return isSameDay(date, today);
}

function emitValue(value) {
    emit('update:modelValue', value);
    emit('change', value);
}

function selectDate(date) {
    emitValue(toValue(date));
    open.value = false;
}

function selectToday() {
    viewYear.value = today.getFullYear();
    viewMonth.value = today.getMonth();
    selectDate(today);
}

function clear() {
    emitValue('');
    open.value = false;
}

function prevMonth() {
    if (viewMonth.value === 0) {
        viewMonth.value = 11;
        viewYear.value -= 1;
    } else {
        viewMonth.value -= 1;
    }
}

function nextMonth() {
    if (viewMonth.value === 11) {
        viewMonth.value = 0;
        viewYear.value += 1;
    } else {
        viewMonth.value += 1;
    }
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
            class="absolute z-50 mt-2 w-72 rounded-md border border-gray-200 bg-white p-3 shadow-lg ltr:origin-top-left ltr:start-0 rtl:origin-top-right rtl:end-0 dark:border-gray-700 dark:bg-gray-800"
        >
            <div class="mb-3 flex items-center justify-between">
                <button
                    type="button"
                    class="rounded p-1 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                    :aria-label="t('common.previousMonth')"
                    @click="prevMonth"
                >
                    <svg class="h-4 w-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </button>
                <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ monthYearLabel }}</span>
                <button
                    type="button"
                    class="rounded p-1 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                    :aria-label="t('common.nextMonth')"
                    @click="nextMonth"
                >
                    <svg class="h-4 w-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-7 gap-1 text-center text-xs text-gray-400 dark:text-gray-500">
                <span v-for="label in weekdayLabels" :key="label">{{ label }}</span>
            </div>

            <div class="mt-1 grid grid-cols-7 gap-1">
                <button
                    v-for="date in calendarDays"
                    :key="date.toISOString()"
                    type="button"
                    class="rounded-md py-1.5 text-sm transition"
                    :class="[
                        !isCurrentViewMonth(date) ? 'text-gray-300 dark:text-gray-600' : '',
                        isSelected(date)
                            ? 'bg-vue-500 font-semibold text-white'
                            : isToday(date)
                              ? 'font-semibold text-vue-600 dark:text-vue-400'
                              : isCurrentViewMonth(date)
                                ? 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                                : 'hover:bg-gray-50 dark:hover:bg-gray-700/50',
                    ]"
                    @click="selectDate(date)"
                >
                    {{ date.getDate() }}
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
                    @click="selectToday"
                >
                    {{ t('common.today') }}
                </button>
            </div>
        </div>
    </div>
</template>
