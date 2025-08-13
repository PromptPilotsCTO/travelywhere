-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 08, 2025 at 12:56 PM
-- Server version: 10.11.10-MariaDB
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u952254373_travelywhereDB`
--

-- --------------------------------------------------------

--
-- Table structure for table `email_templates`
--

CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL,
  `template_key` varchar(100) NOT NULL,
  `language` varchar(5) NOT NULL DEFAULT 'de',
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_templates`
--

INSERT INTO `email_templates` (`id`, `template_key`, `language`, `subject`, `body`, `created_at`, `updated_at`) VALUES
(1, 'registration_confirmation', 'de', 'Bitte bestätigen Sie Ihre E-Mail-Adresse für Travelywhere', 'Hallo {{name}},<br><br>Vielen Dank für Ihre Registrierung bei Travelywhere!<br>Bitte klicken Sie auf den folgenden Link, um Ihre E-Mail-Adresse zu bestätigen:<br><a href=\"{{verification_link}}\">{{verification_link}}</a><br><br>Wenn Sie sich nicht registriert haben, ignorieren Sie diese E-Mail bitte.<br><br>Viele Grüße,<br>Ihr Travelywhere Team', '2025-05-30 07:34:22', '2025-05-30 07:34:22'),
(2, 'registration_confirmation', 'en', 'Please confirm your email address for Travelywhere', 'Hello {{name}},<br><br>Thank you for registering with Travelywhere!<br>Please click the following link to confirm your email address:<br><a href=\"{{verification_link}}\">{{verification_link}}</a><br><br>If you did not register, please ignore this email.<br><br>Best regards,<br>Your Travelywhere Team', '2025-05-30 07:36:23', '2025-05-30 07:36:23');

-- --------------------------------------------------------

--
-- Table structure for table `triplogs`
--

CREATE TABLE `triplogs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `remember_text` text DEFAULT NULL,
  `ds_story` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `triplog_media`
--

CREATE TABLE `triplog_media` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `triplog_id` bigint(20) UNSIGNED NOT NULL,
  `file_url` text NOT NULL,
  `file_type` enum('image','video','audio') NOT NULL,
  `gps_latitude` decimal(10,7) DEFAULT NULL,
  `gps_longitude` decimal(10,7) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_confirmed` tinyint(1) NOT NULL DEFAULT 0,
  `activation_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `firstname`, `lastname`, `username`, `password_hash`, `is_confirmed`, `activation_token`, `created_at`, `updated_at`) VALUES
(1, 'test@example.com', 'Max Mustermann', NULL, NULL, '$2y$10$Vhbzt9H5sr1rUPd2h5Qai.Ih/BSpAeaa7Nu4gZr.hMUgxkYpz0ewS', 0, '4055c4fb20de2f59e277ddba8ebc45775250d1d95ad0dea76678a9c909a9309e', '2025-05-30 08:05:42', '2025-05-30 08:05:42'),
(2, 'test2@example.com', 'Max2 Mustermann', NULL, NULL, '$2y$10$UI1HF3xZZ/.VzAJyx/xqdOjFFQJ5EYQX2TjkBJZ0lH6px3i3DJSBm', 0, '9bb304d77483a2f1e3ba2a21fb9fce601a2ccf039c4e143c8c2c28fac51d868e', '2025-05-30 08:09:22', '2025-05-30 08:09:22'),
(3, 'max@travelywhere.com', 'Max2 Mustermann', NULL, NULL, '$2y$10$zq8UinqME1Mx.nbjGXKTze0H5degBYd6o4v.bQ/lHErWr7m43VcdC', 1, NULL, '2025-05-30 08:11:44', '2025-05-30 08:12:35'),
(4, 'heinz@travelywhere.com', 'Heinz', NULL, NULL, '$2y$10$NXNVTyHWyNWzobBOAapU9eyDUsYHCiMMWUrcKIrdVnLNoSb9WVUAC', 1, NULL, '2025-05-30 08:26:56', '2025-05-30 08:28:12'),
(5, 'andre.stormberg@gmx.de', 'Andi', NULL, NULL, '$2y$10$eL8N42cLAIUAmJMbJp0FZeBYAjrEbSoO5lCaS7DJ2kH1yAIOd/QYe', 1, NULL, '2025-05-30 08:27:53', '2025-05-30 10:04:00'),
(6, 'inga@travelywhere.com', 'Inga', NULL, NULL, '$2y$10$xzyP6hYEhRdGatDOA06o5ev.KkuW0iMexH81iyjVneESJ4KmD.2We', 1, NULL, '2025-05-30 08:31:11', '2025-05-30 08:31:55'),
(7, 'andre@create-an-artist.com', 'Andre', NULL, NULL, '$2y$10$Dki2Q2BrowP3JuUDINH1/O8cxEmm8jio8v9dl4b4CR2PhcBioTQdK', 1, NULL, '2025-08-08 12:50:19', '2025-08-08 12:50:47');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `device_info` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `email_templates`
--
ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_template_key_language` (`template_key`,`language`);

--
-- Indexes for table `triplogs`
--
ALTER TABLE `triplogs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `triplog_media`
--
ALTER TABLE `triplog_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `triplog_id` (`triplog_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `email_templates`
--
ALTER TABLE `email_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `triplogs`
--
ALTER TABLE `triplogs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `triplog_media`
--
ALTER TABLE `triplog_media`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `triplog_media`
--
ALTER TABLE `triplog_media`
  ADD CONSTRAINT `triplog_media_ibfk_1` FOREIGN KEY (`triplog_id`) REFERENCES `triplogs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
