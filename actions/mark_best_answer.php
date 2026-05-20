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
    $answer_id = $_POST['answer_id'];
    $doubt_id = $_POST['doubt_id'];
    $user_id = $_SESSION['user_id'];
    
    // Verify user is the question owner
    $stmt = $pdo->prepare("SELECT user_id FROM doubts WHERE id = ?");
    $stmt->execute([$doubt_id]);
    $doubt = $stmt->fetch();
    
    if ($doubt['user_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'Only question owner can mark best answer']);
        exit();
    }
    
    // Remove previous best answer
    $stmt = $pdo->prepare("UPDATE doubt_answers SET is_best = 0 WHERE doubt_id = ?");
    $stmt->execute([$doubt_id]);
    
    // Mark new best answer
    $stmt = $pdo->prepare("UPDATE doubt_answers SET is_best = 1 WHERE id = ?");
    $stmt->execute([$answer_id]);
    
    // Mark doubt as resolved
    $stmt = $pdo->prepare("UPDATE doubts SET is_resolved = 1 WHERE id = ?");
    $stmt->execute([$doubt_id]);
    
    // Give extra points to answerer
    $stmt = $pdo->prepare("SELECT user_id FROM doubt_answers WHERE id = ?");
    $stmt->execute([$answer_id]);
    $answer = $stmt->fetch();
    addPoints($answer['user_id'], 50, $pdo);
    
    echo json_encode(['success' => true]);
}
?>