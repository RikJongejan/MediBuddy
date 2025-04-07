<?php
session_start();
require_once 'config.php';

// Set page title
$page_title = 'Account Management';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "Please log in to access your account";
    header('Location: login.php');
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$sql = "SELECT username, email FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Process messages
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Include header
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
<link rel="stylesheet" href="css/style.css">


<div class="container">
    <h1>Account Management</h1>
    
    <?php if (!empty($message)): ?>
        <div class="alert <?php echo strpos($message, 'success') !== false ? 'alert-success' : 'alert-danger'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <div class="user-info">
        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
    </div>
    
    <div class="account-options">
        <h2>Account Options</h2>
        
        <div class="option-card">
            <h3>Change Username</h3>
            <p>Update your current username to a new one.</p>
            <a href="update_username.php" class="btn">Change Username</a>
        </div>
        
        <div class="option-card">
            <h3>Change Password</h3>
            <p>Update your password to keep your account secure.</p>
            <a href="update_password.php" class="btn">Change Password</a>
        </div>
        
        <div class="option-card danger">
            <h3>Delete Account</h3>
            <p>Permanently delete your account and all associated data.</p>
            <a href="delete_account.php" class="btn btn-danger">Delete Account</a>
        </div>
    </div>
</div>

</body>
</html>
