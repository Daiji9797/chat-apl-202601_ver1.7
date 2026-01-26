import React, { useState, useEffect } from 'react';
import MessageList from './MessageList';

const RoomChat = ({ roomId, onRoomSelect, rooms, updateRoom, deleteRoom }) => {
  const [room, setRoom] = useState(null);
  const [aiProvider, setAiProvider] = useState('openai');

  useEffect(() => {
    if (roomId && rooms) {
      const currentRoom = rooms.find((r) => r.id === roomId);
      console.log('RoomChat - roomId:', roomId, 'rooms:', rooms, 'found:', currentRoom);
      setRoom(currentRoom);
    } else {
      setRoom(null);
    }
  }, [roomId, rooms]);

  const handleDeleteRoom = async () => {
    if (!window.confirm('このチャットを削除してもよろしいですか？')) return;

    try {
      await deleteRoom(roomId);
      onRoomSelect(null);
    } catch (err) {
      console.error('Failed to delete room:', err);
    }
  };

  const handleRenameRoom = async () => {
    const newName = window.prompt('新しいルーム名を入力してください', room?.name || '');
    if (!newName || !newName.trim()) return;

    try {
      await updateRoom(roomId, { name: newName.trim() });
    } catch (err) {
      console.error('Failed to rename room:', err);
    }
  };

  if (!room) {
    return (
      <div className="chat-content">
        <div className="chat-placeholder">
          <p>チャットルームを選択してください</p>
        </div>
      </div>
    );
  }

  return (
    <div className="chat-content">
      <div className="room-header">
        <h2>{room.name}</h2>
        <div className="room-actions">
          <div className="provider-selector-chat">
            <label>AI:</label>
            <select value={aiProvider} onChange={(e) => setAiProvider(e.target.value)}>
              <option value="openai">OpenAI</option>
              <option value="gemini">Gemini</option>
            </select>
          </div>
          <button className="btn btn-sm" onClick={handleRenameRoom}>
            名前変更（目的を表現）
          </button>
          <button className="btn btn-sm btn-danger" onClick={handleDeleteRoom}>
            削除
          </button>
        </div>
      </div>
      <div className="room-notice">
        ※ チャット内容は直近の20メッセージの会話履歴を参照しています
      </div>
      <MessageList roomId={roomId} aiProvider={aiProvider} />
    </div>
  );
};

export default RoomChat;
