import React, { useEffect, useRef, useState } from 'react';
import { useAuth } from '../context/AuthContext';
import ChangePasswordForm from './ChangePasswordForm';
import DeleteAccountModal from './DeleteAccountModal';

const Sidebar = ({ selectedRoomId, onRoomSelect, rooms, createRoom, loadRooms, onShowStats, onShowGacha, onShowFutureStory, onShowContact }) => {
  const { user, logout, deleteAccount, fetchUserPoints } = useAuth();
  const fileInputRef = useRef(null);
  const [showChangePassword, setShowChangePassword] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);

  // マウント時とログイン時にポイント情報を更新
  useEffect(() => {
    if (user) {
      fetchUserPoints();
    }
  }, [user?.id]);

  const handleCreateRoom = async () => {
    const name = window.prompt('チャットルーム名を入力してください', 'New Chat');
    if (name && name.trim()) {
      try {
        const newRoom = await createRoom(name);
        onRoomSelect(newRoom.id);
      } catch (err) {
        console.error('Failed to create room:', err);
      }
    }
  };

  const handleDeleteAccount = async (password) => {
    setIsDeleting(true);
    try {
      await deleteAccount(password);
      alert('アカウントが削除されました。');
      setShowDeleteModal(false);
    } catch (err) {
      alert('アカウント削除に失敗しました: ' + err.message);
    } finally {
      setIsDeleting(false);
    }
  };

  const handleFileSelect = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('stalkerImage', file);

    const API_BASE_URL = import.meta.env.VITE_API_URL 
      ? `${import.meta.env.VITE_API_URL}/api/`
      : 'http://localhost/chatapp/src/api/';

    try {
      const response = await fetch(`${API_BASE_URL}stalker.php`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
        },
        body: formData,
      });

      const data = await response.json();
      if (!response.ok) {
        alert('Failed to upload stalker image: ' + (data.message || 'Unknown error'));
        return;
      }

      // ユーザー情報を更新
      await fetchUserPoints();
      alert('Stalker image updated!');
    } catch (err) {
      console.error('Upload error:', err);
      alert('Failed to upload stalker image: ' + err.message);
    }

    // ファイル入力をリセット
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const handleReset = async () => {
    if (!window.confirm('Stalker image をリセットしますか？')) {
      return;
    }

    const API_BASE_URL = import.meta.env.VITE_API_URL 
      ? `${import.meta.env.VITE_API_URL}/api/`
      : 'http://localhost/chatapp/src/api/';

    try {
      const response = await fetch(`${API_BASE_URL}stalker.php`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json',
        },
      });

      const data = await response.json();
      if (!response.ok) {
        alert('Failed to reset stalker image: ' + (data.message || 'Unknown error'));
        return;
      }

      // ユーザー情報を更新
      await fetchUserPoints();
      alert('Stalker image reset!');
    } catch (err) {
      console.error('Reset error:', err);
      alert('Failed to reset stalker image: ' + err.message);
    }
  };

  return (
    <div className="sidebar">
      <div className="sidebar-header">
        <h2>Chat App</h2>
        <button
          className="btn btn-sm btn-primary"
          onClick={handleCreateRoom}
          style={{ width: '100%' }}
        >
          + 新しいチャット
        </button>
        <button
          className="btn btn-sm btn-secondary"
          onClick={onShowStats}
          style={{ width: '100%', marginTop: '8px' }}
        >
          📊 利用状況を見る
        </button>
        <button
          className="btn btn-sm btn-secondary"
          onClick={() => onShowFutureStory()}
          style={{ width: '100%', marginTop: '8px' }}
          title="未来Storyを管理"
        >
          🌟 未来Story
        </button>
        <div className="stalker-control-inline">
          <label className="stalker-control-btn" htmlFor="stalkerImageInput">
            寄り添い画像選択
          </label>
          <input
            ref={fileInputRef}
            id="stalkerImageInput"
            type="file"
            accept="image/*"
            onChange={handleFileSelect}
          />
          <button 
            id="stalkerReset" 
            type="button" 
            onClick={handleReset}
            className="btn btn-sm"
          >
            リセット
          </button>
        </div>
      </div>

      <div className="rooms-list">
        {rooms.map((room) => (
          <div
            key={room.id}
            className={`room-item ${selectedRoomId === room.id ? 'active' : ''}`}
            onClick={() => onRoomSelect(room.id)}
          >
            <div className="room-name">{room.name}</div>
            <div className="room-date">
              {new Date(room.created_at).toLocaleDateString()}
            </div>
          </div>
        ))}
      </div>

      <div className="sidebar-footer">
        <div className="user-meta user-meta-footer">
          <div className="user-label">ログイン中</div>
          <div className="user-info-row">
            <div className="user-name" title={user?.email}>
              {user?.name || user?.email || 'Guest'}
            </div>
            {user?.points !== undefined && (
              <div 
                className="user-points" 
                title="ポイント"
                onClick={onShowGacha}
                style={{ cursor: 'pointer' }}
              >
                ポイント {user.points}
              </div>
            )}
          </div>
        </div>
        <button
          className="btn btn-sm btn-primary"
          onClick={() => setShowChangePassword(true)}
          style={{ width: '100%', marginBottom: '8px' }}
        >
          🔐 パスワード変更
        </button>
        <button
          className="btn btn-sm btn-secondary"
          onClick={onShowContact}
          style={{ width: '100%', marginBottom: '8px' }}
        >
          📧 お問い合わせ
        </button>
        <button
          className="btn btn-sm btn-danger"
          onClick={() => setShowDeleteModal(true)}
          style={{ width: '100%' }}
        >
          アカウント削除
        </button>
        <button className="btn btn-logout" onClick={logout}>
          ログアウト
        </button>
      </div>

      {showChangePassword && (
        <ChangePasswordForm
          onClose={() => setShowChangePassword(false)}
          onSuccess={() => {
            // パスワード変更成功時の処理
            fetchUserPoints();
          }}
        />
      )}

      <DeleteAccountModal
        isOpen={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        onConfirm={handleDeleteAccount}
        isLoading={isDeleting}
      />
    </div>
  );
};

export default Sidebar;
