import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/{views,js}/**/*.{vue,js,ts,jsx,tsx}',
    ],

    theme: {
        extend: {
            fontFamily: {
                heading: ['Montserrat', ...defaultTheme.fontFamily.sans],
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: '#A32130',
            },
        },
    },

    future: {
        hoverOnlyWhenSupported: true,
    },

    plugins: [
        forms,
        require('@tailwindcss/typography'),
        require('@tailwindcss/aspect-ratio'),
    ],
};
