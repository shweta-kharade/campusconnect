<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];
    $owner_id = $_POST['owner_id'];
    $user_id = $_SESSION['user_id'];
    $message = $_POST['message'] ?? '';
    
    // Can't connect to yourself
    if ($user_id == $owner_id) {
        echo json_encode(['success' => false, 'message' => 'You cannot connect to your own request']);
        exit();
    }
    
    // Check if already connected
    $stmt = $pdo->prepare("
        SELECT id FROM study_matches 
        WHERE request_id = ? AND matched_user_id = ?
    ");
    $stmt->execute([$request_id, $user_id]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You already requested to connect']);
        exit();
    }
    
    // Create match request
    $stmt = $pdo->prepare("
        INSERT INTO study_matches (request_id, matched_user_id, message) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$request_id, $user_id, $message]);
    
    echo json_encode(['success' => true, 'message' => 'Connection request sent!']);
    exit();
}
?>