<?php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

// Check if user is admin
requireAdmin();

$error = null;
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = sanitizeInput($conn, $_POST['name']);
    $description = sanitizeInput($conn, $_POST['description']);
    $icon = sanitizeInput($conn, $_POST['icon']);
    
    // Check if category name already exists
    $checkQuery = "SELECT id FROM categories WHERE name = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("s", $name);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $error = "A category with this name already exists.";
    } else {
        // Insert new category
        $query = "INSERT INTO categories (name, description, icon) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $name, $description, $icon);
        
        if ($stmt->execute()) {
            $success = true;
            $categoryId = $conn->insert_id;
        } else {
            $error = "Failed to add category: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Category - MediBuddy Admin</title>
    <link rel="stylesheet" href="../css/main.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- AOS Animation Framework CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="container header-container">
            <div class="logo-container">
                <a href="../index.php"><span class="logo">MediBuddy</span></a>
            </div>
            
            <nav class="main-nav">
                <a href="../index.php" class="nav-link">Home</a>
                <a href="../products.php" class="nav-link">Products</a>
                <a href="../categories.php" class="nav-link">Categories</a>
                <a href="dashboard.php" class="nav-link active">Admin Panel</a>
            </nav>
            
            <button class="mobile-menu-button">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="nav-icons">
                <div class="user-menu">
                    <a href="../account.php"><span class="icon user-icon active"><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span></a>
                    <a href="../logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Mobile Navigation Menu -->
    <nav class="mobile-nav">
        <a href="../index.php" class="nav-link">Home</a>
        <a href="../products.php" class="nav-link">Products</a>
        <a href="../categories.php" class="nav-link">Categories</a>
        <a href="dashboard.php" class="nav-link active">Admin Panel</a>
        <a href="../account.php" class="nav-link">Account</a>
    </nav>
    
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <h3 class="sidebar-title">Admin Panel</h3>
            <nav class="admin-menu">
                <a href="dashboard.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="products.php" class="menu-item">
                    <i class="fas fa-pills"></i> Products
                </a>
                <a href="categories.php" class="menu-item active">
                    <i class="fas fa-th-large"></i> Categories
                </a>
                <a href="orders.php" class="menu-item">
                    <i class="fas fa-shopping-bag"></i> Orders
                </a>
                <a href="users.php" class="menu-item">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="tracking.php" class="menu-item">
                    <i class="fas fa-shipping-fast"></i> Order Tracking
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-content">
            <div class="admin-header">
                <h1 class="admin-title" data-aos="fade-right">Add New Category</h1>
                <div class="admin-actions" data-aos="fade-left">
                    <a href="categories.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Categories</a>
                </div>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success" data-aos="fade-up">
                    Category added successfully! <a href="categories.php">Return to category list</a> or add another category below.
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error" data-aos="fade-up">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="admin-card" data-aos="fade-up">
                <div class="card-header">
                    <h2 class="card-title">Category Information</h2>
                </div>
                
                <form method="post" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name" class="form-label">Category Name</label>
                            <input type="text" id="name" name="name" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="icon" class="form-label">Icon (emoji or FontAwesome class)</label>
                            <input type="text" id="icon" name="icon" class="form-input" placeholder="💊 or fa-pills">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" class="form-input" rows="4"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
            
            <div class="admin-card" data-aos="fade-up" style="margin-top: 20px;">
                <div class="card-header">
                    <h2 class="card-title">Available Icons</h2>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; padding: 15px;">
                    <div class="icon-item">💊 - Pills</div>
                    <div class="icon-item">🩹 - Bandage</div>
                    <div class="icon-item">💉 - Syringe</div>
                    <div class="icon-item">🩺 - Stethoscope</div>
                    <div class="icon-item">🧪 - Test Tube</div>
                    <div class="icon-item">🧬 - DNA</div>
                    <div class="icon-item">🦠 - Microbe</div>
                    <div class="icon-item">🧫 - Petri Dish</div>
                    <div class="icon-item">🧴 - Lotion</div>
                    <div class="icon-item">❤️ - Heart</div>
                    <div class="icon-item">🫀 - Anatomical Heart</div>
                    <div class="icon-item">🫁 - Lungs</div>
                    <div class="icon-item">🧠 - Brain</div>
                    <div class="icon-item">👁️ - Eye</div>
                    <div class="icon-item">🩸 - Drop of Blood</div>
                    <div class="icon-item">🦷 - Tooth</div>
                    <div class="icon-item">🦴 - Bone</div>
                    <div class="icon-item">🌡️ - Thermometer</div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Footer -->
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
                <p class="footer-text">
                    <i class="fab fa-facebook"></i> Facebook | 
                    <i class="fab fa-twitter"></i> Twitter | 
                    <i class="fab fa-instagram"></i> Instagram | 
                    <i class="fab fa-linkedin"></i> LinkedIn
                </p>
            </div>
        </div>
        <div class="footer-copyright">© 2025 MediBuddy. All rights reserved.</div>
    </footer>
    
    <!-- JavaScript -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });
            
            // Mobile menu toggle
            const mobileMenuButton = document.querySelector('.mobile-menu-button');
            const mobileNav = document.querySelector('.mobile-nav');
            
            if (mobileMenuButton && mobileNav) {
                mobileMenuButton.addEventListener('click', function() {
                    mobileNav.classList.toggle('active');
                });
            }
        });
    </script>
</body>
</html>
