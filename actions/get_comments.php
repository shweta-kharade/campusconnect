<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $post_id = $_GET['post_id'];
    $user_id = $_SESSION['user_id'];
    
    // Get comments with user info
    $stmt = $pdo->prepare("
        SELECT c.*, u.name, u.avatar, u.role,
            (SELECT COUNT(*) FROM comment_likes WHERE comment_id = c.id) as likes_count
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$post_id]);
    $comments = $stmt->fetchAll();
    
    // Check which comments the user has liked
    $comment_ids = array_column($comments, 'id');
    $liked_comments = [];
    if (!empty($comment_ids)) {
        $placeholders = str_repeat('?,', count($comment_ids) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT comment_id FROM comment_likes 
            WHERE comment_id IN ($placeholders) AND user_id = ?
        ");
        $params = array_merge($comment_ids, [$user_id]);
        $stmt->execute($params);
        $liked_comments = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Format comments for JSON
    $formatted_comments = [];
    foreach ($comments as $comment) {
        $avatar_url = '';
        if (!empty($comment['avatar'])) {
            $avatar_url = '/campusconnect/' . ltrim($comment['avatar'], '/');
        }
        
        $formatted_comments[] = [
            'id' => $comment['id'],
            'user_id' => $comment['user_id'],  // Add user_id here
            'name' => $comment['name'],
            'comment' => $comment['comment'],
            'created_at' => $comment['created_at'],
            'likes_count' => $comment['likes_count'],
            'role' => $comment['role'],
            'avatar_url' => $avatar_url,
            'is_liked' => in_array($comment['id'], $liked_comments)
        ];
    }
    
    echo json_encode(['success' => true, 'comments' => $formatted_comments]);
    exit();
}
?>