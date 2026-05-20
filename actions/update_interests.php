<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $interests = $_POST['interests'] ?? '';
    $skill_level = $_POST['skill_level'] ?? 'beginner';
    
    // Delete existing interests
    $stmt = $pdo->prepare("DELETE FROM user_interests WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Add new interests
    if (!empty($interests)) {
        $interests_array = array_map('trim', explode(',', $interests));
        $stmt = $pdo->prepare("INSERT INTO user_interests (user_id, interest, skill_level) VALUES (?, ?, ?)");
        
        foreach ($interests_array as $interest) {
            if (!empty($interest)) {
                $stmt->execute([$user_id, $interest, $skill_level]);
            }
        }
    }
    
    $_SESSION['success'] = "Interests updated successfully!";
    header('Location: ../pages/study.php');
    exit();
}
?>