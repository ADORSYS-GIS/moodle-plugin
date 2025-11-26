// PostCSS configuration for Tailwind CSS v4
import tailwindcss from '@tailwindcss/postcss';
import autoprefixer from 'autoprefixer';
import cssnano from 'cssnano';

export default {
  plugins: [
    tailwindcss(),
    autoprefixer(),
    cssnano({ preset: 'default' }),
  ],
};