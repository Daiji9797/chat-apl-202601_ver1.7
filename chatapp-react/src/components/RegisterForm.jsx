import React, { useState, useMemo } from 'react';
import { useAuth } from '../context/AuthContext';
import './RegisterForm.css';

const hasSequentialNumbers = (password, minLen = 3) => {
  const len = password.length;
  let run = 1;
  for (let i = 1; i < len; i++) {
    if (/[0-9]/.test(password[i - 1]) && /[0-9]/.test(password[i])) {
      const diff = Number(password[i]) - Number(password[i - 1]);
      if (diff === 1 || diff === -1) {
        run++;
        if (run >= minLen) return true;
        continue;
      }
    }
    run = 1;
  }
  return false;
};

const hasRepeatedChars = (password, minLen = 3) => {
  const len = password.length;
  let run = 1;
  for (let i = 1; i < len; i++) {
    if (password[i].toLowerCase() === password[i - 1].toLowerCase()) {
      run++;
      if (run >= minLen) return true;
    } else {
      run = 1;
    }
  }
  return false;
};

const hasKeyboardSequence = (password, minLen = 3) => {
  const rows = ['qwertyuiop', 'asdfghjkl', 'zxcvbnm', '1234567890'];
  const lower = password.toLowerCase();
  return rows.some((row) => {
    const rev = row.split('').reverse().join('');
    for (let i = 0; i <= row.length - minLen; i++) {
      const chunk = row.slice(i, i + minLen);
      const revChunk = rev.slice(i, i + minLen);
      if (lower.includes(chunk) || lower.includes(revChunk)) return true;
    }
    return false;
  });
};

const RegisterForm = ({ onShowLogin, onShowContact }) => {
  const { register } = useAuth();
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [agreedToTerms, setAgreedToTerms] = useState(false);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  // パスワード強度チェック
  const passwordStrength = useMemo(() => {
    if (!password) return { level: 0, label: '', color: '' };

    let strength = 0;
    const checks = {
      length: password.length >= 8,
      lowercase: /[a-z]/.test(password),
      uppercase: /[A-Z]/.test(password),
      number: /[0-9]/.test(password),
      notEmail: email && password.toLowerCase() !== email.toLowerCase() && !password.toLowerCase().includes(email.split('@')[0].toLowerCase()),
      noRepeat: !hasRepeatedChars(password, 3),
      noSeqNumber: !hasSequentialNumbers(password, 3),
      noKeyboardSeq: !hasKeyboardSequence(password, 3),
    };

    if (checks.length) strength += 16;
    if (checks.lowercase) strength += 14;
    if (checks.uppercase) strength += 14;
    if (checks.number) strength += 14;
    if (checks.notEmail) strength += 14;
    if (checks.noRepeat) strength += 14;
    if (checks.noSeqNumber) strength += 14;
    if (checks.noKeyboardSeq) strength += 14;

    let level, label, color;
    if (strength < 40) {
      level = 1;
      label = '弱い';
      color = '#ef4444';
    } else if (strength < 70) {
      level = 2;
      label = '普通';
      color = '#f59e0b';
    } else {
      level = 3;
      label = '強い';
      color = '#10b981';
    }

    return { level, label, color, strength, checks };
  }, [password, email]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');

    // 利用規約同意チェック
    if (!agreedToTerms) {
      setError('利用規約に同意する必要があります。');
      return;
    }

    // パスワード強度チェック
    if (passwordStrength.level < 2) {
      setError('パスワードが弱すぎます。8文字以上で、大文字・小文字・数字を含めてください。');
      return;
    }

    if (!passwordStrength.checks.notEmail) {
      setError('パスワードにメールアドレスと同じ内容は使用できません。');
      return;
    }

    if (!passwordStrength.checks.noRepeat) {
      setError('同じ文字を3回以上連続で使用できません。');
      return;
    }

    if (!passwordStrength.checks.noSeqNumber) {
      setError('数字の昇順・降順を3桁以上連続で使用できません。');
      return;
    }

    if (!passwordStrength.checks.noKeyboardSeq) {
      setError('キーボードの横並び（qwerty/asdf/123など）を3文字以上連続で使用できません。');
      return;
    }

    setLoading(true);

    try {
      await register(email, password, name);
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
      <div className="auth-box">
        <h1>新規登録</h1>
        {error && <div className="error-message" style={{ display: 'block' }}>{error}</div>}
        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label htmlFor="name">名前</label>
            <input
              type="text"
              id="name"
              value={name}
              onChange={(e) => setName(e.target.value)}
            />
          </div>
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
            {password && (
              <div className="password-strength">
                <div className="password-strength-bar">
                  <div 
                    className="password-strength-fill"
                    style={{ 
                      width: `${passwordStrength.strength}%`,
                      backgroundColor: passwordStrength.color 
                    }}
                  />
                </div>
                <div className="password-strength-label" style={{ color: passwordStrength.color }}>
                  {passwordStrength.label}
                </div>
                <div className="password-requirements">
                  <div className={passwordStrength.checks.length ? 'req-met' : 'req-unmet'}>
                    {passwordStrength.checks.length ? '✓' : '○'} 8文字以上
                  </div>
                  <div className={passwordStrength.checks.lowercase ? 'req-met' : 'req-unmet'}>
                    {passwordStrength.checks.lowercase ? '✓' : '○'} 小文字を含む
                  </div>
                  <div className={passwordStrength.checks.uppercase ? 'req-met' : 'req-unmet'}>
                    {passwordStrength.checks.uppercase ? '✓' : '○'} 大文字を含む
                  </div>
                  <div className={passwordStrength.checks.number ? 'req-met' : 'req-unmet'}>
                    {passwordStrength.checks.number ? '✓' : '○'} 数字を含む
                  </div>
                  <div className={passwordStrength.checks.noRepeat ? 'req-met' : 'req-unmet'}>
                    {passwordStrength.checks.noRepeat ? '✓' : '○'} 同一文字3連続なし
                  </div>
                  <div className={passwordStrength.checks.noSeqNumber ? 'req-met' : 'req-unmet'}>
                    {passwordStrength.checks.noSeqNumber ? '✓' : '○'} 数字の昇降順3連続なし
                  </div>
                  <div className={passwordStrength.checks.noKeyboardSeq ? 'req-met' : 'req-unmet'}>
                    {passwordStrength.checks.noKeyboardSeq ? '✓' : '○'} キーボード横並び3連続なし
                  </div>
                  <div className={passwordStrength.checks.notEmail ? 'req-met' : 'req-unmet'}>
                    {passwordStrength.checks.notEmail ? '✓' : '○'} メールと異なる
                  </div>
                </div>
              </div>
            )}
          </div>
          <div className="form-group terms-agreement">
            <label htmlFor="agree-terms" className="checkbox-label">
              <input
                type="checkbox"
                id="agree-terms"
                checked={agreedToTerms}
                onChange={(e) => setAgreedToTerms(e.target.checked)}
              />
              <span>
                <a href="/terms.html" target="_blank" rel="noopener noreferrer" className="terms-link">
                  利用規約
                </a>
                に同意します
              </span>
            </label>
          </div>
          <button type="submit" className="btn btn-primary btn-glass-flash" disabled={loading || !agreedToTerms}>
            {loading ? '登録中...' : '登録'}
          </button>
          <p className="auth-switch">
            既にアカウントをお持ちですか？{' '}
            <button
              type="button"
              className="link-btn"
              onClick={() => onShowLogin()}
            >
              ログイン
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
  );
};

export default RegisterForm;
