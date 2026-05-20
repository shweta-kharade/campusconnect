<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    die('Please login first');
}

$stmt = $pdo->prepare("SELECT * FROM notes ORDER BY id DESC");
$stmt->execute();
$notes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Check Notes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Notes Status Check</h2>
        <div class="alert alert-info">
            Total notes: <?php echo count($notes); ?>
        </div>
        
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>File Path (DB)</th>
                    <th>File Exists?</th>
                    <th>Size</th>
                    <th>Download Test</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($notes as $note): 
                    $project_root = __DIR__;
                    $possible_paths = [
                        $project_root . '/' . $note['file_path'],
                        $project_root . '/assets/uploads/notes/' . basename($note['file_path']),
                        'C:/xampp/htdocs/campusconnect/' . $note['file_path'],
                        'C:/xampp/htdocs/campusconnect/assets/uploads/notes/' . basename($note['file_path'])
                    ];
                    
                    $found_path = null;
                    foreach ($possible_paths as $path) {
                        if (file_exists($path)) {
                            $found_path = $path;
                            break;
                        }
                    }
                    ?>
                    <tr class="<?php echo $found_path ? 'table-success' : 'table-danger'; ?>">
                        <td><?php echo $note['id']; ?></td>
                        <td><?php echo htmlspecialchars($note['title']); ?></td>
                        <td><small><?php echo $note['file_path']; ?></small></td>
                        <td>
                            <?php if($found_path): ?>
                                ✓ Found<br>
                                <small><?php echo basename($found_path); ?></small>
                            <?php else: ?>
                                ✗ Not Found<br>
                                <small>Tried: assets/uploads/notes/<?php echo basename($note['file_path']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($found_path): ?>
                                <?php echo round(filesize($found_path)/1024, 2); ?> KB
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($found_path): ?>
                                <a href="actions/download_note.php?id=<?php echo $note['id']; ?>" class="btn btn-success btn-sm">Download</a>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm" disabled>Fix Path</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="alert alert-warning">
            <strong>Files in uploads folder:</strong>
            <?php
            $upload_dir = __DIR__ . '/assets/uploads/notes/';
            if (is_dir($upload_dir)) {
                $files = scandir($upload_dir);
                echo "<ul>";
                foreach ($files as $file) {
                    if ($file != '.' && $file != '..') {
                        echo "<li>$file</li>";
                    }
                }
                echo "</ul>";
            } else {
                echo "Upload folder not found!";
            }
            ?>
        </div>
    </div>
</body>
</html>