<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $subject = trim($_POST['subject'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    
    if (empty($title) || empty($description)) {
        $_SESSION['error'] = "Please fill in all required fields";
        header('Location: ../pages/doubts.php');
        exit();
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO doubts (user_id, title, description, subject, tags) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $title, $description, $subject, $tags]);
    
    // Add points for asking question
    addPoints($user_id, 10, $pdo);
    
    $_SESSION['success'] = "Your question has been posted!";
    header('Location: ../pages/doubts.php');
    exit();
}
?>