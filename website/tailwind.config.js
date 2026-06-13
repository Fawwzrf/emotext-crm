import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: { 50: '#ecfdf5', 100: '#d1fae5', 200: '#a7f3d0', 500: '#10b981', 600: '#059669', 900: '#064e3b' }
            },
            animation: {
                'border-beam': 'border-beam 4s linear infinite',
                'sparkle': 'sparkle 2s ease-in-out infinite',
                'float': 'float 3s ease-in-out infinite',
            },
            keyframes: {
                'border-beam': {
                    '100%': { transform: 'rotate(360deg)' }
                },
                'sparkle': {
                    '0%, 100%': { opacity: '0.4', transform: 'scale(0.8)' },
                    '50%': { opacity: '1', transform: 'scale(1.2)' }
                },
                'float': {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-10px)' }
                }
            }
        },
    },

    plugins: [forms],
};
