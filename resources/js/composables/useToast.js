import { reactive } from 'vue';

const toasts = reactive([]);
let nextId = 1;

function push(type, message) {
    const id = nextId++;
    toasts.push({ id, type, message });
    setTimeout(() => dismiss(id), 4000);
}

function dismiss(id) {
    const index = toasts.findIndex((toast) => toast.id === id);
    if (index !== -1) toasts.splice(index, 1);
}

export function useToast() {
    return {
        toasts,
        success: (message) => push('success', message),
        error: (message) => push('error', message),
        dismiss,
    };
}
