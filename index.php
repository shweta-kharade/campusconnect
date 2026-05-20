<?php
// Redirect to feed or login based on session
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: pages/feed.php');
} else {
    header('Location: auth/login.php');
}
exit();
?>