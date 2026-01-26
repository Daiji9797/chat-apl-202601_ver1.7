import React, { useState, useEffect } from 'react';
import { useChat } from '../hooks/useApi';
import { useAuth } from '../context/AuthContext';
import './FutureStory.css';

const FutureStory = ({ onBack }) => {
  const { getStories, createStory, updateStory, deleteStory, getRoomGoals, generateStoryImage } = useChat();
  const { user } = useAuth();

  const [stories, setStories] = useState({
    past: [],
    future: []
  });
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('future'); // 'past', 'future'
  
  // 新規ストーリー作成フォーム
  const [newStoryText, setNewStoryText] = useState('');
  const [newStoryDate, setNewStoryDate] = useState('');
  const [newImageComment, setNewImageComment] = useState('');
  const [savingStory, setSavingStory] = useState(false);
  
  // ルーム目標選択
  const [roomGoals, setRoomGoals] = useState([]);
  const [loadingGoals, setLoadingGoals] = useState(false);
  const [showGoalSelector, setShowGoalSelector] = useState(false);
  
  // ストーリー編集
  const [editingStoryId, setEditingStoryId] = useState(null);
  const [editingText, setEditingText] = useState('');
  const [editingDate, setEditingDate] = useState('');
  const [editingImageComment, setEditingImageComment] = useState('');
  const [editingImage, setEditingImage] = useState(null);
  
  // 画像生成
  const [generatingImage, setGeneratingImage] = useState(false);

  // ストーリーを読み込み
  useEffect(() => {
    loadStories();
  }, []);

  const loadStories = async () => {
    setLoading(true);
    try {
      // roomId なしで全ストーリーを取得
      const result = await getStories(null);
      const allStories = Array.isArray(result.data) ? result.data : [];
      const today = new Date();
      
      // タイプ別に分類（「今」は未来に含める）
      const pastStories = allStories.filter(s => s.story_date && new Date(s.story_date) < today);
      const futureStories = allStories.filter(s => s.story_date && new Date(s.story_date) >= today);
      
      setStories({
        past: pastStories,
        future: futureStories
      });
    } catch (err) {
      console.error('Failed to load stories:', err);
    } finally {
      setLoading(false);
    }
  };

  const loadRoomGoals = async () => {
    setLoadingGoals(true);
    try {
      const result = await getRoomGoals();
      setRoomGoals(Array.isArray(result.data) ? result.data : []);
    } catch (err) {
      console.error('Failed to load room goals:', err);
      setRoomGoals([]);
    } finally {
      setLoadingGoals(false);
    }
  };

  const handleOpenGoalSelector = async () => {
    setShowGoalSelector(true);
    await loadRoomGoals();
  };

  const handleSelectGoal = (goal) => {
    setNewStoryText(goal.note_text);
    setShowGoalSelector(false);
  };

  const handleCreateStory = async () => {
    if (!newStoryText.trim()) {
      alert('ストーリーを入力してください');
      return;
    }

    if (!newStoryDate) {
      alert('日付を選択してください');
      return;
    }

    setSavingStory(true);
    try {
      // roomId なしで作成
      await createStory(null, newStoryText, newStoryDate, editingImage, newImageComment);
      setNewStoryText('');
      setNewStoryDate('');
      setNewImageComment('');
      setEditingImage(null);
      await loadStories();
      alert('ストーリーを保存しました！');
    } catch (err) {
      console.error('Failed to create story:', err);
      alert('ストーリーの保存に失敗しました');
    } finally {
      setSavingStory(false);
    }
  };

  const handleUpdateStory = async (storyId) => {
    if (!editingText.trim()) {
      alert('ストーリーを入力してください');
      return;
    }

    setSavingStory(true);
    try {
      await updateStory(storyId, editingText, editingDate);
      setEditingStoryId(null);
      await loadStories();
      alert('ストーリーを更新しました！');
    } catch (err) {
      console.error('Failed to update story:', err);
      alert('ストーリーの更新に失敗しました');
    } finally {
      setSavingStory(false);
    }
  };

  const handleDeleteStory = async (storyId) => {
    if (!window.confirm('このストーリーを削除しますか？')) return;

    try {
      await deleteStory(storyId);
      await loadStories();
      alert('ストーリーを削除しました');
    } catch (err) {
      console.error('Failed to delete story:', err);
      alert('ストーリーの削除に失敗しました');
    }
  };

  const handleGenerateImage = async () => {
    if (!newStoryText.trim()) {
      alert('ストーリーテキストを入力してください');
      return;
    }

    setGeneratingImage(true);
    try {
      const res = await generateStoryImage(newStoryText, newImageComment, 'openai');
      const base64 = res.data?.image_base64;
      if (!base64) {
        throw new Error('画像が取得できませんでした');
      }
      setEditingImage(`data:image/png;base64,${base64}`);
      alert('OpenAIで画像を生成しました');
    } catch (err) {
      console.error('Failed to generate image:', err);
      alert('画像生成に失敗しました');
    } finally {
      setGeneratingImage(false);
    }
  };

  const handleUploadImage = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    // ファイルサイズチェック（5MB以下）
    if (file.size > 5 * 1024 * 1024) {
      alert('ファイルサイズは5MB以下にしてください');
      return;
    }

    try {
      const reader = new FileReader();
      reader.onload = (event) => {
        const base64 = event.target?.result;
        if (base64) {
          setEditingImage(base64);
          alert('画像をアップロードしました');
        }
      };
      reader.readAsDataURL(file);
    } catch (err) {
      console.error('Failed to upload image:', err);
      alert('画像のアップロードに失敗しました');
    }
  };

  const formatDate = (date) => {
    return new Date(date).toLocaleDateString('ja-JP', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  const getCurrentDate = () => {
    const today = new Date();
    return today.toISOString().split('T')[0];
  };

  if (loading) {
    return (
      <div className="future-story-container">
        <div className="loading">ストーリーを読み込み中...</div>
      </div>
    );
  }

  const currentStories = stories[activeTab] || [];

  return (
    <div className="future-story-container">
      <div className="story-header">
        <div className="story-header-content">
          <button className="btn-back" onClick={onBack}>← 戻る</button>
          <h1>🌟 未来Story</h1>
        </div>
      </div>

      {/* 新規ストーリー作成フォーム */}
      <div className="story-create-panel">
        <h2>📖 新しいストーリーを追加</h2>
        <div className="story-form">
          <div className="form-group">
            <label>ルーム目標から選択（オプション）</label>
            <button
              className="btn btn-outline-secondary"
              onClick={handleOpenGoalSelector}
            >
              {loadingGoals ? '読み込み中...' : '📌 ルーム目標を選択'}
            </button>
            {showGoalSelector && (
              <div className="goal-selector-panel" onClick={() => setShowGoalSelector(false)}>
                <div onClick={(e) => e.stopPropagation()}>
                  <div className="goal-selector-header">
                    <h3>ルーム目標を選択</h3>
                    <button
                      className="btn-close"
                      onClick={() => setShowGoalSelector(false)}
                    >
                      ×
                    </button>
                  </div>
                  {roomGoals.length === 0 ? (
                    <p className="goal-selector-empty">
                      チャットルームで作成した目標がありません
                    </p>
                  ) : (
                    <div className="goal-list">
                      {roomGoals.map((goal) => (
                        <div
                          key={goal.id}
                          className="goal-item"
                          onClick={() => handleSelectGoal(goal)}
                        >
                          <div className="goal-item-room">{goal.room_name}</div>
                          <div className="goal-item-text">{goal.note_text}</div>
                          <div className="goal-item-date">
                            {new Date(goal.created_at).toLocaleDateString('ja-JP')}
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              </div>
            )}
          </div>

          <div className="form-group">
            <label>ストーリー日付</label>
            <input
              type="date"
              value={newStoryDate}
              onChange={(e) => setNewStoryDate(e.target.value)}
              className="story-date-input"
            />
            <small>
              {newStoryDate ? `${formatDate(newStoryDate)}` : '日付を選択してください'}
            </small>
          </div>

          <div className="form-group">
            <label>ストーリー内容</label>
            <textarea
              value={newStoryText}
              onChange={(e) => setNewStoryText(e.target.value)}
              placeholder="達成したい未来のストーリー、過去の成功体験、現在の気づきなど..."
              className="story-textarea"
              rows={4}
            />
          </div>

          <div className="form-group">
            <label>🎨 画像補足コメント（生成のイメージを補助）</label>
            <textarea
              value={newImageComment}
              onChange={(e) => setNewImageComment(e.target.value)}
              placeholder="例：暖かい雰囲気、自然光、笑顔のキャラクター、青空背景..."
              className="story-textarea story-textarea-small"
              rows={2}
            />
            <small>画像生成時に、このコメントを使用してイメージをサポートします</small>
          </div>



          <div className="form-actions">
            <button
              className="btn btn-secondary"
              onClick={handleGenerateImage}
              disabled={generatingImage || !newStoryText.trim() || !newStoryDate}
            >
              {generatingImage ? '生成中...' : '✨ 画像を生成'}
            </button>
            <label className="btn btn-secondary btn-upload">
              📤 画像をアップロード
              <input
                type="file"
                accept="image/*"
                onChange={handleUploadImage}
                style={{ display: 'none' }}
              />
            </label>
            <button
              className="btn btn-primary"
              onClick={handleCreateStory}
              disabled={savingStory || !newStoryText.trim() || !newStoryDate}
            >
              {savingStory ? '保存中...' : '保存する'}
            </button>
          </div>

          {editingImage && (
            <div className="generated-image-preview">
              <img src={editingImage} alt="生成された画像" />
              <button
                type="button"
                className="btn-remove-image"
                onClick={() => setEditingImage(null)}
              >
                ×
              </button>
            </div>
          )}
        </div>
      </div>

      {/* タブナビゲーション */}
      <div className="story-tabs">
        <button
          className={`tab-button ${activeTab === 'past' ? 'active' : ''}`}
          onClick={() => setActiveTab('past')}
        >
          📚 過去のストーリー ({stories.past.length})
        </button>
        <button
          className={`tab-button ${activeTab === 'future' ? 'active' : ''}`}
          onClick={() => setActiveTab('future')}
        >
          🌟 未来のストーリー ({stories.future.length})
        </button>
      </div>

      {/* ストーリー一覧 */}
      <div className="story-timeline">
        {currentStories.length === 0 ? (
          <div className="story-empty">
            <p>ストーリーはまだ追加されていません</p>
          </div>
        ) : (
          currentStories.map((story, index) => (
            <div key={story.id} className={`story-card story-type-${activeTab}`}>
              <div className="story-card-header">
                <div className="story-date-badge">
                  {formatDate(story.story_date)}
                </div>
                <div className="story-actions">
                  {editingStoryId === story.id ? (
                    <>
                      <button
                        className="btn-small btn-save"
                        onClick={() => handleUpdateStory(story.id)}
                        disabled={savingStory}
                      >
                        ✓
                      </button>
                      <button
                        className="btn-small btn-cancel"
                        onClick={() => setEditingStoryId(null)}
                        disabled={savingStory}
                      >
                        ×
                      </button>
                    </>
                  ) : (
                    <>
                      <button
                        className="btn-small btn-edit"
                        onClick={() => {
                          setEditingStoryId(story.id);
                          setEditingText(story.note_text);
                          setEditingDate(story.story_date);
                        }}
                      >
                        編集
                      </button>
                      <button
                        className="btn-small btn-delete"
                        onClick={() => handleDeleteStory(story.id)}
                      >
                        削除
                      </button>
                    </>
                  )}
                </div>
              </div>

              {editingStoryId === story.id ? (
                <div className="story-edit-form">
                  <input
                    type="date"
                    value={editingDate}
                    onChange={(e) => setEditingDate(e.target.value)}
                    className="story-date-input"
                  />
                  <textarea
                    value={editingText}
                    onChange={(e) => setEditingText(e.target.value)}
                    className="story-textarea"
                    rows={4}
                  />
                </div>
              ) : (
                <>
                  <div className="story-content">
                    <p>{story.note_text}</p>
                  </div>
                  {story.image_comment && (
                    <div className="story-image-comment">
                      <strong>🎨 画像補足:</strong> {story.image_comment}
                    </div>
                  )}
                  {story.story_image && (
                    <div className="story-image">
                      <img src={story.story_image} alt="ストーリー画像" />
                    </div>
                  )}
                </>
              )}

              <div className="story-meta">
                {new Date(story.created_at).toLocaleString('ja-JP')}
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  );
};

export default FutureStory;
