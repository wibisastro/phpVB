import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// Bundle inti phpVB (jalur core, #6118 3b):
// - IIFE tunggal tanpa hash → Twig cukup <script src="/dist/main.js?v=...">
// - dist/ DI-COMMIT (Prinsip #6: deploy git pull tanpa Node)
// - vue penuh (runtime + compiler) karena template in-DOM dari Twig
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      vue: 'vue/dist/vue.esm-bundler.js',
    },
  },
  define: {
    'process.env.NODE_ENV': JSON.stringify('production'),
    __VUE_OPTIONS_API__: true,
    __VUE_PROD_DEVTOOLS__: false,
    __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: false,
  },
  build: {
    outDir: '../public/dist',
    emptyOutDir: true,
    cssCodeSplit: false,
    lib: {
      entry: 'src/main.js',
      name: 'phpvb',
      formats: ['iife'],
      fileName: () => 'main.js',
      cssFileName: 'style',
    },
  },
})
