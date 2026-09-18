<script setup lang="ts">
/**
 * GuestLayout — Educator Digital Workspace Layout
 * 80% Hero anchored at bottom-left corner with MingCute chips
 * 20% Form sidebar utilizing full size
 */

import { usePage } from '@inertiajs/vue3';
import {
    BlackBoard2Regular,
    Book2Regular,
    CalendarMonthRegular,
    ChartBarRegular,
    ExternalLinkRegular,
    FileCheckRegular,
} from '@mingcute/vue/core-regular';
import { computed } from 'vue';
import LocaleSwitch from '@/Components/LocaleSwitch.vue';
import PromediatumLogo from '@/Components/PromediatumLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { useTranslations } from '@/composables/useTranslations';

const { t, locale } = useTranslations();
const page = usePage();

const sxnnysideUrl = computed(() => {
    const loc = locale.value === 'en' ? 'en' : 'es';
    return `https://sxnnysideproject.com/${loc}/realms/sxnnyside-scholarships/`;
});

const pedagogicalChips = computed(() => [
    { icon: BlackBoard2Regular, label: t('hero.chip_groups') },
    { icon: CalendarMonthRegular, label: t('hero.chip_attendance') },
    { icon: ChartBarRegular, label: t('hero.chip_rubrics') },
    { icon: Book2Regular, label: t('hero.chip_observations') },
    { icon: FileCheckRegular, label: t('hero.chip_reports') },
]);
</script>

<template>
    <div class="min-h-screen w-full flex flex-col lg:flex-row bg-cafe-50 dark:bg-surface-dark text-cafe-800 dark:text-cafe-100 selection:bg-accent-400 selection:text-white">
        <!-- ── HERO ZONE (80%) ── -->
        <section
            class="relative w-full lg:w-[78%] xl:w-[80%] min-h-[460px] lg:min-h-screen flex flex-col justify-between p-4 sm:p-5 lg:p-6 pb-2.5 lg:pb-3 bg-cover bg-center overflow-hidden transition-all duration-300"
            style="background-image: url('/images/hero-teacher.jpg');"
        >
            <!-- Gentle localized gradient: photo remains bright and completely visible -->
            <div class="absolute inset-0 bg-gradient-to-t from-cafe-950/90 via-cafe-950/20 to-transparent pointer-events-none" />
            <div class="absolute inset-0 bg-gradient-to-r from-cafe-950/75 via-transparent to-transparent pointer-events-none" />

            <!-- Hero Top Bar: Brand with borderless official logo -->
            <header class="relative z-10 flex items-center justify-between w-full">
                <div class="flex items-center gap-2.5">
                    <PromediatumLogo class="h-9 w-9 drop-shadow-md" />
                    <div>
                        <span class="text-xl sm:text-2xl font-bold tracking-tight text-white font-serif drop-shadow-md">
                            Promediatum
                        </span>
                        <span class="block text-[11px] font-medium tracking-wide text-accent-200">
                            {{ t('hero.brand_subtitle') }}
                        </span>
                    </div>
                </div>
            </header>

            <!-- Hero Bottom-Left Content: compact and snugly anchored to bottom-left corner -->
            <div class="relative z-10 mt-auto w-full max-w-3xl xl:max-w-4xl">
                <h1 class="text-xl sm:text-2xl lg:text-3xl xl:text-4xl font-serif font-bold text-white tracking-tight leading-[1.12] drop-shadow-lg mb-1.5">
                    {{ t('hero.title') }}
                </h1>

                <p class="text-xs sm:text-sm text-cafe-100/90 font-sans leading-snug drop-shadow max-w-xl mb-2.5">
                    {{ t('hero.description') }}
                </p>

                <!-- Chips with MingCute icons (0 emojis, 0 hardcoded SVGs) -->
                <div class="flex flex-wrap gap-1.5 mb-2.5">
                    <div
                        v-for="(chip, idx) in pedagogicalChips"
                        :key="idx"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white/15 dark:bg-black/40 backdrop-blur-md border border-white/20 text-[11px] font-medium text-white shadow-sm hover:bg-white/20 transition-colors"
                    >
                        <component :is="chip.icon" class="w-3.5 h-3.5 text-accent-300 shrink-0" />
                        <span>{{ chip.label }}</span>
                    </div>
                </div>

                <!-- Hero Footer: Educational Term Indicator snugly positioned below chips -->
                <div class="pt-2 border-t border-white/15 flex flex-col sm:flex-row items-start sm:items-center justify-between text-[11px] text-white/80 gap-1">
                    <p>{{ t('hero.footer_note') }}</p>
                    <span class="text-[11px] text-accent-200 font-medium">{{ t('hero.term_indicator') }}</span>
                </div>
            </div>
        </section>

        <!-- ── FORM ZONE (20-22%) ── -->
        <section class="w-full lg:w-[22%] xl:w-[20%] min-w-[320px] max-w-full bg-cafe-50 dark:bg-surface-dark border-t lg:border-t-0 lg:border-l border-cafe-200 dark:border-cafe-800 flex flex-col justify-between p-4 lg:p-5 xl:p-5 shadow-2xl z-20">
            <!-- Form Top Bar: Controls Only (No duplicate Promediatum brand) -->
            <header class="flex items-center justify-end gap-2 pb-2.5 border-b border-cafe-200/60 dark:border-cafe-800/60">
                <LocaleSwitch />
                <ThemeToggle />
            </header>

            <!-- Form Content Slot: Utilizes vertical space smoothly -->
            <main class="flex-1 flex flex-col justify-center py-3">
                <slot />
            </main>

            <!-- Form Footer: Attribution Link -->
            <footer class="pt-2.5 border-t border-cafe-200/60 dark:border-cafe-800/60 text-center">
                <a
                    :href="sxnnysideUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center justify-center gap-1.5 text-[11px] font-medium text-cafe-500 hover:text-accent-600 dark:text-cafe-400 dark:hover:text-accent-300 transition-colors group"
                >
                    <span>{{ t('brand.credit') }}</span>
                    <ExternalLinkRegular class="w-3.5 h-3.5 opacity-70 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all" />
                </a>
            </footer>
        </section>
    </div>
</template>
