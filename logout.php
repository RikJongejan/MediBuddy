<?php
session_start();

// Log the logout activity if user was logged in
if (isset($_SESSION['user_id'])) {
    require_once 'includes/functions.php';
    logActivity("User logged out: " . $_SESSION['username']);
}

// Clear session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Remove all auth cookies
setcookie('remember_token', '', time() - 3600, '/');
setcookie('user_id', '', time() - 3600, '/');
setcookie('username', '', time() - 3600, '/');
setcookie('is_admin', '', time() - 3600, '/');

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: index.php?logout=success");
exit();
?>
