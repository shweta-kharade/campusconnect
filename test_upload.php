<?php
// test_upload.php - Place in C:\xampp\htdocs\campusconnect\
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test File Upload</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h4>Test File Upload</h4>
            </div>
            <div class="card-body">
                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    echo "<div class='alert alert-info'>";
                    echo "<strong>Debug Info:</strong><br>";
                    echo "Upload directory: " . __DIR__ . "/assets/uploads/notes/<br>";
                    
                    if (isset($_FILES['test_file']) && $_FILES['test_file']['error'] === 0) {
                        $upload_dir = __DIR__ . '/assets/uploads/notes/';
                        
                        // Create directory if not exists
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                            echo "Created upload directory<br>";
                        }
                        
                        $filename = time() . '_' . $_FILES['test_file']['name'];
                        $destination = $upload_dir . $filename;
                        
                        if (move_uploaded_file($_FILES['test_file']['tmp_name'], $destination)) {
                            echo "<strong class='text-success'>✓ File uploaded successfully!</strong><br>";
                            echo "Saved to: " . $destination . "<br>";
                            echo "File size: " . round($_FILES['test_file']['size']/1024, 2) . " KB<br>";
                        } else {
                            echo "<strong class='text-danger'>✗ Failed to move file</strong><br>";
                            echo "Check folder permissions.<br>";
                        }
                    } else {
                        echo "<strong class='text-danger'>✗ No file uploaded or upload error</strong><br>";
                    }
                    echo "</div>";
                }
                ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Select a file to upload</label>
                        <input type="file" name="test_file" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Test Upload</button>
                </form>
                
                <hr>
                
                <h5>Upload Directory Status:</h5>
                <?php
                $upload_dir = __DIR__ . '/assets/uploads/notes/';
                if (file_exists($upload_dir)) {
                    echo "<p class='text-success'>✓ Upload directory exists: $upload_dir</p>";
                    echo "<p>Permissions: " . substr(sprintf('%o', fileperms($upload_dir)), -4) . "</p>";
                    
                    // List files
                    $files = scandir($upload_dir);
                    if (count($files) > 2) {
                        echo "<h6>Files in directory:</h6>";
                        echo "<ul>";
                        foreach ($files as $file) {
                            if ($file != '.' && $file != '..') {
                                echo "<li>$file</li>";
                            }
                        }
                        echo "</ul>";
                    }
                } else {
                    echo "<p class='text-danger'>✗ Upload directory does not exist</p>";
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>