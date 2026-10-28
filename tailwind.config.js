/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './index.html',
    './frontend/src/**/*.{vue,js,ts,jsx,tsx}',
  ],
  theme: {
    extend: {
      colors: {
        surface: {
          DEFAULT: 'rgb(var(--surface) / <alpha-value>)',
          card: 'rgb(var(--surface-card) / <alpha-value>)',
          elevated: 'rgb(var(--surface-elevated) / <alpha-value>)',
          border: 'rgb(var(--surface-border) / <alpha-value>)',
        },
        slate: {
          100: 'rgb(var(--text-slate-100) / <alpha-value>)',
          200: 'rgb(var(--text-slate-200) / <alpha-value>)',
          300: 'rgb(var(--text-slate-300) / <alpha-value>)',
          400: 'rgb(var(--text-slate-400) / <alpha-value>)',
          500: 'rgb(var(--text-slate-500) / <alpha-value>)',
        },
        accent: {
          DEFAULT: '#3b82f6',
          soft: 'rgba(59, 130, 246, 0.12)',
        },
        success: {
          DEFAULT: '#22c55e',
          soft: 'rgba(34, 197, 94, 0.12)',
        },
        warning: {
          DEFAULT: '#f59e0b',
          soft: 'rgba(245, 158, 11, 0.12)',
        },
        danger: {
          DEFAULT: '#ef4444',
          soft: 'rgba(239, 68, 68, 0.12)',
        },
      },
      fontFamily: {
        sans: ['"DM Sans"', 'system-ui', 'sans-serif'],
        mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace'],
      },
      borderRadius: {
        xl: '12px',
      },
      boxShadow: {
        card: '0 4px 24px rgba(0, 0, 0, 0.35)',
      },
    },
  },
  plugins: [],
}
