-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- ホスト: mysql3112.db.sakura.ne.jp
-- 生成日時: 2026 年 1 月 31 日 11:15
-- サーバのバージョン： 8.0.43
-- PHP のバージョン: 8.2.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- データベース: `silvercow67_chatgpt`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `goal_notes`
--

CREATE TABLE `goal_notes` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `room_id` int NOT NULL,
  `message_id` int DEFAULT NULL,
  `note_text` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `story_date` date DEFAULT NULL COMMENT '未来ストーリーの日付（未来ストーリーの場合のみ設定）',
  `image_comment` text COLLATE utf8mb4_unicode_ci,
  `story_image` longtext COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `goal_notes`
--

INSERT INTO `goal_notes` (`id`, `user_id`, `room_id`, `message_id`, `note_text`, `created_at`, `story_date`, `image_comment`, `story_image`) VALUES
(3, 1, 3, NULL, '自分のできる範囲を広げて、小さな成功体験から、自分の成長が少しでも実感できるものにする。そんな周りの人たちの状況が見えたとき、明るい日本の未来が見えるような気がする。', '2026-01-15 17:17:32', NULL, NULL, NULL);

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `goal_notes`
--
ALTER TABLE `goal_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `message_id` (`message_id`),
  ADD KEY `idx_story_date` (`user_id`,`story_date`,`created_at` DESC),
  ADD KEY `idx_image_comment` (`image_comment`(50));

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `goal_notes`
--
ALTER TABLE `goal_notes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `goal_notes`
--
ALTER TABLE `goal_notes`
  ADD CONSTRAINT `goal_notes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `goal_notes_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `goal_notes_ibfk_3` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
