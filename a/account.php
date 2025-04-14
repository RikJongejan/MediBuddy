<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// Redirect to login if not authenticated
requireLogin();

$userId = $_SESSION['user_id'];

// Get user information
$userQuery = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();

// Get user orders with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

$orderCountQuery = "SELECT COUNT(*) as total FROM orders WHERE user_id = ?";
$countStmt = $conn->prepare($orderCountQuery);
$countStmt->bind_param("i", $userId);
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalOrders = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalOrders / $limit);

$ordersQuery = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT ?, ?";
$orderStmt = $conn->prepare($ordersQuery);
$orderStmt->bind_param("iii", $userId, $offset, $limit);
$orderStmt->execute();
$ordersResult = $orderStmt->get_result();

// Process account update if submitted
$updateSuccess = false;
$updateError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
    $firstName = sanitizeInput($conn, $_POST['first_name']);
    $lastName = sanitizeInput($conn, $_POST['last_name']);
    $email = sanitizeInput($conn, $_POST['email']);
    $phone = sanitizeInput($conn, $_POST['phone']);
    $address = sanitizeInput($conn, $_POST['address']);
    $city = sanitizeInput($conn, $_POST['city']);
    $state = sanitizeInput($conn, $_POST['state']);
    $zipcode = sanitizeInput($conn, $_POST['zipcode']);
    
    // Update user information
    $updateQuery = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, city = ?, state = ?, zipcode = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("ssssssssi", $firstName, $lastName, $email, $phone, $address, $city, $state, $zipcode, $userId);
    
    if ($updateStmt->execute()) {
        $updateSuccess = true;
        
        // Refresh user data
        $stmt->execute();
        $userResult = $stmt->get_result();
        $user = $userResult->fetch_assoc();
    } else {
        $updateError = "An error occurred while updating your information. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - MediBuddy</title>
    <link rel="stylesheet" href="css/main.css">
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
                <a href="index.php"><span class="logo">MediBuddy</span></a>
            </div>
            
            <nav class="main-nav">
                <a href="index.php" class="nav-link">Home</a>
                <a href="products.php" class="nav-link">Products</a>
                <a href="categories.php" class="nav-link">Categories</a>
                <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <a href="admin/dashboard.php" class="nav-link">Admin Panel</a>
                <?php endif; ?>
            </nav>
            
            <button class="mobile-menu-button">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="search-bar">
                <input type="text" class="search-input" placeholder="Search for medicines...">
                <span class="search-icon"><i class="fas fa-search"></i></span>
            </div>
            
            <div class="nav-icons">
                <div class="user-menu">
                    <a href="account.php"><span class="icon user-icon active"><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span></a>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
                <a href="cart.php">
                    <div class="icon cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-badge">
                            <?php
                                if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
                                    echo count($_SESSION['cart']);
                                } else {
                                    echo '0';
                                }
                            ?>
                        </span>
                    </div>
                </a>
            </div>
        </div>
    </header>
    
    <!-- Mobile Navigation Menu -->
    <nav class="mobile-nav">
        <a href="index.php" class="nav-link">Home</a>
        <a href="products.php" class="nav-link">Products</a>
        <a href="categories.php" class="nav-link">Categories</a>
        <a href="cart.php" class="nav-link">Cart</a>
        <a href="account.php" class="nav-link active">Account</a>
        <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
            <a href="admin/dashboard.php" class="nav-link">Admin Panel</a>
        <?php endif; ?>
    </nav>
    
    <!-- Breadcrumbs -->
    <div class="breadcrumbs">
        <div class="container">
            <a href="index.php">Home</a> &gt; My Account
        </div>
    </div>
    
    <!-- Account Section -->
    <section class="section">
        <div class="container">
            <h1 class="page-title" data-aos="fade-up">My Account</h1>
            
            <?php if ($updateSuccess): ?>
                <div class="alert alert-success" data-aos="fade-up">
                    Your account information has been updated successfully.
                </div>
            <?php endif; ?>
            
            <?php if ($updateError): ?>
                <div class="alert alert-error" data-aos="fade-up">
                    <?php echo $updateError; ?>
                </div>
            <?php endif; ?>
            
            <!-- Account Tabs -->
            <div class="tab-container" data-aos="fade-up">
                <div class="tab-buttons">
                    <button class="tab-button active" data-tab="orders">My Orders</button>
                    <button class="tab-button" data-tab="profile">Profile Information</button>
                </div>
                
                <!-- Orders Tab -->
                <div class="tab-content active" id="orders-tab">
                    <div class="admin-card">
                        <h2 class="card-title">My Orders</h2>
                        
                        <?php if ($ordersResult->num_rows > 0): ?>
                            <table class="order-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($order = $ordersResult->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                            <td><?php echo formatCurrency($order['total_amount']); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo getStatusClass($order['status']); ?>">
                                                    <?php echo $order['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-small">View</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            
                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <div class="pagination" style="margin-top: 20px; text-align: center;">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?>" class="btn btn-small">Previous</a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <a href="?page=<?php echo $i; ?>" class="btn btn-small <?php echo $i == $page ? 'btn-primary' : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>" class="btn btn-small">Next</a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="empty-orders">
                                <i class="fas fa-shopping-bag empty-cart-icon"></i>
                                <h3>No orders found</h3>
                                <p>You haven't placed any orders yet.</p>
                                <a href="products.php" class="btn btn-primary">Start Shopping</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Profile Tab -->
                <div class="tab-content" id="profile-tab">
                    <div class="admin-card">
                        <h2 class="card-title">Profile Information</h2>
                        
                        <form method="post" action="" class="login-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" id="first_name" name="first_name" class="form-input" required value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" id="last_name" name="last_name" class="form-input" required value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" id="email" name="email" class="form-input" required value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="text" id="phone" name="phone" class="form-input" required value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" id="address" name="address" class="form-input" required value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" id="city" name="city" class="form-input" required value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="state" class="form-label">State/Province</label>
                                    <input type="text" id="state" name="state" class="form-input" required value="<?php echo htmlspecialchars($user['state'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="zipcode" class="form-label">Zipcode</label>
                                    <input type="text" id="zipcode" name="zipcode" class="form-input" required value="<?php echo htmlspecialchars($user['zipcode'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <button type="submit" name="update_account" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
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
            
            // Tab functionality
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons and contents
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Show corresponding tab content
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId + '-tab').classList.add('active');
                });
            });
        });
    </script>
</body>
</html>
