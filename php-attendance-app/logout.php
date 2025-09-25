<?php
session_start();

require_once 'config/database.php';
require_once 'includes/functions.php';

// Log logout activity if user is logged in
if (isset($_SESSION['user_id'])) {
    logActivity($_SESSION['user_id'], 'logout', 'User logged out');
}

// Clear all session data
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Set success message for login page
session_start();
$_SESSION['success_message'] = 'Anda telah berhasil keluar dari sistem.';

// Redirect to login page
header('Location: login.php');
exit();
?>
