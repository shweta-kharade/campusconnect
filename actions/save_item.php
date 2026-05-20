<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Log the request for debugging
error_log("Save item request: " . print_r($_POST, true));

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
    $item_type = isset($_POST['item_type']) ? $_POST['item_type'] : 'post';
    $user_id = $_SESSION['user_id'];
    
    if (!$item_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
        exit();
    }
    
    // Validate item_type
    if (!in_array($item_type, ['post', 'note', 'event'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid item type']);
        exit();
    }
    
    try {
        // Check if already saved
        $stmt = $pdo->prepare("SELECT id FROM saved_items WHERE user_id = ? AND item_type = ? AND item_id = ?");
        $stmt->execute([$user_id, $item_type, $item_id]);
        $saved = $stmt->fetch();
        
        if ($saved) {
            // Remove from saved
            $stmt = $pdo->prepare("DELETE FROM saved_items WHERE user_id = ? AND item_type = ? AND item_id = ?");
            $stmt->execute([$user_id, $item_type, $item_id]);
            
            echo json_encode(['success' => true, 'saved' => false, 'message' => 'Removed from bookmarks']);
        } else {
            // Add to saved
            $stmt = $pdo->prepare("INSERT INTO saved_items (user_id, item_type, item_id) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $item_type, $item_id]);
            
            echo json_encode(['success' => true, 'saved' => true, 'message' => 'Saved to bookmarks']);
        }
    } catch (PDOException $e) {
        error_log("Save item error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>