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
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $semester = !empty($_POST['semester']) ? $_POST['semester'] : null;
    
    if (empty($title)) {
        $_SESSION['error'] = "Please enter a title";
        header('Location: ../pages/notes.php');
        exit();
    }
    
    if (!isset($_FILES['note_file']) || $_FILES['note_file']['error'] !== 0) {
        $_SESSION['error'] = "Please select a file to upload";
        header('Location: ../pages/notes.php');
        exit();
    }
    
    $upload_dir = dirname(__DIR__) . '/assets/uploads/notes/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file = $_FILES['note_file'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Create a clean filename (remove special characters, spaces)
    $clean_title = preg_replace('/[^a-zA-Z0-9]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
    $filename = time() . '_' . $clean_title . '.' . $file_extension;
    $destination = $upload_dir . $filename;
    $file_size = $file['size'];
    
    // Allowed file types
    $allowed = ['pdf', 'docx', 'doc', 'txt', 'jpg', 'jpeg', 'png'];
    if (!in_array($file_extension, $allowed)) {
        $_SESSION['error'] = "Invalid file type. Allowed: " . implode(', ', $allowed);
        header('Location: ../pages/notes.php');
        exit();
    }
    
    // Check file size (10MB max)
    if ($file_size > 10 * 1024 * 1024) {
        $_SESSION['error'] = "File too large. Maximum 10MB allowed.";
        header('Location: ../pages/notes.php');
        exit();
    }
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $file_path = 'assets/uploads/notes/' . $filename;
        $file_size_formatted = round($file_size / 1024, 2) . ' KB';
        
        $stmt = $pdo->prepare("
            INSERT INTO notes (user_id, title, description, subject, semester, file_path, file_size, is_approved) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$user_id, $title, $description, $subject, $semester, $file_path, $file_size_formatted]);
        
        addPoints($user_id, 20, $pdo);
        $_SESSION['success'] = "Note uploaded successfully!";
    } else {
        $_SESSION['error'] = "Failed to upload file.";
    }
    
    header('Location: ../pages/notes.php');
    exit();
}
?>