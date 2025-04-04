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
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">MediBuddy</div>
            <nav>
                <a href="index.php">Home</a>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            </nav>
        </div>
    </header>
    
    <main class="container">
        <div class="form-container">
            <h2>Create an Account</h2>
            
            <?php if(!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn primary" style="width:100%">Register</button>
                </div>
            </form>
            <p class="text-center">Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </main>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> MediBuddy. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
