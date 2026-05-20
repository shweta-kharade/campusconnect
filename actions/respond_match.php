<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $match_id = $_POST['match_id'];
    $action = $_POST['action'];
    $user_id = $_SESSION['user_id'];
    
    if ($action == 'accept') {
        $stmt = $pdo->prepare("UPDATE study_matches SET status = 'accepted' WHERE id = ?");
        $stmt->execute([$match_id]);
        
        // Update request status
        $stmt = $pdo->prepare("
            UPDATE study_requests 
            SET status = 'matched' 
            WHERE id = (SELECT request_id FROM study_matches WHERE id = ?)
        ");
        $stmt->execute([$match_id]);
        
        echo json_encode(['success' => true, 'message' => 'Connection accepted!']);
    } elseif ($action == 'reject') {
        $stmt = $pdo->prepare("UPDATE study_matches SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$match_id]);
        
        echo json_encode(['success' => true, 'message' => 'Connection declined']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit();
}
?>