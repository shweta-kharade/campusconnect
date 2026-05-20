<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Check if user is teacher
if ($_SESSION['user_role'] != 'teacher') {
    $_SESSION['error'] = "Only teachers can create events";
    header('Location: ../pages/events.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Print POST data
    echo "<!-- POST Data: ";
    print_r($_POST);
    echo " -->";
    
    $user_id = $_SESSION['user_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $event_type = $_POST['event_type'];
    $event_date = $_POST['event_date'];
    $event_time = !empty($_POST['event_time']) ? $_POST['event_time'] : null;
    $venue = trim($_POST['venue'] ?? '');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    // Validation
    if (empty($title) || empty($description) || empty($event_date)) {
        $_SESSION['error'] = "Please fill in all required fields";
        header('Location: ../pages/events.php');
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO events (user_id, title, description, event_type, event_date, event_time, venue, is_featured) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $result = $stmt->execute([$user_id, $title, $description, $event_type, $event_date, $event_time, $venue, $is_featured]);
        
        if ($result) {
            // Add points for creating event
            if (function_exists('addPoints')) {
                addPoints($user_id, 30, $pdo);
            }
            $_SESSION['success'] = "Event created successfully!";
        } else {
            $_SESSION['error'] = "Failed to create event";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        error_log("Event creation error: " . $e->getMessage());
    }
    
    header('Location: ../pages/events.php');
    exit();
} else {
    header('Location: ../pages/events.php');
    exit();
}
?>