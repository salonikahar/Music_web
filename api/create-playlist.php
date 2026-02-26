<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$name = trim($data['name'] ?? '');

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Playlist name is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO playlists (name, user_id) VALUES (?, ?)");
    $stmt->execute([$name, $user_id]);

    echo json_encode(['success' => true, 'message' => 'Playlist created successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
