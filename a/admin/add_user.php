<?php
// Start session at the beginning
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit();
}

$current_page = 'admin';
$current_admin_page = 'users';
$page_title = 'Add User';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($conn, $_POST['username']);
    $email = sanitizeInput($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = sanitizeInput($conn, $_POST['first_name']);
    $last_name = sanitizeInput($conn, $_POST['last_name']);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if username or email already exists
        $checkQuery = "SELECT id FROM users WHERE username = ? OR email = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("ss", $username, $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $error = "Username or email already exists.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $insertQuery = "INSERT INTO users (username, email, password, first_name, last_name, is_admin, is_active, register_date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("sssssii", $username, $email, $hashed_password, $first_name, $last_name, $is_admin, $is_active);
            
            if ($insertStmt->execute()) {
                $success = "User added successfully!";
                // Clear form data
                $username = $email = $first_name = $last_name = '';
            } else {
                $error = "Error adding user: " . $conn->error;
            }
        }
    }
}

// Add CSS for admin panel
$extra_css = '<link rel="stylesheet" href="../css/admin.css">';

require_once '../includes/admin_header.php';
?>

<div class="admin-header">
    <h1 class="admin-title" data-aos="fade-right">Add New User</h1>
    <div class="admin-actions" data-aos="fade-left">
        <a href="users.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Users</a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error" data-aos="fade-up">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success" data-aos="fade-up">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
    </div>
<?php endif; ?>

<div class="admin-card" data-aos="fade-up">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-user-plus"></i> User Information</h2>
    </div>
    
    <div class="card-body">
        <form action="add_user.php" method="post" class="form-container">
            <div class="form-row">
                <div class="form-group">
                    <label for="username" class="form-label">Username *</label>
                    <input type="text" id="username" name="username" class="form-control" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
                    <div class="form-help">Choose a unique username for the account.</div>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address *</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password" class="form-label">Password *</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                    <div class="form-help">Use a strong password with at least 8 characters.</div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo isset($first_name) ? htmlspecialchars($first_name) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo isset($last_name) ? htmlspecialchars($last_name) : ''; ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">User Role</label>
                    <div class="form-check">
                        <input type="checkbox" id="is_admin" name="is_admin" class="form-check-input" <?php echo isset($is_admin) && $is_admin ? 'checked' : ''; ?>>
                        <label for="is_admin" class="form-check-label">Administrator</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Account Status</label>
                    <div class="form-check">
                        <input type="checkbox" id="is_active" name="is_active" class="form-check-input" <?php echo !isset($is_active) || $is_active ? 'checked' : ''; ?>>
                        <label for="is_active" class="form-check-label">Active</label>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save User</button>
                <a href="users.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
