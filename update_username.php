<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get current username
$user_id = $_SESSION['user_id'];
$sql = "SELECT username FROM users WHERE id = ?";
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Username - Medipil</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Update Username</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo strpos($message, 'success') !== false ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form action="account_actions.php" method="post">
            <div class="form-group">
                <label for="current_username">Current Username</label>
                <input type="text" id="current_username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
            </div>
            
            <div class="form-group">
                <label for="new_username">New Username</label>
                <input type="text" id="new_username" name="new_username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Current Password (for verification)</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <input type="hidden" name="action" value="update_username">
            <button type="submit" class="btn">Update Username</button>
        </form>
        
        <div class="back-link">
            <a href="account.php">Back to Account Management</a>
        </div>
    </div>
</body>
</html>
