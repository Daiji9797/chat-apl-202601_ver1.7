import { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { api } from '../services/api';
import ChangePasswordForm from './ChangePasswordForm';
import '../styles/AdminPanel.css';

function AdminPanel() {
  const { user, logout } = useAuth();
  const [gachaImages, setGachaImages] = useState([]);
  const [uploading, setUploading] = useState(false);
  const [message, setMessage] = useState('');
  const [selectedGachaId, setSelectedGachaId] = useState(0);
  const [selectedStage, setSelectedStage] = useState(1);
  const [showChangePassword, setShowChangePassword] = useState(false);

  useEffect(() => {
    loadGachaImages();
  }, []);

  const loadGachaImages = async () => {
    try {
      const response = await api.request('gacha-images.php', {
        method: 'GET',
      });
      if (response && response.success) {
        setGachaImages(response.data || []);
      }
    } catch (err) {
      console.error('Failed to load gacha images:', err);
    }
  };

  const handleImageUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    // ファイルサイズチェック（5MB以下）
    if (file.size > 5 * 1024 * 1024) {
      setMessage('ファイルサイズは5MB以下にしてください');
      setTimeout(() => setMessage(''), 3000);
      return;
    }

    // 画像形式チェック
    if (!file.type.startsWith('image/')) {
      setMessage('画像ファイルを選択してください');
      setTimeout(() => setMessage(''), 3000);
      return;
    }

    setUploading(true);
    setMessage('アップロード中...');

    try {
      // Base64に変換
      const reader = new FileReader();
      reader.onload = async (e) => {
        try {
          const base64Image = e.target.result;

          const response = await api.request('gacha-images.php', {
            method: 'POST',
            body: JSON.stringify({
              gacha_id: selectedGachaId,
              stage: selectedStage,
              image_data: base64Image,
              filename: file.name,
            }),
          });

          console.log('Upload response:', response);

          if (response && response.success) {
            setMessage('アップロード成功！');
            loadGachaImages();
            event.target.value = ''; // ファイル選択をリセット
          } else {
            setMessage('アップロード失敗: ' + (response?.message || 'Unknown error'));
            console.error('Upload failed:', response);
          }
        } catch (err) {
          console.error('Upload error:', err);
          setMessage('アップロード失敗: ' + err.message);
        } finally {
          setUploading(false);
          setTimeout(() => setMessage(''), 3000);
        }
      };
      reader.readAsDataURL(file);
    } catch (err) {
      console.error('File read error:', err);
      setMessage('ファイル読み込み失敗');
      setUploading(false);
      setTimeout(() => setMessage(''), 3000);
    }
  };

  const handleDeleteImage = async (imageId) => {
    if (!window.confirm('この画像を削除しますか？')) return;

    try {
      const response = await api.request('gacha-images.php', {
        method: 'DELETE',
        body: JSON.stringify({ image_id: imageId }),
      });

      if (response && response.success) {
        setMessage('削除しました');
        loadGachaImages();
      } else {
        setMessage('削除失敗');
      }
    } catch (err) {
      console.error('Delete error:', err);
      setMessage('削除失敗');
    } finally {
      setTimeout(() => setMessage(''), 3000);
    }
  };

  return (
    <div className="admin-panel">
      <header className="admin-header">
        <div className="header-top">
          <div>
            <h1>管理者パネル</h1>
            <p>ようこそ、{user?.name || user?.email}さん</p>
          </div>
          <div className="header-actions">
            <button 
              className="change-password-button" 
              onClick={() => setShowChangePassword(true)}
            >
              🔐 パスワード変更
            </button>
            <button className="logout-button" onClick={logout}>
              ログアウト
            </button>
          </div>
        </div>
      </header>

      <main className="admin-content">
        <section className="upload-section">
          <h2>ガチャ画像アップロード</h2>
          
          <div className="upload-form">
            <div className="form-group">
              <label>ガチャID（セル番号 0〜29）:</label>
              <select 
                value={selectedGachaId} 
                onChange={(e) => setSelectedGachaId(parseInt(e.target.value))}
              >
                {Array.from({ length: 30 }, (_, i) => (
                  <option key={i} value={i}>ガチャ {i} （行{Math.floor(i/5)+1} 列{(i%5)+1}）</option>
                ))}
              </select>
            </div>

            <div className="form-group">
              <label>ステージ:</label>
              <select 
                value={selectedStage} 
                onChange={(e) => setSelectedStage(parseInt(e.target.value))}
              >
                <option value={1}>ステージ1（閉じた状態）</option>
                <option value={2}>ステージ2（少し開いた状態）</option>
                <option value={3}>ステージ3（開いた状態）</option>
              </select>
            </div>

            <div className="form-group">
              <label className="file-input-label">
                <input
                  type="file"
                  accept="image/*"
                  onChange={handleImageUpload}
                  disabled={uploading}
                />
                <span className="file-button">
                  {uploading ? 'アップロード中...' : '画像を選択'}
                </span>
              </label>
            </div>

            {message && <div className="message">{message}</div>}
          </div>
        </section>

        <section className="images-section">
          <h2>登録済みガチャ画像</h2>
          
          <div className="images-grid">
            {gachaImages.map((image) => (
              <div key={image.id} className="image-card">
                <div className="image-preview">
                  <img 
                    src={image.image_url || image.image_path} 
                    alt={`Gacha ${image.gacha_id} - Stage ${image.stage}`} 
                  />
                </div>
                <div className="image-info">
                  <p><strong>ガチャID:</strong> {image.gacha_id} （行{Math.floor(image.gacha_id/5)+1} 列{(image.gacha_id%5)+1}）</p>
                  <p><strong>ステージ:</strong> {image.stage}</p>
                  <p><strong>ファイル名:</strong> {image.filename}</p>
                </div>
                <button 
                  className="delete-button"
                  onClick={() => handleDeleteImage(image.id)}
                >
                  削除
                </button>
              </div>
            ))}
          </div>

          {gachaImages.length === 0 && (
            <p className="no-images">画像が登録されていません</p>
          )}
        </section>
      </main>

      {showChangePassword && (
        <ChangePasswordForm
          onClose={() => setShowChangePassword(false)}
          onSuccess={() => {
            // パスワード変更成功時の処理
          }}
        />
      )}
    </div>
  );
}

export default AdminPanel;
