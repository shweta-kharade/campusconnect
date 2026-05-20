<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];
    
    // Check if already liked
    $stmt = $pdo->prepare("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);
    $liked = $stmt->fetch();
    
    if ($liked) {
        // Unlike
        $stmt = $pdo->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
        $stmt = $pdo->prepare("UPDATE feed_posts SET likes_count = likes_count - 1 WHERE id = ?");
        $stmt->execute([$post_id]);
        
        // Get new count
        $stmt = $pdo->prepare("SELECT likes_count FROM feed_posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $count = $stmt->fetch();
        
        echo json_encode([
            'success' => true, 
            'liked' => false, 
            'likes_count' => $count['likes_count']
        ]);
    } else {
        // Like
        $stmt = $pdo->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
        $stmt->execute([$post_id, $user_id]);
        $stmt = $pdo->prepare("UPDATE feed_posts SET likes_count = likes_count + 1 WHERE id = ?");
        $stmt->execute([$post_id]);
        
        // Get new count
        $stmt = $pdo->prepare("SELECT likes_count FROM feed_posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $count = $stmt->fetch();
        
        echo json_encode([
            'success' => true, 
            'liked' => true, 
            'likes_count' => $count['likes_count']
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>