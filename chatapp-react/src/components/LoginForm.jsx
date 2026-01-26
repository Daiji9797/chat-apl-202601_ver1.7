import React, { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';

const LoginForm = ({ onShowRegister, onShowContact }) => {
  const { login } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [typewriterText, setTypewriterText] = useState('');

  const fullText = `人生を豊かにするために。
自分がトキメキを感じるイメージをより具体的に。
自分が何にトキメクのかを具体的にできると違った未来が見えてくるかもしれません。`;

  useEffect(() => {
    let index = 0;
    const timer = setInterval(() => {
      if (index <= fullText.length) {
        setTypewriterText(fullText.slice(0, index));
        index++;
      } else {
        clearInterval(timer);
      }
    }, 50);

    return () => clearInterval(timer);
  }, []);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      await login(email, password);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="auth-container">
      <div className="auth-logo">
        <img src="./assets/title_logo.png" alt="Logo" />
      </div>
      <div className="auth-content">
        <div className="auth-left">
          <div className="typewriter-text">
            {typewriterText.split('\n').map((line, index) => (
              <p key={index}>{line}</p>
            ))}
          </div>
        </div>
        <div className="auth-right">
          <div className="auth-box">
        <h1>ログイン</h1>
        {error && <div className="error-message" style={{ display: 'block' }}>{error}</div>}
        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label htmlFor="email">メールアドレス</label>
            <input
              type="email"
              id="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
            />
          </div>
          <div className="form-group">
            <label htmlFor="password">パスワード</label>
            <input
              type="password"
              id="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
          </div>
          <button type="submit" className="btn btn-primary" disabled={loading}>
            {loading ? 'ログイン中...' : 'ログイン'}
          </button>
          <p className="auth-switch">
            アカウントをお持ちでないですか？{' '}
            <button
              type="button"
              className="link-btn"
              onClick={() => onShowRegister()}
            >
              新規登録
            </button>
          </p>
          <p className="auth-switch" style={{ marginTop: '10px' }}>
            <button
              type="button"
              className="link-btn"
              onClick={() => onShowContact()}
              style={{ fontSize: '14px' }}
            >
              📧 お問い合わせ
            </button>
          </p>
        </form>
          </div>
        </div>
      </div>
    </div>
  );
};

export default LoginForm;
