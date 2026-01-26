/**
 * API通信モジュール
 */

// 環境変数から API URL を取得（ある場合）し、なければ実行中オリジンを利用
const ORIGIN_API_BASE = (typeof window !== 'undefined' && window.location && window.location.origin)
  ? `${window.location.origin}/api/`
  : '/api/';

const API_BASE_URL = import.meta.env.VITE_API_URL
  ? `${import.meta.env.VITE_API_URL}/api/`
  : ORIGIN_API_BASE;

console.log('API_BASE_URL:', API_BASE_URL);

class APIClient {
  constructor() {
    this.token = localStorage.getItem('token');
  }

  setToken(token) {
    this.token = token;
    localStorage.setItem('token', token);
  }

  getToken() {
    return localStorage.getItem('token');
  }

  removeToken() {
    localStorage.removeItem('token');
  }

  getHeaders() {
    const headers = {
      'Content-Type': 'application/json',
    };

    const token = this.getToken();
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    return headers;
  }

  async request(endpoint, options = {}) {
    const url = API_BASE_URL + endpoint;
    const response = await fetch(url, {
      headers: this.getHeaders(),
      credentials: 'include', // Cookieとトークンを送信
      ...options,
    });

    const rawText = await response.text();
    console.log('API raw response:', rawText);
    
    let data;
    try {
      data = rawText ? JSON.parse(rawText) : {};
    } catch (err) {
      console.error('API response is not JSON:', rawText);
      console.error('Parse error:', err);
      throw new Error('サーバー応答の解析に失敗しました');
    }

    if (!response.ok) {
      throw new Error(data.message || 'API request failed');
    }

    return data;
  }

  // 認証関連
  async register(email, password, name = '') {
    return this.request('register.php', {
      method: 'POST',
      body: JSON.stringify({ email, password, name }),
    });
  }

  async login(email, password) {
    return this.request('login.php', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });
  }

  // ルーム関連
  async getRooms(limit = 50, offset = 0) {
    return this.request(`rooms.php?limit=${limit}&offset=${offset}`, {
      method: 'GET',
    });
  }

  async createRoom(name = 'New Chat') {
    return this.request('rooms.php', {
      method: 'POST',
      body: JSON.stringify({ name }),
    });
  }

  async getRoom(roomId) {
    return this.request(`room.php?roomId=${roomId}`, {
      method: 'GET',
    });
  }

  async updateRoom(roomId, data) {
    return this.request(`room.php?roomId=${roomId}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  }

  async deleteRoom(roomId) {
    return this.request(`room.php?roomId=${roomId}`, {
      method: 'POST',
      body: JSON.stringify({ _method: 'DELETE' }),
    });
  }

  // チャット関連
  async sendChat(message, roomId, history = [], provider = 'openai') {
    const trimmedHistory = Array.isArray(history) ? history.slice(-20) : [];
    return this.request('chat.php', {
      method: 'POST',
      body: JSON.stringify({ message, roomId, history: trimmedHistory, provider }),
    });
  }

  async deleteMessage(roomId, messageId) {
    return this.request(
      `message.php?roomId=${roomId}&messageId=${messageId}`,
      {
        method: 'POST',
        body: JSON.stringify({ _method: 'DELETE' }),
      }
    );
  }

  async likeMessage(roomId, messageId, like = true) {
    return this.request('message_like.php', {
      method: 'POST',
      body: JSON.stringify({ roomId, messageId, like }),
    });
  }

  // 目標達成メモ
  async createGoalNote(roomId, noteText, messageId = null) {
    return this.request('goal.php', {
      method: 'POST',
      body: JSON.stringify({ roomId, note_text: noteText, messageId }),
    });
  }

  async getGoalNotes(roomId = null, limit = 50, offset = 0) {
    const roomQuery = roomId ? `roomId=${roomId}&` : '';
    return this.request(`goal.php?${roomQuery}limit=${limit}&offset=${offset}`, {
      method: 'GET',
    });
  }

  // 未来Story
  async getStories(roomId = null, storyType = null) {
    let query = 'story.php?';
    if (roomId) query += `roomId=${roomId}&`;
    if (storyType) query += `storyType=${storyType}&`;
    return this.request(query, {
      method: 'GET',
    });
  }

  async getRoomGoals() {
    return this.request('story.php?action=room_goals', {
      method: 'GET',
    });
  }

  async createStory(roomId, noteText, storyDate = null, storyImage = null, imageComment = null) {
    return this.request('story.php', {
      method: 'POST',
      body: JSON.stringify({ 
        roomId, 
        note_text: noteText, 
        story_date: storyDate,
        story_image: storyImage,
        image_comment: imageComment
      }),
    });
  }

  async updateStory(storyId, noteText = null, storyDate = null, imageComment = null, storyImage = null) {
    return this.request('story.php', {
      method: 'PUT',
      body: JSON.stringify({ 
        storyId, 
        note_text: noteText,
        story_date: storyDate,
        image_comment: imageComment,
        story_image: storyImage
      }),
    });
  }

  async generateStoryImage(noteText, imageComment = '', provider = 'openai') {
    return this.request('story_image.php', {
      method: 'POST',
      body: JSON.stringify({ 
        note_text: noteText, 
        image_comment: imageComment,
        provider: provider
      })
    });
  }

  async deleteStory(storyId) {
    return this.request(`story.php?storyId=${storyId}`, {
      method: 'DELETE',
    });
  }

  // アカウント関連
  async getUser() {
    return this.request('user.php', {
      method: 'GET',
    });
  }

  async deleteAccount(password) {
    return this.request('user.php', {
      method: 'POST',
      body: JSON.stringify({ _method: 'DELETE', password }),
    });
  }

  // 今日のテーマランキング
  async getTodayTopics() {
    return this.request('today-topics.php', {
      method: 'GET',
    });
  }
}

export const api = new APIClient();
