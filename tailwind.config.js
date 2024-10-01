const colors = require('tailwindcss/colors')

/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./assets/**/*.js",
    "./templates/**/*.html.twig",
  ],
  darkMode: 'media',
  theme: {
    extend: {
      fontFamily: {
        'playfair': ['Playfir', 'serif'],
        'tenor': ['TenorSans', 'sans-serif'],
        'poppins': ['Poppins', 'sans-serif'],
        'poppins-semi-bold': ['PoppinsSemiBold', 'sans-serif'],
        'roboto-thin': ['RobotoThin', 'sans-serif'],
        'roboto': ['Roboto', 'sans-serif'],
        'roboto-medium': ['RobotoMedium', 'sans-serif']
      },
      borderRadius: {
        '4xl': '2rem',
        '5xl': '2.5rem'
      },
      colors: {
        transparent: 'transparent',
        current: 'currentColor',
        body: {
          DEFAULT: colors.gray["100"],
          dark: '#212536'
        },
        box: {
          DEFAULT: '#fff',
          dark: '#3b435cc2'
        },
        shadow: {
          DEFAULT: '#fff',
          dark: '#363d54'
        }
      },
    },
  },
  plugins: [],
}

