import { useState } from 'react';
import '../styles/DeleteAccountModal.css';

/**
 * アカウント削除確認モーダル
 * 
 * データの永久削除を確認するための2段階確認ダイアログ
 * - 削除されるデータの詳細リスト表示
 * - 同意チェックボックス
 * - パスワード再入力による本人確認
 */
export default function DeleteAccountModal({ isOpen, onClose, onConfirm, isLoading }) {
  const [agreed, setAgreed] = useState(false);
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);

  if (!isOpen) return null;

  const handleConfirm = () => {
    if (!agreed || !password) return;
    onConfirm(password);
  };

  const handleClose = () => {
    setAgreed(false);
    setPassword('');
    setShowPassword(false);
    onClose();
  };

  return (
    <div className="modal-overlay" onClick={handleClose}>
      <div className="modal-content delete-account-modal" onClick={(e) => e.stopPropagation()}>
        <div className="modal-header">
          <h2>⚠️ アカウントの完全削除</h2>
          <button className="close-btn" onClick={handleClose} disabled={isLoading}>
            ×
          </button>
        </div>

        <div className="modal-body">
          <div className="warning-message">
            <p><strong>この操作は取り消すことができません。</strong></p>
            <p>アカウントを削除すると、<strong>あなたの</strong>以下のデータが完全に削除されます：</p>
          </div>

          <ul className="deletion-list">
            <li>🏠 <strong>あなたが作成したすべてのチャットルーム</strong></li>
            <li>💬 <strong>あなたが送信したすべてのメッセージ</strong></li>
            <li>📝 <strong>あなたの目標達成メモ</strong></li>
            <li>🌟 <strong>あなたの未来Story</strong>（テキストと生成画像）</li>
            <li>🎰 <strong>あなたのガチャ進捗状況</strong>（獲得した画像含む）</li>
            <li>💰 <strong>あなたのポイント残高</strong>（ログインボーナス等）</li>
            <li>🖼️ <strong>あなたの寄り添い画像</strong>（Stalker画像）</li>
            <li>❤️ <strong>あなたがつけたメッセージのいいね</strong></li>
          </ul>

          <div className="warning-note">
            <p>⚠️ これらのデータは、削除後に復元することはできません。</p>
            <p>⚠️ 他のユーザーのデータには影響しません。あなたのアカウントと、あなたが作成したデータのみが削除されます。</p>
          </div>

          <div className="confirmation-section">
            <label className="checkbox-label">
              <input
                type="checkbox"
                checked={agreed}
                onChange={(e) => setAgreed(e.target.checked)}
                disabled={isLoading}
              />
              <span>上記を理解し、自分のアカウントと全データの永久削除に同意します</span>
            </label>

            <div className="password-field">
              <label htmlFor="confirm-password">本人確認のため、パスワードを入力してください：</label>
              <div className="password-input-wrapper">
                <input
                  id="confirm-password"
                  type={showPassword ? 'text' : 'password'}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="パスワード"
                  disabled={isLoading}
                  autoComplete="current-password"
                />
                <button
                  type="button"
                  className="toggle-password-btn"
                  onClick={() => setShowPassword(!showPassword)}
                  disabled={isLoading}
                  aria-label={showPassword ? 'パスワードを隠す' : 'パスワードを表示'}
                >
                  {showPassword ? '🙈' : '👁️'}
                </button>
              </div>
            </div>
          </div>
        </div>

        <div className="modal-footer">
          <button
            className="btn btn-secondary"
            onClick={handleClose}
            disabled={isLoading}
          >
            キャンセル
          </button>
          <button
            className="btn btn-danger"
            onClick={handleConfirm}
            disabled={!agreed || !password || isLoading}
          >
            {isLoading ? '削除中...' : 'アカウントを削除する'}
          </button>
        </div>
      </div>
    </div>
  );
}
