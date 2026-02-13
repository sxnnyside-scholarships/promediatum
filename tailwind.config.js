import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                serif: ['Lora', ...defaultTheme.fontFamily.serif],
            },

            colors: {
                // Café Pedagógico — Warm tonal palette
                cafe: {
                    50:  '#FAF7F4',   // Lightest warm surface
                    100: '#F3EDE6',   // Base layer light
                    200: '#E8DDD0',   // Section surface light
                    300: '#D4C4AE',   // Border / separator
                    400: '#B8A48A',   // Muted text
                    500: '#9C8468',   // Secondary text
                    600: '#7A6348',   // Primary text light mode
                    700: '#5C4A36',   // Strong text
                    800: '#3E3226',   // Dark surface text
                    900: '#2A2118',   // Near-black
                    950: '#1A1510',   // Darkest
                },

                // Accent — Deep warm copper (primary actions only)
                accent: {
                    50:  '#FCF5F0',
                    100: '#F7E8DB',
                    200: '#F0D0B5',
                    300: '#E5B085',
                    400: '#D4894D',   // Primary action
                    500: '#C47434',   // Hover
                    600: '#A85D28',   // Pressed
                    700: '#8B4A22',
                    800: '#6E3A1C',
                    900: '#5A3019',
                },

                // State colors (muted, not saturated)
                state: {
                    success: '#5D8C5A',
                    warning: '#C49A3C',
                    danger:  '#B85C4A',
                    info:    '#5A7D99',
                },

                // Dark mode surfaces
                surface: {
                    dark:     '#1C1917',   // Base dark
                    'dark-1': '#231F1C',   // Work surface dark
                    'dark-2': '#2C2623',   // Section surface dark
                    'dark-3': '#36302C',   // Elevated dark
                },
            },

            spacing: {
                // Design system rhythm
                'section': '2rem',     // 32px — section spacing
                'read-pad': '1.5rem',  // 24px — read mode padding
                'scan-pad': '0.625rem', // 10px — scan mode padding
            },

            maxWidth: {
                'read': '720px',
                'scan': '960px',
            },

            fontSize: {
                // Read mode
                'read-body': ['0.9375rem', { lineHeight: '1.6' }],     // 15px
                'read-body-sm': ['0.875rem', { lineHeight: '1.6' }],   // 14px
                // Scan mode
                'scan-body': ['0.8125rem', { lineHeight: '1.3' }],     // 13px
            },

            borderRadius: {
                'subtle': '0.25rem',  // Minimal radius per design system
            },

            transitionDuration: {
                '150': '150ms',  // Design system: only 150ms fade
            },
        },
    },

    plugins: [forms],
};
