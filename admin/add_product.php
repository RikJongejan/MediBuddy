<?php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

// Check if user is admin
requireAdmin();

// Initialize variables
$error = null;
$success = false;
$formData = [
    'name' => '',
    'price' => '',
    'description' => '',
    'category_id' => '',
    'stock_quantity' => '',
    'image' => '',
    'featured' => 0,
    'contents' => '',
    'dosage' => ''
];

// Get categories for dropdown
$categoriesQuery = "SELECT * FROM categories ORDER BY name";
$categoriesResult = $conn->query($categoriesQuery);
$categories = [];
while ($category = $categoriesResult->fetch_assoc()) {
    $categories[] = $category;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    // Save form data to variable to repopulate form on error
    foreach ($formData as $key => $value) {
        if (isset($_POST[$key])) {
            $formData[$key] = $_POST[$key];
        }
    }
    
    // Basic validation
    if (empty($formData['name']) || empty($formData['price']) || empty($formData['description']) || empty($formData['category_id'])) {
        $error = "Please fill in all required fields.";
    } else {
        // Handle image upload
        $target_dir = "../uploads/";
        $default_image = "https://via.placeholder.com/300x300.png?text=No+Image";
        $image_path = $default_image; // Default image
        
        if (isset($_FILES["product_image"]) && $_FILES["product_image"]["error"] == 0) {
            $file_ext = pathinfo($_FILES["product_image"]["name"], PATHINFO_EXTENSION);
            $allowed_types = ["jpg", "jpeg", "png", "gif"];
            
            if (in_array(strtolower($file_ext), $allowed_types)) {
                $new_filename = uniqid() . '.' . $file_ext;
                $target_file = $target_dir . $new_filename;
                
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
                    $image_path = "uploads/" . $new_filename;
                } else {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "Invalid file type. Allowed types: JPG, JPEG, PNG, GIF.";
            }
        }
        
        if (!$error) {
            // Prepare data for insertion
            $name = sanitizeInput($conn, $formData['name']);
            $price = floatval($formData['price']);
            $description = sanitizeInput($conn, $formData['description']);
            $category_id = intval($formData['category_id']);
            $stock_quantity = intval($formData['stock_quantity']);
            $featured = isset($formData['featured']) ? 1 : 0;
            $contents = sanitizeInput($conn, $formData['contents']);
            $dosage = sanitizeInput($conn, $formData['dosage']);
            
            // Insert product
            $query = "INSERT INTO products (name, description, price, category_id, stock_quantity, image, featured, contents, dosage, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssdiisiss", $name, $description, $price, $category_id, $stock_quantity, $image_path, $featured, $contents, $dosage);
            
            if ($stmt->execute()) {
                $success = true;
                // Reset form data after successful submission
                $formData = [
                    'name' => '',
                    'price' => '',
                    'description' => '',
                    'category_id' => '',
                    'stock_quantity' => '',
                    'image' => '',
                    'featured' => 0,
                    'contents' => '',
                    'dosage' => ''
                ];
            } else {
                $error = "Failed to add product: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - MediBuddy Admin</title>
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
                <a href="products.php" class="menu-item active">
                    <i class="fas fa-pills"></i> Products
                </a>
                <a href="categories.php" class="menu-item">
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
                <h1 class="admin-title" data-aos="fade-right">Add New Product</h1>
                <div class="admin-actions" data-aos="fade-left">
                    <a href="products.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Products</a>
                </div>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success" data-aos="fade-up">
                    Product added successfully. <a href="products.php">View all products</a>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error" data-aos="fade-up">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="admin-card" data-aos="fade-up">
                <div class="card-header">
                    <h2 class="card-title">Product Information</h2>
                </div>
                
                <form method="post" action="" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name" class="form-label">Product Name*</label>
                            <input type="text" id="name" name="name" class="form-input" required value="<?php echo htmlspecialchars($formData['name']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="price" class="form-label">Price ($)*</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" class="form-input" required value="<?php echo htmlspecialchars($formData['price']); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category_id" class="form-label">Category*</label>
                            <select id="category_id" name="category_id" class="form-input" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo ($formData['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="stock_quantity" class="form-label">Stock Quantity*</label>
                            <input type="number" id="stock_quantity" name="stock_quantity" min="0" class="form-input" required value="<?php echo htmlspecialchars($formData['stock_quantity']); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Description*</label>
                        <textarea id="description" name="description" rows="5" class="form-input" required><?php echo htmlspecialchars($formData['description']); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contents" class="form-label">Contents</label>
                            <input type="text" id="contents" name="contents" class="form-input" value="<?php echo htmlspecialchars($formData['contents']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="dosage" class="form-label">Dosage</label>
                            <input type="text" id="dosage" name="dosage" class="form-input" value="<?php echo htmlspecialchars($formData['dosage']); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_image" class="form-label">Product Image</label>
                        <input type="file" id="product_image" name="product_image" class="form-input" accept=".jpg, .jpeg, .png, .gif">
                        <small>Recommended size: 600x600 pixels. Max file size: 2MB.</small>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <div class="remember-me">
                            <input type="checkbox" id="featured" name="featured" value="1" <?php echo $formData['featured'] ? 'checked' : ''; ?>>
                            <label for="featured">Featured Product</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                        <a href="products.php" class="btn">Cancel</a>
                    </div>
                </form>
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
            
            // Image preview
            const fileInput = document.getElementById('product_image');
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            let previewContainer = document.querySelector('.image-preview-container');
                            
                            if (!previewContainer) {
                                previewContainer = document.createElement('div');
                                previewContainer.className = 'image-preview-container';
                                previewContainer.style.marginTop = '10px';
                                fileInput.parentElement.appendChild(previewContainer);
                            }
                            
                            previewContainer.innerHTML = `
                                <h4 style="margin-bottom: 10px;">Image Preview:</h4>
                                <img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 5px;">
                            `;
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
    </script>
</body>
</html>
