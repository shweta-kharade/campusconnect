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
    $user_id = $_SESSION['user_id'];
    
    // Verify ownership
    $stmt = $pdo->prepare("
        SELECT id FROM study_matches 
        WHERE id = ? AND matched_user_id = ?
    ");
    $stmt->execute([$match_id, $user_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    // Delete or update the request
    $stmt = $pdo->prepare("UPDATE study_matches SET status = 'rejected' WHERE id = ?");
    $stmt->execute([$match_id]);
    
    echo json_encode(['success' => true, 'message' => 'Request cancelled']);
    exit();
}
?>