<?php
$current_page = basename($_SERVER['PHP_SELF']);
$isLoggedIn = isset($_SESSION['user_id']);
?>
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="/campusconnect/index.php">
            <i class="bi bi-mortarboard-fill"></i> CampusConnect
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <?php if ($isLoggedIn): ?>
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'feed.php' ? 'active' : ''; ?>"
                            href="/campusconnect/pages/feed.php">
                            <i class="bi bi-house-door"></i> Feed
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'notes.php' ? 'active' : ''; ?>"
                            href="/campusconnect/pages/notes.php">
                            <i class="bi bi-journal-bookmark"></i> Notes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'doubts.php' ? 'active' : ''; ?>"
                            href="/campusconnect/pages/doubts.php">
                            <i class="bi bi-chat-dots"></i> Doubts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'events.php' ? 'active' : ''; ?>"
                            href="/campusconnect/pages/events.php">
                            <i class="bi bi-calendar-event"></i> Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'study.php' ? 'active' : ''; ?>"
                            href="/campusconnect/pages/study.php">
                            <i class="bi bi-people"></i> Study
                        </a>
                    </li>
                </ul>

                <div class="dropdown">
                    <a href="#" data-bs-toggle="dropdown" style="text-decoration: none;">
                        <?php
                        $nav_avatar_url = '';
                        if (!empty($_SESSION['user_avatar'])) {
                            $clean_path = ltrim($_SESSION['user_avatar'], '/');
                            $nav_avatar_url = '/campusconnect/' . $clean_path;

                            // Check if file exists
                            $full_path = $_SERVER['DOCUMENT_ROOT'] . '/campusconnect/' . $clean_path;
                            if (!file_exists($full_path)) {
                                $nav_avatar_url = 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['user_name']) . '&background=0A66C2&color=fff';
                            }
                        } else {
                            $nav_avatar_url = 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['user_name']) . '&background=0A66C2&color=fff';
                        }
                        ?>
                        <img src="<?php echo $nav_avatar_url; ?>" class="avatar-sm"
                            style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;"
                            onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name']); ?>&background=0A66C2&color=fff'">
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="/campusconnect/pages/profile.php">
                                <i class="bi bi-person"></i> Profile
                            </a></li>
                        <li><a class="dropdown-item" href="/campusconnect/pages/saved.php">
                                <i class="bi bi-bookmark"></i> Saved
                            </a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="/campusconnect/auth/logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>

                </div>
            <?php else: ?>
                <div class="ms-auto">
                    <a href="/campusconnect/auth/login.php" class="btn btn-outline-primary btn-sm">Login</a>
                    <a href="/campusconnect/auth/register.php" class="btn btn-primary btn-sm">Register</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- <?php if ($isLoggedIn): ?>
<div class="bottom-nav">
    <a href="/campusconnect/pages/feed.php" class="<?php echo $current_page == 'feed.php' ? 'text-primary' : 'text-secondary'; ?>">
        <i class="bi bi-house-door-fill fs-5"></i>
    </a>
    <a href="/campusconnect/pages/notes.php" class="<?php echo $current_page == 'notes.php' ? 'text-primary' : 'text-secondary'; ?>">
        <i class="bi bi-journal-bookmark-fill fs-5"></i>
    </a>
    <a href="/campusconnect/pages/doubts.php" class="<?php echo $current_page == 'doubts.php' ? 'text-primary' : 'text-secondary'; ?>">
        <i class="bi bi-chat-dots-fill fs-5"></i>
    </a>
    <a href="/campusconnect/pages/profile.php" class="<?php echo $current_page == 'profile.php' ? 'text-primary' : 'text-secondary'; ?>">
        <i class="bi bi-person-fill fs-5"></i>
    </a>
</div> -->
<?php endif; ?>