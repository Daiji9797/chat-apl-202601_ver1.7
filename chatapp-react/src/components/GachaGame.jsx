import { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { api } from '../services/api';
import '../styles/GachaGame.css';

const GachaGame = ({ onBack }) => {
  const [gachaGrid, setGachaGrid] = useState([]);
  const [userPoints, setUserPoints] = useState(0);
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');
  const [gachaImages, setGachaImages] = useState({}); // {gacha_id: {1: image_path, 2: image_path, 3: image_path}}
  const { user, fetchUserPoints } = useAuth();

  // ガチャ画像を取得
  useEffect(() => {
    const initializeGacha = async () => {
      // 画像を先に読み込む
      await loadGachaImages();
      // その後、ガチャグリッドを初期化
      await initializeGachaGrid();
    };
    initializeGacha();
  }, []);

  const loadGachaImages = async () => {
    try {
      const response = await api.request('gacha-images.php', {
        method: 'GET',
      });
      console.log('Gacha images response:', response);
      if (response && response.success) {
        // 画像データを整形: {gacha_id: {stage: image_path}}
        const imageMap = {};
        response.data.forEach(img => {
          if (!imageMap[img.gacha_id]) {
            imageMap[img.gacha_id] = {};
          }
          imageMap[img.gacha_id][img.stage] = img.image_path;
        });
        console.log('Image map:', imageMap);
        setGachaImages(imageMap);
      }
    } catch (err) {
      console.error('Failed to load gacha images:', err);
    }
  };

  // 画像パスを取得（Base64またはデフォルト）
  const getImagePath = (gachaId, stage) => {
    if (gachaImages[gachaId] && gachaImages[gachaId][stage]) {
      return gachaImages[gachaId][stage]; // Base64データ
    }
    // デフォルト画像（未設定の場合）
    return `./assets/1_${stage}_gatway_${
      stage === 1 ? 'close' : stage === 2 ? 'little_open' : 'open'
    }.png`;
  };


  // ユーザーポイントの同期
  useEffect(() => {
    if (user && user.points !== undefined) {
      setUserPoints(user.points);
    }
  }, [user]);

  const loadGachaStatus = async () => {
    try {
      const response = await api.request('gacha-status.php', {
        method: 'GET',
      });
      if (response && response.success && response.data) {
        return response.data; // Array of {gacha_id, stage}
      }
    } catch (err) {
      console.error('Failed to load gacha status:', err);
    }
    return [];
  };

  const initializeGachaGrid = async () => {
    const grid = Array(30).fill(null).map((_, index) => ({
      id: index,
      stage: 1,
      isAnimating: false,
    }));

    // DBからガチャの状態を読み込む
    const gachaStatuses = await loadGachaStatus();
    
    // DBの状態をグリッドに反映
    gachaStatuses.forEach(status => {
      if (grid[status.gacha_id]) {
        grid[status.gacha_id].stage = status.stage;
      }
    });

    console.log('Initializing gacha grid:', grid);
    setGachaGrid(grid);
  };

  // ガチャが見えるかどうかを判定
  const isGachaVisible = (gachaId) => {
    // 最初のガチャ（ID 0）は常に表示
    if (gachaId === 0) return true;

    // 5列のグリッド
    const COLUMNS = 5;
    const currentRow = Math.floor(gachaId / COLUMNS);
    const currentCol = gachaId % COLUMNS;

    // 左のガチャが完成（stage 3）しているか確認
    if (currentCol > 0) {
      const leftGachaId = gachaId - 1;
      const leftGacha = gachaGrid[leftGachaId];
      if (leftGacha && leftGacha.stage === 3) {
        return true;
      }
    } else if (currentCol === 0 && currentRow > 0) {
      // 最初の列の場合、上の行の最後のガチャが完成しているか確認
      const prevRowLastId = (currentRow - 1) * COLUMNS + (COLUMNS - 1);
      const prevRowLastGacha = gachaGrid[prevRowLastId];
      if (prevRowLastGacha && prevRowLastGacha.stage === 3) {
        return true;
      }
    }

    return false;
  };

  // 確率計算
  const calculateResult = (points) => {
    const random = Math.random();
    
    if (points === 10) {
      // 10ポイント: 1万分の1で成功、5分の1で途中段階
      if (random < 0.0001) return 3; // 1万分の1
      if (random < 0.2) return 2; // 5分の1
      return 1; // 変化なし
    } else if (points === 1000) {
      // 1000ポイント: 必ず段階的に開く（stage 2を経由してstage 3へ）
      return 2; // 常にstage 2を返す（後で段階的に3へ進む）
    }
    return 1;
  };

  // ガチャを実行
  const playGacha = async (gachaId, points) => {
    if (userPoints < points) {
      setMessage(`ポイントが足りません（必要: ${points}ポイント）`);
      setTimeout(() => setMessage(''), 3000);
      return;
    }

    setLoading(true);
    const gachaItem = gachaGrid[gachaId];

    // アニメーション開始
    const updatedGrid = [...gachaGrid];
    updatedGrid[gachaId] = { ...gachaItem, isAnimating: true };
    setGachaGrid(updatedGrid);

    // 確率計算
    const newStage = calculateResult(points);

    // UIに即座にポイント消費を反映（楽観的更新）
    const newPoints = userPoints - points;
    setUserPoints(newPoints);

    // アニメーション完了後に段階を更新
    setTimeout(async () => {
      // 最終的なstageを計算（stage2は演出で、DBには保存しない）
      let finalStage = gachaItem.stage; // デフォルトは現在のstage
      
      if (newStage === 2) {
        // stage 2 は演出
        if (points === 1000) {
          // 1000ptの場合、最終的に stage 3 へ進む
          finalStage = 3;
        } else {
          // 10ptの場合、stage 1 のまま（失敗）
          finalStage = 1;
        }
      } else if (newStage === 3) {
        // 既に stage 3 の場合はそのまま
        finalStage = 3;
      }

      // バックエンドにガチャ実行結果を送信してポイント消費
      try {
        const response = await api.request('gacha.php', {
          method: 'POST',
          body: JSON.stringify({
            gacha_type: 1, // 固定値（後方互換性）
            gacha_id: gachaId,
            points_used: points,
            result_stage: finalStage, // 最終的なstageを送る
          }),
        });

        // ガチャ実行成功後、サーバーから最新のポイント情報を取得
        if (response && (response.success || (response.data && response.data.success))) {
          try {
            const updatedUser = await fetchUserPoints();
            if (updatedUser && updatedUser.points !== undefined) {
              setUserPoints(updatedUser.points);
              // API から取得した最新データで localStorage を更新
              localStorage.setItem('user', JSON.stringify(updatedUser));
            }
          } catch (err) {
            console.error('Failed to fetch updated user points:', err);
          }
        } else {
          setMessage('ガチャの実行に失敗しました');
          setTimeout(() => setMessage(''), 3000);
        }
      } catch (err) {
        setMessage('ガチャの実行に失敗しました');
        setTimeout(() => setMessage(''), 3000);
      }

      // ポイント消費はサーバーから取得した値で更新
      // または、ローカルの消費済みポイントを保持
      const finalGrid = [...gachaGrid];
      
      // stage 2 の場合は一瞬表示してから進める
      if (newStage === 2) {
        // 一度 stage 2 を表示
        finalGrid[gachaId] = { 
          ...gachaItem, 
          stage: 2,
          isAnimating: false 
        };
        setGachaGrid(finalGrid);
        
        // 1秒後に最終stageに進む
        setTimeout(() => {
          setGachaGrid(prevGrid => {
            const resetGrid = [...prevGrid];
            // 1000ptの場合は stage 3へ、10ptの場合は stage 1 へ
            const nextStage = points === 1000 ? 3 : 1;
            resetGrid[gachaId] = { 
              ...resetGrid[gachaId], 
              stage: nextStage,
              isAnimating: false 
            };
            return resetGrid;
          });
        }, 1000);
      } else if (newStage === 1) {
        finalGrid[gachaId] = { 
          ...gachaItem, 
          stage: 1,
          isAnimating: false 
        };
        setGachaGrid(finalGrid);
      } else {
        finalGrid[gachaId] = { 
          ...gachaItem, 
          stage: Math.max(gachaItem.stage, newStage),
          isAnimating: false 
        };
        setGachaGrid(finalGrid);
      }

      // メッセージ表示（最終stageに基づいて）
      if (points === 1000 && newStage === 2) {
        // 1000pt で stage 2 が表示されたら、次のメッセージで成功を表示
        setMessage('🎉 成功！ガチャが段階的に開きました！');
      } else if (newStage === 2) {
        // 10pt で stage 2 が表示された場合
        setMessage('✨ 少し開きましたが...戻ってしまいました。');
      } else if (newStage === 1) {
        setMessage('残念... 扉は閉じたままです。');
      } else {
        setMessage('残念... 扉は閉じたままです。');
      }

      setTimeout(() => setMessage(''), 3000);
      setLoading(false);
    }, 600);
  };

  return (
    <div className="gacha-game-container">
      <div className="gacha-header">
        <div className="header-top">
          <div className="title-points">
            <h1>ガチャゲーム</h1>
            <div className="points-display">
              <span>🪙 ポイント: {userPoints}</span>
            </div>
          </div>
          <button className="back-to-chat-btn" onClick={onBack}>チャットに戻る</button>
        </div>
      </div>

      {message && (
        <div className="gacha-toast" aria-live="polite" aria-atomic="true">
          {message}
        </div>
      )}

      <div className="gacha-grid">
        {gachaGrid.map((gacha) => {
          const isVisible = isGachaVisible(gacha.id);
          return (
            <div 
              key={gacha.id} 
              className={`gacha-cell ${isVisible ? 'visible' : 'disabled'}`}
            >
              <div className={`gacha-item ${gacha.isAnimating ? 'animating' : ''}`}>
                {isVisible ? (
                  <>
                    <img
                      src={getImagePath(gacha.id, gacha.stage)}
                      alt={`gacha-${gacha.id}-stage-${gacha.stage}`}
                      className="gacha-image"
                    />
                    {gacha.stage < 3 && (
                      <div className="gacha-buttons">
                        <button
                          onClick={() => playGacha(gacha.id, 10)}
                          disabled={loading || userPoints < 10}
                          className="gacha-btn points-10"
                        >
                          10pt
                        </button>
                        <button
                          onClick={() => playGacha(gacha.id, 1000)}
                          disabled={loading || userPoints < 1000}
                          className="gacha-btn points-1000"
                        >
                          1000pt
                        </button>
                      </div>
                    )}
                  </>
                ) : (
                  <div className="gacha-placeholder">???</div>
                )}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};

export default GachaGame;
