<script setup>
defineProps({
    variant: {
        type: String,
        default: 'primary',
        validator: (v) => ['primary', 'secondary', 'ghost'].includes(v),
    },
    type: {
        type: String,
        default: 'submit',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
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
        ]"
    >
        <slot />
    </button>
</template>
