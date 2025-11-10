/** @type {import('tailwindcss').Config} */
// Tailwind CSS v4 Configuration
export default {
  content: [
    './templates/**/*.mustache',
    './layout/**/*.php',
    './scss/**/*.scss',
    './src/**/*.{js,ts}',
  ],
  theme: {
    extend: {
      colors: {
        // Indigo palette (primary brand color)
        primary: {
          DEFAULT: '#4f46e5',
          50: '#eef2ff',
          100: '#e0e7ff',
          200: '#c7d2fe',
          300: '#a5b4fc',
          400: '#818cf8',
          500: '#6366f1',
          600: '#4f46e5',
          700: '#4338ca',
          800: '#3730a3',
          900: '#312e81',
        },
      },
      spacing: {
        'navbar-height': '4rem',
      },
    },
  },
  plugins: [
    require('daisyui'),
  ],
  daisyui: {
    themes: [
      {
        adorsys: {
          primary: '#4f46e5',
          secondary: '#6366f1',
          accent: '#818cf8',
          neutral: '#1f2937',
          'base-100': '#ffffff',
          'base-200': '#f3f4f6',
          'base-300': '#e5e7eb',
          'base-content': '#1f2937',
          info: '#2563eb',
          success: '#16a34a',
          warning: '#eab308',
          error: '#dc2626',
        },
      },
    ],
    styled: true,
    base: true,
    utils: true,
    logs: false,
    rtl: false,
  },
};