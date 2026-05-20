<?php
// Check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: /campusconnect/auth/login.php');
        exit();
    }
}

// Get user data
function getUser($user_id, $pdo)
{
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// Get current user
function currentUser($pdo)
{
    if (isLoggedIn()) {
        return getUser($_SESSION['user_id'], $pdo);
    }
    return null;
}

// Add points to user
function addPoints($user_id, $points, $pdo)
{
    $stmt = $pdo->prepare("UPDATE users SET total_points = total_points + ? WHERE id = ?");
    $stmt->execute([$points, $user_id]);
}

// Update streak
function updateStreak($user_id, $pdo)
{
    // Check if last_activity column exists, if not add it
    try {
        $stmt = $pdo->prepare("SELECT streak_days, last_activity FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!$user) return;

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        // If last_activity is NULL or not set, initialize it
        if (!isset($user['last_activity']) || $user['last_activity'] == $yesterday) {
            $stmt = $pdo->prepare("UPDATE users SET streak_days = streak_days + 1, last_activity = ? WHERE id = ?");
            $stmt->execute([$today, $user_id]);
        } elseif ($user['last_activity'] != $today) {
            $stmt = $pdo->prepare("UPDATE users SET streak_days = 1, last_activity = ? WHERE id = ?");
            $stmt->execute([$today, $user_id]);
        }
    } catch (PDOException $e) {
        // If column doesn't exist, skip streak update
        error_log("Streak update error: " . $e->getMessage());
    }
}

// Get all feed posts - FIXED VERSION
function getFeedPosts($pdo, $limit = 10, $offset = 0) {
    try {
        $limit = (int)$limit;
        $offset = (int)$offset;
        
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
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error in getFeedPosts: " . $e->getMessage());
        return [];
    }
}

// Alternative version using bindValue (more secure but requires casting)
function getFeedPostsSafe($pdo, $limit = 10, $offset = 0)
{
    try {
        $sql = "
            SELECT fp.*, u.name, u.avatar, u.role,
                (SELECT COUNT(*) FROM comments WHERE post_id = fp.id) as comment_count
            FROM feed_posts fp
            JOIN users u ON fp.user_id = u.id
            ORDER BY fp.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);
        // Bind as integers using PDO::PARAM_INT
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error in getFeedPostsSafe: " . $e->getMessage());
        return [];
    }
}

// Get all notes with filters
function getNotes($pdo, $subject = null, $semester = null)
{
    try {
        $sql = "SELECT n.*, u.name, u.avatar FROM notes n JOIN users u ON n.user_id = u.id WHERE 1=1";
        $params = [];

        if ($subject && $subject != 'all') {
            $sql .= " AND n.subject = ?";
            $params[] = $subject;
        }
        if ($semester && $semester != 'all') {
            $sql .= " AND n.semester = ?";
            $params[] = $semester;
        }

        $sql .= " ORDER BY n.downloads_count DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error in getNotes: " . $e->getMessage());
        return [];
    }
}

// Get single post with comments
function getPostWithComments($post_id, $pdo)
{
    try {
        // Get post
        $stmt = $pdo->prepare("
            SELECT fp.*, u.name, u.avatar, u.role 
            FROM feed_posts fp 
            JOIN users u ON fp.user_id = u.id 
            WHERE fp.id = ?
        ");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch();

        if (!$post) return null;

        // Get comments
        $stmt = $pdo->prepare("
            SELECT c.*, u.name, u.avatar 
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.post_id = ? 
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$post_id]);
        $post['comments'] = $stmt->fetchAll();

        return $post;
    } catch (PDOException $e) {
        error_log("Error in getPostWithComments: " . $e->getMessage());
        return null;
    }
}

// Add comment to post
function addComment($post_id, $user_id, $content, $pdo)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        return $stmt->execute([$post_id, $user_id, $content]);
    } catch (PDOException $e) {
        error_log("Error in addComment: " . $e->getMessage());
        return false;
    }
}

