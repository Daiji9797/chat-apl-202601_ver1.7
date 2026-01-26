import React, { useState, useMemo } from 'react';
import { api } from '../services/api';
import { useAuth } from '../context/AuthContext';
import '../styles/ChangePasswordForm.css';

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

const ChangePasswordForm = ({ onClose, onSuccess }) => {
  const { user } = useAuth();
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [loading, setLoading] = useState(false);

  // パスワード強度チェック
  const passwordStrength = useMemo(() => {
    if (!newPassword) return { level: 0, label: '', color: '', checks: {} };

    const email = user?.email || '';
    const checks = {
      length: newPassword.length >= 8,
      lowercase: /[a-z]/.test(newPassword),
      uppercase: /[A-Z]/.test(newPassword),
      number: /[0-9]/.test(newPassword),
      notEmail:
        email &&
        newPassword.toLowerCase() !== email.toLowerCase() &&
        !newPassword.toLowerCase().includes(email.split('@')[0].toLowerCase()),
      noRepeat: !hasRepeatedChars(newPassword, 3),
      noSeqNumber: !hasSequentialNumbers(newPassword, 3),
      noKeyboardSeq: !hasKeyboardSequence(newPassword, 3),
    };

    let strength = 0;
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
  }, [newPassword, user?.email]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');

    // バリデーション
    if (!currentPassword) {
      setError('現在のパスワードを入力してください');
      return;
    }

    if (!newPassword) {
      setError('新しいパスワードを入力してください');
      return;
    }

    if (newPassword !== confirmPassword) {
      setError('新しいパスワードが一致しません');
      return;
    }

    if (passwordStrength.level < 2) {
      setError('パスワードが弱すぎます。8文字以上で、大文字・小文字・数字を含め、連番/連続/キーボード列の繰り返しを避けてください。');
      return;
    }

    if (passwordStrength.checks && passwordStrength.checks.notEmail === false) {
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
      const response = await api.request('change-password.php', {
        method: 'POST',
        body: JSON.stringify({
          current_password: currentPassword,
          new_password: newPassword,
        }),
      });

      console.log('Change password response:', response);

      if (response && response.success) {
        setSuccess('パスワードを変更しました');
        setCurrentPassword('');
        setNewPassword('');
        setConfirmPassword('');
        
        // 2秒後にモーダルを閉じる
        setTimeout(() => {
          onSuccess && onSuccess();
          onClose();
        }, 1500);
      } else {
        setError(response?.message || 'パスワード変更に失敗しました');
      }
    } catch (err) {
      console.error('Change password error:', err);
      setError(err.message || 'パスワード変更に失敗しました');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal-content change-password-modal" onClick={(e) => e.stopPropagation()}>
        <div className="modal-header">
          <h2>パスワード変更</h2>
          <button className="modal-close" onClick={onClose}>✕</button>
        </div>

        <form onSubmit={handleSubmit} className="change-password-form">
          {error && <div className="error-message" style={{ display: 'block' }}>{error}</div>}
          {success && <div className="success-message" style={{ display: 'block' }}>{success}</div>}

          <div className="form-group">
            <label htmlFor="current-password">現在のパスワード</label>
            <input
              type="password"
              id="current-password"
              value={currentPassword}
              onChange={(e) => setCurrentPassword(e.target.value)}
              placeholder="現在のパスワードを入力"
              disabled={loading}
              required
            />
          </div>

          <div className="form-group">
            <label htmlFor="new-password">新しいパスワード</label>
            <input
              type="password"
              id="new-password"
              value={newPassword}
              onChange={(e) => setNewPassword(e.target.value)}
              placeholder="新しいパスワードを入力"
              disabled={loading}
              required
            />
            
            {newPassword && (
              <div className="password-strength">
                <div className="password-strength-bar">
                  <div
                    className="password-strength-fill"
                    style={{
                      width: `${passwordStrength.strength}%`,
                      backgroundColor: passwordStrength.color,
                    }}
                  />
                </div>
                <div className="password-strength-label" style={{ color: passwordStrength.color }}>
                  強度: {passwordStrength.label}
                </div>
                <div className="password-requirements">
                  <div style={{
                    color: passwordStrength.checks.length ? '#10b981' : '#6b7280',
                    background: passwordStrength.checks.length ? '#d1fae5' : '#f3f4f6'
                  }}>
                    {passwordStrength.checks.length ? '✓' : '✗'} 8文字以上
                  </div>
                  <div style={{
                    color: passwordStrength.checks.lowercase ? '#10b981' : '#6b7280',
                    background: passwordStrength.checks.lowercase ? '#d1fae5' : '#f3f4f6'
                  }}>
                    {passwordStrength.checks.lowercase ? '✓' : '✗'} 小文字
                  </div>
                  <div style={{
                    color: passwordStrength.checks.uppercase ? '#10b981' : '#6b7280',
                    background: passwordStrength.checks.uppercase ? '#d1fae5' : '#f3f4f6'
                  }}>
                    {passwordStrength.checks.uppercase ? '✓' : '✗'} 大文字
                  </div>
                  <div style={{

                    color: passwordStrength.checks.notEmail ? '#10b981' : '#6b7280',
                    background: passwordStrength.checks.notEmail ? '#d1fae5' : '#f3f4f6'
                  }}>
                    {passwordStrength.checks.notEmail ? '✓' : '✗'} メールと異なる
                  </div>
                  <div style={{
                    color: passwordStrength.checks.noRepeat ? '#10b981' : '#6b7280',
                    background: passwordStrength.checks.noRepeat ? '#d1fae5' : '#f3f4f6'
                  }}>
                    {passwordStrength.checks.noRepeat ? '✓' : '✗'} 同一文字3連続なし
                  </div>
                  <div style={{
                    color: passwordStrength.checks.noSeqNumber ? '#10b981' : '#6b7280',
                    background: passwordStrength.checks.noSeqNumber ? '#d1fae5' : '#f3f4f6'
                  }}>
                    {passwordStrength.checks.noSeqNumber ? '✓' : '✗'} 数字の昇降順3連続なし
                  </div>
                  <div style={{
                    color: passwordStrength.checks.noKeyboardSeq ? '#10b981' : '#6b7280',
                    background: passwordStrength.checks.noKeyboardSeq ? '#d1fae5' : '#f3f4f6'
                  }}>
                    {passwordStrength.checks.noKeyboardSeq ? '✓' : '✗'} キーボード横並び3連続なし
                  </div>
                </div>
              </div>
            )}
          </div>

          <div className="form-group">
            <label htmlFor="confirm-password">新しいパスワード（確認）</label>
            <input
              type="password"
              id="confirm-password"
              value={confirmPassword}
              onChange={(e) => setConfirmPassword(e.target.value)}
              placeholder="新しいパスワードを再入力"
              disabled={loading}
              required
            />
            {newPassword && confirmPassword && newPassword !== confirmPassword && (
              <div style={{ color: '#ef4444', fontSize: '12px', marginTop: '4px' }}>
                パスワードが一致しません
              </div>
            )}
            {newPassword && confirmPassword && newPassword === confirmPassword && (
              <div style={{ color: '#10b981', fontSize: '12px', marginTop: '4px' }}>
                ✓ パスワードが一致しています
              </div>
            )}
          </div>

          <div className="form-actions">
            <button
              type="button"
              className="btn btn-secondary"
              onClick={onClose}
              disabled={loading}
            >
              キャンセル
            </button>
            <button
              type="submit"
              className="btn btn-primary"
              disabled={
                loading ||
                newPassword !== confirmPassword ||
                passwordStrength.level < 2 ||
                passwordStrength.checks.notEmail === false ||
                !passwordStrength.checks.noRepeat ||
                !passwordStrength.checks.noSeqNumber ||
                !passwordStrength.checks.noKeyboardSeq
              }
            >
              {loading ? 'パスワード変更中...' : 'パスワード変更'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default ChangePasswordForm;
