<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$page_title = 'Study Partner Finder';
$current_user = currentUser($pdo);

// Handle filters
$study_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$subject = isset($_GET['subject']) ? $_GET['subject'] : '';

// Build query
if ($study_type != 'all') {
    $sql = "SELECT sr.*, u.name, u.avatar, u.department, u.semester as user_semester
            FROM study_requests sr
            JOIN users u ON sr.user_id = u.id
            WHERE sr.status = 'open' AND sr.study_type = ?
            ORDER BY sr.created_at DESC";
    $params = [$study_type];
} else {
    $sql = "SELECT sr.*, u.name, u.avatar, u.department, u.semester as user_semester
            FROM study_requests sr
            JOIN users u ON sr.user_id = u.id
            WHERE sr.status = 'open'
            ORDER BY sr.created_at DESC";
    $params = [];
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Get user's own requests
$stmt = $pdo->prepare("SELECT id FROM study_requests WHERE user_id = ? AND status = 'open'");
$stmt->execute([$_SESSION['user_id']]);
$my_requests = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get pending matches
$stmt = $pdo->prepare("
    SELECT sm.*, sr.title, u.name 
    FROM study_matches sm
    JOIN study_requests sr ON sm.request_id = sr.id
    JOIN users u ON sm.matched_user_id = u.id
    WHERE sm.matched_user_id = ? AND sm.status = 'pending'
");
$stmt->execute([$_SESSION['user_id']]);
$pending_matches = $stmt->fetchAll();

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

    .study-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        color: white;
    }

    .request-card {
        background: white;
        border-radius: 16px;
        padding: 1.25rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .request-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .type-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 500;
    }

    .type-study_buddy {
        background: #4361ee;
        color: white;
    }

    .type-project_team {
        background: #ef476f;
        color: white;
    }

    .type-group_study {
        background: #06d6a0;
        color: white;
    }

    .type-mentorship {
        background: #ffd166;
        color: #333;
    }

    .type-accountability {
        background: #7209b7;
        color: white;
    }

    .connect-btn {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.5rem 1.25rem;
        transition: all 0.3s;
    }

    .connect-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        color: white;
    }

    .filter-btn {
        border-radius: 50px;
        padding: 0.5rem 1rem;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-block;
    }

    .filter-btn.active {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
    }

    .create-btn {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.5rem 1.5rem;
    }

    .notification-badge {
        position: relative;
        display: inline-block;
    }

    .notification-count {
        position: absolute;
        top: -8px;
        right: -8px;
        background: var(--danger);
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .request-card {
        animation: slideUp 0.4s ease;
    }

    .interest-tag {
        background: rgba(67, 97, 238, 0.1);
        color: var(--primary);
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.7rem;
    }

    .nav-tabs .nav-link {
        transition: all 0.3s ease;
    }

    .nav-tabs .nav-link:hover {
        transform: translateY(-2px);
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white !important;
    }

    .nav-tabs .nav-link.active {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white !important;
        box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
    }
</style>

<div class="container py-4">
    <!-- Header -->
    <div class="study-header">
        <div class="row align-items-center">
            <div class="col-md-7">
                <h1 class="mb-2">
                    <i class="bi bi-people-fill"></i> Study Partner Finder
                </h1>
                <p class="mb-0 opacity-75">
                    Find study buddies, project teammates, and mentors to learn together
                </p>
            </div>
            <div class="col-md-5 text-md-end mt-3 mt-md-0">
                <button class="btn btn-light px-4" data-bs-toggle="modal" data-bs-target="#createRequestModal" style="border-radius: 50px;">
                    <i class="bi bi-plus-circle"></i> Post Request
                </button>
            </div>
        </div>
    </div>

    <!-- After the study-header div, add this: -->

    <!-- My Requests Tabs -->
    <div class="mb-4">
        <ul class="nav nav-tabs" style="border: none; gap: 0.5rem;" id="requestTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#allRequests" type="button" role="tab"
                    style="border-radius: 50px; border: none; padding: 0.5rem 1.5rem; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    <i class="bi bi-search"></i> Browse Requests
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#mySentRequests" type="button" role="tab"
                    style="border-radius: 50px; border: none; padding: 0.5rem 1.5rem; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    <i class="bi bi-send"></i> Sent Requests
                    <?php
                    // Count sent requests
                    $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count FROM study_matches sm
                    JOIN study_requests sr ON sm.request_id = sr.id
                    WHERE sm.matched_user_id = ? AND sm.status = 'pending'
                ");
                    $stmt->execute([$_SESSION['user_id']]);
                    $sent_count = $stmt->fetch()['count'];
                    if ($sent_count > 0): ?>
                        <span class="badge bg-danger rounded-pill ms-1"><?php echo $sent_count; ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#myReceivedRequests" type="button" role="tab"
                    style="border-radius: 50px; border: none; padding: 0.5rem 1.5rem; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    <i class="bi bi-inbox"></i> Received Requests
                    <?php
                    // Count received requests
                    $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count FROM study_matches sm
                    JOIN study_requests sr ON sm.request_id = sr.id
                    WHERE sr.user_id = ? AND sm.status = 'pending'
                ");
                    $stmt->execute([$_SESSION['user_id']]);
                    $received_count = $stmt->fetch()['count'];
                    if ($received_count > 0): ?>
                        <span class="badge bg-danger rounded-pill ms-1"><?php echo $received_count; ?></span>
                    <?php endif; ?>
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content">
        <!-- All Requests Tab -->
        <div class="tab-pane fade show active" id="allRequests" role="tabpanel">
            <!-- The existing filters and requests list goes here -->
            <?php // Move the existing content inside this div 
            ?>
        </div>

        <!-- Sent Requests Tab -->
        <div class="tab-pane fade" id="mySentRequests" role="tabpanel">
            <?php
            // Get sent connection requests
            $stmt = $pdo->prepare("
            SELECT sm.*, sr.title, sr.study_type, u.name, u.avatar, u.department
            FROM study_matches sm
            JOIN study_requests sr ON sm.request_id = sr.id
            JOIN users u ON sr.user_id = u.id
            WHERE sm.matched_user_id = ? AND sm.status != 'rejected'
            ORDER BY sm.created_at DESC
        ");
            $stmt->execute([$_SESSION['user_id']]);
            $sent_requests = $stmt->fetchAll();
            ?>

            <div class="row g-4">
                <div class="col-12">
                    <?php if (empty($sent_requests)): ?>
                        <div class="text-center py-5 bg-white rounded-4">
                            <i class="bi bi-envelope-paper" style="font-size: 3rem; color: #ccc;"></i>
                            <h6 class="mt-2">No sent requests</h6>
                            <p class="text-muted small">You haven't sent any connection requests yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($sent_requests as $request):
                            $statusClass = '';
                            $statusText = '';
                            if ($request['status'] == 'pending') {
                                $statusClass = 'warning';
                                $statusText = 'Pending';
                            } elseif ($request['status'] == 'accepted') {
                                $statusClass = 'success';
                                $statusText = 'Accepted ✓';
                            } else {
                                $statusClass = 'danger';
                                $statusText = 'Declined ✗';
                            }
                        ?>
                            <div class="request-card">
                                <div class="d-flex gap-3">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($request['name']); ?>&background=4361ee&color=fff"
                                        class="avatar-sm" style="width: 48px; height: 48px;">
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($request['title']); ?></h6>
                                                <div class="d-flex flex-wrap gap-2 mb-2">
                                                    <span class="type-badge type-<?php echo str_replace('_', '-', $request['study_type']); ?>">
                                                        <?php echo str_replace('_', ' ', ucfirst($request['study_type'])); ?>
                                                    </span>
                                                    <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('M d', strtotime($request['created_at'])); ?>
                                            </small>
                                        </div>

                                        <p class="text-muted small mb-2">
                                            <i class="bi bi-person"></i> Requested from: <?php echo htmlspecialchars($request['name']); ?>
                                            <?php if ($request['department']): ?> • <?php echo htmlspecialchars($request['department']); ?><?php endif; ?>
                                        </p>
                                        <?php if ($request['message']): ?>
                                            <div class="alert alert-light small mb-2" style="border-radius: 12px;">
                                                <i class="bi bi-chat-dots"></i> "<?php echo htmlspecialchars($request['message']); ?>"
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($request['status'] == 'pending'): ?>
                                            <button class="btn btn-sm btn-danger rounded-pill" onclick="cancelRequest(<?php echo $request['id']; ?>)">
                                                Cancel Request
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Received Requests Tab -->
        <div class="tab-pane fade" id="myReceivedRequests" role="tabpanel">
            <?php
            // Get received connection requests
            $stmt = $pdo->prepare("
            SELECT sm.*, sr.title, sr.study_type, sr.user_id as request_owner_id, u.name, u.avatar, u.department, u.email
            FROM study_matches sm
            JOIN study_requests sr ON sm.request_id = sr.id
            JOIN users u ON sm.matched_user_id = u.id
            WHERE sr.user_id = ? AND sm.status = 'pending'
            ORDER BY sm.created_at DESC
        ");
            $stmt->execute([$_SESSION['user_id']]);
            $received_requests = $stmt->fetchAll();
            ?>

            <div class="row g-4">
                <div class="col-12">
                    <?php if (empty($received_requests)): ?>
                        <div class="text-center py-5 bg-white rounded-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <h6 class="mt-2">No received requests</h6>
                            <p class="text-muted small">When someone wants to connect, you'll see it here</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($received_requests as $request): ?>
                            <div class="request-card" style="border-left: 4px solid #4361ee;">
                                <div class="d-flex gap-3">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($request['name']); ?>&background=4361ee&color=fff"
                                        class="avatar-sm" style="width: 48px; height: 48px;">
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($request['title']); ?></h6>
                                                <div class="d-flex flex-wrap gap-2 mb-2">
                                                    <span class="type-badge type-<?php echo str_replace('_', '-', $request['study_type']); ?>">
                                                        <?php echo str_replace('_', ' ', ucfirst($request['study_type'])); ?>
                                                    </span>
                                                    <span class="badge bg-warning">Pending Response</span>
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('M d, H:i', strtotime($request['created_at'])); ?>
                                            </small>
                                        </div>
                                        <p class="text-muted small mb-2">
                                            <i class="bi bi-person"></i> Request from: <strong><?php echo htmlspecialchars($request['name']); ?></strong>
                                            <?php if ($request['department']): ?> • <?php echo htmlspecialchars($request['department']); ?><?php endif; ?>
                                        </p>
                                        <?php if ($request['message']): ?>
                                            <div class="alert alert-primary small mb-2" style="border-radius: 12px;">
                                                <i class="bi bi-chat-dots-fill"></i> <strong>Message:</strong> "<?php echo htmlspecialchars($request['message']); ?>"
                                            </div>
                                        <?php endif; ?>
                                        <div class="d-flex gap-2 mt-2">
                                            <button class="btn btn-sm btn-success rounded-pill" onclick="respondToMatch(<?php echo $request['id']; ?>, 'accept')">
                                                <i class="bi bi-check-lg"></i> Accept
                                            </button>
                                            <button class="btn btn-sm btn-danger rounded-pill" onclick="respondToMatch(<?php echo $request['id']; ?>, 'reject')">
                                                <i class="bi bi-x-lg"></i> Decline
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="viewProfile(<?php echo $request['matched_user_id']; ?>)">
                                                <i class="bi bi-person"></i> View Profile
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Matches Alert -->
    <?php if (!empty($pending_matches)): ?>
        <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert" style="border-radius: 16px;">
            <i class="bi bi-bell-fill"></i>
            <strong>You have <?php echo count($pending_matches); ?> pending connection request(s)!</strong>
            <a href="#" data-bs-toggle="modal" data-bs-target="#matchesModal" class="alert-link">View requests</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Filters -->
            <div class="d-flex flex-wrap gap-2 mb-4">
                <a href="?type=all" class="filter-btn <?php echo $study_type == 'all' ? 'active' : 'btn-outline-secondary'; ?>">
                    All Requests
                </a>
                <a href="?type=study_buddy" class="filter-btn <?php echo $study_type == 'study_buddy' ? 'active' : 'btn-outline-secondary'; ?>">
                    <i class="bi bi-person-plus"></i> Study Buddy
                </a>
                <a href="?type=project_team" class="filter-btn <?php echo $study_type == 'project_team' ? 'active' : 'btn-outline-secondary'; ?>">
                    <i class="bi bi-code-square"></i> Project Team
                </a>
                <a href="?type=group_study" class="filter-btn <?php echo $study_type == 'group_study' ? 'active' : 'btn-outline-secondary'; ?>">
                    <i class="bi bi-people"></i> Group Study
                </a>
                <a href="?type=mentorship" class="filter-btn <?php echo $study_type == 'mentorship' ? 'active' : 'btn-outline-secondary'; ?>">
                    <i class="bi bi-mortarboard"></i> Mentorship
                </a>
                <a href="?type=accountability" class="filter-btn <?php echo $study_type == 'accountability' ? 'active' : 'btn-outline-secondary'; ?>">
                    <i class="bi bi-calendar-check"></i> Accountability
                </a>
            </div>

            <!-- Study Requests List -->
            <?php if (empty($requests)): ?>
                <div class="text-center py-5 bg-white rounded-4">
                    <i class="bi bi-people" style="font-size: 4rem; color: #ccc;"></i>
                    <h5 class="mt-3">No requests found</h5>
                    <p class="text-muted">Be the first to post a study partner request!</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRequestModal">
                        Post Your Request
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($requests as $request):
                    $isMyRequest = in_array($request['id'], $my_requests);
                    $typeClass = 'type-' . str_replace('_', '-', $request['study_type']);
                ?>
                    <div class="request-card">
                        <div class="d-flex gap-3">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($request['name']); ?>&background=4361ee&color=fff"
                                class="avatar-sm" style="width: 48px; height: 48px;">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="mb-1"><?php echo htmlspecialchars($request['title']); ?></h5>
                                        <div class="d-flex flex-wrap gap-2 mb-2">
                                            <span class="type-badge <?php echo $typeClass; ?>">
                                                <?php echo str_replace('_', ' ', ucfirst($request['study_type'])); ?>
                                            </span>
                                            <?php if ($request['subject']): ?>
                                                <span class="interest-tag">
                                                    <i class="bi bi-book"></i> <?php echo htmlspecialchars($request['subject']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($request['semester']): ?>
                                                <span class="interest-tag">
                                                    <i class="bi bi-calendar"></i> Semester <?php echo $request['semester']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2 align-items-center">
                                        <small class="text-muted">
                                            <?php echo date('M d', strtotime($request['created_at'])); ?>
                                        </small>
                                        <!-- Delete Button for own requests -->
                                        <?php if ($_SESSION['user_id'] == $request['user_id'] || $_SESSION['user_role'] == 'teacher'): ?>
                                            <button class="btn btn-sm btn-link text-danger"
                                                onclick="deleteStudyRequest(<?php echo $request['id']; ?>, <?php echo $request['user_id']; ?>)"
                                                title="Delete request">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <p class="text-muted small mb-2">
                                    <?php echo htmlspecialchars(substr($request['description'], 0, 150)); ?>
                                    <?php if (strlen($request['description']) > 150) echo '...'; ?>
                                </p>

                                <?php if ($request['preferred_time']): ?>
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i> Preferred: <?php echo htmlspecialchars($request['preferred_time']); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>

                                <?php if ($request['looking_for']): ?>
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i class="bi bi-person-check"></i> Looking for: <?php echo htmlspecialchars($request['looking_for']); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div>
                                        <small class="text-muted">
                                            <i class="bi bi-person"></i> Posted by <?php echo htmlspecialchars($request['name']); ?>
                                            <?php if ($request['department']): ?>
                                                • <?php echo htmlspecialchars($request['department']); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>

                                    <?php if (!$isMyRequest): ?>
                                        <button class="connect-btn" onclick="connectRequest(<?php echo $request['id']; ?>, <?php echo $request['user_id']; ?>)">
                                            <i class="bi bi-chat-dots"></i> Connect
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary rounded-pill" disabled>
                                            <i class="bi bi-check-circle"></i> Your Request
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Match Suggestions -->
            <div class="card border-0 rounded-4 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="mb-3">
                        <i class="bi bi-stars"></i> Recommended For You
                    </h6>
                    <?php
                    // Get user interests for recommendations
                    $stmt = $pdo->prepare("SELECT interest FROM user_interests WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $my_interests = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    if (!empty($my_interests)):
                        $interest_placeholders = str_repeat('?,', count($my_interests) - 1) . '?';
                        $stmt = $pdo->prepare("
                            SELECT DISTINCT sr.*, u.name
                            FROM study_requests sr
                            JOIN users u ON sr.user_id = u.id
                            WHERE sr.subject IN ($interest_placeholders)
                            AND sr.user_id != ?
                            AND sr.status = 'open'
                            LIMIT 3
                        ");
                        $params = array_merge($my_interests, [$_SESSION['user_id']]);
                        $stmt->execute($params);
                        $recommendations = $stmt->fetchAll();
                    else:
                        $recommendations = [];
                    endif;
                    ?>

                    <?php if (!empty($recommendations)): ?>
                        <?php foreach ($recommendations as $rec): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                <div>
                                    <strong><?php echo htmlspecialchars($rec['title']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($rec['name']); ?></small>
                                </div>
                                <button class="btn btn-sm btn-outline-primary rounded-pill" onclick="connectRequest(<?php echo $rec['id']; ?>, <?php echo $rec['user_id']; ?>)">
                                    Connect
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted small">Update your interests to get recommendations</p>
                        <button class="btn btn-sm btn-primary w-100" data-bs-toggle="modal" data-bs-target="#interestsModal">
                            <i class="bi bi-pencil"></i> Set Your Interests
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Your Interests -->
            <div class="card border-0 rounded-4 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="mb-3">
                        <i class="bi bi-tags"></i> Your Interests
                    </h6>
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM user_interests WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $interests = $stmt->fetchAll();
                    ?>

                    <?php if (!empty($interests)): ?>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <?php foreach ($interests as $interest): ?>
                                <span class="interest-tag">
                                    <?php echo htmlspecialchars($interest['interest']); ?>
                                    <span class="badge bg-secondary ms-1"><?php echo $interest['skill_level']; ?></span>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <button class="btn btn-sm btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#interestsModal">
                            <i class="bi bi-pencil"></i> Edit Interests
                        </button>
                    <?php else: ?>
                        <p class="text-muted small">You haven't set any interests yet</p>
                        <button class="btn btn-sm btn-primary w-100" data-bs-toggle="modal" data-bs-target="#interestsModal">
                            <i class="bi bi-plus"></i> Add Interests
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Study Tips -->
            <div class="card border-0 rounded-4 shadow-sm" style="background: linear-gradient(135deg, #1a1a2e, #16213e); color: white;">
                <div class="card-body">
                    <i class="bi bi-lightbulb-fill" style="font-size: 1.5rem; color: #ffd166;"></i>
                    <h6 class="mt-2">💡 Tips for Finding Study Partners</h6>
                    <ul class="small mt-2 mb-0" style="padding-left: 1rem;">
                        <li>Be clear about your goals</li>
                        <li>Specify your availability</li>
                        <li>Share your skill level honestly</li>
                        <li>Respond to connection requests quickly</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Request Modal -->
<div class="modal fade" id="createRequestModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 20px;">
            <form method="POST" action="../actions/create_study_request.php">
                <div class="modal-header border-0">
                    <h5 class="modal-title">
                        <i class="bi bi-person-plus"></i> Post Study Partner Request
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">What are you looking for? *</label>
                        <select name="study_type" class="form-select" required style="border-radius: 12px;">
                            <option value="study_buddy">📚 Study Buddy - Learn together</option>
                            <option value="project_team">💻 Project Team - Build something together</option>
                            <option value="group_study">👥 Group Study - Multiple people</option>
                            <option value="mentorship">🎓 Mentorship - Get/Give guidance</option>
                            <option value="accountability">⏰ Accountability - Keep each other on track</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-control" required
                            placeholder="e.g., Looking for DSA study partner" style="border-radius: 12px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description *</label>
                        <textarea name="description" rows="4" class="form-control" required
                            placeholder="Describe what you're looking for, your goals, expectations..." style="border-radius: 12px;"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Subject/Course</label>
                            <input type="text" name="subject" class="form-control"
                                placeholder="e.g., Data Structures, Web Development" style="border-radius: 12px;">
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
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Preferred Time</label>
                            <input type="text" name="preferred_time" class="form-control"
                                placeholder="e.g., Evenings 7-9 PM, Weekends" style="border-radius: 12px;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control"
                                placeholder="e.g., Library, Online, CSE Department" style="border-radius: 12px;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">What skills/qualities are you looking for?</label>
                        <textarea name="looking_for" rows="2" class="form-control"
                            placeholder="e.g., Good with Python, Reliable, Available on weekends" style="border-radius: 12px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 50px;">Cancel</button>
                    <button type="submit" class="btn" style="background: linear-gradient(135deg, #4361ee, #7209b7); color: white; border-radius: 50px;">
                        <i class="bi bi-send"></i> Post Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Interests Modal -->
<div class="modal fade" id="interestsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px;">
            <form method="POST" action="../actions/update_interests.php">
                <div class="modal-header border-0">
                    <h5 class="modal-title">
                        <i class="bi bi-tags"></i> Your Learning Interests
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Add interests (separate by commas)</label>
                        <input type="text" name="interests" class="form-control"
                            placeholder="e.g., PHP, JavaScript, Python, DSA, Web Development" style="border-radius: 12px;">
                        <small class="text-muted">This helps us find matching study partners</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Your skill level</label>
                        <select name="skill_level" class="form-select" style="border-radius: 12px;">
                            <option value="beginner">Beginner - Just starting out</option>
                            <option value="intermediate">Intermediate - Comfortable with basics</option>
                            <option value="advanced">Advanced - Can help others</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 50px;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="border-radius: 50px;">Save Interests</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Matches Modal -->
<div class="modal fade" id="matchesModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header border-0">
                <h5 class="modal-title">
                    <i class="bi bi-bell-fill"></i> Connection Requests
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php foreach ($pending_matches as $match): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <div>
                            <strong><?php echo htmlspecialchars($match['name']); ?></strong><br>
                            <small>wants to connect for: <?php echo htmlspecialchars($match['title']); ?></small>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-success rounded-pill" onclick="respondToMatch(<?php echo $match['id']; ?>, 'accept')">
                                Accept
                            </button>
                            <button class="btn btn-sm btn-danger rounded-pill" onclick="respondToMatch(<?php echo $match['id']; ?>, 'reject')">
                                Decline
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function connectRequest(requestId, ownerId) {
        // Show a message modal or redirect
        let message = prompt("Send a message to the person (optional):");

        $.ajax({
            url: '../actions/connect_request.php',
            method: 'POST',
            data: {
                request_id: requestId,
                owner_id: ownerId,
                message: message
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(response.message, 'error');
                }
            }
        });
    }

    function respondToMatch(matchId, action) {
        $.ajax({
            url: '../actions/respond_match.php',
            method: 'POST',
            data: {
                match_id: matchId,
                action: action
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

    function showToast(message, type) {
        let bgColor = type === 'success' ? '#28a745' : '#dc3545';
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

    function cancelRequest(matchId) {
        if (confirm('Are you sure you want to cancel this request?')) {
            $.ajax({
                url: '../actions/cancel_request.php',
                method: 'POST',
                data: {
                    match_id: matchId
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
    }

    function viewProfile(userId) {
        window.location.href = 'profile.php?id=' + userId;
    }

    // Delete study request function
function deleteStudyRequest(requestId, requestOwnerId) {
    if (confirm('Are you sure you want to delete this study request? All connection requests related to it will also be removed. This action cannot be undone!')) {
        $.ajax({
            url: '../actions/delete_study_request.php',
            method: 'POST',
            data: { 
                request_id: requestId,
                request_owner_id: requestOwnerId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast('Study request deleted successfully', 'success');
                    // Remove the card from DOM
                    $(`#request-${requestId}`).fadeOut(500, function() {
                        $(this).remove();
                        if ($('.request-card').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    showToast(response.message || 'Error deleting request', 'error');
                }
            },
            error: function() {
                showToast('Error deleting request', 'error');
            }
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>