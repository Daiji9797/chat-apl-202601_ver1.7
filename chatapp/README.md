# Chat Application with React & PHP

React + Vite でビルドされたフロントエンドと PHP バックエンドで構成されたチャットアプリケーションです。

## 機能

- ユーザー認証（登録・ログイン）
  - **カスタム背景画像** - ログイン/新規登録画面に背景画像（gatway_login_logo.png）を設定可能
  - **タイプライター効果** - ログイン画面の左側に「人生を豊かにするために」等のメッセージが1文字ずつ表示されるアニメーション
  - **ガラスフラッシュ効果** - 新規登録ボタンに光沢の光が流れるアニメーション
  - **パスワード変更機能** - ユーザーが定期的にパスワードを変更可能。パスワード強度検証あり
  - **利用規約同意機能** - 新規登録時に利用規約への同意が必須。同意なしでは登録不可
- **お問い合わせフォーム** - ログイン前/後に利用可能。内容はDBに保存され、管理者がステータス（new/in_progress/resolved）を更新可能
- チャットルームの作成・管理
- AI チャット（プロバイダ選択: OpenAI / Gemini［プレビュー］）
  - Gemini は現在プレビュー実装中（実験的）。挙動が変わる可能性があります
  - **コンテンツフィルタリング** - 不適切なコンテンツ（暴力、自傷行為、有害なチャレンジ等）のリクエストに対して応答を拒否
- リアルタイムメッセージ送受信
- メッセージ削除機能
- メッセージいいね機能
- **Stalker（寄り添い画像）選択機能** - ユーザーが画像をアップロードでき、マウスカーソルに追従するアニメーション
- **ポイント付与機能** - ログイン時に自動的にポイントが付与される
- **目標達成メモ** - チャット送信時に考察モードをオンにしてテキストを保存。ルームID紐付けで「どの目標か」分かる
- **ルーム名変更** - 既存のチャットルーム名称を後からリネーム可能
- **ルーム利用状況統計画面** - 各ルームの質問数・いいね数・目標設定数を一覧表示。質問数TOP5を棒グラフで可視化。ルームごとに手動で完了状態を管理可能。ルーム名クリックで日別利用推移を折れ線グラフで表示。統計画面遷移時に寄り添い画像から励ましメッセージを表示。**目標欄でルームごとの目標をモーダルで編集可能**。
- **📊 この1週間のテーマランキング（TOP3 + もっと見る）** 
- 直近7日間の質問から自動抽出したテーマを出現頻度でランキング表示（初期は上位3件、［もっと見る］で拡張）
- **🌟 未来Story機能**
  - **目標達成ジャーニー管理** - 過去→現在→未来のストーリーを時系列で管理
  - **タイムラインビュー** - 過去のストーリー、現在の成功体験、未来の目標達成イメージを視覚化
  - **日付ベースの整理** - story_dateフィールドで時間軸を管理し、ストーリー進行を表示
  - **ルーム目標の統合** - チャットルームで作成した目標を選択して未来Storyに追加可能
  - **画像生成機能** - 目標テキストから達成イメージを画像化（OpenAI DALL-E統合予定）
  - **アニメーション振り返り** - 過去から現在、そして未来へのストーリー進行を視覚的に表現
- **ガチャゲーム機能**
  - **30セルのガチャグリッド** - ユーザーがポイントを消費してガチャに挑戦。左から順番に解放される
  - **段階的な開閉演出** - stage 1（閉じた状態）→ stage 2（少し開いた状態）→ stage 3（完全に開いた状態）で視覚的に表現
  - **複数確率レート** - 10ptで10000分の1の確率でstage 3達成、5分の1でstage 2到達。1000ptで確実にstage 3へ進展
  - **ガチャ画像管理機能** - 管理者が各ガチャIDと段階ごとに Base64画像をアップロード・削除可能
  - **ガチャ状態永続化** - ユーザーの進捗状態がデータベースに保存され、ページ再読み込み後も維持

## 必要な環境

- PHP 7.4 以上
- MySQL 5.7 以上
- XAMPP（Apache + PHP + MySQL）
- Node.js 18 以上
- OpenAI API キー
- Google Gemini API キー（任意・Gemini利用時）

## インストール手順

### 1. ファイルをXAMPPのhtdocsディレクトリにコピー

```bash
C:\xampp\htdocs\chatapp        # バックエンド（PHP API）
C:\xampp\htdocs\chatapp-react  # フロントエンド（React）
```

### 2. バックエンドの環境設定

`chatapp` ディレクトリで `.env.example` を `.env` にコピーして編集：

```bash
cd C:\xampp\htdocs\chatapp
cp .env.example .env
```

`.env` ファイルを編集：

```env
# Database Configuration
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=
DB_NAME=chatapp

# OpenAI Configuration
OPENAI_API_KEY=your_openai_api_key_here

# Gemini Configuration (optional)
GEMINI_API_KEY=your_gemini_api_key_here

# Session Configuration
SESSION_SECRET=your_session_secret_here
```

### 3. データベースを初期化

ブラウザで以下のURLにアクセス：

```
http://localhost/chatapp/init_db.php
```

または、ターミナルから：

```bash
cd C:\xampp\htdocs\chatapp
php init_db.php
```

