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
-- テーブルの構造 `rooms`
--

CREATE TABLE `rooms` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_completed` tinyint DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `delete_flag` tinyint DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `rooms`
--

INSERT INTO `rooms` (`id`, `user_id`, `name`, `is_completed`, `created_at`, `updated_at`, `delete_flag`) VALUES
(3, 1, 'チャット機能をわかりやすく、目標が立てやすいものに', 0, '2025-12-25 00:32:32', '2026-01-22 03:38:24', 0),
(4, 1, '3回目の確認', 0, '2025-12-25 01:17:07', '2025-12-25 03:02:03', 1),
(5, 2, '1回目の確認', 0, '2025-12-25 02:00:58', '2025-12-25 02:00:58', 0),
(6, 1, '3回目の確認', 0, '2025-12-25 03:02:11', '2026-01-12 01:29:32', 0),
(7, 1, '卒業制作', 0, '2026-01-11 02:31:24', '2026-01-12 01:29:30', 0),
(8, 1, '4回目の確認', 0, '2026-01-13 13:34:51', '2026-01-13 13:34:51', 0),
(9, 1, '５回目の確認', 0, '2026-01-13 13:35:32', '2026-01-22 03:38:28', 0),
(10, 1, '６回目の確認', 0, '2026-01-13 13:35:43', '2026-01-22 03:38:26', 0),
(11, 4, 'New Chat', 0, '2026-01-21 10:20:10', '2026-01-21 10:20:10', 0);

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
