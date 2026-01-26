import { useState } from 'react';
import '../styles/ContactForm.css';

const ContactForm = ({ onClose }) => {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    subject: '',
    message: ''
  });
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState(false);
  const [error, setError] = useState('');

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      const token = localStorage.getItem('token');
      const response = await fetch('http://localhost/chatapp/src/api/contact.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          ...(token && { 'Authorization': `Bearer ${token}` })
        },
        body: JSON.stringify(formData)
      });

      const data = await response.json();

      if (data.success) {
        setSuccess(true);
        setTimeout(() => {
          if (onClose) onClose();
        }, 2000);
      } else {
        setError(data.message || 'お問い合わせの送信に失敗しました');
      }
    } catch (err) {
      setError('ネットワークエラーが発生しました');
    } finally {
      setLoading(false);
    }
  };

  if (success) {
    return (
      <div className="contact-form-container">
        <div className="contact-form-success">
          <div className="success-icon">✓</div>
          <h2>送信完了</h2>
          <p>お問い合わせありがとうございます。</p>
          <p>内容を確認の上、ご連絡させていただきます。</p>
        </div>
      </div>
    );
  }

  return (
    <div className="contact-form-container">
      <div className="contact-form">
        <div className="contact-form-header">
          <h2>📧 お問い合わせ</h2>
          {onClose && (
            <button className="close-button" onClick={onClose}>×</button>
          )}
        </div>

        <form onSubmit={handleSubmit}>
          {error && (
            <div className="error-message">
              {error}
            </div>
          )}

          <div className="form-group">
            <label htmlFor="name">お名前 *</label>
            <input
              type="text"
              id="name"
              name="name"
              value={formData.name}
              onChange={handleChange}
              required
              minLength={2}
              placeholder="山田 太郎"
            />
          </div>

          <div className="form-group">
            <label htmlFor="email">メールアドレス *</label>
            <input
              type="email"
              id="email"
              name="email"
              value={formData.email}
              onChange={handleChange}
              required
              placeholder="example@email.com"
            />
          </div>

          <div className="form-group">
            <label htmlFor="subject">件名 *</label>
            <input
              type="text"
              id="subject"
              name="subject"
              value={formData.subject}
              onChange={handleChange}
              required
              minLength={3}
              placeholder="お問い合わせの件名"
            />
          </div>

          <div className="form-group">
            <label htmlFor="message">お問い合わせ内容 *</label>
            <textarea
              id="message"
              name="message"
              value={formData.message}
              onChange={handleChange}
              required
              minLength={10}
              rows={6}
              placeholder="お問い合わせ内容を詳しくご記入ください"
            />
          </div>

          <button 
            type="submit" 
            className="submit-button"
            disabled={loading}
          >
            {loading ? '送信中...' : '送信する'}
          </button>
        </form>
      </div>
    </div>
  );
};

export default ContactForm;
