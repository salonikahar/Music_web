<?php
session_start();
require_once '../config/db.php';
require_once '../includes/premium_check.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
$songId = isset($_GET['song_id']) ? (int)$_GET['song_id'] : 0;

if ($songId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid song ID']);
    exit;
}

// Check if user is premium
if (!isPremiumUser($userId)) {
    // Redirect to payment page for download
    header('Location: ../payment.php?song_id=' . $songId);
    exit;
}

// Get song file path
$stmt = $pdo->prepare("SELECT file_path FROM songs WHERE id = ?");
$stmt->execute([$songId]);
$song = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$song) {
    http_response_code(404);
    echo json_encode(['error' => 'Song not found']);
    exit;
}

// Serve the file
$filePath = '../' . $song['file_path'];
if (file_exists($filePath)) {
    header('Content-Type: audio/mpeg');
    header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
} else {
    http_response_code(404);
    echo json_encode(['error' => 'File not found']);
}
?>
