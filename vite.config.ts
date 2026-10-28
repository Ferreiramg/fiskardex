import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  publicDir: false,
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './frontend/src'),
    },
  },
  server: {
    port: 5173,
    allowedHosts: [
      'interspatially-unnegotiable-arian.ngrok-free.dev',
      'melanoid-presatisfactory-rebbeca.ngrok-free.dev',
    ],
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
  build: {
    outDir: path.resolve(__dirname, './public'),
    assetsDir: 'app-assets',
    emptyOutDir: false,
    manifest: true,
  },
})
