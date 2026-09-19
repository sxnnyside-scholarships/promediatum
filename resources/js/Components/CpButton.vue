<script setup lang="ts">
interface Props {
    variant?: 'primary' | 'secondary' | 'ghost' | 'danger';
    type?: 'button' | 'submit' | 'reset';
    disabled?: boolean;
    loading?: boolean;
}

withDefaults(defineProps<Props>(), {
    variant: 'primary',
    type: 'submit',
    disabled: false,
    loading: false,
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        :class="[
            variant === 'primary' && 'cp-btn-primary',
            variant === 'secondary' && 'cp-btn-secondary',
            variant === 'ghost' && 'cp-btn-ghost',
            variant === 'danger' && 'bg-state-danger hover:bg-state-danger/90 text-white font-medium py-2 px-4 rounded-subtle shadow-sm transition-all duration-150',
        ]"
    >
        <slot />
    </button>
</template>
