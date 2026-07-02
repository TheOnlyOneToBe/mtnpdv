import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
  root: '.',
  build: {
    outDir: 'public/build',
    emptyOutDir: true,
    manifest: 'manifest.json',
    target: 'esnext',
    rollupOptions: {
      input: {
        app: './assets/app.js',
      },
    },
  },
  server: {
    middlewareMode: true,
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './assets'),
    },
  },
});
