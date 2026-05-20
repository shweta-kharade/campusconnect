<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doubt_id = isset($_POST['doubt_id']) ? (int)$_POST['doubt_id'] : 0;
    $doubt_owner_id = isset($_POST['doubt_owner_id']) ? (int)$_POST['doubt_owner_id'] : 0;
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];
    
    if (!$doubt_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid doubt ID']);
        exit();
    }
    
    // Check permission: doubt owner OR teacher
    $can_delete = ($doubt_owner_id == $user_id) || ($user_role == 'teacher');
    
    if (!$can_delete) {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this doubt']);
        exit();
    }
    
    // First, delete all answer likes (foreign key constraints)
    $stmt = $pdo->prepare("
        DELETE FROM answer_likes 
        WHERE answer_id IN (SELECT id FROM doubt_answers WHERE doubt_id = ?)
    ");
    $stmt->execute([$doubt_id]);
    
    // Delete all answers
    $stmt = $pdo->prepare("DELETE FROM doubt_answers WHERE doubt_id = ?");
    $stmt->execute([$doubt_id]);
    
    // Delete the doubt
    $stmt = $pdo->prepare("DELETE FROM doubts WHERE id = ?");
    $stmt->execute([$doubt_id]);
    
    echo json_encode(['success' => true, 'message' => 'Doubt deleted successfully']);
    exit();
}
?>