<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$doubt_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get doubt details
$stmt = $pdo->prepare("
    SELECT d.*, u.name, u.avatar, u.role 
    FROM doubts d
    JOIN users u ON d.user_id = u.id
    WHERE d.id = ?
");
$stmt->execute([$doubt_id]);
$doubt = $stmt->fetch();

if (!$doubt) {
    header('Location: doubts.php');
    exit();
}

// Update view count
$stmt = $pdo->prepare("UPDATE doubts SET views_count = views_count + 1 WHERE id = ?");
$stmt->execute([$doubt_id]);

// Get answers
$stmt = $pdo->prepare("
    SELECT a.*, u.name, u.avatar, u.role,
        (SELECT COUNT(*) FROM answer_likes WHERE answer_id = a.id) as like_count
    FROM doubt_answers a
    JOIN users u ON a.user_id = u.id
    WHERE a.doubt_id = ?
    ORDER BY a.is_best DESC, a.likes_count DESC, a.created_at ASC
");
$stmt->execute([$doubt_id]);
$answers = $stmt->fetchAll();

$page_title = $doubt['title'];

include '../includes/header.php';
include '../includes/navbar.php';
?>

<style>
    .answer-card {
        background: white;
        border-radius: 16px;
        padding: 1.25rem;
        margin-bottom: 1rem;
        transition: all 0.2s;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .answer-card.best-answer {
        background: linear-gradient(135deg, rgba(6, 214, 160, 0.05), rgba(6, 214, 160, 0.02));
        border-left: 4px solid #06d6a0;
    }

    .like-btn {
        background: none;
        border: none;
        color: #6c757d;
        transition: all 0.2s;
    }

    .like-btn:hover {
        color: #dc3545;
    }

    .like-btn.liked {
        color: #dc3545;
    }

    .best-answer-badge {
        background: #06d6a0;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.7rem;
    }
</style>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Back button -->
            <a href="doubts.php" class="btn btn-outline-secondary mb-3 rounded-pill">
                <i class="bi bi-arrow-left"></i> Back to Forum
            </a>

            <!-- Question Card -->
            <div class="card border-0 rounded-4 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex gap-3 mb-3">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($doubt['name']); ?>&background=4361ee&color=fff"
                            class="avatar-sm" style="width: 48px; height: 48px;">
                        <div>
                            <h5 class="mb-1"><?php echo htmlspecialchars($doubt['title']); ?></h5>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($doubt['name']); ?>
                                </small>
                                <small class="text-muted">
                                    <i class="bi bi-clock"></i> <?php echo date('F d, Y', strtotime($doubt['created_at'])); ?>
                                </small>
                                <small class="text-muted">
                                    <i class="bi bi-eye"></i> <?php echo $doubt['views_count']; ?> views
                                </small>
                                <?php if ($doubt['subject']): ?>
                                    <span class="subject-badge">
                                        <i class="bi bi-book"></i> <?php echo htmlspecialchars($doubt['subject']); ?>
                                    </span>
                                <?php endif; ?>
                                <!-- Delete Button -->
                                <?php if ($_SESSION['user_id'] == $doubt['user_id'] || $_SESSION['user_role'] == 'teacher'): ?>
                                    <button class="btn btn-outline-danger rounded-pill"
                                        onclick="deleteDoubt(<?php echo $doubt['id']; ?>, <?php echo $doubt['user_id']; ?>)">
                                        <i class="bi bi-trash3"></i> Delete Question
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <p style="white-space: pre-wrap; line-height: 1.6;">
                            <?php echo nl2br(htmlspecialchars($doubt['description'])); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Answers Section -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>
                    <i class="bi bi-chat-dots"></i>
                    <?php echo count($answers); ?> Answers
                </h5>
                <button class="btn btn-primary rounded-pill" data-bs-toggle="collapse" data-bs-target="#answerForm">
                    <i class="bi bi-reply-fill"></i> Post Answer
                </button>
            </div>

            <!-- Answer Form -->
            <div class="collapse mb-4" id="answerForm">
                <div class="card border-0 rounded-4 shadow-sm">
                    <div class="card-body">
                        <form method="POST" action="../actions/post_answer.php">
                            <input type="hidden" name="doubt_id" value="<?php echo $doubt_id; ?>">
                            <div class="mb-3">
                                <label class="form-label">Your Answer</label>
                                <textarea name="answer" rows="5" class="form-control" required
                                    placeholder="Write your answer here..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary rounded-pill">
                                <i class="bi bi-send"></i> Post Answer
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- List of Answers -->
            <?php foreach ($answers as $answer): ?>
                <div class="answer-card <?php echo $answer['is_best'] ? 'best-answer' : ''; ?>">
                    <div class="d-flex gap-3">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($answer['name']); ?>&background=06d6a0&color=fff"
                            class="avatar-sm" style="width: 40px; height: 40px;">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?php echo htmlspecialchars($answer['name']); ?></strong>
                                    <?php if ($answer['role'] == 'teacher'): ?>
                                        <span class="badge bg-primary ms-1">Teacher</span>
                                    <?php endif; ?>
                                    <div class="text-muted small">
                                        <?php echo date('M d, Y', strtotime($answer['created_at'])); ?>
                                    </div>
                                </div>
                                <?php if ($answer['is_best']): ?>
                                    <span class="best-answer-badge">
                                        <i class="bi bi-check-circle-fill"></i> Best Answer
                                    </span>
                                <?php endif; ?>

                                <!-- Delete Answer Button -->
                                <?php if ($_SESSION['user_id'] == $answer['user_id'] || $_SESSION['user_role'] == 'teacher' || $_SESSION['user_id'] == $doubt['user_id']): ?>
                                    <button class="btn btn-sm btn-link text-danger"
                                        onclick="deleteAnswer(<?php echo $answer['id']; ?>, <?php echo $doubt_id; ?>)"
                                        title="Delete answer">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                <?php endif; ?>
                            </div>

                            <div class="mt-2">
                                <p style="white-space: pre-wrap;"><?php echo nl2br(htmlspecialchars($answer['answer'])); ?></p>
                            </div>

                            <div class="mt-2">
                                <button class="like-btn <?php echo userLikedAnswer($answer['id'], $_SESSION['user_id'], $pdo) ? 'liked' : ''; ?>"
                                    data-answer-id="<?php echo $answer['id']; ?>">
                                    <i class="bi bi-heart<?php echo userLikedAnswer($answer['id'], $_SESSION['user_id'], $pdo) ? '-fill' : ''; ?>"></i>
                                    <span class="like-count"><?php echo $answer['like_count']; ?></span>
                                </button>

                                <?php if ($_SESSION['user_id'] == $doubt['user_id'] && !$doubt['is_resolved'] && !$answer['is_best']): ?>
                                    <button class="btn btn-sm btn-outline-success ms-2 mark-best" data-answer-id="<?php echo $answer['id']; ?>">
                                        <i class="bi bi-check-lg"></i> Mark as Best Answer
                                    </button>
                                <?php endif; ?>


                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($answers)): ?>
                <div class="text-center py-5 bg-white rounded-4">
                    <i class="bi bi-chat" style="font-size: 3rem; color: #ccc;"></i>
                    <p class="mt-2 text-muted">No answers yet. Be the first to help!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Like answer functionality
    $(document).on('click', '.like-btn', function() {
        let btn = $(this);
        let answerId = btn.data('answer-id');
        let icon = btn.find('i');
        let likeCount = btn.find('.like-count');

        $.ajax({
            url: '../actions/like_answer.php',
            method: 'POST',
            data: {
                answer_id: answerId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (response.liked) {
                        icon.removeClass('bi-heart').addClass('bi-heart-fill');
                        btn.addClass('liked');
                    } else {
                        icon.removeClass('bi-heart-fill').addClass('bi-heart');
                        btn.removeClass('liked');
                    }
                    likeCount.text(response.likes_count);
                }
            }
        });
    });

    // Mark as best answer
    $(document).on('click', '.mark-best', function() {
        let answerId = $(this).data('answer-id');

        if (confirm('Mark this as the best answer?')) {
            $.ajax({
                url: '../actions/mark_best_answer.php',
                method: 'POST',
                data: {
                    answer_id: answerId,
                    doubt_id: <?php echo $doubt_id; ?>
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.message);
                    }
                }
            });
        }
    });

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


    // Delete answer function
    function deleteAnswer(answerId, doubtId) {
        if (confirm('Are you sure you want to delete this answer?')) {
            $.ajax({
                url: '../actions/delete_answer.php',
                method: 'POST',
                data: {
                    answer_id: answerId,
                    doubt_id: doubtId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $(`#answer-${answerId}`).fadeOut(500, function() {
                            $(this).remove();
                            showToast('Answer deleted successfully', 'success');

                            // Update answer count
                            $('#answerCount').text(response.answers_count);
                        });
                    } else {
                        showToast(response.message || 'Error deleting answer', 'error');
                    }
                },
                error: function() {
                    showToast('Error deleting answer', 'error');
                }
            });
        }
    }
</script>

<?php include '../includes/footer.php';

function userLikedAnswer($answer_id, $user_id, $pdo)
{
    $stmt = $pdo->prepare("SELECT id FROM answer_likes WHERE answer_id = ? AND user_id = ?");
    $stmt->execute([$answer_id, $user_id]);
    return $stmt->fetch();
}

?>