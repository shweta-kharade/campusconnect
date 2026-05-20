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
    $study_type = $_POST['study_type'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $subject = trim($_POST['subject'] ?? '');
    $semester = !empty($_POST['semester']) ? $_POST['semester'] : null;
    $preferred_time = trim($_POST['preferred_time'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $looking_for = trim($_POST['looking_for'] ?? '');
    
    if (empty($title) || empty($description)) {
        $_SESSION['error'] = "Please fill in all required fields";
        header('Location: ../pages/study.php');
        exit();
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO study_requests (user_id, study_type, title, description, subject, semester, preferred_time, location, looking_for) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $study_type, $title, $description, $subject, $semester, $preferred_time, $location, $looking_for]);
    
    addPoints($user_id, 15, $pdo);
    
    $_SESSION['success'] = "Study request posted successfully!";
    header('Location: ../pages/study.php');
    exit();
}
?>