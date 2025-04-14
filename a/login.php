<?php
$current_page = 'login';
$page_title = 'Login';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// Include auth.php directly here to ensure it exists
if (file_exists('includes/auth.php')) {
    require_once 'includes/auth.php';
} else {
    // If auth.php doesn't exist yet, session handling is done here
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// If user is already logged in, redirect to account page
if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: index.php");
    exit();
}

$error = null;
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        // For development/testing, allow a hardcoded login
        if ($username === 'admin' && $password === 'admin123') {
            // Set session variables for test admin
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = 'admin';
            $_SESSION['full_name'] = 'Admin User';
            $_SESSION['is_admin'] = true;
            $_SESSION['logged_in'] = true;
            
            // Set cookies if remember me is checked
            if ($remember) {
                // Set cookies for 30 days
                setcookie('user_id', 1, time() + 30*24*60*60, '/');
                setcookie('username', 'admin', time() + 30*24*60*60, '/');
                setcookie('is_admin', '1', time() + 30*24*60*60, '/');
                setcookie('remember_token', md5('admin_token'), time() + 30*24*60*60, '/');
                $_SESSION['remember_token'] = md5('admin_token');
            }
            
            // Redirect to homepage or requested page
            header("Location: " . $redirect);
            exit();
        } else if ($username === 'user' && $password === 'user123') {
            // Set session variables for test user
            $_SESSION['user_id'] = 2;
            $_SESSION['username'] = 'user';
            $_SESSION['full_name'] = 'Regular User';
            $_SESSION['is_admin'] = false;
            $_SESSION['logged_in'] = true;
            
            // Set cookies if remember me is checked
            if ($remember) {
                // Set cookies for 30 days
                setcookie('user_id', 2, time() + 30*24*60*60, '/');
                setcookie('username', 'user', time() + 30*24*60*60, '/');
                setcookie('is_admin', '0', time() + 30*24*60*60, '/');
                setcookie('remember_token', md5('user_token'), time() + 30*24*60*60, '/');
                $_SESSION['remember_token'] = md5('user_token');
            }
            
            // Redirect to homepage or requested page
            header("Location: " . $redirect);
            exit();
        }
        
        // Check if user exists in database (uncomment when DB is set up)
        /* 
        $query = "SELECT id, username, password, is_admin, first_name, last_name, is_active FROM users WHERE (username = ? OR email = ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if ($user['is_active']) {
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['is_admin'] = $user['is_admin'] ? true : false;
                    $_SESSION['logged_in'] = true;
                    
                    // Set cookies if remember me is checked
                    if ($remember) {
                        // Create a secure token
                        $token = bin2hex(random_bytes(16));
                        
                        // Set cookies for 30 days
                        setcookie('user_id', $user['id'], time() + 30*24*60*60, '/');
                        setcookie('username', $user['username'], time() + 30*24*60*60, '/');
                        setcookie('is_admin', $user['is_admin'] ? '1' : '0', time() + 30*24*60*60, '/');
                        setcookie('remember_token', $token, time() + 30*24*60*60, '/');
                        $_SESSION['remember_token'] = $token;
                        
                        // In a real app, store the token in the database
                        // $stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                        // $stmt->bind_param("si", $token, $user['id']);
                        // $stmt->execute();
                    }
                    
                    header("Location: " . $redirect);
                    exit();
                } else {
                    $error = "Invalid password.";
                }
            } else {
                $error = "Your account is not active. Please contact support.";
            }
        } else {
            $error = "User not found.";
        }
        */
        
        // If no hardcoded login or DB login matched
        $error = "Invalid username or password. Try admin/admin123 or user/user123 for testing.";
    }
}

// Include header
require_once 'includes/header.php';
?>

<!-- Login Section -->
<section class="login-section">
    <div class="container">
        <div class="login-container" data-aos="fade-up">
            <div class="login-form-container">
                <h1 class="login-title">Welcome Back</h1>
                <p class="login-subtitle">Log in to access your account.</p>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form action="" method="post" class="login-form">
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                    
                    <div class="form-group">
                        <label for="username" class="form-label">Username or Email</label>
                        <input type="text" id="username" name="username" class="form-input" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="password-input-container">
                            <input type="password" id="password" name="password" class="form-input" required>
                            <button type="button" class="toggle-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember" checked>
                            <span>Remember me</span>
                        </label>
                        <a href="forgot_password.php" class="forgot-password">Forgot password?</a>
                    </div>
                    
                    <button type="submit" name="login" class="btn btn-primary login-btn">Log In</button>
                </form>
                
                <div class="divider">
                    <span>OR</span>
                </div>
                
                <div class="register-prompt">
                    Don't have an account? <a href="register.php<?php echo !empty($redirect) ? '?redirect=' . urlencode($redirect) : ''; ?>" class="register-link">Create one now</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php 
$extra_js = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Toggle password visibility
    const togglePasswordButton = document.querySelector(".toggle-password");
    
    if (togglePasswordButton) {
        togglePasswordButton.addEventListener("click", function() {
            const input = this.parentNode.querySelector("input");
            const type = input.getAttribute("type") === "password" ? "text" : "password";
            input.setAttribute("type", type);
            
            // Toggle eye icon
            const eyeIcon = this.querySelector("i");
            eyeIcon.classList.toggle("fa-eye");
            eyeIcon.classList.toggle("fa-eye-slash");
        });
    }
});
</script>';
require_once 'includes/footer.php'; 
?>
