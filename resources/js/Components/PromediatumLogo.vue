<script setup lang="ts">
/**
 * Promediatum Logo — Official Brand Logo
 */
defineProps({
    class: {
        type: String,
        default: 'h-8 w-8',
    },
    alt: {
        type: String,
        default: 'Promediatum',
    },
});
</script>

<template>
    <img
        src="/images/promediatum-logo.png"
        :alt="alt"
        :class="['inline-block object-contain select-none pointer-events-none', $props.class]"
        draggable="false"
    />
</template>
