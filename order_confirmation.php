<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// Redirect to login if not authenticated
requireLogin();

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: account.php?error=invalid_order");
    exit();
}

$orderId = intval($_GET['id']);
$userId = $_SESSION['user_id'];

// Get order details
$orderQuery = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($orderQuery);
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$orderResult = $stmt->get_result();

if ($orderResult->num_rows == 0) {
    header("Location: account.php?error=order_not_found");
    exit();
}

$order = $orderResult->fetch_assoc();

// Get order items
$orderItems = getOrderDetails($conn, $orderId);

// Get order tracking
$trackingHistory = getOrderTracking($conn, $orderId);

// Include CSS
$extra_css = '<link rel="stylesheet" href="css/notifications.css">
              <link rel="stylesheet" href="css/product-page.css">';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - MediBuddy</title>
    <link rel="stylesheet" href="css/main.css">
    <?php echo $extra_css; ?>
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
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="user-menu">
                        <a href="account.php"><span class="icon user-icon active"><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span></a>
                        <a href="logout.php" class="logout-btn">Logout</a>
                    </div>
                <?php else: ?>
                    <a href="login.php"><span class="icon user-icon"><i class="fas fa-user"></i></span></a>
                <?php endif; ?>
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
            <a href="index.php">Home</a> &gt; <a href="account.php">My Account</a> &gt; Order #<?php echo $order['id']; ?>
        </div>
    </div>
    
    <!-- Order Confirmation Section -->
    <section class="section">
        <div class="container">
            <div class="order-confirmation" data-aos="fade-up">
                <div class="confirmation-header">
                    <i class="fas fa-check-circle confirmation-icon"></i>
                    <h1 class="page-title">Order Confirmed!</h1>
                    <p class="confirmation-message">
                        Thank you for your purchase. Your order has been received and is being processed.
                    </p>
                </div>
                
                <!-- Order Details Card -->
                <div class="admin-card" data-aos="fade-up">
                    <div class="card-header">
                        <h2 class="card-title">Order #<?php echo $order['id']; ?></h2>
                        <span class="status-badge <?php echo getStatusClass($order['status']); ?>">
                            <?php echo $order['status']; ?>
                        </span>
                    </div>
                    
                    <div class="order-details-grid">
                        <div class="order-detail-section">
                            <h3>Order Information</h3>
                            <p><strong>Order Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['order_date'])); ?></p>
                            <p><strong>Order Number:</strong> #<?php echo $order['id']; ?></p>
                            <p><strong>Payment Method:</strong> <?php echo $order['payment_method']; ?></p>
                            <p><strong>Total Amount:</strong> <?php echo formatCurrency($order['total_amount']); ?></p>
                        </div>
                        
                        <div class="order-detail-section">
                            <h3>Shipping Information</h3>
                            <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="admin-card" data-aos="fade-up">
                    <div class="card-header">
                        <h2 class="card-title">Order Items</h2>
                    </div>
                    
                    <table class="order-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Unit Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderItems as $item): ?>
                                <tr>
                                    <td>
                                        <div class="item-product">
                                            <img src="<?php echo htmlspecialchars($item['product_image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="item-image">
                                            <div>
                                                <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo formatCurrency($item['unit_price']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo formatCurrency($item['total_price']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="text-align: right;"><strong>Subtotal:</strong></td>
                                <td><?php echo formatCurrency($order['total_amount'] - ($order['shipping_fee'] ?? 0)); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" style="text-align: right;"><strong>Shipping:</strong></td>
                                <td><?php echo formatCurrency($order['shipping_fee'] ?? 0); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                                <td><strong><?php echo formatCurrency($order['total_amount']); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <!-- Order Tracking -->
                <div class="admin-card" data-aos="fade-up">
                    <div class="card-header">
                        <h2 class="card-title">Order Tracking</h2>
                    </div>
                    
                    <div class="timeline">
                        <?php if (count($trackingHistory) > 0): ?>
                            <?php foreach ($trackingHistory as $tracking): ?>
                                <div class="timeline-item">
                                    <div class="timeline-point"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-date"><?php echo date('F j, Y, g:i a', strtotime($tracking['tracking_date'])); ?></div>
                                        <div class="timeline-title"><?php echo htmlspecialchars($tracking['status']); ?></div>
                                        <div class="timeline-description"><?php echo htmlspecialchars($tracking['description']); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No tracking information available for this order yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="confirmation-actions">
                    <a href="products.php" class="btn btn-primary">Continue Shopping</a>
                    <a href="account.php" class="btn">View All Orders</a>
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
        });
    </script>
</body>
</html>
