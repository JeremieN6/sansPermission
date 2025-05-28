/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class', // 👈 Enable switch light/dark mode
  content: [
    './templates/**/*.html.twig',
    './assets/**/*.js',
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
// This is a Tailwind CSS configuration file that enables dark mode support
// and specifies the content sources for class scanning. It extends the default theme
// but does not add any custom styles. The `darkMode: 'class'` setting allows
// toggling dark mode by adding a `dark` class to an element, typically the `<html>` or `<body>` tag.
// The `content` array includes paths to HTML templates and JavaScript files where Tailwind CSS classes will be used.