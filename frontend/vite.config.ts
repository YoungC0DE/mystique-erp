import path from 'node:path'
import { fileURLToPath } from 'node:url'
import tailwindcss from '@tailwindcss/vite'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const srcPath = path.resolve(__dirname, 'src')

// O backend é exposto pelo nginx. Em Docker use VITE_BACKEND_URL=http://nginx.
// No contexto da config do Vite (Node), as variáveis vêm de process.env.
const backendUrl = process.env.VITE_BACKEND_URL ?? 'http://localhost:8000'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue(), tailwindcss()],
  resolve: {
    alias: [
      { find: '@', replacement: srcPath },
    ],
  },
  server: {
    host: true,
    port: 5173,
    proxy: {
      '/api': { target: backendUrl, changeOrigin: true },
      '/oauth': { target: backendUrl, changeOrigin: true },
    },
  },
})
