<?php
session_start();

// Check if already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once "db_connect.php";
$errors = [];

if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username
    $username = $conn->real_escape_string($_POST['username']);
    if(empty($username)) {
        $errors[] = "Please enter a username";
    } elseif(!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers, and underscores";
    } else {
        // Check if username exists
        $sql = "SELECT id FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if($stmt->num_rows > 0) {
            $errors[] = "Username already taken";
        }
        $stmt->close();
    }
    
    // Validate email
    $email = $conn->real_escape_string($_POST['email']);
    if(empty($email)) {
        $errors[] = "Please enter an email address";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        // Check if email exists
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if($stmt->num_rows > 0) {
            $errors[] = "Email address already in use";
        }
        $stmt->close();
    }
    
    // Validate password
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    if(empty($password)) {
        $errors[] = "Please enter a password";
    } elseif(strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    // Check if passwords match
    if($password != $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // If no errors, create the user
    if(empty($errors)) {
        // Hash the password - IMPORTANT FOR SECURITY
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $email, $hashed_password);
        
        if($stmt->execute()) {
            // Registration successful, now log in
            $user_id = $conn->insert_id;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Registration failed. Please try again.";
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
    <title>Register - MediBuddy</title>
    <link rel="stylesheet" href="Css/main.css">
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
                <span class="icon user-icon active">👤</span>
                <div class="icon cart-icon">
                    <span>🛒</span>
                    <span class="cart-badge">3</span>
                </div>
            </div>
        </div>
    </header>
 
    <!-- Registratiesectie - Formulier voor nieuwe gebruikersaccounts -->
    <section class="login-section">
        <div class="container">
            <div class="login-container signup-container">
                <div class="login-form-container">
                    <h1 class="login-title">Register</h1>
                    <p class="login-subtitle">Create an account and order your medicines right away!</p>
                   
                    <?php if(!empty($errors)): ?>
                        <div class="error-message">
                            <ul>
                                <?php foreach($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Registratieformulier met uitgebreide veldvalidatie -->
                    <form id="signup-form" class="login-form" method="post" action="">
                        <!-- Gebruikersnaam veld voor persoonlijke identificatie -->
                        <div class="form-group">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" id="username" name="username" class="form-input" placeholder="Your username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>
 
                        <!-- E-mailveld voor account en communicatie -->
                        <div class="form-group">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" id="email" name="email" class="form-input" placeholder="your@email.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                       
                        <!-- Wachtwoordveld met zichtbaarheidstoggle voor veiligheid -->
                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <div class="password-input-container">
                                <input type="password" id="password" name="password" class="form-input" placeholder="Your password" required>
                                <button type="button" id="toggle-password" class="toggle-password">👁️</button>
                            </div>
                        </div>
 
                        <!-- Wachtwoordbevestiging om typefouten te voorkomen -->
                        <div class="form-group">
                            <label for="confirm-password" class="form-label">Confirm password</label>
                            <div class="password-input-container">
                                <input type="password" id="confirm-password" name="confirm_password" class="form-input" placeholder="Confirm password" required>
                                <button type="button" id="toggle-confirm-password" class="toggle-password">👁️</button>
                            </div>
                        </div>
                       
                        <!-- Voorwaardenakkoord voor juridische bescherming -->
                        <div class="terms-container">
                            <div class="remember-me">
                                <input type="checkbox" id="terms" name="terms" required>
                                <label for="terms">I agree to the <a href="#" class="terms-link">terms of service</a></label>
                            </div>
                        </div>
                       
                        <button type="submit" class="btn btn-primary login-btn">Register</button>
                    </form>
                   
                    <div class="divider">
                        <span>or</span>
                    </div>
                    <!-- Link naar inloggen voor bestaande gebruikers -->
                    <p class="register-prompt">
                        Already have an account? <a href="login.php" class="register-link">Log in here</a>
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
   
    <!-- JavaScript - Laadt authenticatielogica voor formulierverwerking -->
    <script src="js/auth.js"></script>
</body>
</html>