#### ガチャ関連テーブル（未作成の場合の追加SQL）
init_db で作成されていない環境では、以下を一度だけ実行してください（phpMyAdmin もしくは mysql クライアントで実行）。

```sql
-- ガチャ画像テーブル（管理者がアップロードした画像を保持）
CREATE TABLE IF NOT EXISTS gacha_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gacha_id INT NOT NULL,
  stage INT NOT NULL,
  filename VARCHAR(255) NOT NULL,
  image_path LONGTEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_gacha (gacha_id, stage)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ガチャ進捗テーブル（ユーザーごとの開封状態を保持）
CREATE TABLE IF NOT EXISTS gacha_status (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  gacha_id INT NOT NULL,
  stage INT NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_gacha (user_id, gacha_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### お問い合わせテーブル（新規追加）
お問い合わせフォームを有効にするには、以下を一度だけ実行してください。

```bash
cd C:\xampp\htdocs\chatapp
mysql -u root -p chatapp < migrations/create_contact_messages.sql
```

### 4. データベースマイグレーション（v1.7.2以降）

未来Story画像補足コメント機能を使用する場合、goal_notesテーブルに新しいカラムを追加する必要があります。

**ローカル環境:**
```bash
cd C:\xampp\htdocs\chatapp
mysql -u root -p chatapp < migrations/add_image_comment_to_goal_notes.sql
```

**本番環境（さくらインターネット）:**
```bash
ssh -l username sakura.ne.jp
cd ~/chatapp
mysql -u username -p ユーザー名_chatapp < migrations/add_image_comment_to_goal_notes.sql
```

### 5. 管理者画面（ガチャ画像管理）

- 管理者としてログインすると、サイドバーの「Admin」から管理画面に入れます。
- ガチャIDとステージごとに Base64 画像をアップロード・削除できます（`gacha-images.php` を利用）。
- 登録された画像はフロントのガチャゲームに自動反映され、`gacha_images` テーブルに保存されます。

### 6. フロントエンドのセットアップ

```bash
cd C:\xampp\htdocs\chatapp-react
npm install
npm run build
```

### 6. アプリケーションにアクセス

ブラウザで以下のURLにアクセス：

```
http://localhost/chatapp-react/dist/
```

## さくらインターネットへのデプロイ手順

### 重要な注意事項

**⚠️ さくらインターネット共有サーバーの制限事項**

1. **Node.jsサーバーは起動できません** - `npm run dev` などのNode.js開発サーバーは実行不可
2. **静的ファイル + PHP のみ実行可能** - HTML、CSS、JavaScript、PHPのみサポート
3. **Reactの動作方法** - ビルドして生成された静的ファイル（HTML/JS/CSS）として配信
4. **ビルドはローカルで実行** - メモリ制限により、サーバー上で `npm run build` を実行すると "Killed" エラーで強制終了

**Node.jsバージョンの違いについて:**

- ローカル環境がNode.js v24、サーバーがv18でも**まったく問題ありません**
- ビルド後の静的ファイル（HTML/CSS/JS）は、Node.jsバージョンに依存しません
- サーバーでは静的ファイルを配信するだけで、Node.jsは実行されません
- ビルド済みファイルは、Node.jsがインストールされていないサーバーでも動作します

**つまり:**
- フロントエンド: ローカルでビルドした静的ファイルをアップロード（v24でビルドしても問題なし）
- バックエンド: PHPファイルをアップロード（そのまま実行可能）

### 推奨ディレクトリ構成

```
~/ (ホームディレクトリ)
├── www/                                 # 公開ディレクトリ
│   └── chatapp-react/
│       └── dist/                        # フロントエンドビルド出力（ローカルでビルドしたもの）
└── chatapp/                             # 非公開ディレクトリ（バックエンド）
    ├── src/
    ├── config/
    ├── .env                             # 環境設定ファイル（非公開）
    └── init_db.php
```

**メリット:**
- バックエンドコードが外部から直接アクセスできない
- 環境設定を一元管理
- セキュリティ向上

### セットアップ手順

#### 1. .env ファイルを作成

バックエンドディレクトリに `.env` ファイルを作成：

```bash
# .env.example をコピー
cp .env.example .env

# nano エディタで編集
nano .env
```

設定内容：
```properties
DB_HOST=localhost
DB_USER=ユーザー名
DB_PASSWORD=パスワード
DB_NAME=ユーザー名_chatapp

OPENAI_API_KEY=your_api_key
SESSION_SECRET=任意の長い文字列

API_URL=https://ドメイン/api
ALLOWED_ORIGINS=https://ドメイン
```

ファイルのパーミッション設定（重要）：
```bash
chmod 600 .env
```

#### 2. フロントエンドをローカルでビルド

**重要: ローカル環境でのみビルドしてください**

さくらインターネットのサーバー上では `npm run build` を実行しないでください。メモリ制限により失敗します。

**ローカル環境（Windows）で実行：**

```powershell
cd C:\xampp\htdocs\chatapp-react

# 任意: .env の VITE_API_URL を設定（未設定時は window.location.origin + '/api/' を使用）
# さくら環境では .htaccess により /api が PHP バックエンドへリライトされるため、未設定運用を推奨

