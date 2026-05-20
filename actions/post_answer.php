<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doubt_id = $_POST['doubt_id'];
    $user_id = $_SESSION['user_id'];
    $answer = trim($_POST['answer']);
    
    if (empty($answer)) {
        $_SESSION['error'] = "Please write an answer";
        header("Location: ../pages/doubt_detail.php?id=$doubt_id");
        exit();
    }
    
    $stmt = $pdo->prepare("INSERT INTO doubt_answers (doubt_id, user_id, answer) VALUES (?, ?, ?)");
    $stmt->execute([$doubt_id, $user_id, $answer]);
    
    // Update answer count in doubts table
    $stmt = $pdo->prepare("UPDATE doubts SET answers_count = answers_count + 1 WHERE id = ?");
    $stmt->execute([$doubt_id]);
    
    // Add points for answering
    addPoints($user_id, 15, $pdo);
    
    $_SESSION['success'] = "Your answer has been posted!";
    header("Location: ../pages/doubt_detail.php?id=$doubt_id");
    exit();
}
?>