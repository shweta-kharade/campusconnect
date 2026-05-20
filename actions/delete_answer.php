<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answer_id = isset($_POST['answer_id']) ? (int)$_POST['answer_id'] : 0;
    $doubt_id = isset($_POST['doubt_id']) ? (int)$_POST['doubt_id'] : 0;
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];
    
    // Get answer owner
    $stmt = $pdo->prepare("SELECT user_id FROM doubt_answers WHERE id = ?");
    $stmt->execute([$answer_id]);
    $answer = $stmt->fetch();
    
    // Get doubt owner
    $stmt = $pdo->prepare("SELECT user_id FROM doubts WHERE id = ?");
    $stmt->execute([$doubt_id]);
    $doubt = $stmt->fetch();
    
    // Check permission: answer owner OR doubt owner OR teacher
    $can_delete = ($answer['user_id'] == $user_id) || ($doubt['user_id'] == $user_id) || ($user_role == 'teacher');
    
    if (!$can_delete) {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this answer']);
        exit();
    }
    
    // Delete answer likes
    $stmt = $pdo->prepare("DELETE FROM answer_likes WHERE answer_id = ?");
    $stmt->execute([$answer_id]);
    
    // Delete answer
    $stmt = $pdo->prepare("DELETE FROM doubt_answers WHERE id = ?");
    $stmt->execute([$answer_id]);
    
    // Update answer count in doubts table
    $stmt = $pdo->prepare("UPDATE doubts SET answers_count = answers_count - 1 WHERE id = ?");
    $stmt->execute([$doubt_id]);
    
    // Get updated answer count
    $stmt = $pdo->prepare("SELECT answers_count FROM doubts WHERE id = ?");
    $stmt->execute([$doubt_id]);
    $count = $stmt->fetch();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Answer deleted successfully',
        'answers_count' => $count['answers_count']
    ]);
    exit();
}
?>