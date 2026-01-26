import { useState, useCallback } from 'react';
import { api } from '../services/api';

export const useApi = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const request = useCallback(async (fn) => {
    setLoading(true);
    setError(null);
    try {
      const result = await fn();
      return result;
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  }, []);

  return { loading, error, request };
};

export const useRooms = () => {
  const [rooms, setRooms] = useState([]);
  const { loading, error, request } = useApi();

  const loadRooms = useCallback(async () => {
    const result = await request(() => api.getRooms());
    setRooms(result.data || []);
    return result.data;
  }, [request]);

  const createRoom = useCallback(
    async (name) => {
      const result = await request(() => api.createRoom(name));
      setRooms((prev) => [result.data, ...prev]);
      return result.data;
    },
    [request]
  );

  const updateRoom = useCallback(
    async (roomId, data) => {
      const result = await request(() => api.updateRoom(roomId, data));
      setRooms((prev) =>
        prev.map((r) => (r.id === roomId ? { ...r, ...result.data } : r))
      );
      return result.data;
    },
    [request]
  );

  const deleteRoom = useCallback(
    async (roomId) => {
      await request(() => api.deleteRoom(roomId));
      setRooms((prev) => prev.filter((r) => r.id !== roomId));
    },
    [request]
  );

  return { rooms, loading, error, loadRooms, createRoom, updateRoom, deleteRoom };
};

export const useChat = () => {
  const { loading, error, request } = useApi();

  const getRoom = useCallback(
    (roomId) => request(() => api.getRoom(roomId)),
    [request]
  );

  const sendMessage = useCallback(
    (message, roomId, history, provider = 'openai') =>
      request(() => api.sendChat(message, roomId, history, provider)),
    [request]
  );

  const deleteMessage = useCallback(
    (roomId, messageId) =>
      request(() => api.deleteMessage(roomId, messageId)),
    [request]
  );

  const likeMessage = useCallback(
    (roomId, messageId, like = true) =>
      request(() => api.likeMessage(roomId, messageId, like)),
    [request]
  );

  const createGoalNote = useCallback(
    (roomId, noteText, messageId = null) =>
      request(() => api.createGoalNote(roomId, noteText, messageId)),
    [request]
  );

  const getGoalNotes = useCallback(
    (roomId) => request(() => api.getGoalNotes(roomId)),
    [request]
  );

  const getStories = useCallback(
    (roomId = null, storyType = null) =>
      request(() => api.getStories(roomId, storyType)),
    [request]
  );

  const createStory = useCallback(
    (roomId, noteText, storyDate = null, storyImage = null, imageComment = null) =>
      request(() => api.createStory(roomId, noteText, storyDate, storyImage, imageComment)),
    [request]
  );

  const updateStory = useCallback(
    (storyId, noteText = null, storyDate = null, imageComment = null, storyImage = null) =>
      request(() => api.updateStory(storyId, noteText, storyDate, imageComment, storyImage)),
    [request]
  );

  const deleteStory = useCallback(
    (storyId) =>
      request(() => api.deleteStory(storyId)),
    [request]
  );

  const getRoomGoals = useCallback(
    () => request(() => api.getRoomGoals()),
    [request]
  );

  const generateStoryImage = useCallback(
    (noteText, imageComment = '', provider = 'openai') => 
      request(() => api.generateStoryImage(noteText, imageComment, provider)),
    [request]
  );

  return {
    loading,
    error,
    getRoom,
    sendMessage,
    deleteMessage,
    likeMessage,
    createGoalNote,
    getGoalNotes,
    getStories,
    createStory,
    updateStory,
    deleteStory,
    getRoomGoals,
    generateStoryImage,
  };
};
