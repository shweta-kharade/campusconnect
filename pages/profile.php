<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

// Get profile user ID
$profile_id = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];
$is_own_profile = ($profile_id == $_SESSION['user_id']);

// Get user data
$stmt = $pdo->prepare("
    SELECT u.*, 
        (SELECT COUNT(*) FROM feed_posts WHERE user_id = u.id) as posts_count,
        (SELECT COUNT(*) FROM notes WHERE user_id = u.id AND is_approved = 1) as notes_count,
        (SELECT COUNT(*) FROM doubts WHERE user_id = u.id) as doubts_count,
        (SELECT COUNT(*) FROM doubt_answers WHERE user_id = u.id) as answers_count,
        (SELECT COUNT(*) FROM study_requests WHERE user_id = u.id) as study_requests_count,
        (SELECT COUNT(*) FROM study_matches sm JOIN study_requests sr ON sm.request_id = sr.id WHERE sr.user_id = u.id AND sm.status = 'accepted') as connections_count
    FROM users u
    WHERE u.id = ?
");
$stmt->execute([$profile_id]);
$profile_user = $stmt->fetch();

if (!$profile_user) {
    header('Location: feed.php');
    exit();
}

$page_title = $profile_user['name'] . " | Profile";

// Get user's recent activity
$stmt = $pdo->prepare("
    (SELECT 'post' as type, id, title, content, created_at FROM feed_posts WHERE user_id = ? LIMIT 3)
    UNION ALL
    (SELECT 'note' as type, id, title, description as content, created_at FROM notes WHERE user_id = ? AND is_approved = 1 LIMIT 3)
    UNION ALL
    (SELECT 'doubt' as type, id, title, description as content, created_at FROM doubts WHERE user_id = ? LIMIT 3)
    ORDER BY created_at DESC LIMIT 5
");
$stmt->execute([$profile_id, $profile_id, $profile_id]);
$recent_activity = $stmt->fetchAll();

// Get user's interests
$stmt = $pdo->prepare("SELECT * FROM user_interests WHERE user_id = ?");
$stmt->execute([$profile_id]);
$user_interests = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/navbar.php';
?>

<style>
    /* LinkedIn Inspired Styles */
    :root {
        --linkedin-blue: #0A66C2;
        --linkedin-blue-hover: #004182;
        --linkedin-bg: #F3F2EF;
        --linkedin-card: #FFFFFF;
        --linkedin-text: #191919;
        --linkedin-gray: #666666;
        --linkedin-light-gray: #E0E0E0;
        --linkedin-success: #057642;
        --linkedin-border-radius: 12px;
    }

    body {
        background-color: var(--linkedin-bg);
    }

    /* Profile Container */
    .profile-container {
        max-width: 1120px;
        margin: 0 auto;
        padding: 1.5rem 1rem;
    }

    /* Cover Section */
    .profile-cover {
        position: relative;
        background: linear-gradient(135deg, #0A66C2 0%, #004182 100%);
        height: 200px;
        border-radius: var(--linkedin-border-radius) var(--linkedin-border-radius) 0 0;
        overflow: hidden;
    }

    .profile-cover img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0.8;
    }

    /* Profile Card */
    .profile-card {
        background: var(--linkedin-card);
        border-radius: var(--linkedin-border-radius);
        box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }

    /* Avatar Section */
    .profile-avatar-wrapper {
        position: relative;
        padding: 0 1.5rem;
    }

    .profile-avatar {
        position: relative;
        margin-top: -64px;
        width: 152px;
        height: 152px;
        background: white;
        border-radius: 50%;
        padding: 3px;
        box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.08);
    }

    .profile-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    .edit-avatar-btn {
        position: absolute;
        bottom: 8px;
        right: 8px;
        background: white;
        border: none;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        color: var(--linkedin-blue);
        transition: all 0.2s;
    }

    .edit-avatar-btn:hover {
        background: var(--linkedin-blue);
        color: white;
    }

    /* Profile Info */
    .profile-info {
        padding: 0.75rem 1.5rem 1.5rem;
    }

    .profile-name {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--linkedin-text);
        margin-bottom: 0.25rem;
    }

    .profile-headline {
        color: var(--linkedin-gray);
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }

    .profile-location {
        font-size: 0.8rem;
        color: var(--linkedin-gray);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }

    /* Connection Stats */
    .connection-stats {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--linkedin-light-gray);
    }

    .connection-item {
        cursor: pointer;
    }

    .connection-number {
        font-size: 1rem;
        font-weight: 600;
        color: var(--linkedin-text);
    }

    .connection-label {
        font-size: 0.75rem;
        color: var(--linkedin-gray);
    }

    .connection-item:hover .connection-label {
        color: var(--linkedin-blue);
        text-decoration: underline;
    }

    /* Action Buttons */
    .profile-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .btn-linkedin-primary {
        background: var(--linkedin-blue);
        color: white;
        border: none;
        border-radius: 24px;
        padding: 0.5rem 1.5rem;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.2s;
    }

    .btn-linkedin-primary:hover {
        background: var(--linkedin-blue-hover);
        color: white;
    }

    .btn-linkedin-outline {
        background: transparent;
        color: var(--linkedin-gray);
        border: 1px solid var(--linkedin-light-gray);
        border-radius: 24px;
        padding: 0.5rem 1.5rem;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.2s;
    }

    .btn-linkedin-outline:hover {
        background: rgba(10, 102, 194, 0.1);
        border-color: var(--linkedin-blue);
        color: var(--linkedin-blue);
    }

    .btn-message {
        background: transparent;
        color: var(--linkedin-gray);
        border: 1px solid var(--linkedin-light-gray);
        border-radius: 24px;
        padding: 0.5rem 1.5rem;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .btn-message:hover {
        background: var(--linkedin-bg);
    }

    /* Section Styles */
    .profile-section {
        background: var(--linkedin-card);
        border-radius: var(--linkedin-border-radius);
        box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }

    .section-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--linkedin-light-gray);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--linkedin-text);
        margin: 0;
    }

    .section-title i {
        color: var(--linkedin-blue);
        margin-right: 0.5rem;
    }

    .section-content {
        padding: 1rem 1.5rem;
    }

    /* About Section */
    .about-text {
        font-size: 0.9rem;
        line-height: 1.5;
        color: var(--linkedin-text);
        margin-bottom: 0;
    }

    /* Experience/Skills Items */
    .skill-item {
        display: inline-flex;
        align-items: center;
        background: var(--linkedin-bg);
        padding: 0.375rem 0.875rem;
        border-radius: 24px;
        font-size: 0.8rem;
        margin: 0.25rem;
        transition: all 0.2s;
    }

    .skill-item:hover {
        background: var(--linkedin-light-gray);
    }

    /* Activity Feed */
    .activity-item {
        display: flex;
        gap: 1rem;
        padding: 1rem 0;
        border-bottom: 1px solid var(--linkedin-light-gray);
    }

    .activity-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .activity-icon {
        width: 48px;
        height: 48px;
        background: var(--linkedin-bg);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        color: var(--linkedin-blue);
        flex-shrink: 0;
    }

    .activity-content {
        flex: 1;
    }

    .activity-title {
        font-size: 0.9rem;
        font-weight: 500;
        color: var(--linkedin-text);
        margin-bottom: 0.25rem;
    }

    .activity-text {
        font-size: 0.8rem;
        color: var(--linkedin-gray);
        margin-bottom: 0.25rem;
    }

    .activity-time {
        font-size: 0.7rem;
        color: var(--linkedin-gray);
    }

    /* Stats Widget */
    .stat-widget {
        text-align: center;
        padding: 1rem 0;
    }

    .stat-number {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--linkedin-text);
    }

    .stat-label {
        font-size: 0.75rem;
        color: var(--linkedin-gray);
    }

    /* Badges */
    .role-badge {
        background: var(--linkedin-bg);
        color: var(--linkedin-gray);
        padding: 0.25rem 0.75rem;
        border-radius: 24px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .connection-badge {
        background: var(--linkedin-blue);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 24px;
        font-size: 0.7rem;
        font-weight: 500;
    }

    /* Edit Modal */
    .modal-content-linkedin {
        border-radius: var(--linkedin-border-radius);
        border: none;
    }

    .modal-header-linkedin {
        border-bottom: 1px solid var(--linkedin-light-gray);
        padding: 1rem 1.5rem;
    }

    .form-control-linkedin {
        border-radius: 8px;
        border: 1px solid var(--linkedin-light-gray);
        padding: 0.5rem 0.75rem;
    }

    .form-control-linkedin:focus {
        border-color: var(--linkedin-blue);
        box-shadow: 0 0 0 2px rgba(10, 102, 194, 0.2);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .profile-avatar-wrapper {
            text-align: center;
        }

        .profile-avatar {
            margin: -64px auto 0;
        }

        .profile-info {
            text-align: center;
        }

        .profile-actions {
            justify-content: center;
        }

        .connection-stats {
            justify-content: center;
        }

        .profile-location {
            justify-content: center;
        }
    }
</style>

<div class="profile-container">
    <div class="row g-3">
        <!-- Left Column - Main Profile -->
        <div class="col-lg-8">
            <!-- Profile Card -->
            <div class="profile-card">
                <!-- Cover Photo -->
                <div class="profile-cover">
                    <img src="https://images.unsplash.com/photo-1557683316-973673baf926?w=1200&h=200&fit=crop" alt="Cover">
                </div>

                <!-- Avatar -->
                <!-- Avatar Section - CORRECTED PATH -->
                <div class="profile-avatar-wrapper">
                    <div class="profile-avatar">
                        <?php
                        // Build correct avatar URL - FIXED
                        $avatar_url = '';

                        if (!empty($profile_user['avatar'])) {
                            // The path is stored as: assets/uploads/avatars/1779260599_3.jpeg
                            // We need to serve it as: /campusconnect/assets/uploads/avatars/1779260599_3.jpeg
                            $clean_path = ltrim($profile_user['avatar'], '/');
                            $avatar_url = '/campusconnect/' . $clean_path;

                            // Check if the file actually exists
                            $full_file_path = $_SERVER['DOCUMENT_ROOT'] . '/campusconnect/' . $clean_path;
                            if (!file_exists($full_file_path)) {
                                $avatar_url = 'https://ui-avatars.com/api/?name=' . urlencode($profile_user['name']) . '&background=0A66C2&color=fff&size=150&bold=true';
                            }
                        } else {
                            $avatar_url = 'https://ui-avatars.com/api/?name=' . urlencode($profile_user['name']) . '&background=0A66C2&color=fff&size=150&bold=true';
                        }
                        ?>
                        <img src="<?php echo $avatar_url; ?>"
                            alt="<?php echo htmlspecialchars($profile_user['name']); ?>"
                            onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($profile_user['name']); ?>&background=0A66C2&color=fff&size=150&bold=true'">
                        <?php if ($is_own_profile): ?>
                            <button class="edit-avatar-btn" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="bi bi-camera-fill"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Profile Info -->
                <div class="profile-info">
                    <h1 class="profile-name"><?php echo htmlspecialchars($profile_user['name']); ?></h1>
                    <div class="profile-headline">
                        <?php if ($profile_user['department']): ?>
                            <?php echo htmlspecialchars($profile_user['department']); ?>
                            <?php if ($profile_user['semester']): ?> • Semester <?php echo $profile_user['semester']; ?><?php endif; ?>
                            <?php else: ?>
                                <?php echo ucfirst($profile_user['role']); ?> at CampusConnect
                            <?php endif; ?>
                    </div>

                    <div class="profile-location">
                        <i class="bi bi-geo-alt-fill"></i>
                        <?php echo !empty($profile_user['address']) ? htmlspecialchars($profile_user['address']) : 'CampusConnect University'; ?>
                        <span class="role-badge ms-2">
                            <i class="bi bi-mortarboard-fill"></i> <?php echo ucfirst($profile_user['role']); ?>
                        </span>
                    </div>

                    <!-- Connection Stats -->
                    <div class="connection-stats">
                        <div class="connection-item">
                            <span class="connection-number"><?php echo $profile_user['connections_count'] ?? 0; ?></span>
                            <span class="connection-label">connections</span>
                        </div>
                        <div class="connection-item">
                            <span class="connection-number"><?php echo $profile_user['posts_count']; ?></span>
                            <span class="connection-label">posts</span>
                        </div>
                        <div class="connection-item">
                            <span class="connection-number"><?php echo $profile_user['notes_count']; ?></span>
                            <span class="connection-label">notes</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="profile-actions">
                        <?php if ($is_own_profile): ?>
                            <button class="btn-linkedin-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="bi bi-pencil-square"></i> Edit Profile
                            </button>
                            <button class="btn-linkedin-outline" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="bi bi-plus-circle"></i> Add section
                            </button>
                        <?php else: ?>
                            <button class="btn-linkedin-primary" onclick="sendConnectionRequest(<?php echo $profile_id; ?>)">
                                <i class="bi bi-person-plus-fill"></i> Connect
                            </button>
                            <button class="btn-message" onclick="sendMessage(<?php echo $profile_id; ?>, '<?php echo addslashes($profile_user['name']); ?>')">
                                <i class="bi bi-chat-fill"></i> Message
                            </button>
                            <button class="btn-linkedin-outline">
                                <i class="bi bi-three-dots"></i> More
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- About Section -->
            <?php if ($profile_user['bio']): ?>
                <div class="profile-section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="bi bi-person-badge"></i> About
                        </h3>
                    </div>
                    <div class="section-content">
                        <p class="about-text"><?php echo nl2br(htmlspecialchars($profile_user['bio'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Skills & Interests -->
            <?php if (!empty($user_interests)): ?>
                <div class="profile-section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="bi bi-trophy"></i> Skills & Interests
                        </h3>
                        <?php if ($is_own_profile): ?>
                            <button class="btn-linkedin-outline btn-sm" data-bs-toggle="modal" data-bs-target="#interestsModal">
                                <i class="bi bi-plus"></i> Add
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="section-content">
                        <div>
                            <?php foreach ($user_interests as $interest): ?>
                                <span class="skill-item">
                                    <?php echo htmlspecialchars($interest['interest']); ?>
                                    <span class="badge bg-secondary ms-1"><?php echo $interest['skill_level']; ?></span>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Activity Feed -->
            <div class="profile-section">
                <div class="section-header">
                    <h3 class="section-title">
                        <i class="bi bi-activity"></i> Recent Activity
                    </h3>
                    <a href="#" class="text-decoration-none" style="color: var(--linkedin-blue); font-size: 0.8rem;">View all →</a>
                </div>
                <div class="section-content">
                    <?php if (empty($recent_activity)): ?>
                        <p class="text-muted text-center py-3 mb-0">No recent activity</p>
                    <?php else: ?>
                        <?php foreach ($recent_activity as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <?php if ($activity['type'] == 'post'): ?>
                                        <i class="bi bi-newspaper"></i>
                                    <?php elseif ($activity['type'] == 'note'): ?>
                                        <i class="bi bi-journal-bookmark-fill"></i>
                                    <?php else: ?>
                                        <i class="bi bi-question-circle-fill"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">
                                        <?php echo htmlspecialchars($activity['title']); ?>
                                    </div>
                                    <div class="activity-text">
                                        <?php echo htmlspecialchars(substr($activity['content'], 0, 120)); ?>
                                        <?php if (strlen($activity['content']) > 120) echo '...'; ?>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo date('F j, Y', strtotime($activity['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column - Sidebar -->
        <div class="col-lg-4">
            <!-- Profile Stats -->
            <div class="profile-section">
                <div class="section-header">
                    <h3 class="section-title">
                        <i class="bi bi-graph-up"></i> Analytics
                    </h3>
                </div>
                <div class="section-content">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="stat-widget">
                                <div class="stat-number"><?php echo $profile_user['total_points']; ?></div>
                                <div class="stat-label">Total Points</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-widget">
                                <div class="stat-number"><?php echo $profile_user['streak_days']; ?></div>
                                <div class="stat-label">Day Streak 🔥</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-widget">
                                <div class="stat-number"><?php echo $profile_user['answers_count']; ?></div>
                                <div class="stat-label">Answers Given</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-widget">
                                <div class="stat-number"><?php echo $profile_user['study_requests_count']; ?></div>
                                <div class="stat-label">Study Requests</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Info -->
            <?php if ($profile_user['email'] || $profile_user['github'] || $profile_user['linkedin']): ?>
                <div class="profile-section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="bi bi-link-45deg"></i> Contact Info
                        </h3>
                    </div>
                    <div class="section-content">
                        <?php if ($profile_user['email']): ?>
                            <div class="mb-2">
                                <i class="bi bi-envelope-fill me-2" style="color: var(--linkedin-blue);"></i>
                                <span style="font-size: 0.85rem;"><?php echo htmlspecialchars($profile_user['email']); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($profile_user['phone']): ?>
                            <div class="mb-2">
                                <i class="bi bi-telephone-fill me-2" style="color: var(--linkedin-blue);"></i>
                                <span style="font-size: 0.85rem;"><?php echo htmlspecialchars($profile_user['phone']); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($profile_user['github']): ?>
                            <div class="mb-2">
                                <i class="bi bi-github me-2" style="color: var(--linkedin-blue);"></i>
                                <a href="<?php echo htmlspecialchars($profile_user['github']); ?>" target="_blank" class="text-decoration-none" style="font-size: 0.85rem;">
                                    GitHub Profile
                                </a>
                            </div>
                        <?php endif; ?>
                        <?php if ($profile_user['linkedin']): ?>
                            <div class="mb-2">
                                <i class="bi bi-linkedin me-2" style="color: var(--linkedin-blue);"></i>
                                <a href="<?php echo htmlspecialchars($profile_user['linkedin']); ?>" target="_blank" class="text-decoration-none" style="font-size: 0.85rem;">
                                    LinkedIn Profile
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Achievements -->
            <?php if ($profile_user['achievements']): ?>
                <div class="profile-section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="bi bi-award"></i> Achievements
                        </h3>
                    </div>
                    <div class="section-content">
                        <?php
                        $achievements = explode(',', $profile_user['achievements']);
                        foreach ($achievements as $achievement):
                        ?>
                            <div class="mb-2">
                                <i class="bi bi-trophy-fill me-2" style="color: #ffd166;"></i>
                                <span style="font-size: 0.85rem;"><?php echo htmlspecialchars(trim($achievement)); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- People Also Viewed (Suggested Connections) -->
            <div class="profile-section">
                <div class="section-header">
                    <h3 class="section-title">
                        <i class="bi bi-people-fill"></i> People You May Know
                    </h3>
                </div>
                <div class="section-content">
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT id, name, avatar, department 
                        FROM users 
                        WHERE id != ? AND role = 'student'
                        ORDER BY RAND() 
                        LIMIT 3
                    ");
                    $stmt->execute([$profile_id]);
                    $suggestions = $stmt->fetchAll();
                    ?>
                    <?php foreach ($suggestions as $suggestion): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                            <div class="d-flex gap-2">
                                <img src="<?php echo $suggestion['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($suggestion['name']) . '&background=0A66C2&color=fff&size=40'; ?>"
                                    style="width: 40px; height: 40px; border-radius: 50%;">
                                <div>
                                    <div style="font-size: 0.85rem; font-weight: 500;"><?php echo htmlspecialchars($suggestion['name']); ?></div>
                                    <div style="font-size: 0.7rem; color: var(--linkedin-gray);"><?php echo htmlspecialchars($suggestion['department'] ?? 'Student'); ?></div>
                                </div>
                            </div>
                            <button class="btn-linkedin-outline btn-sm" onclick="sendConnectionRequest(<?php echo $suggestion['id']; ?>)">
                                Connect
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modal-content-linkedin">
            <div class="modal-header modal-header-linkedin">
                <h5 class="modal-title">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="../actions/update_profile.php" enctype="multipart/form-data">
                <div class="modal-body" style="padding: 1.5rem;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control form-control-linkedin" value="<?php echo htmlspecialchars($profile_user['name']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control form-control-linkedin" value="<?php echo htmlspecialchars($profile_user['email']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control form-control-linkedin" value="<?php echo htmlspecialchars($profile_user['phone'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" class="form-control form-control-linkedin" value="<?php echo htmlspecialchars($profile_user['department'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Semester</label>
                            <select name="semester" class="form-select form-control-linkedin">
                                <option value="">Select Semester</option>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($profile_user['semester'] == $i) ? 'selected' : ''; ?>>
                                        Semester <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Profile Photo</label>
                            <input type="file" name="avatar" class="form-control form-control-linkedin" accept="image/*">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Bio / Headline</label>
                            <textarea name="bio" rows="3" class="form-control form-control-linkedin" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($profile_user['bio'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GitHub URL</label>
                            <input type="url" name="github" class="form-control form-control-linkedin" value="<?php echo htmlspecialchars($profile_user['github'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">LinkedIn URL</label>
                            <input type="url" name="linkedin" class="form-control form-control-linkedin" value="<?php echo htmlspecialchars($profile_user['linkedin'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Achievements (comma separated)</label>
                            <input type="text" name="achievements" class="form-control form-control-linkedin" value="<?php echo htmlspecialchars($profile_user['achievements'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" rows="2" class="form-control form-control-linkedin"><?php echo htmlspecialchars($profile_user['address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--linkedin-light-gray); padding: 1rem 1.5rem;">
                    <button type="button" class="btn-linkedin-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-linkedin-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Interests Modal -->
<div class="modal fade" id="interestsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-linkedin">
            <div class="modal-header modal-header-linkedin">
                <h5 class="modal-title">Add Skills & Interests</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="../actions/update_interests.php">
                <div class="modal-body" style="padding: 1.5rem;">
                    <div class="mb-3">
                        <label class="form-label">Add interests (separate by commas)</label>
                        <input type="text" name="interests" class="form-control form-control-linkedin"
                            placeholder="e.g., PHP, JavaScript, Python, DSA, Web Development">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Your skill level</label>
                        <select name="skill_level" class="form-select form-control-linkedin">
                            <option value="beginner">Beginner - Just starting out</option>
                            <option value="intermediate">Intermediate - Comfortable with basics</option>
                            <option value="advanced">Advanced - Can help others</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--linkedin-light-gray); padding: 1rem 1.5rem;">
                    <button type="button" class="btn-linkedin-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-linkedin-primary">Save Interests</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function sendConnectionRequest(userId) {
        $.ajax({
            url: '../actions/send_connection_request.php',
            method: 'POST',
            data: {
                user_id: userId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(response.message, 'error');
                }
            }
        });
    }

    function sendMessage(userId, userName) {
        let message = prompt("Send a message to " + userName + ":");
        if (message && message.trim()) {
            showToast("Message sent to " + userName + "!", "success");
        }
    }

    function showToast(message, type) {
        let bgColor = type === 'success' ? '#057642' : '#dc3545';
        let toast = $(`
        <div style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;
                    background: ${bgColor}; color: white; padding: 12px 24px;
                    border-radius: 8px; animation: fadeIn 0.3s;">
            ${message}
        </div>
    `);
        $('body').append(toast);
        setTimeout(() => toast.fadeOut(() => toast.remove()), 3000);
    }
</script>

<?php include '../includes/footer.php'; ?>