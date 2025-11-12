/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class', // 👈 Enable switch light/dark mode
  content: [
    './templates/**/*.html.twig',
    './assets/**/*.js',
  ],
  theme: {
    extend: {
      screens: {
        'xl2': '1600px', // Ajoute ce breakpoint personnalisé
      },
      colors: {
        primary: {
          DEFAULT: '#450996',
          hover: '#4B25B6',
        },
        dark: {
          DEFAULT: '#002626',
          light: '#003737',
        },
        accent: '#4ADE80',
      },
    },
  },
  plugins: [],
}
// This is a Tailwind CSS configuration file that enables dark mode support
// and specifies the content sources for class scanning. It extends the default theme
// but does not add any custom styles. The `darkMode: 'class'` setting allows
// toggling dark mode by adding a `dark` class to an element, typically the `<html>` or `<body>` tag.
// The `content` array includes paths to HTML templates and JavaScript files where Tailwind CSS classes will be used.