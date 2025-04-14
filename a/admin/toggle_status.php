<?php
$current_page = 'admin';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

// Check if user is admin
requireAdmin();

// Check if user ID and action are provided
if (!isset($_GET['id']) || empty($_GET['id']) || !isset($_GET['action']) || empty($_GET['action'])) {
    header("Location: users.php");
    exit();
}

$userId = intval($_GET['id']);
$action = $_GET['action'];

// Make sure user isn't trying to deactivate their own account
if ($userId === $_SESSION['user_id']) {
    $_SESSION['error_message'] = "You cannot change the status of your own account.";
    header("Location: users.php");
    exit();
}

// Update user status based on action
if ($action === 'activate') {
    $stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
    $message = "User activated successfully.";
} elseif ($action === 'deactivate') {
    $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
    $message = "User deactivated successfully.";
} else {
    header("Location: users.php");
    exit();
}

$stmt->bind_param("i", $userId);
$stmt->execute();

if ($conn->affected_rows > 0) {
    $_SESSION['success_message'] = $message;
} else {
    $_SESSION['error_message'] = "Failed to update user status.";
}

header("Location: users.php");
exit();
?>