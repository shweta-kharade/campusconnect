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
    $comment_owner_id = $_POST['comment_owner_id'];
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];
    
    // Get post_id from the comment
    $stmt = $pdo->prepare("SELECT post_id FROM comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch();
    
    if (!$comment) {
        echo json_encode(['success' => false, 'message' => 'Comment not found']);
        exit();
    }
    
    $post_id = $comment['post_id'];
    
    // Get post owner
    $stmt = $pdo->prepare("SELECT user_id FROM feed_posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();
    $post_owner_id = $post['user_id'];
    
    // Check permission: comment owner OR post owner OR teacher
    $can_delete = ($comment_owner_id == $user_id) || ($post_owner_id == $user_id) || ($user_role == 'teacher');
    
    if (!$can_delete) {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this comment']);
        exit();
    }
    
    // Delete comment likes first (foreign key constraint)
    $stmt = $pdo->prepare("DELETE FROM comment_likes WHERE comment_id = ?");
    $stmt->execute([$comment_id]);
    
    // Delete the comment
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    
    // Update comment count in feed_posts
    $stmt = $pdo->prepare("UPDATE feed_posts SET comment_count = comment_count - 1 WHERE id = ?");
    $stmt->execute([$post_id]);
    
    // Get updated comment count
    $stmt = $pdo->prepare("SELECT comment_count FROM feed_posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $count = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'Comment deleted successfully',
        'comment_count' => $count['comment_count']
    ]);
    exit();
}
?>