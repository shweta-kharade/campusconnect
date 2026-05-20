<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answer_id = $_POST['answer_id'];
    $user_id = $_SESSION['user_id'];
    
    // Check if already liked
    $stmt = $pdo->prepare("SELECT id FROM answer_likes WHERE answer_id = ? AND user_id = ?");
    $stmt->execute([$answer_id, $user_id]);
    $liked = $stmt->fetch();
    
    if ($liked) {
        $stmt = $pdo->prepare("DELETE FROM answer_likes WHERE answer_id = ? AND user_id = ?");
        $stmt->execute([$answer_id, $user_id]);
        $stmt = $pdo->prepare("UPDATE doubt_answers SET likes_count = likes_count - 1 WHERE id = ?");
        $stmt->execute([$answer_id]);
        echo json_encode(['success' => true, 'liked' => false, 'likes_count' => getLikeCount($answer_id, $pdo)]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO answer_likes (answer_id, user_id) VALUES (?, ?)");
        $stmt->execute([$answer_id, $user_id]);
        $stmt = $pdo->prepare("UPDATE doubt_answers SET likes_count = likes_count + 1 WHERE id = ?");
        $stmt->execute([$answer_id]);
        echo json_encode(['success' => true, 'liked' => true, 'likes_count' => getLikeCount($answer_id, $pdo)]);
    }
}

function getLikeCount($answer_id, $pdo) {
    $stmt = $pdo->prepare("SELECT likes_count FROM doubt_answers WHERE id = ?");
    $stmt->execute([$answer_id]);
    $result = $stmt->fetch();
    return $result['likes_count'];
}
?>