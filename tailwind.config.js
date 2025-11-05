/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./**/*.php",
    "!./vendor/**/*.php",
    "!./debug_archive/**/*.php",
    "!./node_modules/**/*.php"
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
