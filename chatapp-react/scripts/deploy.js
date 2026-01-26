const fs = require('fs');
const path = require('path');

const distDir = path.resolve(__dirname, '../dist');
const targetDir = path.resolve(__dirname, '../../chatapp/public');

// ターゲットディレクトリをクリア（.htaccessは保持）
function clearTargetDir() {
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
}

// ディレクトリを再帰的にコピー
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

console.log('Deploying React build to XAMPP...');
clearTargetDir();
copyDir(distDir, targetDir);
console.log('Deployed successfully to', targetDir);
console.log('Access at: http://localhost/chatapp/public/index.html');
