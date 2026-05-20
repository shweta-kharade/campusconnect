<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
    $request_owner_id = isset($_POST['request_owner_id']) ? (int)$_POST['request_owner_id'] : 0;
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];
    
    if (!$request_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
        exit();
    }
    
    // Check permission: request owner OR teacher
    $can_delete = ($request_owner_id == $user_id) || ($user_role == 'teacher');
    
    if (!$can_delete) {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this request']);
        exit();
    }
    
    // First, delete all matches related to this request
    $stmt = $pdo->prepare("DELETE FROM study_matches WHERE request_id = ?");
    $stmt->execute([$request_id]);
    
    // Delete the study request
    $stmt = $pdo->prepare("DELETE FROM study_requests WHERE id = ?");
    $stmt->execute([$request_id]);
    
    echo json_encode(['success' => true, 'message' => 'Study request deleted successfully']);
    exit();
}
?>