<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note_id = isset($_POST['note_id']) ? (int)$_POST['note_id'] : 0;
    $note_owner_id = isset($_POST['note_owner_id']) ? (int)$_POST['note_owner_id'] : 0;
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];
    
    if (!$note_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid note ID']);
        exit();
    }
    
    // Check permission: note owner OR teacher
    $can_delete = ($note_owner_id == $user_id) || ($user_role == 'teacher');
    
    if (!$can_delete) {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this note']);
        exit();
    }
    
    // Get file path to delete the actual file
    $stmt = $pdo->prepare("SELECT file_path FROM notes WHERE id = ?");
    $stmt->execute([$note_id]);
    $note = $stmt->fetch();
    
    if ($note) {
        // Delete the physical file
        $file_path = dirname(__DIR__) . '/' . $note['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ?");
        $stmt->execute([$note_id]);
        
        // Remove points from user (optional - subtract points for deleting)
        // addPoints($note_owner_id, -20, $pdo);
        
        echo json_encode(['success' => true, 'message' => 'Note deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Note not found']);
    }
    exit();
}
?>