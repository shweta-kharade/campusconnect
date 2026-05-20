<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'];
    $user_id = $_SESSION['user_id'];
    
    // Check if already registered
    $stmt = $pdo->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$event_id, $user_id]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Already registered for this event']);
        exit();
    }
    
    // Register user
    $stmt = $pdo->prepare("INSERT INTO event_registrations (event_id, user_id) VALUES (?, ?)");
    $stmt->execute([$event_id, $user_id]);
    
    // Update registered count
    $stmt = $pdo->prepare("UPDATE events SET registered_count = registered_count + 1 WHERE id = ?");
    $stmt->execute([$event_id]);
    
    echo json_encode(['success' => true, 'message' => 'Successfully registered for event!']);
    exit();
}
?>