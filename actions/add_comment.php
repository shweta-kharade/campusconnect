<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];
    $comment = trim($_POST['comment']);
    
    if (empty($comment)) {
        echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
        exit();
    }
    
    // Insert comment
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)");
    $stmt->execute([$post_id, $user_id, $comment]);
    
    // Update comment count in feed_posts
    $stmt = $pdo->prepare("UPDATE feed_posts SET comment_count = comment_count + 1 WHERE id = ?");
    $stmt->execute([$post_id]);
    
    // Get new comment count
    $stmt = $pdo->prepare("SELECT comment_count FROM feed_posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $count = $stmt->fetch();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Comment added',
        'comment_count' => $count['comment_count']
    ]);
    exit();
}
?>