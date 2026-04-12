import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  build: {
    sourcemap: false,
    minify: 'esbuild',
    rolldownOptions: {
      output: {
        manualChunks: undefined
      }
    }
  },
  server: {
    proxy: {
      '/get_dashboard_data.php': {
        target: 'http://localhost', // Ajuste para o endereço do seu servidor PHP (XAMPP/WAMP/etc)
        changeOrigin: true,
      }
    }
  }
})
