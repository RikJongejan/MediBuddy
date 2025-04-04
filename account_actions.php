<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_username':
            updateUsername($conn, $user_id);
            break;
            
        case 'update_password':
            updatePassword($conn, $user_id);
            break;
            
        case 'delete_account':
            deleteAccount($conn, $user_id);
            break;
            
        default:
            $_SESSION['message'] = "Invalid action requested";
            header('Location: account.php');
            exit();
    }
} else {
    // Redirect if accessed directly without POST
    header('Location: account.php');
    exit();
}

/**
 * Update username function
 */
function updateUsername($conn, $user_id) {
    $new_username = trim($_POST['new_username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($new_username) || empty($password)) {
        $_SESSION['message'] = "All fields are required";
        header('Location: update_username.php');
        exit();
    }
    
    // Username requirements
    if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $new_username)) {
        $_SESSION['message'] = "Username must be 3-50 characters and can only contain letters, numbers, and underscores";
        header('Location: update_username.php');
        exit();
    }
    
    // Check if new username already exists
    $check_sql = "SELECT id FROM users WHERE username = ? AND id != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("si", $new_username, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $_SESSION['message'] = "Username already exists. Please choose another one.";
        header('Location: update_username.php');
        exit();
    }
    
    // Verify password
    $verify_sql = "SELECT password FROM users WHERE id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("i", $user_id);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user || !password_verify($password, $user['password'])) {
        $_SESSION['message'] = "Incorrect password. Username not updated.";
        header('Location: update_username.php');
        exit();
    }
    
    // Update username
    $update_sql = "UPDATE users SET username = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $new_username, $user_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['message'] = "Username updated successfully to " . htmlspecialchars($new_username);
        header('Location: account.php');
        exit();
    } else {
        $_SESSION['message'] = "Error updating username: " . $conn->error;
        header('Location: update_username.php');
        exit();
    }
}

/**
 * Update password function
 */
function updatePassword($conn, $user_id) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['message'] = "All fields are required";
        header('Location: update_password.php');
        exit();
    }
    
    // Check if new passwords match
    if ($new_password !== $confirm_password) {
        $_SESSION['message'] = "New passwords do not match";
        header('Location: update_password.php');
        exit();
    }
    
    // Password strength requirements
    if (strlen($new_password) < 8) {
        $_SESSION['message'] = "Password must be at least 8 characters";
        header('Location: update_password.php');
        exit();
    }
    
    // Verify current password
    $verify_sql = "SELECT password FROM users WHERE id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("i", $user_id);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user || !password_verify($current_password, $user['password'])) {
        $_SESSION['message'] = "Current password is incorrect";
        header('Location: update_password.php');
        exit();
    }
    
    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password
    $update_sql = "UPDATE users SET password = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $hashed_password, $user_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['message'] = "Password updated successfully";
        header('Location: account.php');
        exit();
    } else {
        $_SESSION['message'] = "Error updating password: " . $conn->error;
        header('Location: update_password.php');
        exit();
    }
}

/**
 * Delete account function
 */
function deleteAccount($conn, $user_id) {
    $password = $_POST['password'] ?? '';
    $confirm_deletion = isset($_POST['confirm_deletion']);
    
    // Validate inputs
    if (empty($password) || !$confirm_deletion) {
        $_SESSION['message'] = "You must enter your password and confirm deletion";
        header('Location: delete_account.php');
        exit();
    }
    
    // Verify password
    $verify_sql = "SELECT password FROM users WHERE id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("i", $user_id);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user || !password_verify($password, $user['password'])) {
        $_SESSION['message'] = "Incorrect password. Account not deleted.";
        header('Location: delete_account.php');
        exit();
    }
    
    // Delete user account
    $delete_sql = "DELETE FROM users WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $user_id);
    
    if ($delete_stmt->execute()) {
        // Clear session and redirect to home
        session_destroy();
        session_start();
        $_SESSION['message'] = "Your account has been permanently deleted";
        header('Location: index.php');
        exit();
    } else {
        $_SESSION['message'] = "Error deleting account: " . $conn->error;
        header('Location: delete_account.php');
        exit();
    }
}
?>
