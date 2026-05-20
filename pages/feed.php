<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$page_title = 'Feed';
$current_user = currentUser($pdo);

// Update streak
updateStreak($_SESSION['user_id'], $pdo);

// Get feed posts
$posts = getFeedPostsSafe($pdo, 10, 0);

// Get liked posts for current user
$stmt = $pdo->prepare("SELECT post_id FROM post_likes WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$liked_posts = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get saved posts
$stmt = $pdo->prepare("SELECT item_id FROM saved_items WHERE user_id = ? AND item_type = 'post'");
$stmt->execute([$_SESSION['user_id']]);
$saved_posts = $stmt->fetchAll(PDO::FETCH_COLUMN);

include '../includes/header.php';
include '../includes/navbar.php';
?>

<style>
    /* Modern Color Variables */
    :root {
        --primary: #4361ee;
        --primary-dark: #3a0ca3;
        --secondary: #7209b7;
        --success: #06d6a0;
        --danger: #ef476f;
        --warning: #ffd166;
        --dark: #1a1a2e;
        --light: #f8f9fa;
        --gray: #6c757d;
        --border: #e9ecef;
    }

    /* Body Gradient Background */
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
    }

    /* Modern Card Design */
    .modern-card {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(10px);
        border: none;
        border-radius: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }

    .modern-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.1);
    }

    /* Create Post Card */
    .create-post-card {
        background: white;
        border-radius: 20px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    /* Avatar Styles */
    .avatar-modern {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    /* Post Type Badges */
    .badge-modern {
        padding: 0.35rem 0.75rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .badge-achievement {
        background: linear-gradient(135deg, #ffd166, #ff9f1c);
        color: #fff;
    }

    .badge-opportunity {
        background: linear-gradient(135deg, #06d6a0, #118ab2);
        color: #fff;
    }

    .badge-doubt {
        background: linear-gradient(135deg, #4361ee, #7209b7);
        color: #fff;
    }

    .badge-announcement {
        background: linear-gradient(135deg, #ef476f, #d90429);
        color: #fff;
    }

    /* Sidebar Widgets */
    .widget-modern {
        background: white;
        border-radius: 20px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s;
    }

    .widget-modern:hover {
        transform: translateY(-2px);
    }

    .widget-title-modern {
        font-size: 1rem;
        font-weight: 700;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--primary);
        display: inline-block;
        color: var(--dark);
    }

    /* Profile Stats Widget */
    .stats-widget {
        background: linear-gradient(135deg, #4361ee, #3a0ca3);
        color: white;
        border-radius: 20px;
        padding: 1.5rem;
        text-align: center;
        margin-bottom: 1.5rem;
    }

    /* Action Buttons */
    .action-btn-modern {
        background: transparent;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        color: var(--gray);
        font-weight: 500;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .action-btn-modern:hover {
        background: rgba(67, 97, 238, 0.1);
        color: var(--primary);
    }

    .like-btn-modern.liked {
        color: var(--danger);
    }

    .like-btn-modern.liked i {
        animation: heartBeat 0.3s ease;
    }

    @keyframes heartBeat {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.2);
        }
    }

    /* Trending Tags */
    .trending-tag {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--dark);
        transition: all 0.2s;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }

    .trending-tag:hover {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        transform: translateY(-2px);
    }

    /* Event Item */
    .event-item {
        padding: 0.75rem;
        border-radius: 12px;
        transition: all 0.2s;
        cursor: pointer;
    }

    .event-item:hover {
        background: rgba(67, 97, 238, 0.05);
    }

    /* Top Contributor Item */
    .contributor-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid var(--border);
    }

    .contributor-item:last-child {
        border-bottom: none;
    }

    .contributor-rank {
        font-weight: 700;
        color: var(--primary);
    }

    /* Loading Animation */
    .loader-modern {
        display: inline-block;
        width: 40px;
        height: 40px;
        border: 3px solid rgba(67, 97, 238, 0.3);
        border-radius: 50%;
        border-top-color: var(--primary);
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* Post Content */
    .post-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--dark);
        margin: 0.5rem 0;
    }

    .post-content {
        color: #4a5568;
        line-height: 1.6;
        margin-bottom: 1rem;
    }

    /* Attachment Button */
    .attachment-btn {
        background: rgba(67, 97, 238, 0.1);
        color: var(--primary);
        border-radius: 50px;
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
        transition: all 0.2s;
    }

    .attachment-btn:hover {
        background: var(--primary);
        color: white;
    }

    .delete-comment-btn {
        opacity: 0.6;
        transition: opacity 0.2s;
    }

    .delete-comment-btn:hover {
        opacity: 1;
        background-color: rgba(220, 53, 69, 0.1);
        border-radius: 50%;
    }


    .delete-post-btn {
        opacity: 0.5;
        transition: all 0.2s;
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .delete-post-btn:hover {
        opacity: 1;
        background-color: rgba(220, 53, 69, 0.1);
        transform: scale(1.1);
    }

    .delete-post-btn i {
        font-size: 1rem;
    }
</style>

<div class="container-fluid py-4" style="max-width: 1400px;">
    <div class="row g-4">

        <!-- LEFT SIDEBAR -->
        <div class="col-lg-3 col-md-4">
            <!-- Profile Stats Widget -->
            <div class="stats-widget">
                <img src="<?php
                            if (!empty($_SESSION['user_avatar'])) {
                                echo '/campusconnect/' . ltrim($_SESSION['user_avatar'], '/');
                            } else {
                                echo 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['user_name']) . '&background=0A66C2&color=fff';
                            }
                            ?>" class="avatar-sm" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;">
                <h5 class="mb-1"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h5>
                <p class="small mb-3" style="opacity: 0.9;"><?php echo ucfirst($_SESSION['user_role']); ?></p>

                <div class="row text-center mt-3">
                    <div class="col-6">
                        <div class="border-end border-white-50">
                            <h4 class="mb-0"><?php echo $current_user['streak_days'] ?? 0; ?></h4>
                            <small>🔥 Day Streak</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div>
                            <h4 class="mb-0"><?php echo $current_user['total_points'] ?? 0; ?></h4>
                            <small>⭐ Points</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Links Widget -->
            <div class="widget-modern">
                <h6 class="widget-title-modern">Quick Navigation</h6>
                <div class="d-flex flex-column gap-2">
                    <a href="/campusconnect/pages/notes.php" class="text-decoration-none text-dark py-2 px-3 rounded" style="transition: all 0.2s;" onmouseover="this.style.backgroundColor='rgba(67,97,238,0.05)'" onmouseout="this.style.backgroundColor='transparent'">
                        <i class="bi bi-journal-bookmark-fill" style="color: var(--primary);"></i> My Notes
                    </a>
                    <a href="/campusconnect/pages/saved.php" class="text-decoration-none text-dark py-2 px-3 rounded" style="transition: all 0.2s;" onmouseover="this.style.backgroundColor='rgba(67,97,238,0.05)'" onmouseout="this.style.backgroundColor='transparent'">
                        <i class="bi bi-bookmark-fill" style="color: var(--primary);"></i> Saved Items
                    </a>
                    <a href="/campusconnect/pages/profile.php" class="text-decoration-none text-dark py-2 px-3 rounded" style="transition: all 0.2s;" onmouseover="this.style.backgroundColor='rgba(67,97,238,0.05)'" onmouseout="this.style.backgroundColor='transparent'">
                        <i class="bi bi-person-fill" style="color: var(--primary);"></i> My Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- MAIN FEED -->
        <div class="col-lg-6 col-md-8">
            <!-- Create Post Card -->
            <!-- Create Post Card -->
            <div class="create-post-card">
                <div class="d-flex gap-3 mb-3">
                    <img src="<?php
                                if (!empty($_SESSION['user_avatar'])) {
                                    // Clean the path and add /campusconnect/ prefix
                                    $avatar_path = ltrim($_SESSION['user_avatar'], '/');
                                    echo '/campusconnect/' . $avatar_path;
                                } else {
                                    // Fallback to UI Avatars
                                    echo 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['user_name']) . '&background=4361ee&color=fff&size=48';
                                }
                                ?>" class="avatar-modern" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;"
                        onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name']); ?>&background=4361ee&color=fff&size=48'">
                    <input type="text" class="form-control rounded-pill border-0 bg-light"
                        placeholder="Share something with your campus..." data-bs-toggle="modal"
                        data-bs-target="#createPostModal" style="cursor: pointer; padding: 0.75rem 1.25rem;">
                </div>
                <div class="d-flex justify-content-around pt-2 border-top">
                    <button class="btn btn-link text-decoration-none" onclick="showPostForm('achievement')" style="color: var(--gray);">
                        <i class="bi bi-trophy-fill" style="color: #ff9f1c;"></i> Achievement
                    </button>
                    <button class="btn btn-link text-decoration-none" onclick="showPostForm('opportunity')" style="color: var(--gray);">
                        <i class="bi bi-briefcase-fill" style="color: #06d6a0;"></i> Opportunity
                    </button>
                    <button class="btn btn-link text-decoration-none" onclick="showPostForm('doubt')" style="color: var(--gray);">
                        <i class="bi bi-question-circle-fill" style="color: var(--primary);"></i> Doubt
                    </button>
                    <?php if ($_SESSION['user_role'] == 'teacher'): ?>
                        <button class="btn btn-link text-decoration-none" onclick="showPostForm('announcement')" style="color: var(--gray);">
                            <i class="bi bi-megaphone-fill" style="color: var(--danger);"></i> Announce
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Feed Posts Container -->
            <div id="feed-container">
                <?php if (empty($posts)): ?>
                    <div class="modern-card text-center py-5" id="post-<?php echo $posts['id']; ?>">
                        <i class="bi bi-newspaper" style="font-size: 4rem; color: #ccc;"></i>
                        <h5 class="mt-3">No posts yet</h5>
                        <p class="text-muted">Be the first to share something!</p>
                        <button class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#createPostModal" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); border: none;">
                            Create Your First Post
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post):
                        $isLiked = in_array($post['id'], $liked_posts);
                        $isSaved = in_array($post['id'], $saved_posts);
                    ?>
                        <div class="modern-card fade-in">
                            <div class="card-body p-4">
                                <!-- Post Header -->
                                <div class="d-flex gap-3">
                                    <img src="<?php
                                                if (!empty($post['avatar'])) {
                                                    echo '/campusconnect/' . ltrim($post['avatar'], '/');
                                                } else {
                                                    echo 'https://ui-avatars.com/api/?name=' . urlencode($post['name']) . '&background=0A66C2&color=fff';
                                                }
                                                ?>" class="avatar-sm" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($post['name']); ?></h6>
                                                <small class="text-muted">
                                                    <i class="bi bi-clock"></i> <?php echo date('M d, H:i', strtotime($post['created_at'])); ?>
                                                </small>
                                            </div>
                                            <?php
                                            $badgeClass = '';
                                            $badgeIcon = '';
                                            switch ($post['type']) {
                                                case 'achievement':
                                                    $badgeClass = 'badge-achievement';
                                                    $badgeIcon = '🏆';
                                                    break;
                                                case 'opportunity':
                                                    $badgeClass = 'badge-opportunity';
                                                    $badgeIcon = '💼';
                                                    break;
                                                case 'doubt':
                                                    $badgeClass = 'badge-doubt';
                                                    $badgeIcon = '❓';
                                                    break;
                                                case 'announcement':
                                                    $badgeClass = 'badge-announcement';
                                                    $badgeIcon = '📢';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge-modern <?php echo $badgeClass; ?>">
                                                <?php echo $badgeIcon; ?> <?php echo ucfirst($post['type']); ?>
                                            </span>
                                        </div>

                                        <!-- Delete Button - Only show for post owner or teacher -->
                                        <?php if ($_SESSION['user_id'] == $post['user_id'] || $_SESSION['user_role'] == 'teacher'): ?>
                                            <button class="btn btn-sm btn-link text-danger delete-post-btn"
                                                onclick="deletePost(<?php echo $post['id']; ?>, <?php echo $post['user_id']; ?>)"
                                                title="Delete post">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        <?php endif; ?>

                                        <!-- Post Content -->
                                        <h6 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h6>
                                        <p class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>

                                        <!-- Attachment -->
                                        <?php if ($post['file_path']): ?>
                                            <div class="mt-2 mb-3">
                                                <a href="/campusconnect/<?php echo $post['file_path']; ?>" class="text-decoration-none attachment-btn" download>
                                                    <i class="bi bi-paperclip"></i> Download Attachment
                                                </a>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Post Actions -->
                                        <div class="post-actions mt-3 pt-2 d-flex gap-2">
                                            <button class="action-btn-modern like-btn-modern <?php echo $isLiked ? 'liked' : ''; ?>"
                                                data-post-id="<?php echo $post['id']; ?>">
                                                <i class="bi <?php echo $isLiked ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                                                <span class="like-count"><?php echo $post['likes_count']; ?></span>
                                            </button>
                                            <button class="action-btn-modern comment-btn" data-post-id="<?php echo $post['id']; ?>">
                                                <i class="bi bi-chat"></i>
                                                <span><?php echo $post['comment_count']; ?></span>
                                            </button>
                                            <button class="action-btn-modern save-btn" data-post-id="<?php echo $post['id']; ?>" data-type="post">
                                                <i class="bi <?php echo $isSaved ? 'bi-bookmark-check-fill' : 'bi-bookmark'; ?>"></i>
                                                <span>Save</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Loader -->
            <div id="loader" class="text-center my-3" style="display: none;">
                <div class="loader-modern"></div>
            </div>
            <div id="no-more-posts" class="text-center text-muted my-3" style="display: none;">
                <i class="bi bi-emoji-smile"></i> You've seen all posts!
            </div>
        </div>

        <!-- RIGHT SIDEBAR -->
        <div class="col-lg-3">
            <!-- Trending Widget -->
            <div class="widget-modern">
                <h6 class="widget-title-modern">
                    <i class="bi bi-fire" style="color: #ff9f1c;"></i> Trending Now
                </h6>
                <div class="d-flex flex-wrap gap-2">
                    <span class="trending-tag">#exams</span>
                    <span class="trending-tag">#placement</span>
                    <span class="trending-tag">#webdev</span>
                    <span class="trending-tag">#internship</span>
                    <span class="trending-tag">#campuslife</span>
                    <span class="trending-tag">#hackathon</span>
                    <span class="trending-tag">#coding</span>
                    <span class="trending-tag">#motivation</span>
                </div>
            </div>

            <!-- Events Widget -->
            <div class="widget-modern">
                <h6 class="widget-title-modern">
                    <i class="bi bi-calendar-event" style="color: #06d6a0;"></i> Upcoming Events
                </h6>
                <div class="event-item mb-2">
                    <div class="d-flex gap-3">
                        <div class="text-center" style="min-width: 50px;">
                            <div style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; border-radius: 12px; padding: 5px;">
                                <small>MAR</small>
                                <strong>20</strong>
                            </div>
                        </div>
                        <div>
                            <strong>Hackathon 2024</strong><br>
                            <small class="text-muted">Online • 2 PM</small>
                        </div>
                    </div>
                </div>
                <div class="event-item mb-2">
                    <div class="d-flex gap-3">
                        <div class="text-center" style="min-width: 50px;">
                            <div style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; border-radius: 12px; padding: 5px;">
                                <small>MAR</small>
                                <strong>25</strong>
                            </div>
                        </div>
                        <div>
                            <strong>Career Fair</strong><br>
                            <small class="text-muted">Main Auditorium • 10 AM</small>
                        </div>
                    </div>
                </div>
                <div class="event-item">
                    <div class="d-flex gap-3">
                        <div class="text-center" style="min-width: 50px;">
                            <div style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; border-radius: 12px; padding: 5px;">
                                <small>MAR</small>
                                <strong>28</strong>
                            </div>
                        </div>
                        <div>
                            <strong>Tech Talk: AI/ML</strong><br>
                            <small class="text-muted">Room 101 • 3 PM</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Contributors Widget -->
            <div class="widget-modern">
                <h6 class="widget-title-modern">
                    <i class="bi bi-trophy-fill" style="color: #ffd166;"></i> Top Contributors
                </h6>
                <div class="contributor-item">
                    <span class="contributor-rank">#1</span>
                    <span>Rahul Sharma</span>
                    <span class="text-warning fw-bold">1,240 pts</span>
                </div>
                <div class="contributor-item">
                    <span class="contributor-rank">#2</span>
                    <span>Priya Mehta</span>
                    <span class="text-warning fw-bold">980 pts</span>
                </div>
                <div class="contributor-item">
                    <span class="contributor-rank">#3</span>
                    <span>Prof. Sharma</span>
                    <span class="text-warning fw-bold">750 pts</span>
                </div>
                <div class="contributor-item">
                    <span class="contributor-rank">#4</span>
                    <span>Amit Kumar</span>
                    <span class="text-warning fw-bold">620 pts</span>
                </div>
                <div class="contributor-item">
                    <span class="contributor-rank">#5</span>
                    <span>Neha Singh</span>
                    <span class="text-warning fw-bold">510 pts</span>
                </div>
            </div>

            <!-- Tip of the Day -->
            <div class="widget-modern" style="background: linear-gradient(135deg, #1a1a2e, #16213e); color: white;">
                <i class="bi bi-lightbulb-fill" style="color: #ffd166; font-size: 1.5rem;"></i>
                <h6 class="mt-2 mb-1">💡 Study Tip</h6>
                <p class="small mb-0" style="opacity: 0.9;">
                    "Use the Pomodoro technique: 25 min study, 5 min break. Track your streak daily!"
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Create Post Modal (Keep same as before) -->
<div class="modal fade" id="createPostModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px;">
            <form method="POST" action="../actions/create_post.php" enctype="multipart/form-data">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Create a Post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Post Type</label>
                        <select name="type" class="form-select" required id="postTypeSelect" style="border-radius: 12px;">
                            <option value="achievement">🏆 Achievement</option>
                            <option value="opportunity">💼 Opportunity</option>
                            <option value="doubt">❓ Doubt</option>
                            <?php if ($_SESSION['user_role'] == 'teacher'): ?>
                                <option value="announcement">📢 Announcement</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required style="border-radius: 12px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea name="content" rows="4" class="form-control" required style="border-radius: 12px;"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Attachment (Optional)</label>
                        <input type="file" name="attachment" class="form-control" style="border-radius: 12px;">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 50px;">Cancel</button>
                    <button type="submit" class="btn" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; border-radius: 50px; padding: 0.5rem 1.5rem;">Post</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Comment Modal -->
<div class="modal fade" id="commentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header border-0">
                <h5 class="modal-title">
                    <i class="bi bi-chat-dots-fill"></i> Comments
                </h5>
                <small class="text-muted">
                    <i class="bi bi-info-circle"></i> Post owner and teachers can delete comments
                </small>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="max-height: 500px; overflow-y: auto;">
                <div id="comments-list">
                    <!-- Comments will load here -->
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                        <p class="mt-2">Loading comments...</p>
                    </div>
                </div>

                <!-- Add Comment Form -->
                <div class="mt-3 pt-3 border-top">
                    <div class="d-flex gap-3">
                        <img src="<?php
                                    if (!empty($_SESSION['user_avatar'])) {
                                        echo '/campusconnect/' . ltrim($_SESSION['user_avatar'], '/');
                                    } else {
                                        echo 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['user_name']) . '&background=4361ee&color=fff&size=40';
                                    }
                                    ?>" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                        <div class="flex-grow-1">
                            <textarea id="comment-text" rows="2" class="form-control"
                                placeholder="Write a comment..." style="border-radius: 20px;"></textarea>
                            <div class="text-end mt-2">
                                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                <button class="btn btn-primary btn-sm" onclick="submitComment()">
                                    <i class="bi bi-send"></i> Post Comment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Post Confirmation Modal -->
<div class="modal fade" id="deletePostModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i> Delete Post
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this post?</p>
                <p class="text-muted small">This action cannot be undone. All comments and likes will also be permanently deleted.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete Permanently</button>
            </div>
        </div>
    </div>
</div>

<script>
    function showPostForm(type) {
        document.getElementById('postTypeSelect').value = type;
        var myModal = new bootstrap.Modal(document.getElementById('createPostModal'));
        myModal.show();
    }

    // Enhanced like button animation
    $(document).on('click', '.like-btn-modern', function() {
        let btn = $(this);
        let icon = btn.find('i');

        // Add animation
        icon.css('transform', 'scale(1.3)');
        setTimeout(() => icon.css('transform', 'scale(1)'), 200);
    });


    // Like functionality - FIXED VERSION
    $(document).ready(function() {
        // Like/Unlike post
        $(document).on('click', '.like-btn-modern', function(e) {
            e.preventDefault();
            e.stopPropagation();

            let btn = $(this);
            let postId = btn.data('post-id');
            let icon = btn.find('i');
            let likeCount = btn.find('.like-count');

            // Disable button temporarily to prevent double clicks
            btn.prop('disabled', true);

            $.ajax({
                url: '../actions/like_post.php',
                method: 'POST',
                data: {
                    post_id: postId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (response.liked) {
                            icon.removeClass('bi-heart').addClass('bi-heart-fill text-danger');
                            btn.addClass('liked');
                        } else {
                            icon.removeClass('bi-heart-fill text-danger').addClass('bi-heart');
                            btn.removeClass('liked');
                        }
                        likeCount.text(response.likes_count);
                    } else {
                        showToast(response.message || 'Error', 'error');
                    }
                    btn.prop('disabled', false);
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', error);
                    showToast('Error liking post', 'error');
                    btn.prop('disabled', false);
                }
            });
        });
    });



    // Global variable to store current post ID
    let currentPostId = null;

    // Open comment modal and load comments
    function openCommentModal(postId) {
        currentPostId = postId;
        $('#commentModal').modal('show');
        loadComments(postId);
    }

    // Load comments for a post
    function loadComments(postId) {
        $('#comments-list').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2">Loading comments...</p></div>');

        $.ajax({
            url: '../actions/get_comments.php',
            method: 'GET',
            data: {
                post_id: postId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayComments(response.comments);
                } else {
                    $('#comments-list').html('<div class="text-center py-5 text-muted">No comments yet. Be the first to comment!</div>');
                }
            },
            error: function() {
                $('#comments-list').html('<div class="text-center py-5 text-danger">Error loading comments</div>');
            }
        });
    }

    // Display comments
    // Display comments - Updated with delete button
    function displayComments(comments) {
        if (comments.length === 0) {
            $('#comments-list').html('<div class="text-center py-5 text-muted">No comments yet. Be the first to comment!</div>');
            return;
        }

        let currentUserId = <?php echo $_SESSION['user_id']; ?>;
        let currentUserRole = '<?php echo $_SESSION['user_role']; ?>';
        let postOwnerId = <?php echo isset($post) ? $post['user_id'] : 0; ?>;

        let html = '';
        for (let comment of comments) {
            let avatarUrl = comment.avatar_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(comment.name) + '&background=4361ee&color=fff&size=40';

            // Check if user can delete this comment (comment owner OR post owner OR teacher)
            let canDelete = (comment.user_id == currentUserId) || (postOwnerId == currentUserId) || (currentUserRole == 'teacher');

            html += `
            <div class="d-flex gap-3 mb-3 pb-3 border-bottom" id="comment-${comment.id}">
                <img src="${avatarUrl}" 
                     class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong class="me-2">${escapeHtml(comment.name)}</strong>
                            ${comment.role === 'teacher' ? '<span class="badge bg-primary ms-1">Teacher</span>' : ''}
                            <small class="text-muted ms-2">${formatDate(comment.created_at)}</small>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-link text-muted like-comment-btn" 
                                    onclick="likeComment(${comment.id})">
                                <i class="bi bi-heart${comment.is_liked ? '-fill text-danger' : ''}"></i>
                                <span class="like-count">${comment.likes_count || 0}</span>
                            </button>
                            ${canDelete ? `
                            <button class="btn btn-sm btn-link text-danger delete-comment-btn" 
                                    onclick="deleteComment(${comment.id}, ${comment.user_id})" 
                                    title="Delete comment">
                                <i class="bi bi-trash3"></i>
                            </button>
                            ` : ''}
                        </div>
                    </div>
                    <p class="mb-1 mt-1">${escapeHtml(comment.comment)}</p>
                </div>
            </div>
        `;
        }
        $('#comments-list').html(html);
    }

    // Delete a comment
    function deleteComment(commentId, commentOwnerId) {
        if (confirm('Are you sure you want to delete this comment? This action cannot be undone.')) {
            $.ajax({
                url: '../actions/delete_comment.php',
                method: 'POST',
                data: {
                    comment_id: commentId,
                    comment_owner_id: commentOwnerId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Remove the comment from DOM
                        $(`#comment-${commentId}`).fadeOut(300, function() {
                            $(this).remove();
                            showToast('Comment deleted successfully', 'success');

                            // Check if no comments left
                            if ($('#comments-list .d-flex').length === 0) {
                                $('#comments-list').html('<div class="text-center py-5 text-muted">No comments yet. Be the first to comment!</div>');
                            }

                            // Update comment count on the post
                            if (response.comment_count !== undefined) {
                                $(`.comment-count-${currentPostId}`).text(response.comment_count);
                            }
                        });
                    } else {
                        showToast(response.message || 'Error deleting comment', 'error');
                    }
                },
                error: function() {
                    showToast('Error deleting comment', 'error');
                }
            });
        }
    }

    // Submit a new comment
    function submitComment() {
        let commentText = $('#comment-text').val().trim();

        if (!commentText) {
            showToast('Please enter a comment', 'error');
            return;
        }

        $.ajax({
            url: '../actions/add_comment.php',
            method: 'POST',
            data: {
                post_id: currentPostId,
                comment: commentText
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#comment-text').val('');
                    loadComments(currentPostId);

                    // Update comment count on the post
                    $(`.comment-count-${currentPostId}`).text(response.comment_count);
                    showToast('Comment posted!', 'success');
                } else {
                    showToast(response.message || 'Error posting comment', 'error');
                }
            },
            error: function() {
                showToast('Error posting comment', 'error');
            }
        });
    }

    // Like a comment
    function likeComment(commentId) {
        $.ajax({
            url: '../actions/like_comment.php',
            method: 'POST',
            data: {
                comment_id: commentId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let btn = $(`.like-comment-btn[onclick="likeComment(${commentId})"]`);
                    let icon = btn.find('i');
                    let likeCount = btn.find('.like-count');

                    if (response.liked) {
                        icon.removeClass('bi-heart').addClass('bi-heart-fill text-danger');
                    } else {
                        icon.removeClass('bi-heart-fill text-danger').addClass('bi-heart');
                    }
                    likeCount.text(response.likes_count);
                }
            }
        });
    }

    // Reply to a comment (opens comment box with @mention)
    function replyToComment(commentId, userName) {
        $('#comment-text').val('@' + userName + ' ').focus();
    }

    // Helper function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Helper function to format date
    function formatDate(dateString) {
        let date = new Date(dateString);
        let now = new Date();
        let diff = Math.floor((now - date) / 1000);

        if (diff < 60) return 'just now';
        if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
        if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
        return date.toLocaleDateString();
    }

    // Update the comment button to open modal
    $(document).ready(function() {
        // Change existing comment buttons to open modal
        $('.comment-btn').each(function() {
            let postId = $(this).data('post-id');
            $(this).attr('onclick', `openCommentModal(${postId})`);
            $(this).css('cursor', 'pointer');
        });
    });


    // Delete a post


    let postToDelete = null;

    function deletePost(postId, postOwnerId) {
        postToDelete = {
            id: postId,
            ownerId: postOwnerId
        };
        $('#deletePostModal').modal('show');
    }

    $('#confirmDeleteBtn').click(function() {
        if (postToDelete) {
            $.ajax({
                url: '../actions/delete_post.php',
                method: 'POST',
                data: {
                    post_id: postToDelete.id,
                    post_owner_id: postToDelete.ownerId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $(`#post-${postToDelete.id}`).fadeOut(500, function() {
                            $(this).remove();
                            showToast('Post deleted successfully', 'success');
                            $('#deletePostModal').modal('hide');
                            postToDelete = null;

                            if ($('#feed-container .modern-card').length === 0) {
                                location.reload();
                            }
                        });
                    } else {
                        showToast(response.message || 'Error deleting post', 'error');
                    }
                },
                error: function() {
                    showToast('Error deleting post', 'error');
                }
            });
        }
    });
</script>



<?php include '../includes/footer.php'; ?>