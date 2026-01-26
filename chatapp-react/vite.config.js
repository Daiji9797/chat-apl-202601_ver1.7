import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'

// https://vitejs.dev/config/
export default defineConfig(({ mode }) => {
  // 環境変数を読み込む
  const env = loadEnv(mode, process.cwd(), '')
  
  // 本番環境かどうかを判定
  const isProduction = mode === 'production'
  
  return {
    plugins: [react()],
    // 本番環境の場合は相対パスに設定
    base: isProduction ? './' : '/',
    server: {
      port: 5173,
      proxy: {
        // 開発環境でのみ動作（npm run dev）
        '/api': {
          target: 'http://localhost',
          changeOrigin: true,
        },
      },
    },
    build: {
      outDir: 'dist',
      // 本番ビルド時の設定
      rollupOptions: {
        output: {
          manualChunks: undefined,
        },
      },
    },
  }
})
