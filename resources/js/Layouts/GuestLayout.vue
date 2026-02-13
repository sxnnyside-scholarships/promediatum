<script setup>
/**
 * GuestLayout — Café Pedagógico
 *
 * Centered form layout for unauthenticated pages.
 * No SaaS card shadow. Warm tonal layering.
 * Structured spacing with clear vertical rhythm.
 */
import PromediatumLogo from '@/Components/PromediatumLogo.vue';
import LocaleSwitch from '@/Components/LocaleSwitch.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { useTranslations } from '@/composables/useTranslations.js';

const { t } = useTranslations();
</script>

<template>
    <div class="min-h-screen bg-cafe-50 dark:bg-surface-dark flex flex-col">
        <!-- Top bar: controls -->
        <header class="flex items-center justify-between px-6 py-4">
            <div class="flex items-center gap-3">
                <PromediatumLogo class="h-8 w-8 text-cafe-600 dark:text-cafe-300" />
                <span class="text-lg font-semibold tracking-tight text-cafe-800 dark:text-cafe-100 font-serif">
                    Promediatum
                </span>
            </div>
            <div class="flex items-center gap-3">
                <LocaleSwitch />
                <ThemeToggle />
            </div>
        </header>

        <!-- Main content: centered form -->
        <main class="flex-1 flex items-center justify-center px-6 pb-12">
            <div class="w-full max-w-md">
                <slot />
            </div>
        </main>

        <!-- Footer: credit -->
        <footer class="px-6 py-4 text-center">
            <p class="text-xs text-cafe-400 dark:text-cafe-500">
                {{ t('brand.credit') }}
                <span class="mx-1">·</span>
                <a
                    href="https://www.sxnnysideproject.com"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="underline underline-offset-2 hover:text-cafe-600 dark:hover:text-cafe-300 transition-colors duration-150"
                >
                    {{ t('brand.project') }}
                </a>
            </p>
        </footer>
    </div>
</template>
