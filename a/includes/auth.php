<?php
/**
 * Authentication helper functions
 * Handles session persistence and auto-login from cookies
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in from session or cookie
 * Auto-login if remember me cookie is set
 */
function check_auth() {
    // Already logged in by session
    if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        return true;
    }
    
    // Try to auto-login from cookie
    if (isset($_COOKIE['remember_token']) && !empty($_COOKIE['remember_token'])) {
        // In a real application, you would validate this token against a database
        if (isset($_SESSION['remember_token']) && $_SESSION['remember_token'] === $_COOKIE['remember_token']) {
            // Re-establish the session
            $_SESSION['logged_in'] = true;
            return true;
        } else if (isset($_COOKIE['user_id'])) {
            // Alternative cookie-based login
            $_SESSION['user_id'] = $_COOKIE['user_id'];
            $_SESSION['username'] = isset($_COOKIE['username']) ? $_COOKIE['username'] : 'User';
            $_SESSION['logged_in'] = true;
            
            // Set admin status if stored in cookie
            $_SESSION['is_admin'] = isset($_COOKIE['is_admin']) && $_COOKIE['is_admin'] == '1' ? true : false;
            
            return true;
        }
        
        // If token doesn't match or we don't have the user info, clear the cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    return false;
}

/**
 * Get current user's display name
 */
function get_user_display_name() {
    if (isset($_SESSION['full_name'])) {
        return $_SESSION['full_name'];
    } else if (isset($_SESSION['username'])) {
        return $_SESSION['username'];
    }
    return 'Guest';
}

/**
 * Check if current user is an admin
 */
function is_admin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Require user to be logged in to access a page
 * Redirect to login if not authenticated
 */
function require_login() {
    if (!check_auth()) {
        $_SESSION['error_message'] = "Please log in to access this page.";
        header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
}

/**
 * Require admin privileges to access a page
 * Redirect to login or home page if not an admin
 */
function require_admin() {
    require_login();
    
    if (!is_admin()) {
        $_SESSION['error_message'] = "You need admin privileges to access this page.";
        header("Location: index.php");
        exit();
    }
}

// Check auth on every page load
check_auth();
?>
