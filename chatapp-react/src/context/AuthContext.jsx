import React, { createContext, useContext, useState, useEffect } from 'react';
import { api } from '../services/api';

const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  // 初期化時にlocalStorageから復元
  useEffect(() => {
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
      try {
        setUser(JSON.parse(storedUser));
      } catch (err) {
        console.error('Failed to parse stored user:', err);
      }
    }
    setLoading(false);
  }, []);

  const fetchUserPoints = async () => {
    try {
      const response = await api.getUser();
      const userData = response.data.user;
      setUser(userData);
      localStorage.setItem('user', JSON.stringify(userData));
      return userData;
    } catch (err) {
      console.error('Failed to fetch user points:', err);
      return null;
    }
  };

  const login = async (email, password) => {
    const response = await api.login(email, password);
    const userData = response.data.user;
    const token = response.data.token;

    api.setToken(token);
    localStorage.setItem('user', JSON.stringify(userData));
    setUser(userData);

    return userData;
  };

  const register = async (email, password, name) => {
    const response = await api.register(email, password, name);
    const userData = response.data.user;
    const token = response.data.token;

    api.setToken(token);
    localStorage.setItem('user', JSON.stringify(userData));
    setUser(userData);

    return userData;
  };

  const logout = () => {
    api.removeToken();
    localStorage.removeItem('user');
    setUser(null);
  };

  const deleteAccount = async (password) => {
    await api.deleteAccount(password);
    logout();
  };

  const value = {
    user,
    loading,
    isLoggedIn: !!user,
    login,
    register,
    logout,
    deleteAccount,
    fetchUserPoints,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
};
