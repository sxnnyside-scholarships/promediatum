<script setup>
/**
 * Promediatum Logo — Coffee cup, symbolic, minimalist.
 * Works in monochrome. Aligned with Café Pedagógico tone.
 *
 * The cup is an abstracted pedagogical vessel:
 * open at top (receptive to knowledge), grounded at base.
 * Steam lines represent thought, process, emergence.
 */
defineProps({
    class: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <svg
        :class="['inline-block', $props.class]"
        viewBox="0 0 64 64"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-label="Promediatum"
    >
        <!-- Steam: three ascending curves -->
        <path
            d="M24 20c0-3 2-5 2-8"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            opacity="0.5"
        />
        <path
            d="M32 18c0-4 2-6 2-10"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            opacity="0.6"
        />
        <path
            d="M40 20c0-3 2-5 2-8"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            opacity="0.5"
        />

        <!-- Cup body: tapered vessel -->
        <path
            d="M16 26h32l-4 28H20L16 26z"
            stroke="currentColor"
            stroke-width="2"
            fill="none"
            stroke-linejoin="round"
        />

        <!-- Cup rim: horizontal line at top -->
        <line
            x1="14"
            y1="26"
            x2="50"
            y2="26"
            stroke="currentColor"
            stroke-width="2.2"
            stroke-linecap="round"
        />

        <!-- Handle: minimal arc on right -->
        <path
            d="M48 30c5 0 8 3 8 8s-3 8-8 8"
            stroke="currentColor"
            stroke-width="2"
            fill="none"
            stroke-linecap="round"
        />

        <!-- Base/saucer: subtle ground line -->
        <line
            x1="18"
            y1="56"
            x2="46"
            y2="56"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            opacity="0.7"
        />
    </svg>
</template>
