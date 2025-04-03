<?php
session_start();

// Check if already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once "db_connect.php";
$error = "";

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    
    // Validate credentials - MODIFIED to get is_admin flag
    $sql = "SELECT id, username, password, is_admin FROM users WHERE username = ?";
    if($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        
        if($stmt->num_rows == 1) {
            $stmt->bind_result($id, $username, $hashed_password, $is_admin);
            if($stmt->fetch()) {
                if(password_verify($password, $hashed_password)) {
                    // Password is correct, start a new session
                    $_SESSION["user_id"] = $id;
                    $_SESSION["username"] = $username;
                    $_SESSION["is_admin"] = $is_admin; // Store admin status in session
                    
                    // Redirect to home page
                    header("location: index.php");
                    exit;
                } else {
                    $error = "Invalid username or password";
                }
            }
        } else {
            $error = "Invalid username or password";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MediBuddy</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <!-- Header - Site navigatie en gebruikersinterface-elementen -->
    <header class="site-header">
        <div class="container header-container">
            <div class="logo-container">
                <a href="index.php"><span class="logo">MediBuddy</span></a>
            </div>
            <div class="search-bar">
                <input type="text" class="search-input" placeholder="Search for medicines...">
                <span class="search-icon">🔍</span>
            </div>
            <div class="nav-icons">
                <button id="theme-toggle" class="theme-toggle" aria-label="Toggle dark mode">
                    <span id="theme-icon" class="theme-icon">🌙</span>
                </button>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="user-menu">
                        <span class="icon user-icon active">👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <a href="logout.php" class="logout-btn">Logout</a>
                    </div>
                <?php else: ?>
                    <span class="icon user-icon active">👤</span>
                <?php endif; ?>
                <div class="icon cart-icon">
                    <span>🛒</span>
                    <span class="cart-badge">3</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Inloggedeelte - Formulier voor gebruikersauthenticatie -->
    <section class="login-section">
        <div class="container">
            <div class="login-container">
                <div class="login-form-container">
                    <h1 class="login-title">Login</h1>
                    <p class="login-subtitle">Welcome back! Log in to order your medicines.</p>
                    
                    <?php if(!empty($error)): ?>
                    <div class="error-alert">
                        <?php echo $error; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Inlogformulier met PHP verwerking -->
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="login-form">
                        <div class="form-group">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" id="username" name="username" class="form-input" placeholder="Your username" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <div class="password-input-container">
                                <input type="password" id="password" name="password" class="form-input" placeholder="Your password" required>
                                <button type="button" id="toggle-password" class="toggle-password">👁️</button>
                            </div>
                        </div>
                        
                        <!-- Removed remember me checkbox as requested -->
                        <div class="form-options">
                            <a href="#" class="forgot-password">Forgot password?</a>
                        </div>
                        
                        <button type="submit" class="btn btn-primary login-btn">Login</button>
                    </form>
                    
                    <div class="divider">
                        <span>or</span>
                    </div>
                    
                    <!-- Link naar registratie voor nieuwe gebruikers -->
                    <p class="register-prompt">
                        Don't have an account? <a href="signup.php" class="register-link">Register now</a>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer - Consistente voettekst voor alle pagina's -->
    <footer class="footer">
        <div class="container footer-grid">
            <div>
                <h3 class="footer-title">MediBuddy</h3>
                <p class="footer-text">Your reliable online pharmacy for all health needs.</p>
            </div>
            <div>
                <h3 class="footer-subtitle">Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="#" class="footer-link">About Us</a></li>
                    <li><a href="#" class="footer-link">Contact</a></li>
                    <li><a href="#" class="footer-link">Blog</a></li>
                    <li><a href="#" class="footer-link">FAQs</a></li>
                </ul>
            </div>
            <div>
                <h3 class="footer-subtitle">Policies</h3>
                <ul class="footer-links">
                    <li><a href="#" class="footer-link">Privacy Policy</a></li>
                    <li><a href="#" class="footer-link">Return Policy</a></li>
                </ul>
            </div>
            <div>
                <h3 class="footer-subtitle">Follow Us</h3>
                <p class="footer-text">📘 Facebook | 🐦 Twitter | 📸 Instagram | 💼 LinkedIn</p>
            </div>
        </div>
        <div class="footer-copyright">© 2025 MediBuddy. All rights reserved.</div>
    </footer>
    
    <!-- JavaScript only for theme and password visibility -->
    <script src="js/auth.js"></script>
</body>
</html>
