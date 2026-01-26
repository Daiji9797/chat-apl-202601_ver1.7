const { spawn } = require('child_process');
const chokidar = require('chokidar');
const path = require('path');
const fs = require('fs');

const distDir = path.resolve(__dirname, '../dist');
const targetDir = path.resolve(__dirname, '../../chatapp/public');

let deployTimeout = null;

function deploy() {
  console.log('Deploying to XAMPP...');
  
  // ターゲットディレクトリをクリア（.htaccessは保持）
  if (fs.existsSync(targetDir)) {
    const files = fs.readdirSync(targetDir);
    files.forEach(file => {
      if (file !== '.htaccess') {
        const filePath = path.join(targetDir, file);
        if (fs.lstatSync(filePath).isDirectory()) {
          fs.rmSync(filePath, { recursive: true, force: true });
        } else {
          fs.unlinkSync(filePath);
        }
      }
    });
  }
  
  // ビルド成果物をコピー
  function copyDir(src, dest) {
    if (!fs.existsSync(dest)) {
      fs.mkdirSync(dest, { recursive: true });
    }
    
    const files = fs.readdirSync(src);
    files.forEach(file => {
      const srcPath = path.join(src, file);
      const destPath = path.join(dest, file);
      
      if (fs.lstatSync(srcPath).isDirectory()) {
        copyDir(srcPath, destPath);
      } else {
        fs.copyFileSync(srcPath, destPath);
      }
    });
  }
  
  copyDir(distDir, targetDir);
  console.log('Deployed successfully!');
  console.log('http://localhost/chatapp/public/index.html\n');
}

// Viteビルドをwatch modeで起動
console.log('Starting Vite build in watch mode...\n');
const vite = spawn('npm', ['run', 'build', '--', '--watch'], {
  shell: true,
  stdio: 'inherit'
});

// distディレクトリの変更を監視
const watcher = chokidar.watch(distDir, {
  ignored: /^\./,
  persistent: true,
  ignoreInitial: true
});

watcher.on('all', (event, path) => {
  // デバウンス: 500ms以内の連続変更をまとめる
  if (deployTimeout) {
    clearTimeout(deployTimeout);
  }
  deployTimeout = setTimeout(deploy, 500);
});

console.log('👀 Watching for changes...\n');

// 終了処理
process.on('SIGINT', () => {
  console.log('\n\n🛑 Stopping...');
  vite.kill();
  watcher.close();
  process.exit(0);
});
