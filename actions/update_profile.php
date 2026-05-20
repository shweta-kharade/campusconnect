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
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'] ?? '';
    $department = $_POST['department'] ?? '';
    $semester = !empty($_POST['semester']) ? $_POST['semester'] : null;
    $bio = $_POST['bio'] ?? '';
    $github = $_POST['github'] ?? '';
    $linkedin = $_POST['linkedin'] ?? '';
    $achievements = $_POST['achievements'] ?? '';
    $address = $_POST['address'] ?? '';
    
    // Handle avatar upload
    $avatar_path = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
        $upload_dir = dirname(__DIR__) . '/assets/uploads/avatars/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $filename = time() . '_' . $user_id . '.' . $file_extension;
        $destination = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
            $avatar_path = 'assets/uploads/avatars/' . $filename;
        }
    }
    
    // Build update query
    $sql = "UPDATE users SET name = ?, email = ?, phone = ?, department = ?, semester = ?, bio = ?, github = ?, linkedin = ?, achievements = ?, address = ?";
    $params = [$name, $email, $phone, $department, $semester, $bio, $github, $linkedin, $achievements, $address];
    
    if ($avatar_path) {
        $sql .= ", avatar = ?";
        $params[] = $avatar_path;
        $_SESSION['user_avatar'] = $avatar_path;
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $user_id;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $_SESSION['user_name'] = $name;
    $_SESSION['success'] = "Profile updated successfully!";
    header('Location: ../pages/profile.php');
    exit();
}
?>