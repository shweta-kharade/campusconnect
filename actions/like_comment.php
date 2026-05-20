<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment_id = $_POST['comment_id'];
    $user_id = $_SESSION['user_id'];
    
    // Check if already liked
    $stmt = $pdo->prepare("SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?");
    $stmt->execute([$comment_id, $user_id]);
    $liked = $stmt->fetch();
    
    if ($liked) {
        // Unlike
        $stmt = $pdo->prepare("DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?");
        $stmt->execute([$comment_id, $user_id]);
        echo json_encode(['success' => true, 'liked' => false, 'likes_count' => getCommentLikeCount($comment_id, $pdo)]);
    } else {
        // Like
        $stmt = $pdo->prepare("INSERT INTO comment_likes (comment_id, user_id) VALUES (?, ?)");
        $stmt->execute([$comment_id, $user_id]);
        echo json_encode(['success' => true, 'liked' => true, 'likes_count' => getCommentLikeCount($comment_id, $pdo)]);
    }
    exit();
}

function getCommentLikeCount($comment_id, $pdo) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM comment_likes WHERE comment_id = ?");
    $stmt->execute([$comment_id]);
    $result = $stmt->fetch();
    return $result['count'];
}
?>