// Toggle like on post
function toggleLike($post_id, $user_id, $pdo)
{
    try {
        // Check if already liked
        $stmt = $pdo->prepare("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
        $liked = $stmt->fetch();

        if ($liked) {
            // Unlike
            $stmt = $pdo->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
            $stmt->execute([$post_id, $user_id]);
            $stmt = $pdo->prepare("UPDATE feed_posts SET likes_count = likes_count - 1 WHERE id = ?");
            $stmt->execute([$post_id]);
            return ['liked' => false, 'likes_count' => getLikeCount($post_id, $pdo)];
        } else {
            // Like
            $stmt = $pdo->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
            $stmt->execute([$post_id, $user_id]);
            $stmt = $pdo->prepare("UPDATE feed_posts SET likes_count = likes_count + 1 WHERE id = ?");
            $stmt->execute([$post_id]);
            return ['liked' => true, 'likes_count' => getLikeCount($post_id, $pdo)];
        }
    } catch (PDOException $e) {
        error_log("Error in toggleLike: " . $e->getMessage());
        return ['liked' => false, 'likes_count' => 0];
    }
}

// Get like count for post
function getLikeCount($post_id, $pdo)
{
    try {
        $stmt = $pdo->prepare("SELECT likes_count FROM feed_posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $result = $stmt->fetch();
        return $result ? $result['likes_count'] : 0;
    } catch (PDOException $e) {
        return 0;
    }
}

// Check if user liked a post
function userLikedPost($post_id, $user_id, $pdo)
{
    try {
        $stmt = $pdo->prepare("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
        return $stmt->fetch() ? true : false;
    } catch (PDOException $e) {
        return false;
    }
}

// Save item to bookmarks
function saveItem($user_id, $item_type, $item_id, $pdo)
{
    try {
        // Check if already saved
        $stmt = $pdo->prepare("SELECT id FROM saved_items WHERE user_id = ? AND item_type = ? AND item_id = ?");
        $stmt->execute([$user_id, $item_type, $item_id]);
        $saved = $stmt->fetch();

        if ($saved) {
            // Unsaved
            $stmt = $pdo->prepare("DELETE FROM saved_items WHERE user_id = ? AND item_type = ? AND item_id = ?");
            $stmt->execute([$user_id, $item_type, $item_id]);
            return ['saved' => false];
        } else {
            // Save
            $stmt = $pdo->prepare("INSERT INTO saved_items (user_id, item_type, item_id) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $item_type, $item_id]);
            return ['saved' => true];
        }
    } catch (PDOException $e) {
        error_log("Error in saveItem: " . $e->getMessage());
        return ['saved' => false];
    }
}

// Get saved items for user
function getSavedItems($user_id, $pdo)
{
    try {
        $stmt = $pdo->prepare("
            SELECT si.*, 
                CASE 
                    WHEN si.item_type = 'post' THEN (SELECT title FROM feed_posts WHERE id = si.item_id)
                    WHEN si.item_type = 'note' THEN (SELECT title FROM notes WHERE id = si.item_id)
                    WHEN si.item_type = 'event' THEN (SELECT title FROM events WHERE id = si.item_id)
                END as title
            FROM saved_items si
            WHERE si.user_id = ?
            ORDER BY si.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error in getSavedItems: " . $e->getMessage());
        return [];
    }
}

// Search functionality
function searchContent($query, $pdo)
{
    try {
        $searchTerm = "%{$query}%";

        // Search in feed posts
        $stmt = $pdo->prepare("
            SELECT id, title, content, 'post' as type, created_at 
            FROM feed_posts 
            WHERE title LIKE ? OR content LIKE ?
            UNION
            SELECT id, title, description as content, 'doubt' as type, created_at 
            FROM doubts 
            WHERE title LIKE ? OR description LIKE ?
            UNION
            SELECT id, title, '' as content, 'note' as type, created_at 
            FROM notes 
            WHERE title LIKE ?
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error in searchContent: " . $e->getMessage());
        return [];
    }
}
