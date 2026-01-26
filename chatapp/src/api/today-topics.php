<?php
// エラー表示を有効化（デバッグ用）
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// CORS設定（シンプル版）
header('Access-Control-Allow-Origin: https://silvercow67.sakura.ne.jp');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
  // ファイル読み込みを段階的に
  if (!file_exists(__DIR__ . '/../utils/EnvConfig.php')) {
    throw new Exception('EnvConfig.php not found');
  }
  require_once __DIR__ . '/../utils/EnvConfig.php';
  
  // 環境変数を読み込み
  EnvConfig::load();
  
  // 必要な定数を定義
  if (!defined('SESSION_SECRET')) {
    define('SESSION_SECRET', EnvConfig::get('SESSION_SECRET', 'default-secret-key-change-this'));
  }
  if (!defined('DB_HOST')) {
    define('DB_HOST', EnvConfig::get('DB_HOST', 'localhost'));
  }
  if (!defined('DB_USER')) {
    define('DB_USER', EnvConfig::get('DB_USER', 'root'));
  }
  if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', EnvConfig::get('DB_PASSWORD', ''));
  }
  if (!defined('DB_NAME')) {
    define('DB_NAME', EnvConfig::get('DB_NAME', 'chatapp'));
  }
  if (!defined('OPENAI_API_KEY')) {
    define('OPENAI_API_KEY', EnvConfig::get('OPENAI_API_KEY', ''));
  }
  if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', EnvConfig::get('GEMINI_API_KEY', ''));
  }
  
  if (!file_exists(__DIR__ . '/../utils/Database.php')) {
    throw new Exception('Database.php not found');
  }
  require_once __DIR__ . '/../utils/Database.php';
  
  if (!file_exists(__DIR__ . '/../utils/Auth.php')) {
    throw new Exception('Auth.php not found');
  }
  require_once __DIR__ . '/../utils/Auth.php';
  
  if (!file_exists(__DIR__ . '/../utils/ApiResponse.php')) {
    throw new Exception('ApiResponse.php not found');
  }
  require_once __DIR__ . '/../utils/ApiResponse.php';

  // 認証確認
  $userId = Auth::getCurrentUserId();
  if (!$userId) {
    http_response_code(401);
    echo json_encode([
      'success' => false,
      'message' => 'Authentication required'
    ]);
    exit;
  }

  $db = Database::getInstance();
  
  // 過去1週間のメッセージを取得（delete_flag = 0 のみ）
  $sql = "SELECT m.text, DATE(m.created_at) as date, m.created_at 
          FROM messages m
          WHERE m.sender = 'user' 
          AND m.delete_flag = 0
          AND DATE(m.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
          ORDER BY m.created_at DESC";
  
  $stmt = $db->prepare($sql);
  if (!$stmt) {
    throw new Exception('Database prepare error: ' . $db->error);
  }
  
  $stmt->execute();
  $result = $stmt->get_result();
  $messages = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
  
  // デバッグログ: 取得したメッセージ数と日付範囲
  error_log('Today topics: Retrieved ' . count($messages) . ' messages');
  if (count($messages) > 0) {
    $dates = array_unique(array_column($messages, 'date'));
    error_log('Today topics: Date range: ' . json_encode($dates));
  }
  
  // テーマ抽出用のストップワード
  $stopwords = [
    'の', 'て', 'で', 'ある', 'いる', 'ない', 'した', 'される', 'もの', 'こと',
    'ため', 'など', 'ように', 'のは', 'として', 'よう', 'そう', 'ここ',
    'あれ', 'これ', 'それ', 'どれ', 'あの', 'この', 'その', 'どの',
    'ん', 'よ', 'な', 'か', 'も', 'から', 'まで', 'より', 'と', 'や',
    'や', 'や', 'へ', 'に', 'を', 'は', 'が', 'ぐらい', 'ほど',
    'たり', 'だり', 'ながら', 'つつ', 'つつも', 'ずつ', 'ぶり', 'め',
    'あ', 'い', 'う', 'え', 'お', 'ああ', 'いい', 'うう', 'ええ', 'おお',
    // 時間表現
    '今日', '明日', '昨日', '今年', '去年', '来年', '今月', '先月', '来月',
    '今週', '先週', '来週', '今朝', '今夜', '午前', '午後', '昼', '夜',
    '最近', 'いつ', 'いつも', 'たまに', 'ときどき', '時々', 'よく', 'すぐ',
    // 疑問詞・代名詞
    'なに', '何', 'なぜ', 'どう', 'どこ', 'だれ', '誰', 'いくつ', 'いくら',
    'わたし', '私', 'あなた', '彼', '彼女', '自分', 'みんな', '皆',
    // 一般的な動詞・形容詞
    'です', 'ます', 'ました', 'でした', 'ある', 'なる', 'する', 'できる',
    'ください', '下さい', 'ほしい', '欲しい', 'いい', '良い', '悪い',
    'すごい', '凄い', 'やばい', 'ちょっと', 'けっこう', '結構', 'とても', '非常',
    // その他の助詞・接続詞
    'だから', 'しかし', 'でも', 'けど', 'けれど', 'ところ', 'ので', 'のに',
    'また', 'そして', 'それで', 'それから', 'そこで', 'たとえば', '例えば',
    'つまり', 'ちなみに', 'さらに', 'しかも', 'ただ', 'ただし', 'なお',
    'について', 'に関して', 'における', 'によって', 'にとって', 'として'
  ];
  
  // テーマを抽出
  $topics = [];
  
  // 単純な日本語トークナイザ（形態素解析なしの簡易版）
  $particlePattern = '/(は|が|を|に|へ|で|と|や|の|も|より|から|まで)$/u';
  $splitPattern = '/[\s、，・　]+/u'; // 空白、読点、ナカグロ、全角スペース

  foreach ($messages as $msg) {
    $text = $msg['text'];
    
    // 句点や改行で区切る
    $sentences = preg_split('/[。！？\n]+/u', $text);
    
    foreach ($sentences as $sentence) {
      $sentence = trim($sentence);
      if ($sentence === '') { continue; }

      // まず句読点・中点・スペースで分割
      $chunks = preg_split($splitPattern, $sentence, -1, PREG_SPLIT_NO_EMPTY);

      // さらに各チャンクから文末の助詞を除去し、必要なら助詞で追加分割
      $words = [];
      foreach ($chunks as $chunk) {
        // 末尾の助詞を削る
        $clean = preg_replace($particlePattern, '', $chunk);
        // 助詞で区切れる場合は分割（例: 逆算思考で有名な本 → 逆算思考 / 有名な本）
        $sub = preg_split('/(で|へ|に|と|や)/u', $clean, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($sub as $s) {
          $s = trim($s);
          if ($s !== '') { $words[] = $s; }
        }
      }

      foreach ($words as $word) {
        // 文字エンコーディングを確認
        if (!mb_check_encoding($word, 'UTF-8')) {
          continue;
        }
        
        // 「？」や無効な文字を含む単語を除外
        if (strpos($word, '?') !== false || strpos($word, '？') !== false) {
          continue;
        }
        
        // ひらがなのみ、またはとても短い単語は除外
        $hiraganaOnly = preg_match('/^[ぁ-ん]+$/', $word);
        
        // 2文字以上（日本語では3文字以上推奨）で、ストップワードではない
        if (mb_strlen($word) >= 2 && !in_array($word, $stopwords) && !$hiraganaOnly) {
          // 数字のみは除外
          if (!preg_match('/^[0-9]+$/', $word)) {
            // 記号のみの単語も除外
            if (preg_match('/[ぁ-んァ-ヶー一-龠a-zA-Z0-9]/', $word)) {
              // 英数字を含む場合は大文字に統一
              $normalizedWord = mb_strtoupper($word);
              $topics[$normalizedWord] = isset($topics[$normalizedWord]) ? $topics[$normalizedWord] + 1 : 1;
            }
          }
        }
      }
    }
  }
  
  // 出現頻度でソート（降順）
  arsort($topics);
  
  // 「？」を含むテーマや無効なテーマを除外し、最低出現回数（例：1以上）でフィルタしてからTOP 5を取得
  $validTopics = [];
  foreach ($topics as $topic => $count) {
    // 「？」を含まない、有効な文字のみのテーマ
    if (strpos($topic, '?') === false && 
        strpos($topic, '？') === false && 
        mb_check_encoding($topic, 'UTF-8') &&
        preg_match('/[ぁ-んァ-ヶー一-龠a-zA-Z0-9]/', $topic) &&
        $count >= 1) {
      $validTopics[$topic] = $count;
      if (count($validTopics) >= 5) {
        break;
      }
    }
  }
  
  // フォーマット
  $ranking = [];
  $rank = 1;
  foreach ($validTopics as $topic => $count) {
    $ranking[] = [
      'rank' => $rank,
      'theme' => $topic,
      'count' => $count
    ];
    $rank++;
  }
  
  ApiResponse::success($ranking, 'This week\'s topic ranking retrieved successfully');
  
} catch (Exception $e) {
  error_log('today-topics.php error: ' . $e->getMessage());
  error_log('Stack trace: ' . $e->getTraceAsString());
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'message' => $e->getMessage(),
    'file' => $e->getFile(),
    'line' => $e->getLine()
  ]);
}
?>
