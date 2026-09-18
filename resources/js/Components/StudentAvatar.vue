<script setup lang="ts">
/**
 * StudentAvatar — Initials-based Pseudo-Avatar Component
 *
 * Renders an aesthetic, deterministic pseudo-photo avatar using student initials
 * (e.g. "CH" for Carlos Hernández) anchored strictly in the Café Pedagógico Design System
 * (warm copper, sage, ochre, terracotta, slate, espresso, caramel).
 */

import { computed } from 'vue';

interface StudentProps {
    id?: number | string;
    first_name?: string;
    last_name?: string;
    full_name?: string;
    initials?: string;
}

const props = withDefaults(
    defineProps<{
        student?: StudentProps | string | null;
        size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl';
        showRing?: boolean;
    }>(),
    {
        student: null,
        size: 'md',
        showRing: true,
    },
);

// Unified Café Pedagógico Tonal Palettes
const PALETTES = [
    // 1. Warm Copper Accent
    {
        bg: 'bg-accent-100 dark:bg-accent-950/70',
        text: 'text-accent-800 dark:text-accent-200',
        border: 'border-accent-300/80 dark:border-accent-800/60',
        ring: 'ring-accent-400/30 dark:ring-accent-700/40',
    },
    // 2. Muted Sage (State Success)
    {
        bg: 'bg-[#EBF2EA] dark:bg-[#1E2E1D]',
        text: 'text-[#3D663A] dark:text-[#A7D1A4]',
        border: 'border-[#CDE0CB] dark:border-[#2F4A2D]',
        ring: 'ring-[#5D8C5A]/30 dark:ring-[#5D8C5A]/40',
    },
    // 3. Warm Amber Ochre (State Warning)
    {
        bg: 'bg-[#FBF4E6] dark:bg-[#322712]',
        text: 'text-[#8C6B1C] dark:text-[#F3D588]',
        border: 'border-[#EEDDB8] dark:border-[#52401E]',
        ring: 'ring-[#C49A3C]/30 dark:ring-[#C49A3C]/40',
    },
    // 4. Terracotta Brick (State Danger)
    {
        bg: 'bg-[#F9ECE9] dark:bg-[#311C18]',
        text: 'text-[#873A2B] dark:text-[#E8A599]',
        border: 'border-[#ECC8C0] dark:border-[#502C24]',
        ring: 'ring-[#B85C4A]/30 dark:ring-[#B85C4A]/40',
    },
    // 5. Muted Slate Blue (State Info)
    {
        bg: 'bg-[#EDF2F6] dark:bg-[#1A2631]',
        text: 'text-[#38556D] dark:text-[#A1C2DD]',
        border: 'border-[#C8D7E3] dark:border-[#283C4D]',
        ring: 'ring-[#5A7D99]/30 dark:ring-[#5A7D99]/40',
    },
    // 6. Café Clásico
    {
        bg: 'bg-cafe-200/90 dark:bg-surface-dark-3',
        text: 'text-cafe-800 dark:text-cafe-100',
        border: 'border-cafe-300 dark:border-cafe-600',
        ring: 'ring-cafe-400/30 dark:ring-cafe-600/40',
    },
    // 7. Warm Caramel
    {
        bg: 'bg-[#F7EFE6] dark:bg-[#291F16]',
        text: 'text-[#784A1C] dark:text-[#E4B688]',
        border: 'border-[#E5D0BA] dark:border-[#4B3420]',
        ring: 'ring-[#9C6836]/30 dark:ring-[#9C6836]/40',
    },
    // 8. Soft Espresso
    {
        bg: 'bg-cafe-100 dark:bg-surface-dark-2',
        text: 'text-cafe-700 dark:text-cafe-200',
        border: 'border-cafe-300/80 dark:border-cafe-700',
        ring: 'ring-cafe-500/20 dark:ring-cafe-600/30',
    },
];

const initials = computed(() => {
    if (!props.student) {
        return '—';
    }

    if (typeof props.student === 'string') {
        const parts = props.student.trim().split(/\s+/);
        if (parts.length === 1) {
            return parts[0].substring(0, 2).toUpperCase();
        }
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }

    if (props.student.initials) {
        return props.student.initials.toUpperCase();
    }

    const first = (props.student.first_name || '').trim();
    const last = (props.student.last_name || '').trim();

    if (first && last) {
        return (first[0] + last[0]).toUpperCase();
    }

    if (props.student.full_name) {
        const parts = props.student.full_name.trim().split(/\s+/);
        if (parts.length >= 2) {
            return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
        }
        return parts[0].substring(0, 2).toUpperCase();
    }

    return 'ST';
});

const palette = computed(() => {
    let seed = 0;
    if (typeof props.student === 'string') {
        for (let i = 0; i < props.student.length; i++) {
            seed = (seed << 5) - seed + props.student.charCodeAt(i);
        }
    } else if (props.student) {
        if (props.student.id) {
            seed = Number(props.student.id);
        } else {
            const str = `${props.student.first_name || ''} ${props.student.last_name || ''}`;
            for (let i = 0; i < str.length; i++) {
                seed = (seed << 5) - seed + str.charCodeAt(i);
            }
        }
    }

    const index = Math.abs(seed) % PALETTES.length;
    return PALETTES[index];
});

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'xs':
            return 'w-6 h-6 text-[10px] tracking-tight';
        case 'sm':
            return 'w-8 h-8 text-xs font-semibold tracking-tight';
        case 'md':
            return 'w-10 h-10 text-sm font-semibold tracking-normal';
        case 'lg':
            return 'w-14 h-14 text-lg font-bold tracking-normal';
        case 'xl':
            return 'w-20 h-20 text-2xl font-bold tracking-wide';
        case '2xl':
            return 'w-24 h-24 text-3xl font-bold tracking-wide';
        default:
            return 'w-10 h-10 text-sm font-semibold';
    }
});
</script>

<template>
    <div
        :class="[
            'inline-flex items-center justify-center rounded-full select-none shrink-0 font-serif border',
            sizeClasses,
            palette.bg,
            palette.text,
            palette.border,
            showRing ? `ring-2 ${palette.ring}` : '',
        ]"
        :title="typeof student === 'object' && student ? student.full_name : ''"
        aria-hidden="true"
    >
        <span>{{ initials }}</span>
    </div>
</template>
