<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$page_title = 'Doubt Forum';
$current_user = currentUser($pdo);

// Handle filters
$subject = isset($_GET['subject']) ? $_GET['subject'] : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'recent'; // recent, unresolved, popular

// Build query
$sql = "SELECT d.*, u.name, u.avatar, u.role,
        (SELECT COUNT(*) FROM doubt_answers WHERE doubt_id = d.id) as answer_count
        FROM doubts d
        JOIN users u ON d.user_id = u.id
        WHERE 1=1";
$params = [];

if (!empty($subject)) {
    $sql .= " AND d.subject = ?";
    $params[] = $subject;
}

if ($filter == 'unresolved') {
    $sql .= " AND d.is_resolved = 0";
} elseif ($filter == 'popular') {
    $sql .= " ORDER BY d.views_count DESC";
} else {
    $sql .= " ORDER BY d.created_at DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$doubts = $stmt->fetchAll();

// Get subjects for filter
$stmt = $pdo->query("SELECT DISTINCT subject FROM doubts WHERE subject IS NOT NULL");
$subjects = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/navbar.php';
?>

<style>
    :root {
        --primary: #4361ee;
        --secondary: #7209b7;
        --success: #06d6a0;
        --warning: #ffd166;
        --danger: #ef476f;
        --dark: #1a1a2e;
    }

    .doubt-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        color: white;
    }

    .doubt-card {
        background: white;
        border-radius: 16px;
        padding: 1.25rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .doubt-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .doubt-card.resolved {
        background: linear-gradient(135deg, rgba(6, 214, 160, 0.05), rgba(6, 214, 160, 0.02));
        border-left: 4px solid var(--success);
    }

    .subject-badge {
        background: rgba(67, 97, 238, 0.1);
        color: var(--primary);
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 500;
    }

    .answer-badge {
        background: rgba(6, 214, 160, 0.1);
        color: var(--success);
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.7rem;
    }

    .filter-btn {
        border-radius: 50px;
        padding: 0.5rem 1rem;
        transition: all 0.2s;
        text-decoration: none;
    }

    .filter-btn.active {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
    }

    .ask-btn {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.5rem 1.5rem;
        transition: all 0.3s;
    }

    .ask-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
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

    .doubt-card {
        animation: fadeInUp 0.4s ease;
    }

    .stats-icon {
        color: var(--primary);
        margin-right: 0.25rem;
    }
</style>

<div class="container py-4">
    <!-- Header -->
    <div class="doubt-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-2">
                    <i class="bi bi-chat-dots-fill"></i> Doubt Forum
                </h1>
                <p class="mb-0 opacity-75">
                    Ask questions, get answers, and help others learn
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <button class="ask-btn" data-bs-toggle="modal" data-bs-target="#askDoubtModal">
                    <i class="bi bi-plus-circle"></i> Ask a Question
                </button>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Filters -->
            <div class="d-flex flex-wrap gap-2 mb-4">
                <a href="?filter=recent" class="filter-btn <?php echo $filter == 'recent' ? 'active' : 'btn-outline-secondary'; ?>">
                    <i class="bi bi-clock"></i> Recent
                </a>
                <a href="?filter=unresolved" class="filter-btn <?php echo $filter == 'unresolved' ? 'active' : 'btn-outline-secondary'; ?>">
                    <i class="bi bi-question-octagon"></i> Unresolved
                </a>
                <a href="?filter=popular" class="filter-btn <?php echo $filter == 'popular' ? 'active' : 'btn-outline-secondary'; ?>">
                    <i class="bi bi-fire"></i> Most Viewed
                </a>

                <select class="form-select w-auto ms-auto" onchange="window.location.href='?subject='+this.value+'&filter=<?php echo $filter; ?>'">
                    <option value="">All Subjects</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?php echo $s['subject']; ?>" <?php echo $subject == $s['subject'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['subject']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Doubts List -->
            <?php if (empty($doubts)): ?>
                <div class="text-center py-5 bg-white rounded-4">
                    <i class="bi bi-chat-dots" style="font-size: 4rem; color: #ccc;"></i>
                    <h5 class="mt-3">No questions yet</h5>
                    <p class="text-muted">Be the first to ask a question!</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#askDoubtModal">
                        Ask Your First Question
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($doubts as $doubt): ?>
                    <div class="doubt-card <?php echo $doubt['is_resolved'] ? 'resolved' : ''; ?>" id="doubt-<?php echo $doubt['id']; ?>">
                        <div class="d-flex gap-3">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($doubt['name']); ?>&background=4361ee&color=fff"
                                class="avatar-sm" style="width: 48px; height: 48px;">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="mb-1">
                                            <a href="doubt_detail.php?id=<?php echo $doubt['id']; ?>" class="text-decoration-none text-dark">
                                                <?php echo htmlspecialchars($doubt['title']); ?>
                                            </a>
                                        </h5>
                                        <div class="d-flex flex-wrap gap-2 mb-2">
                                            <span class="subject-badge">
                                                <i class="bi bi-book"></i> <?php echo htmlspecialchars($doubt['subject'] ?? 'General'); ?>
                                            </span>
                                            <span class="answer-badge">
                                                <i class="bi bi-chat"></i> <?php echo $doubt['answer_count']; ?> answers
                                            </span>
                                            <?php if ($doubt['is_resolved']): ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle"></i> Resolved
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- DELETE BUTTON - FIXED -->
                                    <?php if ($_SESSION['user_id'] == $doubt['user_id'] || $_SESSION['user_role'] == 'teacher'): ?>
                                        <button class="btn btn-sm btn-outline-danger rounded-pill"
                                            onclick="deleteDoubt(<?php echo $doubt['id']; ?>, <?php echo $doubt['user_id']; ?>)"
                                            style="padding: 0.25rem 0.75rem; font-size: 0.75rem;">
                                            <i class="bi bi-trash3"></i> Delete
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <p class="text-muted small mb-2">
                                    <?php echo htmlspecialchars(substr($doubt['description'], 0, 150)); ?>
                                    <?php if (strlen($doubt['description']) > 150) echo '...'; ?>
                                </p>

                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex gap-3">
                                        <small class="text-muted">
                                            <i class="bi bi-person"></i> <?php echo htmlspecialchars($doubt['name']); ?>
                                        </small>
                                        <small class="text-muted">
                                            <i class="bi bi-eye"></i> <?php echo $doubt['views_count']; ?> views
                                        </small>
                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i> <?php echo date('M d, H:i', strtotime($doubt['created_at'])); ?>
                                        </small>
                                    </div>
                                    <a href="doubt_detail.php?id=<?php echo $doubt['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                                        View Details <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Stats Widget -->
            <div class="card border-0 rounded-4 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="mb-3">📊 Forum Stats</h6>
                    <?php
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM doubts");
                    $total_doubts = $stmt->fetch();
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM doubt_answers");
                    $total_answers = $stmt->fetch();

                    // Check if is_resolved column exists
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) as total FROM doubts WHERE is_resolved = 1");
                        $resolved = $stmt->fetch();
                        $resolved_count = $resolved['total'];
                    } catch (PDOException $e) {
                        $resolved_count = 0;
                    }
                    ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Questions:</span>
                        <strong><?php echo $total_doubts['total']; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Answers:</span>
                        <strong><?php echo $total_answers['total']; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Resolution Rate:</span>
                        <strong><?php echo $total_doubts['total'] > 0 ? round(($resolved_count / $total_doubts['total']) * 100) : 0; ?>%</strong>
                    </div>
                </div>
            </div>

            <!-- Top Contributors -->
            <div class="card border-0 rounded-4 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="mb-3">🏆 Top Answerers</h6>
                    <?php
                    $stmt = $pdo->query("
                SELECT u.name, COUNT(a.id) as answer_count
                FROM doubt_answers a
                JOIN users u ON a.user_id = u.id
                GROUP BY u.id
                ORDER BY answer_count DESC
                LIMIT 5
            ");
                    $top_answerers = $stmt->fetchAll();
                    ?>
                    <?php if (empty($top_answerers)): ?>
                        <p class="text-muted small">No answers yet</p>
                    <?php else: ?>
                        <?php foreach ($top_answerers as $index => $answerer): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>
                                    <span class="fw-bold me-2">#<?php echo $index + 1; ?></span>
                                    <?php echo htmlspecialchars($answerer['name']); ?>
                                </span>
                                <span class="badge bg-primary"><?php echo $answerer['answer_count']; ?> answers</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tips -->
            <div class="card border-0 rounded-4 shadow-sm" style="background: linear-gradient(135deg, #1a1a2e, #16213e); color: white;">
                <div class="card-body">
                    <i class="bi bi-lightbulb-fill" style="font-size: 1.5rem; color: #ffd166;"></i>
                    <h6 class="mt-2">How to ask a good question?</h6>
                    <ul class="small mt-2 mb-0" style="padding-left: 1rem;">
                        <li>Be specific and clear</li>
                        <li>Show what you've tried</li>
                        <li>Add relevant code snippets</li>
                        <li>Choose the right subject tag</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ask Doubt Modal -->
<div class="modal fade" id="askDoubtModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 20px;">
            <form method="POST" action="../actions/ask_doubt.php">
                <div class="modal-header border-0">
                    <h5 class="modal-title">
                        <i class="bi bi-question-circle"></i> Ask a Question
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-control" required
                            placeholder="e.g., How to fix database connection error?" style="border-radius: 12px;">
                        <small class="text-muted">Be specific about your problem</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description *</label>
                        <textarea name="description" rows="6" class="form-control" required
                            placeholder="Explain your problem in detail... What have you tried? What error are you getting?" style="border-radius: 12px;"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" class="form-control"
                                placeholder="e.g., PHP, JavaScript, DSA" style="border-radius: 12px;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tags (comma separated)</label>
                            <input type="text" name="tags" class="form-control"
                                placeholder="e.g., laravel, database, error" style="border-radius: 12px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 50px;">Cancel</button>
                    <button type="submit" class="btn" style="background: linear-gradient(135deg, #4361ee, #7209b7); color: white; border-radius: 50px;">
                        <i class="bi bi-send"></i> Post Question
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Delete doubt function
    function deleteDoubt(doubtId, doubtOwnerId) {
        if (confirm('Are you sure you want to delete this doubt? All answers and likes will also be permanently deleted. This action cannot be undone!')) {
            $.ajax({
                url: '../actions/delete_doubt.php',
                method: 'POST',
                data: {
                    doubt_id: doubtId,
                    doubt_owner_id: doubtOwnerId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast('Doubt deleted successfully', 'success');

                        // If on doubt_detail page, redirect to doubts list
                        if (window.location.pathname.includes('doubt_detail.php')) {
                            setTimeout(function() {
                                window.location.href = 'doubts.php';
                            }, 1000);
                        } else {
                            // Remove from DOM on doubts listing page
                            $(`#doubt-${doubtId}`).fadeOut(500, function() {
                                $(this).remove();
                                if ($('.doubt-card').length === 0) {
                                    location.reload();
                                }
                            });
                        }
                    } else {
                        showToast(response.message || 'Error deleting doubt', 'error');
                    }
                },
                error: function() {
                    showToast('Error deleting doubt', 'error');
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