# ビルド実行
npm run build

# dist/ フォルダが生成される
```

#### 3. ファイルをアップロード

**重要: `dist/` フォルダの中身のみをアップロード**

サーバーにアップロードするのは以下のファイルのみです：
- `dist/index.html` ← ビルド済みHTML
- `dist/assets/` ← CSS・JavaScript
- `dist/.htaccess` ← Apache設定
- `dist/favicon.ico`
- `dist/spinner.svg`

**`src/` フォルダ、`package.json`、`node_modules/` はアップロード不要です。**

```bash
# FTPまたはSCPで dist/ の中身をアップロード
scp -r ./dist/* silvercow67@silvercow67.sakura.ne.jp:~/www/chatapp-react/

# または、バックエンド
scp -r ./chatapp/* silvercow67@silvercow67.sakura.ne.jp:~/chatapp/
```

**FTPまたはSCPで以下をアップロード:**

1. **フロントエンド**: `dist/` フォルダの中身を `www/chatapp-react/` にアップロード
2. **バックエンド**: `chatapp/` フォルダを `~/chatapp/` にアップロード

```bash
# フロントエンド（ローカルでビルド済み）
scp -r ./chatapp-react/dist/* ユーザー名@www3015.sakura.ne.jp:~/www/chatapp-react/

# バックエンド
scp -r ./chatapp/* ユーザー名@www3015.sakura.ne.jp:~/chatapp/
```

#### 4. バックエンドを .env 対応に設定

SSHで接続して、`.env` を本番環境で作成：

```bash
ssh -l ユーザー名 www3015.sakura.ne.jp

# バックエンドディレクトリへ
cd ~/chatapp

# .env を作成
nano .env

# .env が読み込めるか確認
php -r "require 'src/utils/EnvConfig.php'; EnvConfig::load(); echo 'OK';"
```

#### 5. データベースを初期化

```bash
cd chatapp
php init_db.php
```

#### 6. APIエンドポイントをリライト

`www/` 直下に `.htaccess` ファイルを作成：

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  
  # SPA ルーティング
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule ^chatapp-react/dist/(.*)$ chatapp-react/dist/index.html [QSA,L]
  
  # API をバックエンドにプロキシ
  RewriteRule ^api/(.*)$ ../../chatapp/src/api/$1 [QSA,L]
</IfModule>
```

#### 7. アプリケーションにアクセス

```
https://あなたのドメイン/chatapp-react/
```

### よくある質問

**Q: さくらインターネットでNode.jsサーバーを起動できますか？**

A: **いいえ、できません。** さくらインターネットの共有サーバーはNode.jsサーバーの実行に対応していません。Reactアプリは以下の流れで動作します：

1. **ローカル環境**: `npm run build` で静的ファイル（HTML/JS/CSS）を生成
2. **サーバー**: 生成された静的ファイルをアップロード
3. **ブラウザ**: 静的ファイルを読み込み、ブラウザ上でReactが動作
4. **API通信**: PHPバックエンドと通信

**Q: `npm run dev` はサーバー上で実行できますか？**

A: **いいえ、できません。** 開発サーバー（`npm run dev`）はNode.jsが必要で、さくらインターネットでは実行できません。開発はローカル環境で行い、本番環境には**ビルド済みファイルのみ**をアップロードしてください。

**Q: サーバー上で `npm run build` を実行したら "Killed" と表示される**

A: さくらインターネットの共有サーバーはメモリ制限があり、Viteのビルドプロセスが強制終了されます。必ずローカル環境でビルドしてから、`dist/` フォルダをアップロードしてください。

**Q: バックエンドAPIはどうやって動作しますか？**

A: PHPファイルとして動作します。ApacheがPHPを実行するため、Node.jsは不要です。

### トラブルシューティング（さくらインターネット）

**.env が見つからない:**
```bash
# バックエンドディレクトリを確認
ls -la chatapp/ | grep .env

# パーミッションを確認
stat chatapp/.env

# PHPで読み込めるか確認
php -r "require 'chatapp/src/utils/EnvConfig.php'; EnvConfig::load(); echo 'OK';"
```

**API エンドポイントへのアクセスが404:**
- `.htaccess` の RewriteRule が正しいか確認
- Apache モジュールが有効か確認：`mod_rewrite`, `mod_proxy`

**データベース接続エラー:**
- `.env` の DB_HOST, DB_USER, DB_PASSWORD を確認
- さくらのコントロールパネルで DB 情報を確認

## ローカル開発環境

さくらのレンタルサーバに SSH で接続できるプランを前提とした Node.js 導入手順です（参考: https://chigusa-web.com/blog/sakura-npm/）。

1. SSH でサーバーへログイン。
2. nodebrew をセットアップして PATH を通す：

```bash
curl -L git.io/nodebrew | perl - setup
echo 'export PATH=$HOME/.nodebrew/current/bin:$PATH' >> ~/.bash_profile
source ~/.bash_profile
```

3. 推奨バージョン（例: v18 系）の Node.js をバイナリで導入して適用：

```bash
nodebrew install-binary v18.15.0
nice -n 20 nodebrew compile v18.15.0
nodebrew use v18.20.0
```

4. 動作確認：

```bash
node -v
npm -v
```

補足:
- `~/.nodebrew/src` が無いと言われた場合は `mkdir -p ~/.nodebrew/src` を実行してから再試行してください。
- シェルの再ログイン後も PATH が有効になるよう `.bash_profile` に追記しています。既に別のシェル設定を使っている場合は、該当するファイルに PATH 追記を移してください。

## 開発環境

### フロントエンドの開発サーバー起動

```bash
cd C:\xampp\htdocs\chatapp-react
npm run dev
```

開発サーバーが起動したら `http://localhost:5173` にアクセス

### ホットリロード付きビルド＆デプロイ

```bash
npm run watch
```

ファイル変更を自動検知して `dist` ディレクトリにビルド

### 本番ビルド

```bash
npm run build
```

最適化されたファイルが `dist` ディレクトリに出力されます

## カスタマイズ

### パスワード強度チェック

新規登録時にリアルタイムでパスワード強度を表示します。

**チェック項目:**
- 8文字以上
- 小文字を含む
- 大文字を含む
- 数字を含む
- 同一文字3連続なし
- 数字の昇降順3連続なし
- キーボード横並び3連続なし
- メールアドレスと異なる（メールの一部を含まない）

**強度レベル:**
- 弱い（赤）：40未満
- 普通（黄）：40-70未満
- 強い（緑）：70以上

「普通」以上のパスワードのみ登録できます。

**バックエンド検証:**
`chatapp/src/api/register.php` で上記の条件を厳密にチェックします。フロントエンドでの検証を回避されても、サーバー側で必ず検証されます。

### ログイン画面のタイプライター文言を変更する

ログイン画面左側に表示されるタイプライター効果のメッセージを変更するには：

**ファイル:** [chatapp-react/src/components/LoginForm.jsx](chatapp-react/src/components/LoginForm.jsx)

**変更箇所:**
```jsx
const fullText = `人生を豊かにするために。
自分がトキメキを感じるイメージをより具体的に。
自分が何にトキメクのかを具体的にできると違った未来が見えてくるかもしれません。`;
```

上記の文字列を編集して保存すると、開発サーバーが自動でリロードされます。

**タイプ速度の調整:**
同じファイル内の `setInterval` の数値（ミリ秒）を変更：
```jsx
}, 50);  // 50ミリ秒ごとに1文字表示（小さくすると速く、大きくすると遅く）
```

**背景画像の変更:**
- 画像ファイルを `chatapp-react/public/assets/` に配置
- [chatapp-react/src/App.css](chatapp-react/src/App.css) の `background-image: url('/assets/gatway_login_logo.png');` を編集

## APIエンドポイント

### 認証

- `POST /api/register.php` - ユーザー登録
- `POST /api/login.php` - ログイン
- `GET /api/user.php` - ユーザー情報取得（ポイント、Stalker画像含む）

### ルーム管理

- `GET /api/rooms.php` - ルーム一覧取得
- `POST /api/rooms.php` - ルーム作成
- `GET /api/room.php?roomId=<id>` - ルーム詳細取得
- `PUT /api/room.php?roomId=<id>` - ルーム更新（ルーム名変更に利用）
- `DELETE /api/room.php?roomId=<id>` - ルーム削除

### チャット

- `POST /api/chat.php` - メッセージ送信（AIチャット）
  - リクエストボディで `provider` を指定可能：`openai`（既定）/ `gemini`（プレビュー）
- `POST /api/message_like.php` - メッセージにいいね / いいね解除

### ポイント・Stalker

- `POST /api/stalker.php` - Stalker画像をアップロード
- `DELETE /api/stalker.php` - Stalker画像をリセット

### 目標達成メモ

- `POST /api/goal.php` - 目標達成メモを作成（roomId 紐付け必須、messageId は任意）
- `GET /api/goal.php?roomId=<id>` - 指定ルームの目標達成メモ一覧（roomId 省略で全件）

### お問い合わせ

- `POST /api/contact.php` - お問い合わせ送信（ログイン前後どちらでも可）
- `GET /api/admin/contact-messages.php?status=all|new|in_progress|resolved` - 管理者用お問い合わせ一覧
- `PUT /api/admin/contact-messages.php` - ステータス更新（body: `id`, `status`）
- `DELETE /api/admin/contact-messages.php` - 問い合わせ削除（body: `id`）

### 統計・ランキング

 - `GET /api/today-topics.php` - この1週間のテーマランキングを取得（初期表示はTOP3、拡張表示あり）

## リクエスト/レスポンス例

### ログイン

```bash
curl -X POST http://localhost/chatapp/src/api/login.php \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

レスポンス:

```json
{
  "success": true,
  "message": "Logged in successfully",
  "data": {
    "user": {
      "id": 1,
      "email": "user@example.com",
      "name": "User Name"
    },
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

### チャット送信

```bash
curl -X POST http://localhost/chatapp/src/api/chat.php \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{
    "message": "こんにちは",
    "roomId": 1,
    "history": [
      {"role": "user", "content": "こんにちは"},
      {"role": "assistant", "content": "こんにちは！お手伝いします。"}
    ]
  }'
```

## GPTプロンプト設定

このプロジェクトでは、OpenAI Chat Completions を PHP バックエンドから呼び出しています。システムプロンプトや生成パラメータの調整は [chatapp/src/api/chat.php](chatapp/src/api/chat.php) の `callOpenAIAPI()` 内で行います。

### システムプロンプトの変更箇所

`callOpenAIAPI()`の先頭でシステムメッセージ（ロール: `system`）を追加しています。回答の方針やキャラクター性を変えたい場合は、以下の文字列を書き換えてください。

```php
       // システムプロンプトを追加（回答の多様性と品質を向上）
        $systemPrompt = 'あなたは親切で知識豊富なアシスタントです。ユーザーの質問に対して、常に新しく、思慮深い、かつユニークな回答を提供してください。単調または繰り返しの回答は避けてください。異なる視点や具体的な例を含めるようにしてください。常に相手の状況や背景を考慮し、より有用で詳細な回答を心がけてください。

【重要な制限事項】
以下のコンテンツに関する質問や要求には一切応じないでください：
- グラフィックな暴力や残虐な内容
- 未成年者に危険または有害な行動を促す可能性のあるバイラルチャレンジ
- 性的、恋愛的、または暴力的なロールプレイ
- 自傷行為の描写
- 極端な美容基準、不健康なダイエット、ボディシェイミングを助長するコンテンツ

これらの内容に該当する質問には、「申し訳ございませんが、その質問にはお答えできません。別の質問がございましたら、お気軽にお尋ねください。」と丁寧に断ってください。';
 
```

### 生成パラメータ（モデル・温度など）の調整

同じく `callOpenAIAPI()` 内の POST ボディでモデル名や生成パラメータを設定しています。

```php
CURLOPT_POSTFIELDS => json_encode([
  'model' => 'gpt-3.5-turbo',
  'messages' => $messages,
  'temperature' => 0.85,        // 出力の多様性（高いほど創造的）
  'top_p' => 0.9,               // 確率分布の上位率からサンプリング
  'frequency_penalty' => 1.0,   // 同語反復の抑制
  'presence_penalty' => 0.5,    // 新規トピック導入の促進
  'max_tokens' => 2000          // 応答の最大トークン数
])
```

- モデル変更例：`'model' => 'gpt-4o-mini'` などへ差し替え
- 出力を安定させたい：`temperature` を下げる（例: 0.2〜0.4）
- より簡潔な応答にしたい：`max_tokens` を抑えつつ、プロンプトに「簡潔に」を明示

### 会話履歴の扱い（フロントエンド → バックエンド）

フロントエンドから `history` を送信できます（[chatapp-react/src/services/api.js](chatapp-react/src/services/api.js) の `sendChat()`）。
構造は OpenAI のチャットフォーマットに合わせ、`role` と `content` を配列で渡します。

```json
[
  {"role": "user", "content": "前回の質問"},
  {"role": "assistant", "content": "前回の回答"}
]
```

バックエンドでは提供された履歴を優先して `messages` に追加した上で、今回のユーザー入力を末尾に加えています。

行数・長さの扱い: 現状サーバー側では履歴を自動でトリミングしていません。OpenAI API のトークン上限超過や応答遅延を避けるため、フロントエンドで送る履歴は bot/user 合計で直近 20 メッセージ以内に絞ってください。

### メッセージのいいね

- エンドポイント: `POST /api/message_like.php`
- パラメータ: `roomId`, `messageId`, `like`（true で付与、false で取り消し）
- レスポンス例: `{ "like_count": 3, "liked_by_me": true }`
- ルームオーナーのみ操作できます。フロントエンドでは直近20件の履歴送信時に、各メッセージのいいね状態（`like_count`, `liked_by_me`）が付与されます。

### APIキーの設定

[chatapp/config/config.php](chatapp/config/config.php) および [chatapp/src/utils/EnvConfig.php](chatapp/src/utils/EnvConfig.php) により `.env` を読み込み、以下のキーを使用します：

- `OPENAI_API_KEY`（必須）: OpenAI プロバイダ用
- `GEMINI_API_KEY`（任意）: Gemini プロバイダ用（プレビュー）

`.env` の設定例は上記「バックエンドの環境設定」を参照してください。

### 運用上の注意

- プロンプトには個人情報や秘密情報を含めないでください。
- 不適切な出力を抑制するには、システムプロンプトで制約を明示し、`temperature` を下げるなどの調整を行ってください。
- モデルと最大トークンはコストに直結します。必要な範囲で最適化してください。

## ディレクトリ構造

### バックエンド（chatapp）

```
chatapp/
├── config/
│   └── config.php              # アプリケーション設定
├── migrations/                 # DBマイグレーション
│   └── create_contact_messages.sql # お問い合わせテーブル
├── src/
│   ├── api/
│   │   ├── register.php          # ユーザー登録API
│   │   ├── login.php             # ログインAPI
│   │   ├── user.php              # ユーザー情報取得API（ポイント・Stalker情報）
│   │   ├── change-password.php   # パスワード変更API
│   │   ├── chat.php              # チャットAPI
│   │   ├── message.php           # メッセージCRUD
│   │   ├── message_like.php      # メッセージいいね
│   │   ├── rooms.php             # ルーム一覧
│   │   ├── room.php              # ルーム詳細
│   │   ├── goal.php              # 目標達成メモ
│   │   ├── story.php             # 未来Story CRUD・ルーム目標取得
│   │   ├── story_image.php       # 未来Story画像生成（OpenAI Images）
│   │   ├── today-topics.php      # テーマランキング（直近1週間）
│   │   ├── gacha.php             # ガチャ進行管理
│   │   ├── gacha-status.php      # ガチャ状態取得
│   │   ├── gacha-images.php      # ガチャ画像取得
│   │   ├── contact.php           # お問い合わせ送信
│   │   └── admin/
│   │       ├── gacha-images.php      # 管理者用ガチャ画像登録
│   │       └── contact-messages.php  # 管理者用お問い合わせ一覧/更新
│   ├── models/
│   │   ├── User.php              # ユーザーモデル（ポイント、Stalker画像管理）
│   │   ├── Room.php              # ルームモデル
│   │   ├── Message.php           # メッセージモデル
│   │   ├── MessageLike.php       # いいねモデル
│   │   └── GoalNote.php          # 目標達成メモ/未来Storyモデル
│   └── utils/
│       ├── Database.php          # DB接続クラス
│       ├── Auth.php              # 認証ユーティリティ
│       ├── ApiResponse.php       # APIレスポンス整形
│       ├── Cors.php              # CORS設定
│       ├── EnvConfig.php         # .env読込ユーティリティ
│       └── PathHelper.php        # パスヘルパー
├── .env.example                # 環境設定例
├── .env                        # 環境設定（.gitignore対象）
├── init_db.php                 # DB初期化スクリプト
└── README.md                   # このファイル
```

### フロントエンド（chatapp-react）

```
chatapp-react/
├── src/
│   ├── components/             # Reactコンポーネント
│   │   ├── AdminPanel.jsx          # ガチャ画像管理（管理者）
│   │   ├── ChangePasswordForm.jsx  # パスワード変更
│   │   ├── ChatPage.jsx            # チャットページメイン
│   │   ├── DeleteAccountModal.jsx  # アカウント削除確認モーダル
│   │   ├── FutureStory.jsx         # 未来Story画面
│   │   ├── GachaGame.jsx           # ガチャゲーム
│   │   ├── ContactForm.jsx         # お問い合わせフォーム
│   │   ├── LoginForm.jsx           # ログインフォーム
│   │   ├── MessageList.jsx         # メッセージ一覧・送信
│   │   ├── RegisterForm.jsx        # 登録フォーム
│   │   ├── RoomChat.jsx            # ルームチャット表示
│   │   ├── RoomStats.jsx           # ルーム統計画面
│   │   └── Sidebar.jsx             # サイドバー（ルーム一覧）
│   ├── styles/                 # スタイルシート
│   │   ├── AdminPanel.css          # 管理画面スタイル
│   │   ├── ChangePasswordForm.css  # パスワード変更スタイル
│   │   ├── DeleteAccountModal.css  # アカウント削除モーダルスタイル
│   │   ├── FutureStory.css         # 未来Story用スタイル
│   │   ├── GachaGame.css           # ガチャゲームスタイル
│   │   ├── ContactForm.css         # お問い合わせフォームスタイル
│   │   ├── RegisterForm.css        # 登録フォームスタイル
│   │   └── RoomStats.css           # 統計画面スタイル
│   ├── context/                # Reactコンテキスト
│   │   └── AuthContext.jsx    # 認証状態管理
│   ├── hooks/                  # カスタムフック
│   │   └── useApi.js           # API通信フック
│   ├── services/               # API通信サービス
│   │   └── api.js              # APIクライアント
│   ├── App.jsx                 # メインアプリケーション
│   ├── App.css                 # スタイルシート
│   └── main.jsx                # エントリーポイント
├── dist/                       # ビルド出力先
├── scripts/                    # ビルドスクリプト
├── index.html                  # HTMLテンプレート
├── vite.config.js              # Vite設定
├── package.json                # Node.js依存関係
└── package-lock.json
```

## データベーススキーマ

### users テーブル

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255),
    points INT DEFAULT 0,
    last_login_date DATE,
    stalker_image LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
);
```

**新フィールド説明:**
- `points` - ユーザーのポイント（ログイン時に付与される）
- `last_login_date` - 最後のログイン日
- `stalker_image` - マウスカーソルに追従するキャラクター画像（Base64形式）

### rooms テーブル

```sql
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    is_completed TINYINT DEFAULT 0,
    goal_text TEXT,
    delete_flag TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### goal_notes テーブル

```sql
CREATE TABLE goal_notes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  room_id INT NOT NULL,
  message_id INT NULL,
  note_text LONGTEXT NOT NULL,
  story_date DATE DEFAULT NULL,
  image_comment TEXT DEFAULT NULL,
  story_image LONGTEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
  FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE SET NULL,
  INDEX idx_story_date (user_id, story_date, created_at DESC),
  INDEX idx_image_comment (image_comment(50))
);
```

**フィールド説明:**
- `room_id` - ルームIDで「どのルームの目標メモか」を追跡
- `message_id` - 任意（未指定でも保存可能）
- `story_date` - 未来Storyの日付。NULL の場合は単なる目標メモ、日付がある場合は時系列で管理される
- `image_comment` - 画像生成のイメージを補助するコメント。生成AIに渡されるプロンプトのヒントとして使用
- `story_image` - 生成された画像（Base64形式）

### messages テーブル

```sql
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    text LONGTEXT NOT NULL,
    sender VARCHAR(50) NOT NULL,
    delete_flag TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);
```

### message_likes テーブル

```sql
CREATE TABLE message_likes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  message_id INT NOT NULL,
  user_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_message_user (message_id, user_id),
  FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_message_id (message_id)
);
```

### お問い合わせメッセージテーブル
```sql
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'in_progress', 'resolved') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## トラブルシューティング

### データベースに接続できない

- MySQL が起動しているか確認
- `.env` ファイルのDB設定を確認
- XAMPP のコントロールパネルで MySQL を起動

### OpenAI API エラー

- `.env` ファイルに正しい API キーを設定
- OpenAI アカウントが有効か確認
- API の使用限度に達していないか確認

### PHP エラー

- PHP 7.4 以上を使用しているか確認
- `php.ini` で `allow_url_fopen = On` に設定
- error_log を確認

### フロントエンドのビルドエラー

- Node.js 18 以上がインストールされているか確認
- `npm install` を実行して依存関係をインストール
- `node_modules` を削除して再度 `npm install` を実行

### CORS エラー

- Apache の設定で CORS ヘッダーが適切に設定されているか確認
- ブラウザの開発者ツールでネットワークタブを確認

## 技術スタック

### フロントエンド
- React 18
- Vite 5（ビルドツール）
- CSS Modules / CSS-in-JS

### バックエンド
- PHP 7.4+
- MySQL 5.7+
- OpenAI API

## セキュリティに関する注意

このアプリケーションは学習目的で作成されています。本番環境での使用時は以下の対策をしてください：

- HTTPS を使用
- パスワードを強力に
- CSRF トークンの実装
- SQL インジェクション対策（プリペアドステートメント使用済み）
- XSS 対策（HTMLエスケープ実装済み）
- レート制限の実装
- 入力値の厳密なバリデーション

## 更新履歴

## v1.7.5（2026-01-25）

- **AIプロバイダ選択（Gemini プレビュー）**：
  - チャットで `provider` に `openai` / `gemini` を選択可能（既定は `openai`）
  - `.env` に `GEMINI_API_KEY` を追加（任意）。Gemini はプレビューのため挙動が変わる可能性があります
- **フロントエンドのAPIベースURLを改善**：
  - `VITE_API_URL` 未設定時、`window.location.origin + '/api/'` に自動フォールバック
  - さくらの `.htaccess` リライトにより `/api` が PHP バックエンドに到達し、CORS 周りの設定が簡潔に
- **テーマランキングを週間+コンパクト表示に**：
  - 期間を「直近1週間」に拡張、初期表示はTOP3、［もっと見る］で拡張
  - 日本語に最適化したトークナイズ・ストップワードの改善（記号や時間表現、助詞などのノイズを除去）

## v1.7.4（2026-01-24）

- **今日のテーマランキング機能を追加**：
  - 📊 利用状況画面に「今日のテーマランキング TOP 5」を表示
  - 今日投稿された質問から自動的にテーマ（キーワード）を抽出
  - 出現頻度が高いテーマを TOP 5 としてランキング表示
  - 金銀銅メダル（🥇🥈🥉）で 1-3 位を表示
  - テーマごとの出現件数を表示
  - 新しい API エンドポイント: `GET /api/today-topics.php` - 今日のテーマランキングを取得

## v1.7.3（2026-01-24）

- **利用規約機能の実装**：
  - 📋 一般的な利用規約ページを作成（`/public/terms.html`）
  - 新規登録フォームに利用規約同意チェックボックスを追加
  - 同意なしでは登録ボタンが無効化される（UX向上）
  - 利用規約にはサービス内容、禁止事項、免責事項、アカウント停止規定を記載

## v1.7.2（2026-01-24）

- **未来Story機能のさらなる改善**：
  - 🎨 **画像補足コメント欄を追加** - 画像生成のイメージを補助するコメント入力フィールド
  - ストーリー作成時に画像補足コメントを入力可能（例：暖かい雰囲気、自然光、笑顔のキャラクター等）
  - ストーリーカード表示時に画像補足コメントを表示
  - グラデーション背景で画像補足コメントを視覚的に強調
  - 新しいマイグレーションスクリプト: `migrations/add_image_comment_to_goal_notes.sql` - goal_notesテーブルにimage_commentカラムを追加
  - フォームボタンを文字幅に合わせた幅に修正

## v1.7.1（2026-01-23）

- **未来Story機能の改善**：
  - 表記を「未来ストーリー」から「未来Story」に統一
  - **ルーム目標選択機能を追加** - チャットルームで作成した目標を未来Storyに追加可能
  - 新しいAPI: `GET /api/story.php?action=room_goals` - ユーザーのルーム目標一覧を取得
  - ゴール選択モーダル: ルーム名、目標テキスト、作成日が表示され、クリックで選択
  - roomId が null の場合は「未来Story」という名前のデフォルトルームを自動作成
  - 目標選択ボタンに視覚的な改善: ホバーエフェクト、アニメーション付き

## v1.7（2026-01-23）

- **未来Story機能の実装**：
  - `goal_notes` テーブルに `story_date` フィールドを追加し、日付ベースでストーリーを管理
  - 新しい `story.php` API エンドポイント：GET（一覧）、POST（作成）、PUT（更新）、DELETE（削除）
  - サイドバーに「🌟 未来Story」ボタンを追加
  - `FutureStory.jsx` コンポーネント：過去→現在→未来のストーリーを時系列で管理
  - タブナビゲーション：「過去のストーリー」「今のストーリー」「未来のストーリー」で分類表示
  - インライン編集：ストーリーのテキストと日付を直接編集可能
  - 画像生成機能（プレースホルダー）：OpenAI DALL-E統合に向けた実装準備
  - アニメーション：ストーリーカードのスライドインアニメーション付き
  - レスポンシブデザイン対応

## v1.6（2026-01-23）

- パスワードポリシーを強化（登録・パスワード変更両方のAPI/画面）：
  - 同一文字3連続禁止、数字の昇降順3連続禁止、キーボード横並び3連続禁止
  - メールアドレスと同一/含有のパスワードを禁止、記号は任意（必須ではない）
  - フロントのRegisterForm/ChangePasswordFormで強度バー・要件チェックと送信ボタン無効化に反映
- 新規登録完了時に初回ログインポイント付与（重複付与は日付で防止）
- **ルーム目標管理機能** 
  - 利用状況画面のルーム一覧に目標欄を追加。クリックでインライン編集可能
  - goal_notesテーブルから最新の目標を取得・表示
- **AIチャットのコンテンツフィルタリング強化**
  - グラフィックな暴力や残虐な内容の回答を制限
  - 未成年者に危険なバイラルチャレンジの回答を制限
  - 性的、恋愛的、暴力的なロールプレイの回答を制限
  - 自傷行為の描写に関する回答を制限
  - 極端な美容基準、不健康なダイエット、ボディシェイミングに関する回答を制限
- ガチャの画像容量を削減

### v1.5 (2026-01-13)

#### 新機能
- **パスワード強度チェック機能** を追加
  - リアルタイムでパスワード強度を表示（弱い/普通/強い）
  - 8文字以上、大文字・小文字・数字を必須に
  - メールアドレスと同じまたは含まれるパスワードを拒否
  - バックエンド側でも厳密に検証

#### 改善
- さくらインターネット対応のビルド設定
  - vite.config.jsを環境変数対応に変更
  - .envファイルでVITE_BASE_PATH、VITE_API_URLを設定可能
  
#### ドキュメント
- さくらインターネットへのデプロイ手順を詳細に追記
- ファイル構成、SSH操作、.htaccess設定等を記載

### v1.4 (2026-01-12)

#### 新機能
- **認証画面UI改善**
  - ログイン/新規登録画面に背景画像（gatway_login_logo.png）を設定
  - ログイン画面にタイプライター効果を追加（左側に動機付けメッセージが1文字ずつ表示）
  - 新規登録ボタンにガラスフラッシュ効果を追加（光沢の光が流れるアニメーション）

#### UI改善
- ログイン画面を2カラムレイアウトに変更（左側：メッセージ、右側：フォーム）
- title_logoを中央上部に配置し、サイズを800pxに拡大
- 新規登録画面はシンプルな中央レイアウトを維持

### v1.3 (2026-01-11)

#### 新機能
- **ルーム利用状況統計画面** を追加
  - 各ルームの質問数、いいね数、目標設定数を一覧表示
  - 質問数TOP5ルームを横棒グラフで可視化
  - ルームごとに手動で完了/進行中の状態を切り替え可能
  - サマリーカードで総ルーム数、完了済み数、総質問数、目標設定数を表示
  - **ルーム名クリックで詳細表示** - テーブル内のルーム名をクリックするとモーダルが開き、日別の質問数推移を折れ線グラフで表示
  - **励ましメッセージ** - 統計画面に遷移すると寄り添い画像から「いつも頑張ってるね！✨」というメッセージが5秒間表示

#### データベース変更
- `rooms` テーブルに `is_completed` フィールドを追加（ルームの完了状態を管理）

#### UI改善
- メッセージのいいねボタンをメッセージ下部に配置（時間表示は削除）
- ルーム選択時にroom-headerの下に注意書きを表示（目立つ黄色背景）
- 完了済みルームは統計画面で緑色の背景で表示
- 統計画面のルーム名をクリック可能に（ホバー時に下線表示）
- 折れ線グラフでルームの日別利用推移を可視化（SVGベースの滑らかなグラフ）
- グラデーション吹き出しで励ましメッセージを表示（バウンスアニメーション付き）

#### API更新
- `PUT /api/room.php` で `is_completed` フィールドの更新に対応

## ライセンス

MIT License

## 参考資料

- [React 公式ドキュメント](https://react.dev/)
- [Vite 公式ドキュメント](https://vitejs.dev/)
- [PHP 公式ドキュメント](https://www.php.net/manual/)
- [OpenAI API ドキュメント](https://platform.openai.com/docs/)
- [XAMPP](https://www.apachefriends.org/)
