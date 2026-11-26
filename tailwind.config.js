import defaultTheme from 'tailwindcss/defaultTheme';
import colors from 'tailwindcss/colors'; // Tambahkan import colors
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';
import preset from './vendor/filament/support/tailwind.config.preset';

/** @type {import('tailwindcss').Config} */
export default {
  presets: [preset],
  content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
    './app/Filament/**/*.php',
    './resources/views/filament/**/*.blade.php',
    './vendor/filament/**/*.blade.php',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Figtree', ...defaultTheme.fontFamily.sans],
      },
      colors: {
        danger: colors.red,
        primary: colors.emerald,
        success: colors.green,
        warning: colors.amber,
        // Pastikan semua warna yang diperlukan telah didefinisikan
      }
    },
  },
  plugins: [forms, typography],
};
