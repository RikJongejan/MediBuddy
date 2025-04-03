<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Process messages
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account - Medipil</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Delete Account</h1>
        
        <div class="warning-box">
            <h2>⚠️ Warning</h2>
            <p>This action cannot be undone. All your account data will be permanently deleted.</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo strpos($message, 'success') !== false ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form action="account_actions.php" method="post">
            <div class="form-group">
                <label for="password">Enter Your Password to Confirm</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="confirmation">
                <label>
                    <input type="checkbox" name="confirm_deletion" required>
                    I understand that this action is permanent and cannot be undone
                </label>
            </div>
            
            <input type="hidden" name="action" value="delete_account">
            <button type="submit" class="btn btn-danger">Permanently Delete My Account</button>
        </form>
        
        <div class="back-link">
            <a href="account.php">Cancel and Return to Account Management</a>
        </div>
    </div>
</body>
</html>
