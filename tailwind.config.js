import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                serif: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                display: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                champagne: {
                    50: '#FAF8F2',
                    100: '#F5EFE0',
                    200: '#EBDCBF',
                    300: '#DFC598',
                    400: '#D4AF37',
                    500: '#C5A059',
                    600: '#A98342',
                    700: '#876530',
                    800: '#674B24',
                    900: '#483319',
                },
                glam: {
                    50: '#FDF2F4',
                    100: '#FCE7EB',
                    200: '#F9D0D9',
                    300: '#F4A9BA',
                    400: '#EB7292',
                    500: '#DE4370',
                    600: '#BE185D',
                    700: '#9E114D',
                    800: '#831241',
                    900: '#701439',
                },
                cashmere: {
                    50: '#FAF7F2',
                    100: '#F5EFE6',
                    200: '#EBE0D3',
                    300: '#DFCDBB',
                    400: '#CDB49C',
                    500: '#B89B7F',
                    600: '#9C7F64',
                    700: '#7B624D',
                    800: '#5F4B3C',
                    900: '#48392F',
                },
                onyx: {
                    800: '#1C1C20',
                    900: '#141416',
                    950: '#0C0C0E',
                },
            },
        },
    },

    plugins: [forms],
};
