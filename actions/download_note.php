<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: ../pages/notes.php');
    exit();
}

$note_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Get note info
$stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ?");
$stmt->execute([$note_id]);
$note = $stmt->fetch();

if (!$note) {
    $_SESSION['error'] = "Note not found.";
    header('Location: ../pages/notes.php');
    exit();
}

// Build file path - try multiple possible locations
$project_root = dirname(__DIR__);
$possible_paths = [
    // Path as stored in database
    $project_root . '/' . $note['file_path'],
    // Just the filename in uploads folder
    $project_root . '/assets/uploads/notes/' . basename($note['file_path']),
    // Direct absolute path
    'C:/xampp/htdocs/campusconnect/' . $note['file_path'],
    'C:/xampp/htdocs/campusconnect/assets/uploads/notes/' . basename($note['file_path'])
];

$file_path = null;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        $file_path = $path;
        break;
    }
}

if (!$file_path) {
    $_SESSION['error'] = "File not found on server. Please contact support.";
    error_log("File not found for note ID $note_id. Tried: " . implode(', ', $possible_paths));
    header('Location: ../pages/notes.php');
    exit();
}

// Increment download count
$stmt = $pdo->prepare("UPDATE notes SET downloads_count = downloads_count + 1 WHERE id = ?");
$stmt->execute([$note_id]);

// Give points to uploader (5 points per download)
$stmt = $pdo->prepare("UPDATE users SET total_points = total_points + 5 WHERE id = ?");
$stmt->execute([$note['user_id']]);

// Clear any output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Get file info
$file_name = basename($file_path);
$file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// Set appropriate content type
$content_types = [
    'pdf' => 'application/pdf',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'doc' => 'application/msword',
    'txt' => 'text/plain',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'zip' => 'application/zip'
];

$content_type = $content_types[$file_extension] ?? 'application/octet-stream';

// Send headers
header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="' . $file_name . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Output file
readfile($file_path);
exit();
?>