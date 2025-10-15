/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './templates/**/*.mustache',
    './layout/**/*.php',
    './src/**/*.ts',
  ],
  theme: {
    extend: {},
  },
  plugins: [require('daisyui')],
}