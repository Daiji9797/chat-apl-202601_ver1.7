import React, { useState, useEffect } from 'react';
import Sidebar from './Sidebar';
import RoomChat from './RoomChat';
import { useRooms } from '../hooks/useApi';
import { useAuth } from '../context/AuthContext';

const ChatPage = ({ onShowStats, onShowGacha, onShowFutureStory, onShowContact }) => {
  const [selectedRoomId, setSelectedRoomId] = useState(null);
  const { rooms, loadRooms, createRoom, updateRoom, deleteRoom } = useRooms();
  const { fetchUserPoints } = useAuth();

  useEffect(() => {
    loadRooms();
    // チャットページがマウントされた時にポイント情報を更新
    fetchUserPoints();
  }, []);

  return (
    <div className="chat-container">
      <Sidebar 
        selectedRoomId={selectedRoomId} 
        onRoomSelect={setSelectedRoomId}
        rooms={rooms}
        createRoom={createRoom}
        loadRooms={loadRooms}
        onShowStats={onShowStats}
        onShowGacha={onShowGacha}
        onShowFutureStory={() => {
          onShowFutureStory();
        }}
        onShowContact={onShowContact}
      />
      <div className="chat-area">
        <RoomChat 
          roomId={selectedRoomId} 
          onRoomSelect={setSelectedRoomId}
          rooms={rooms}
          updateRoom={updateRoom}
          deleteRoom={deleteRoom}
        />
      </div>
    </div>
  );
};

export default ChatPage;
