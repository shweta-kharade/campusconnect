
<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    exit();
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get more posts
$sql = "
    SELECT fp.*, u.name, u.avatar, u.role,
        (SELECT COUNT(*) FROM comments WHERE post_id = fp.id) as comment_count
    FROM feed_posts fp
    JOIN users u ON fp.user_id = u.id
    ORDER BY fp.created_at DESC
    LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$posts = $stmt->fetchAll();

if (empty($posts)) {
    echo '';
    exit();
}

foreach ($posts as $post):
?>
<div class="card post-card fade-in">
    <div class="card-body">
        <div class="d-flex gap-3">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($post['name']); ?>&background=0A66C2&color=fff" 
                 class="avatar-sm">
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="mb-0"><?php echo htmlspecialchars($post['name']); ?></h6>
                        <small class="text-muted">
                            <?php echo ucfirst($post['type']); ?> • 
                            <?php echo date('M d, H:i', strtotime($post['created_at'])); ?>
                        </small>
                    </div>
                    <?php if($post['type'] == 'announcement'): ?>
                        <span class="badge bg-primary">📢 Announcement</span>
                    <?php elseif($post['type'] == 'achievement'): ?>
                        <span class="badge bg-warning text-dark">🏆 Achievement</span>
                    <?php elseif($post['type'] == 'opportunity'): ?>
                        <span class="badge bg-success">💼 Opportunity</span>
                    <?php elseif($post['type'] == 'doubt'): ?>
                        <span class="badge bg-info">❓ Doubt</span>
                    <?php endif; ?>
                </div>
                
                <h6 class="mt-2"><?php echo htmlspecialchars($post['title']); ?></h6>
                <p class="mb-2"><?php echo nl2br(htmlspecialchars(substr($post['content'], 0, 200))); ?></p>
                
                <?php if($post['file_path']): ?>
                    <div class="mt-2">
                        <a href="/campusconnect/<?php echo $post['file_path']; ?>" class="btn btn-sm btn-outline-secondary" download>
                            <i class="bi bi-paperclip"></i> Download Attachment
                        </a>
                    </div>
                <?php endif; ?>
                
                <div class="post-actions">
                    <div class="d-flex gap-4">
                        <button class="action-btn like-btn" data-post-id="<?php echo $post['id']; ?>">
                            <i class="bi bi-heart"></i> 
                            <span class="like-count"><?php echo $post['likes_count']; ?></span>
                        </button>
                        <button class="action-btn comment-btn" data-post-id="<?php echo $post['id']; ?>">
                            <i class="bi bi-chat"></i> 
                            <span><?php echo $post['comment_count']; ?></span>
                        </button>
                        <button class="action-btn save-btn" data-post-id="<?php echo $post['id']; ?>" data-type="post">
                            <i class="bi bi-bookmark"></i> Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>