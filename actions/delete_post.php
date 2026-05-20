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
    $post_owner_id = $_POST['post_owner_id'];
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];
    
    // Check permission: post owner OR teacher
    $can_delete = ($post_owner_id == $user_id) || ($user_role == 'teacher');
    
    if (!$can_delete) {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this post']);
        exit();
    }
    
    // Get file path to delete attachment if exists
    $stmt = $pdo->prepare("SELECT file_path FROM feed_posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();
    
    // Delete file if exists
    if ($post && !empty($post['file_path'])) {
        $file_path = dirname(__DIR__) . '/' . $post['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    // Delete post (cascade will delete comments and likes automatically)
    $stmt = $pdo->prepare("DELETE FROM feed_posts WHERE id = ?");
    $stmt->execute([$post_id]);
    
    echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);
    exit();
}
?>