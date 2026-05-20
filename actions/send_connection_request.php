<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_user_id = $_POST['user_id'];
    $user_id = $_SESSION['user_id'];
    
    // Don't connect to yourself
    if ($user_id == $target_user_id) {
        echo json_encode(['success' => false, 'message' => 'You cannot connect to yourself']);
        exit();
    }
    
    // Check if already connected
    $stmt = $pdo->prepare("
        SELECT id FROM study_matches sm
        JOIN study_requests sr ON sm.request_id = sr.id
        WHERE (sr.user_id = ? AND sm.matched_user_id = ?)
        AND sm.status = 'accepted'
    ");
    $stmt->execute([$target_user_id, $user_id]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Already connected']);
        exit();
    }
    
    // Create a generic study request to connect
    // First, check if user has a general study request
    $stmt = $pdo->prepare("
        SELECT id FROM study_requests 
        WHERE user_id = ? AND study_type = 'study_buddy' AND status = 'open'
        LIMIT 1
    ");
    $stmt->execute([$target_user_id]);
    $request = $stmt->fetch();
    
    if (!$request) {
        // Create a general request for the user
        $stmt = $pdo->prepare("
            INSERT INTO study_requests (user_id, study_type, title, description, status)
            VALUES (?, 'study_buddy', 'General Study Partner', 'Looking for study partners', 'open')
        ");
        $stmt->execute([$target_user_id]);
        $request_id = $pdo->lastInsertId();
    } else {
        $request_id = $request['id'];
    }
    
    // Create connection request
    $stmt = $pdo->prepare("
        INSERT INTO study_matches (request_id, matched_user_id, message)
        VALUES (?, ?, 'Would you like to connect?')
    ");
    $stmt->execute([$request_id, $user_id]);
    
    echo json_encode(['success' => true, 'message' => 'Connection request sent!']);
    exit();
}
?>