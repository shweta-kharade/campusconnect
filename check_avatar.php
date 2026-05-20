<?php
session_start();
require_once 'config/database.php';

$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $pdo->prepare("SELECT id, name, avatar FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Check Avatar</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { background: #f0f0f0; padding: 10px; margin: 10px 0; }
        img { max-width: 150px; border-radius: 50%; margin: 10px 0; }
    </style>
</head>
<body>
    <h2>Avatar Debug Info</h2>
    
    <div class='info'>
        <strong>User:</strong> {$user['name']} (ID: {$user['id']})<br>
        <strong>Database avatar path:</strong> <code>{$user['avatar']}</code>
    </div>";

// Try different paths
$paths_to_try = [
    'Original DB path' => $user['avatar'],
    'With leading slash' => '/' . ltrim($user['avatar'], '/'),
    'Full URL' => '/campusconnect/' . ltrim($user['avatar'], '/'),
    'Absolute path' => $_SERVER['DOCUMENT_ROOT'] . '/campusconnect/' . ltrim($user['avatar'], '/')
];

echo "<h3>Testing Paths:</h3>";
foreach ($paths_to_try as $label => $path) {
    echo "<div>";
    echo "<strong>$label:</strong> <code>$path</code><br>";
    
    if (strpos($path, '/campusconnect/') !== false) {
        $file_path = $_SERVER['DOCUMENT_ROOT'] . str_replace('/campusconnect', '', $path);
        if (file_exists($file_path)) {
            echo "<span class='success'>✓ File exists at: $file_path</span><br>";
            echo "<img src='$path' alt='Avatar'>";
        } else {
            echo "<span class='error'>✗ File not found at: $file_path</span>";
        }
    } elseif (file_exists($path)) {
        echo "<span class='success'>✓ File exists</span><br>";
        echo "<img src='$path' alt='Avatar'>";
    } else {
        echo "<span class='error'>✗ File not found</span>";
    }
    echo "</div><br>";
}

echo "<h3>UI Avatars Fallback:</h3>";
$ui_avatar = "https://ui-avatars.com/api/?name=" . urlencode($user['name']) . "&background=0A66C2&color=fff&size=150&bold=true";
echo "<img src='$ui_avatar'><br>";
echo "<code>$ui_avatar</code>";

echo "
</body>
</html>";
?>