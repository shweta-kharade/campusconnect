<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$page_title = 'Notes Hub';
$current_user = currentUser($pdo);

// Get filter parameters
$subject = isset($_GET['subject']) ? $_GET['subject'] : '';
$semester = isset($_GET['semester']) ? $_GET['semester'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$sql = "SELECT n.*, u.name, u.avatar, u.role 
        FROM notes n 
        JOIN users u ON n.user_id = u.id 
        WHERE n.is_approved = 1";
$params = [];

if (!empty($notes)) {
    foreach ($notes as $note) {
        if (!$note['is_approved']) {
            echo "<div class='alert alert-warning small'>Note '{$note['title']}' is pending approval</div>";
        }
    }
}

if (!empty($subject)) {
    $sql .= " AND n.subject = ?";
    $params[] = $subject;
}
if (!empty($semester)) {
    $sql .= " AND n.semester = ?";
    $params[] = $semester;
}
if (!empty($search)) {
    $sql .= " AND (n.title LIKE ? OR n.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY n.downloads_count DESC, n.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notes = $stmt->fetchAll();

// Get distinct subjects for filter
$stmt = $pdo->query("SELECT DISTINCT subject FROM notes WHERE subject IS NOT NULL ORDER BY subject");
$subjects = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/navbar.php';
?>

<style>
    /* Notes Hub Specific Styles */
    :root {
        --primary: #4361ee;
        --secondary: #7209b7;
        --success: #06d6a0;
        --warning: #ffd166;
        --danger: #ef476f;
        --dark: #1a1a2e;
    }

    .notes-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        color: white;
    }

    .filter-card {
        background: white;
        border-radius: 16px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .note-card {
        background: white;
        border-radius: 16px;
        padding: 1.25rem;
        margin-bottom: 1.25rem;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .note-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
    }

    .note-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(114, 9, 183, 0.1));
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
    }

    .download-btn {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.5rem 1.25rem;
        transition: all 0.3s;
    }

    .download-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        color: white;
    }

    .upload-area {
        border: 2px dashed #dee2e6;
        border-radius: 16px;
        padding: 2rem;
        text-align: center;
        transition: all 0.3s;
        cursor: pointer;
    }

    .upload-area:hover {
        border-color: var(--primary);
        background: rgba(67, 97, 238, 0.05);
    }

    .subject-badge {
        background: rgba(67, 97, 238, 0.1);
        color: var(--primary);
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .semester-badge {
        background: rgba(6, 214, 160, 0.1);
        color: var(--success);
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .stats-badge {
        background: rgba(0, 0, 0, 0.05);
        padding: 0.25rem 0.5rem;
        border-radius: 8px;
        font-size: 0.75rem;
    }

    .filter-btn {
        border-radius: 50px;
        padding: 0.5rem 1rem;
        transition: all 0.2s;
    }

    .filter-btn.active {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .note-card {
        animation: fadeInUp 0.4s ease;
    }

    .btn-outline-danger {
        background: transparent;
        border: 1px solid #dc3545;
        color: #dc3545;
        transition: all 0.2s;
    }

    .btn-outline-danger:hover {
        background: #dc3545;
        color: white;
        transform: translateY(-2px);
    }
</style>

<div class="container py-4">
    <!-- Header Section -->
    <div class="notes-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-2">
                    <i class="bi bi-journal-bookmark-fill"></i> Smart Notes Hub
                </h1>
                <p class="mb-0 opacity-75">
                    Access study materials, past papers, and handwritten notes shared by your campus community
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <button class="btn btn-light px-4" data-bs-toggle="modal" data-bs-target="#uploadNoteModal" style="border-radius: 50px;">
                    <i class="bi bi-cloud-upload"></i> Upload Note
                </button>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Search & Filter Bar -->
            <div class="filter-card">
                <form method="GET" action="" id="filterForm">
                    <div class="row g-2">
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" name="search" class="form-control border-start-0"
                                    placeholder="Search notes..." value="<?php echo htmlspecialchars($search); ?>"
                                    style="border-left: none;">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="subject" class="form-select" onchange="this.form.submit()">
                                <option value="">All Subjects</option>
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?php echo $s['subject']; ?>" <?php echo $subject == $s['subject'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($s['subject']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="semester" class="form-select" onchange="this.form.submit()">
                                <option value="">All Semesters</option>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $semester == $i ? 'selected' : ''; ?>>
                                        Semester <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <a href="notes.php" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-arrow-repeat"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Notes List -->
            <?php if (empty($notes)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-journal-x" style="font-size: 4rem; color: #ccc;"></i>
                    <h5 class="mt-3">No notes found</h5>
                    <p class="text-muted">Be the first to upload study materials!</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadNoteModal">
                        Upload a Note
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($notes as $note): ?>
                    <div class="note-card" id="note-<?php echo $note['id']; ?>">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="note-icon">
                                    <?php
                                    $ext = pathinfo($note['file_path'], PATHINFO_EXTENSION);
                                    $icon = 'bi-file-pdf-fill';
                                    $color = '#ef476f';
                                    if ($ext == 'docx' || $ext == 'doc') {
                                        $icon = 'bi-file-word-fill';
                                        $color = '#4361ee';
                                    } elseif ($ext == 'jpg' || $ext == 'png' || $ext == 'jpeg') {
                                        $icon = 'bi-file-image-fill';
                                        $color = '#06d6a0';
                                    } elseif ($ext == 'txt') {
                                        $icon = 'bi-file-text-fill';
                                        $color = '#ffd166';
                                    }
                                    ?>
                                    <i class="bi <?php echo $icon; ?>" style="font-size: 2rem; color: <?php echo $color; ?>"></i>
                                </div>
                            </div>
                            <div class="col">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="mb-1"><?php echo htmlspecialchars($note['title']); ?></h5>
                                        <p class="text-muted small mb-2">
                                            <?php echo htmlspecialchars(substr($note['description'] ?? '', 0, 100)); ?>
                                        </p>
                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            <span class="subject-badge">
                                                <i class="bi bi-book"></i> <?php echo htmlspecialchars($note['subject'] ?? 'General'); ?>
                                            </span>
                                            <span class="semester-badge">
                                                <i class="bi bi-calendar"></i> Semester <?php echo $note['semester']; ?>
                                            </span>
                                            <span class="stats-badge">
                                                <i class="bi bi-download"></i> <?php echo $note['downloads_count']; ?> downloads
                                            </span>
                                            <span class="stats-badge">
                                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($note['name']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <a href="../actions/download_note.php?id=<?php echo $note['id']; ?>"
                                    class="btn download-btn">
                                    <i class="bi bi-download"></i> Download
                                </a>

                                <!-- Delete Button - Only show for note owner or teacher -->
                                <?php if ($_SESSION['user_id'] == $note['user_id'] || $_SESSION['user_role'] == 'teacher'): ?>
                                    <button class="btn btn-danger"
                                        onclick="deleteNote(<?php echo $note['id']; ?>, <?php echo $note['user_id']; ?>)"
                                        style="border-radius: 50px; padding: 0.5rem 1.25rem;"
                                        title="Delete note">
                                        <i class="bi bi-trash3"></i> Delete
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Upload Stats -->
            <div class="filter-card text-center">
                <i class="bi bi-cloud-arrow-up" style="font-size: 2.5rem; color: var(--primary);"></i>
                <h6 class="mt-2">Share Your Knowledge</h6>
                <p class="small text-muted">Upload notes and earn points when others download</p>
                <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#uploadNoteModal">
                    <i class="bi bi-plus-circle"></i> Upload New Note
                </button>
            </div>

            <!-- Top Contributors -->
            <div class="filter-card">
                <h6 class="mb-3">
                    <i class="bi bi-trophy-fill text-warning"></i> Top Contributors
                </h6>
                <?php
                $stmt = $pdo->query("
                    SELECT u.name, COUNT(n.id) as note_count, SUM(n.downloads_count) as total_downloads
                    FROM users u
                    JOIN notes n ON u.id = n.user_id
                    WHERE n.is_approved = 1
                    GROUP BY u.id
                    ORDER BY total_downloads DESC
                    LIMIT 5
                ");
                $top_contributors = $stmt->fetchAll();
                ?>
                <?php foreach ($top_contributors as $index => $contributor): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <div>
                            <span class="fw-bold me-2">#<?php echo $index + 1; ?></span>
                            <?php echo htmlspecialchars($contributor['name']); ?>
                        </div>
                        <div>
                            <span class="badge bg-primary">
                                <?php echo $contributor['total_downloads']; ?> downloads
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Popular Subjects -->
            <div class="filter-card">
                <h6 class="mb-3">
                    <i class="bi bi-tags"></i> Popular Subjects
                </h6>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($subjects as $s): ?>
                        <a href="?subject=<?php echo urlencode($s['subject']); ?>" class="trending-tag" style="text-decoration: none;">
                            <?php echo htmlspecialchars($s['subject']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Study Tips -->
            <div class="filter-card" style="background: linear-gradient(135deg, #1a1a2e, #16213e); color: white;">
                <i class="bi bi-lightbulb-fill" style="font-size: 1.5rem; color: #ffd166;"></i>
                <h6 class="mt-2">Study Tips</h6>
                <p class="small mb-2">✓ Review notes within 24 hours of lecture</p>
                <p class="small mb-2">✓ Create summaries from multiple sources</p>
                <p class="small mb-0">✓ Practice with past year papers</p>
            </div>
        </div>
    </div>
</div>

<!-- Upload Note Modal -->
<!-- Replace the upload modal in notes.php with this improved version -->
<div class="modal fade" id="uploadNoteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px;">
            <form method="POST" action="../actions/upload_note.php" enctype="multipart/form-data">
                <div class="modal-header border-0">
                    <h5 class="modal-title">
                        <i class="bi bi-cloud-upload"></i> Upload Study Material
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-control" required style="border-radius: 12px;" placeholder="e.g., Complete PHP Notes">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="3" class="form-control" style="border-radius: 12px;" placeholder="Brief description of the notes..."></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" class="form-control" placeholder="e.g., Web Development" style="border-radius: 12px;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Semester</label>
                            <select name="semester" class="form-select" style="border-radius: 12px;">
                                <option value="">Select Semester</option>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?php echo $i; ?>">Semester <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File * (PDF, DOCX, JPG, PNG - Max 10MB)</label>
                        <input type="file" name="note_file" class="form-control" required accept=".pdf,.docx,.doc,.txt,.jpg,.jpeg,.png" style="border-radius: 12px;">
                        <small class="text-muted">Supported formats: PDF, DOCX, DOC, TXT, JPG, PNG</small>
                    </div>
                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle"></i> You earn 20 points for uploading notes! Earn 5 more points each time someone downloads your note.
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 50px;">Cancel</button>
                    <button type="submit" class="btn" style="background: linear-gradient(135deg, #4361ee, #7209b7); color: white; border-radius: 50px;">
                        <i class="bi bi-cloud-upload"></i> Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Delete note function
    function deleteNote(noteId, noteOwnerId) {
        if (confirm('Are you sure you want to delete this note? The file will be permanently deleted. This action cannot be undone!')) {
            $.ajax({
                url: '../actions/delete_note.php',
                method: 'POST',
                data: {
                    note_id: noteId,
                    note_owner_id: noteOwnerId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Remove the note from DOM with animation
                        $(`#note-${noteId}`).fadeOut(500, function() {
                            $(this).remove();
                            showToast('Note deleted successfully', 'success');

                            // Check if no notes left
                            if ($('.note-card').length === 0) {
                                location.reload();
                            }
                        });
                    } else {
                        showToast(response.message || 'Error deleting note', 'error');
                    }
                },
                error: function() {
                    showToast('Error deleting note', 'error');
                }
            });
        }
    }

    // Toast notification function
    function showToast(message, type) {
        let bgColor = type === 'success' ? '#28a745' : '#dc3545';
        let toast = $(`
        <div style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;
                    background: ${bgColor}; color: white; padding: 12px 24px;
                    border-radius: 8px; animation: fadeIn 0.3s; z-index: 10000;">
            ${message}
        </div>
    `);
        $('body').append(toast);
        setTimeout(() => toast.fadeOut(() => toast.remove()), 3000);
    }
</script>
<?php include '../includes/footer.php'; ?>