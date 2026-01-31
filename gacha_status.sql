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
-- テーブルの構造 `gacha_status`
--

CREATE TABLE `gacha_status` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `gacha_id` int NOT NULL,
  `stage` int NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `gacha_status`
--

INSERT INTO `gacha_status` (`id`, `user_id`, `gacha_id`, `stage`, `created_at`, `updated_at`) VALUES
(70, 1, 0, 1, '2026-01-15 17:45:42', '2026-01-15 17:45:47'),
(86, 4, 0, 1, '2026-01-21 10:41:38', '2026-01-21 10:41:38');

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `gacha_status`
--
ALTER TABLE `gacha_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_gacha` (`user_id`,`gacha_id`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `gacha_status`
--
ALTER TABLE `gacha_status`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `gacha_status`
--
ALTER TABLE `gacha_status`
  ADD CONSTRAINT `gacha_status_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
