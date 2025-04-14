<?php
// Start session at the very beginning of the file
session_start();

$current_page = 'admin';
$current_admin_page = 'users';
$page_title = 'Edit User';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit();
}

$error = '';
$success = '';
$warning = '';

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$user_id = intval($_GET['id']);

// Get user details
$userQuery = "SELECT * FROM users WHERE id = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();

if ($userResult->num_rows === 0) {
    header("Location: users.php");
    exit();
}

$user = $userResult->fetch_assoc();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($conn, $_POST['username']);
    $email = sanitizeInput($conn, $_POST['email']);
    $first_name = sanitizeInput($conn, $_POST['first_name']);
    $last_name = sanitizeInput($conn, $_POST['last_name']);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate input
    if (empty($username) || empty($email)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if username or email already exists for other users
        $checkQuery = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("ssi", $username, $email, $user_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $error = "Username or email already exists for another user.";
        } else {
            // Don't let non-admin users change admin status
            if (!$_SESSION['is_admin'] && $is_admin != $user['is_admin']) {
                $is_admin = $user['is_admin'];
            }
            
            // Don't let user deactivate themselves
            if ($user_id == $_SESSION['user_id'] && !$is_active) {
                $is_active = 1;
                $warning = "You cannot deactivate your own account.";
            }
            
            // Update user details
            $updateQuery = "UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, is_admin = ?, is_active = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("ssssiii", $username, $email, $first_name, $last_name, $is_admin, $is_active, $user_id);
            
            if ($updateStmt->execute()) {
                $success = "User updated successfully!";
                
                // Update the user array with new values
                $user['username'] = $username;
                $user['email'] = $email;
                $user['first_name'] = $first_name;
                $user['last_name'] = $last_name;
                $user['is_admin'] = $is_admin;
                $user['is_active'] = $is_active;
            } else {
                $error = "Error updating user: " . $conn->error;
            }
            
            // Process password update if provided
            if (!empty($_POST['password']) && !empty($_POST['confirm_password'])) {
                if ($_POST['password'] !== $_POST['confirm_password']) {
                    $error = "Passwords do not match.";
                } else {
                    $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    
                    $pwdQuery = "UPDATE users SET password = ? WHERE id = ?";
                    $pwdStmt = $conn->prepare($pwdQuery);
                    $pwdStmt->bind_param("si", $hashed_password, $user_id);
                    
                    if ($pwdStmt->execute()) {
                        $success = "User updated successfully with new password!";
                    } else {
                        $error = "Error updating password: " . $conn->error;
                    }
                }
            }
        }
    }
}

// Add CSS for admin panel
$extra_css = '<link rel="stylesheet" href="../css/admin.css">';

$page_title = 'Edit User: ' . htmlspecialchars($user['username']);
require_once '../includes/admin_header.php';
?>

<div class="admin-header">
    <h1 class="admin-title" data-aos="fade-right">Edit User: <?php echo htmlspecialchars($user['username']); ?></h1>
    <div class="admin-actions" data-aos="fade-left">
        <a href="users.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Users</a>
        <a href="view_user.php?id=<?php echo $user_id; ?>" class="btn btn-primary"><i class="fas fa-eye"></i> View Profile</a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error" data-aos="fade-up">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if (!empty($warning)): ?>
    <div class="alert alert-warning" data-aos="fade-up">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $warning; ?>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success" data-aos="fade-up">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
    </div>
<?php endif; ?>

<div class="admin-card" data-aos="fade-up">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-user-edit"></i> User Information</h2>
    </div>
    
    <div class="card-body">
        <form action="edit_user.php?id=<?php echo $user_id; ?>" method="post" class="form-container">
            <div class="form-row">
                <div class="form-group">
                    <label for="username" class="form-label">Username *</label>
                    <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address *</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password" class="form-label">New Password</label>
                    <input type="password" id="password" name="password" class="form-control">
                    <div class="form-help">Leave blank to keep current password.</div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">User Role</label>
                    <div class="form-check">
                        <input type="checkbox" id="is_admin" name="is_admin" class="form-check-input" <?php echo $user['is_admin'] ? 'checked' : ''; ?> 
                            <?php echo ($user_id == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                        <label for="is_admin" class="form-check-label">Administrator</label>
                        <?php if ($user_id == $_SESSION['user_id']): ?>
                            <input type="hidden" name="is_admin" value="1">
                            <div class="form-help">You cannot change your own admin status.</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Account Status</label>
                    <div class="form-check">
                        <input type="checkbox" id="is_active" name="is_active" class="form-check-input" <?php echo $user['is_active'] ? 'checked' : ''; ?>
                            <?php echo ($user_id == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                        <label for="is_active" class="form-check-label">Active</label>
                        <?php if ($user_id == $_SESSION['user_id']): ?>
                            <input type="hidden" name="is_active" value="1">
                            <div class="form-help">You cannot deactivate your own account.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update User</button>
                <a href="users.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
