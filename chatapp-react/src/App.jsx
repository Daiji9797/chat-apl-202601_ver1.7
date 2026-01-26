import React, { useState, useEffect, useRef } from 'react';
import { AuthProvider, useAuth } from './context/AuthContext';
import LoginForm from './components/LoginForm';
import RegisterForm from './components/RegisterForm';
import ChatPage from './components/ChatPage';
import RoomStats from './components/RoomStats';
import GachaGame from './components/GachaGame';
import FutureStory from './components/FutureStory';
import AdminPanel from './components/AdminPanel';
import ContactForm from './components/ContactForm';
import './App.css';

function StalkerComponent({ stalkerImage }) {
  const stalkerRef = useRef(null);

  useEffect(() => {
    const handleMouseMove = (e) => {
      if (stalkerRef.current) {
        const x = e.clientX;
        const y = e.clientY;
        stalkerRef.current.style.transform = `translate(-50%, -50%) translate3d(${x}px, ${y}px, 0)`;
      }
    };

    window.addEventListener('mousemove', handleMouseMove);
    return () => window.removeEventListener('mousemove', handleMouseMove);
  }, []);

  return stalkerImage ? (
    <div
      ref={stalkerRef}
      className="stalker"
      style={{
        backgroundImage: `url('${stalkerImage}')`,
      }}
      id="js-stalker"
    />
  ) : null;
}

function AppContent() {
  const { isLoggedIn, loading, user, fetchUserPoints } = useAuth();
  const [showRegister, setShowRegister] = useState(false);
  const [showStats, setShowStats] = useState(false);
  const [showGacha, setShowGacha] = useState(false);
  const [showFutureStory, setShowFutureStory] = useState(false);
  const [showContact, setShowContact] = useState(false);

  if (loading) {
    return <div className="auth-container">ì«Ç›çûÇ›íÜ...</div>;
  }

  if (!isLoggedIn) {
    return (
      <>
        {showContact && <ContactForm onClose={() => setShowContact(false)} />}
        {showRegister ? (
          <RegisterForm
            onShowLogin={() => setShowRegister(false)}
            onShowContact={() => setShowContact(true)}
          />
        ) : (
          <LoginForm
            onShowRegister={() => setShowRegister(true)}
            onShowContact={() => setShowContact(true)}
          />
        )}
      </>
    );
  }

  if (user?.is_admin) {
    return <AdminPanel />;
  }

  if (showStats) {
    return (
      <>
        {showContact && <ContactForm onClose={() => setShowContact(false)} />}
        <StalkerComponent stalkerImage={user?.stalker_image} />
        <RoomStats onBackToChat={() => setShowStats(false)} />
      </>
    );
  }

  if (showGacha) {
    return (
      <>
        {showContact && <ContactForm onClose={() => setShowContact(false)} />}
        <StalkerComponent stalkerImage={user?.stalker_image} />
        <div className="gacha-container">
          <GachaGame
            onBack={async () => {
              try {
                await fetchUserPoints();
              } catch (err) {
                console.error('Failed to refresh points:', err);
              }
              setShowGacha(false);
            }}
          />
        </div>
      </>
    );
  }

  if (showFutureStory) {
    return (
      <>
        {showContact && <ContactForm onClose={() => setShowContact(false)} />}
        <StalkerComponent stalkerImage={user?.stalker_image} />
        <FutureStory onBack={() => setShowFutureStory(false)} />
      </>
    );
  }

  return (
    <>
      {showContact && <ContactForm onClose={() => setShowContact(false)} />}
      <StalkerComponent stalkerImage={user?.stalker_image} />
      <ChatPage
        onShowStats={() => setShowStats(true)}
        onShowGacha={() => setShowGacha(true)}
        onShowFutureStory={() => setShowFutureStory(true)}
        onShowContact={() => setShowContact(true)}
      />
    </>
  );
}

function App() {
  return (
    <AuthProvider>
      <AppContent />
    </AuthProvider>
  );
}

export default App;
