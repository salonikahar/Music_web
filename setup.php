<?php
// setup.php - Spotify Clone Database Installer
// Fully self-contained installer with embedded SQL

require_once 'config/db.php';

$host = $database_config['host'];
$port = $database_config['port'];
$username = $database_config['username'];
$password = $database_config['password'];
$dbname = $database_config['dbname'];

$message = '';
$status = ''; // 'success' or 'error'

if (isset($_POST['install'])) {
    try {
        // 1. Connect to MySQL (without selecting DB)
        $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 2. Create Database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdo->exec("USE `$dbname`");

        // 3. Drop existing tables for clean install
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $existingTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($existingTables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
        }
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        // 4. Import Embedded SQL
        // Using NOWDOC to avoid variable parsing issues
        $sql = <<<'SQL'
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 02, 2026 at 06:59 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `spotify_clone`
--

-- --------------------------------------------------------

--
-- Table structure for table `albums`
--

CREATE TABLE `albums` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `bg_color` varchar(20) DEFAULT NULL,
  `display_type` enum('color','image') DEFAULT 'color',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `albums`
--

INSERT INTO `albums` (`id`, `name`, `description`, `image_path`, `bg_color`, `display_type`, `created_at`) VALUES
(5, 'Bollywood Love Hits', 'Best romantic Bollywood songs collection', '/uploads/albums/album_69663837a8ed8.png', '#1db954', 'image', '2026-01-11 12:40:54'),
(6, 'Arijit Singh Essentials', 'Top hits of Arijit Singh', '', '#2568ef', 'color', '2026-01-11 12:40:54'),
(7, 'Romantic Hindi Songs', 'Soft and romantic Hindi melodies', '', '#ff230a', 'color', '2026-01-11 12:40:54'),
(8, 'Top Punjabi Hits', 'Popular Punjabi songs and artists', '/uploads/albums/album_69663862ed44a.png', '#1db954', 'image', '2026-01-11 12:40:54'),
(9, 'Soulful Evenings', 'Relaxing and soulful Indian tracks', '/uploads/albums/album_6966386dcfb95.png', '#1db954', 'image', '2026-01-11 12:40:54'),
(10, 'Party Mashup 2024', 'High energy party songs and mashups', '', '#f9f110', 'color', '2026-01-11 12:40:54'),
(11, 'Chill Vibes India', 'Chill and lo-fi Indian music', '', '#ff0088', 'color', '2026-01-11 12:40:54'),
(12, 'Acoustic Hindi Covers', 'Acoustic versions of popular Hindi songs', '', '#15f4e5', 'color', '2026-01-11 12:40:54'),
(13, '90s Bollywood Classics', 'Evergreen Bollywood songs from the 90s', '/uploads/albums/album_6966380c60f4d.png', '#1db954', 'image', '2026-01-11 12:40:54'),
(14, 'Indian Pop Icons', 'Top Indian pop artists and tracks', '', '#1DB954', 'color', '2026-01-11 12:40:54'),
(15, 'Chill Vibes', '', '', '#859dea', 'color', '2026-01-12 11:52:34'),
(16, 'Bollywood Hits', '', '/uploads/albums/album_696638268c58a.png', '#1db954', 'image', '2026-01-12 11:52:57');

-- --------------------------------------------------------

--
-- Table structure for table `artists`
--

CREATE TABLE `artists` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `bio` text DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `artists`
--

INSERT INTO `artists` (`id`, `name`, `bio`, `image_path`, `created_at`) VALUES
(5, 'Arijit Singh', 'Indian playback singer known for soulful and romantic songs', '/uploads/artists/artist_69649a72ef9567.20059452.jpg', '2026-01-11 12:33:38'),
(6, 'Shreya Ghoshal', 'Renowned Indian playback singer with melodious voice', '/uploads/artists/artist_6964991766ec15.09682997.jpeg', '2026-01-11 12:33:38'),
(7, 'Armaan Malik', 'Popular Indian singer known for pop and Bollywood music', '/uploads/artists/artist_696499596fe3e0.87442858.jpg', '2026-01-11 12:33:38'),
(8, 'Neha Kakkar', 'Indian singer famous for party and pop songs', '/uploads/artists/artist_696499079d1320.83715755.jpeg', '2026-01-11 12:33:38'),
(9, 'Atif Aslam', 'Pakistani singer popular in Indian Bollywood music', '/uploads/artists/artist_69649b9ff03396.42086271.jpg', '2026-01-11 12:33:38'),
(10, 'Sonu Nigam', 'Legendary Indian playback singer with versatile voice', '/uploads/artists/artist_69649b3722ea30.76970815.jpg', '2026-01-11 12:33:38'),
(11, 'Jubin Nautiyal', 'Indian singer known for romantic and emotional tracks', '/uploads/artists/artist_69649ab50110a1.40143327.jpg', '2026-01-11 12:33:38'),
(12, 'Badshah', 'Indian rapper, singer and music producer', '/uploads/artists/artist_69649b6ea4caf9.05629711.jpg', '2026-01-11 12:33:38'),
(13, 'Sunidhi Chauhan', 'Indian playback singer known for energetic performances', '/uploads/artists/artist_6964992b6b8f92.08315118.jpeg', '2026-01-11 12:33:38'),
(14, 'Diljit Dosanjh', 'Punjabi singer, actor and global pop icon', '/uploads/artists/artist_696498b545d995.13414640.jpeg', '2026-01-11 12:33:38'),
(17, 'kk', 'kk', '/uploads/artists/artist_697dd66f872ef1.47787091.png', '2026-01-31 09:35:02');

-- --------------------------------------------------------

--
-- Table structure for table `playlists`
--

CREATE TABLE `playlists` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `image_path` varchar(255) DEFAULT '/assets/default-playlist.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `playlists`
--

INSERT INTO `playlists` (`id`, `user_id`, `name`, `image_path`, `created_at`) VALUES
(7, 1, 'test', '/assets/default-playlist.png', '2026-01-23 09:07:13'),
(8, 1, 'test', '/assets/default-playlist.png', '2026-01-23 10:11:32'),
(10, 3, 'test', '/assets/default-playlist.png', '2026-01-29 12:52:00'),
(11, 8, 'test', '/assets/default-playlist.png', '2026-01-31 10:25:21');

-- --------------------------------------------------------

--
-- Table structure for table `songs`
--

CREATE TABLE `songs` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `artist_id` int(11) DEFAULT NULL,
  `album_id` int(11) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `duration` varchar(10) DEFAULT '3:00',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `songs`
--

INSERT INTO `songs` (`id`, `title`, `artist_id`, `album_id`, `file_path`, `image_path`, `duration`, `created_at`) VALUES
(13, 'Tum Hi Ho', 5, 6, '/uploads/songs/song_6964aa7ed807f.mp3', '/uploads/song-covers/cover_696495335900d.jpeg', '4:22', '2026-01-11 12:39:51'),
(14, 'Channa Mereya', 5, 13, '/uploads/songs/song_6964aae0b9d07.mp3', '/uploads/song-covers/cover_69649809f1568.jpg', '4:49', '2026-01-11 12:39:51'),
(15, 'Kesariya', 5, 7, '/uploads/songs/song_6964ab620cba1.mp3', '/uploads/song-covers/cover_6964955d753be.jpeg', '4:28', '2026-01-11 12:39:51'),
(16, 'Agar Tum Saath Ho', 6, NULL, '/uploads/songs/song_6964acaa84e4b.mp3', '/uploads/song-covers/cover_696495754749c.jpeg', '5:41', '2026-01-11 12:39:51'),
(17, 'Bol Do Na Zara', 7, NULL, '/uploads/songs/song_6964ac9766600.mp3', '/uploads/song-covers/cover_696497f94272f.jpg', '4:53', '2026-01-11 12:39:51'),
(18, 'Dilbar', 8, NULL, '/uploads/songs/song_6964ac61339f8.mp3', '/uploads/song-covers/cover_6964979c567f9.jpeg', '3:05', '2026-01-11 12:39:51'),
(19, 'Tera Hone Laga Hoon', 9, NULL, '/uploads/songs/song_6964ac81b1409.mp3', '/uploads/song-covers/cover_6964974909424.jpeg', '4:12', '2026-01-11 12:39:51'),
(20, 'Abhi Mujh Mein Kahin', 10, NULL, '/uploads/songs/song_6964ad235b584.mp3', '/uploads/song-covers/cover_6964973d23482.jpeg', '6:04', '2026-01-11 12:39:51'),
(21, 'Lut Gaye', 11, NULL, '/uploads/songs/song_6964ad6bc4ef5.mp3', '/uploads/song-covers/cover_69649730025aa.jpeg', '4:45', '2026-01-11 12:39:51'),
(22, 'Do You Know', 14, NULL, '/uploads/songs/song_6964adaada63c.mp3', '/uploads/song-covers/cover_696497203ec02.jpeg', '3:38', '2026-01-11 12:39:51');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_picture` varchar(255) DEFAULT NULL,
  `is_premium` tinyint(1) DEFAULT 0,
  `premium_expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `created_at`, `profile_picture`, `is_premium`, `premium_expires_at`) VALUES
(1, 'saloni', 'salonikahar20@gmail.com', '$2y$10$QBg83IXoBnC8GQXZI/pdKOjKFR/PryGbRwmFughpb6Y5BRvw3P8oe', '2026-01-22 09:25:59', 'uploads/profiles/profile_1_1769685250.png', 1, '2026-02-23 15:16:43'),
(2, 'sim', 'sim@gmail.com', '$2y$10$xct3c3Zt03lktJ8xkW8z6ef0FM0nz6kFbvPMUIu//p5X1Wq4Tzn.a', '2026-01-23 05:57:25', NULL, 0, NULL),
(3, 'sam', 'sam@gmail.com', '$2y$10$kg5yUTBA2GCanIfQBIfE8ukjLbnhFSzkn9EU3PoIo50lUDg9jIZ.e', '2026-01-23 06:17:39', 'uploads/profiles/profile_3_1769691333.png', 1, '2026-02-28 18:11:37'),
(4, 'isha', 'isha@gmail.com', '$2y$10$JwSlvVYEhFxPYArIGoiO2OjOspUrW1q61zE8LCkurMiNB.XGhDRyS', '2026-01-31 10:34:15', NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_playlist_songs`
--

CREATE TABLE `user_playlist_songs` (
  `id` int(11) NOT NULL,
  `playlist_id` int(11) NOT NULL,
  `song_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_playlist_songs`
--

INSERT INTO `user_playlist_songs` (`id`, `playlist_id`, `song_id`, `added_at`) VALUES
(4, 7, 20, '2026-01-23 09:07:19'),
(5, 8, 18, '2026-01-23 10:11:38'),
(9, 10, 18, '2026-01-29 12:54:45'),
(10, 10, 14, '2026-01-29 13:07:27'),
(11, 11, 20, '2026-01-31 10:25:32'),
(12, 11, 19, '2026-01-31 10:25:37'),
(13, 11, 18, '2026-01-31 10:25:41'),
(14, 11, 21, '2026-01-31 10:25:44');

-- --------------------------------------------------------

--
-- Indexes for dumped tables
--

--
-- Indexes for table `albums`
--
ALTER TABLE `albums`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `artists`
--
ALTER TABLE `artists`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `playlists`
--
ALTER TABLE `playlists`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `songs`
--
ALTER TABLE `songs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_artist` (`artist_id`),
  ADD KEY `fk_album` (`album_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_playlist_songs`
--
ALTER TABLE `user_playlist_songs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_playlist_song` (`playlist_id`,`song_id`),
  ADD KEY `fk_playlist` (`playlist_id`),
  ADD KEY `fk_song` (`song_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `albums`
--
ALTER TABLE `albums`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `artists`
--
ALTER TABLE `artists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `playlists`
--
ALTER TABLE `playlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `songs`
--
ALTER TABLE `songs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_playlist_songs`
--
ALTER TABLE `user_playlist_songs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `songs`
--
ALTER TABLE `songs`
  ADD CONSTRAINT `fk_album` FOREIGN KEY (`album_id`) REFERENCES `albums` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_artist` FOREIGN KEY (`artist_id`) REFERENCES `artists` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `songs_ibfk_1` FOREIGN KEY (`album_id`) REFERENCES `albums` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_playlist_songs`
--
ALTER TABLE `user_playlist_songs`
  ADD CONSTRAINT `fk_playlist` FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_song` FOREIGN KEY (`song_id`) REFERENCES `songs` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
SQL;

        $pdo->exec($sql);

        $status = 'success';
        $message = "Database '$dbname' installed successfully with all tables and data!";

    } catch (PDOException $e) {
        $status = 'error';
        $message = "Database Error: " . $e->getMessage();
    } catch (Exception $e) {
        $status = 'error';
        $message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spotify Clone Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #121212 0%, #1a1a1a 100%);
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Circular Std', sans-serif;
        }
        .setup-card {
            background: #282828;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .icon {
            font-size: 64px;
            color: #1db954;
            margin-bottom: 20px;
        }
        .btn-install {
            background-color: #1db954;
            color: black;
            font-weight: bold;
            border-radius: 50px;
            padding: 12px 32px;
            border: none;
            transition: transform 0.2s;
        }
        .btn-install:hover {
            background-color: #1ed760;
            transform: scale(1.05);
        }
        .alert {
            text-align: left;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="setup-card">
    <i class="bi bi-database-fill-gear icon"></i>
    <h2 class="mb-4">Spotify Clone Setup</h2>
    
    <?php if ($status === 'success'): ?>
        <div class="alert alert-success">
            <h5 class="alert-heading"><i class="bi bi-check-circle-fill me-2"></i>Success!</h5>
            <p><?= htmlspecialchars($message) ?></p>
        </div>
        <div class="d-grid gap-2">
            <a href="index.php" class="btn btn-light rounded-pill">Open Application</a>
        </div>
    <?php elseif ($status === 'error'): ?>
        <div class="alert alert-danger">
            <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Installation Failed</h5>
            <p><?= htmlspecialchars($message) ?></p>
        </div>
        <form method="post">
            <button type="submit" name="install" class="btn btn-install w-100">Retry Installation</button>
        </form>
    <?php else: ?>
        <p class="text-secondary mb-4">
            This script will automatically install the database <strong><?= htmlspecialchars($dbname) ?></strong>.
            <br>No manual file import required.
        </p>
        <div class="bg-dark p-3 rounded mb-4 text-start small">
            <div><span class="text-secondary">Host:</span> <?= htmlspecialchars($host) ?></div>
            <div><span class="text-secondary">Database:</span> <?= htmlspecialchars($dbname) ?></div>
            <div><span class="text-secondary">Status:</span> Ready to install</div>
        </div>
        <form method="post">
            <button type="submit" name="install" class="btn btn-install w-100">Install Database</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
