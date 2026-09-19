<script setup lang="ts">
/**
 * CpToast — Lightweight notification system.
 *
 * Renders a stack of transient toast messages in the top-right corner.
 * Supports: success, error, info, warning variants.
 * Auto-dismiss after configurable duration (default 4s).
 * Respects design system: 150ms fade only, no dramatic motion.
 *
 * Usage (via provide/inject or event bus):
 *   import { useToast } from '@/composables/useToast';
 *   const toast = useToast();
 *   toast.success('Export downloaded!');
 *   toast.error('SMTP connection failed.');
 */

import { onMounted, onUnmounted, ref } from 'vue';
import CpIcon from '@/Components/CpIcon.vue';

export type ToastType = 'success' | 'error' | 'info' | 'warning';

interface ToastItem {
    id: number;
    type: ToastType;
    message: string;
    visible: boolean;
}

const toasts = ref<ToastItem[]>([]);
let nextId = 0;

const iconMap: Record<ToastType, string> = {
    success: 'check-circle',
    error: 'x-circle',
    info: 'info',
    warning: 'alert-triangle',
};

const colorMap: Record<ToastType, string> = {
    success: 'text-state-success bg-state-success/10 border-state-success/30',
    error: 'text-state-danger bg-state-danger/10 border-state-danger/30',
    info: 'text-state-info bg-state-info/10 border-state-info/30',
    warning: 'text-state-warning bg-state-warning/10 border-state-warning/30',
};

function addToast(event: Event | CustomEvent) {
    const detail = (event as CustomEvent).detail || event;
    const { type = 'info', message = '', duration = 4000 } = detail;
    const id = nextId++;
    toasts.value.push({ id, type, message, visible: true });

    // Auto-dismiss
    if (duration > 0) {
        setTimeout(() => removeToast(id), duration);
    }
}

function removeToast(id: number) {
    const idx = toasts.value.findIndex((t) => t.id === id);
    if (idx !== -1) {
        toasts.value[idx].visible = false;
        setTimeout(() => {
            toasts.value = toasts.value.filter((t) => t.id !== id);
        }, 150); // match fade duration
    }
}

onMounted(() => {
    window.addEventListener('cp-toast', addToast as EventListener);
});

onUnmounted(() => {
    window.removeEventListener('cp-toast', addToast as EventListener);
});
</script>

<template>
    <div class="fixed top-4 right-4 z-50 flex flex-col gap-2 pointer-events-none" style="max-width: 360px;">
        <TransitionGroup
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 translate-y-[-8px]"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-[-8px]"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="pointer-events-auto flex items-start gap-3 px-4 py-3 rounded-subtle border shadow-lg backdrop-blur-sm"
                :class="[colorMap[toast.type] || colorMap.info, toast.visible ? 'opacity-100' : 'opacity-0']"
            >
                <CpIcon :name="iconMap[toast.type] || 'info'" :size="18" class-name="shrink-0 mt-0.5" />
                <p class="text-sm flex-1">{{ toast.message }}</p>
                <button
                    type="button"
                    @click="removeToast(toast.id)"
                    class="shrink-0 opacity-60 hover:opacity-100 transition-opacity duration-150"
                >
                    <CpIcon name="x" :size="14" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
