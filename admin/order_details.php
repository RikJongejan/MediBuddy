<?php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

// Check if user is admin
requireAdmin();

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: orders.php?error=invalid_order");
    exit();
}

$orderId = intval($_GET['id']);

// Get order details
$orderQuery = "SELECT o.*, u.username, u.email FROM orders o 
              JOIN users u ON o.user_id = u.id 
              WHERE o.id = ?";
$stmt = $conn->prepare($orderQuery);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$orderResult = $stmt->get_result();

if ($orderResult->num_rows == 0) {
    header("Location: orders.php?error=order_not_found");
    exit();
}

$order = $orderResult->fetch_assoc();

// Get order items
$orderItems = getOrderDetails($conn, $orderId);

// Get order tracking history
$trackingHistory = getOrderTracking($conn, $orderId);

// Process status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = sanitizeInput($conn, $_POST['status']);
    $statusDescription = sanitizeInput($conn, $_POST['status_description']);
    
    // Update order status
    if (updateOrderStatus($conn, $orderId, $newStatus)) {
        // Add tracking entry
        addOrderTracking($conn, $orderId, $newStatus, $statusDescription);
        header("Location: order_details.php?id=$orderId&success=status_updated");
        exit();
    } else {
        $error = "Failed to update order status.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order['id']; ?> - MediBuddy Admin</title>
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
                <a href="categories.php" class="menu-item">
                    <i class="fas fa-th-large"></i> Categories
                </a>
                <a href="orders.php" class="menu-item active">
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
                <h1 class="admin-title" data-aos="fade-right">Order #<?php echo $order['id']; ?></h1>
                <div class="admin-actions" data-aos="fade-left">
                    <a href="orders.php" class="btn btn-small"><i class="fas fa-arrow-left"></i> Back to Orders</a>
                    <button class="btn btn-primary" id="printButton"><i class="fas fa-print"></i> Print Order</button>
                </div>
            </div>
            
            <?php if (isset($_GET['success']) && $_GET['success'] == 'status_updated'): ?>
                <div class="alert alert-success" data-aos="fade-up">
                    Order status updated successfully!
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error" data-aos="fade-up">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Order Summary -->
            <div class="admin-card" data-aos="fade-up">
                <div class="card-header">
                    <h2 class="card-title">Order Summary</h2>
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
                        <p><strong>Total Amount:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
                    </div>
                    
                    <div class="order-detail-section">
                        <h3>Customer Information</h3>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($order['username']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                        <p><strong>Shipping Address:</strong><br>
                            <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                        </p>
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
                                <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>$<?php echo number_format($item['total_price'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right;"><strong>Subtotal:</strong></td>
                            <td>$<?php echo number_format($order['total_amount'] - ($order['shipping_fee'] ?? 0), 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" style="text-align: right;"><strong>Shipping:</strong></td>
                            <td>$<?php echo number_format($order['shipping_fee'] ?? 0, 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                            <td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
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
                        <p>No tracking information available for this order.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Add Tracking Update -->
                <div class="card-header" style="margin-top: 2rem;">
                    <h2 class="card-title">Update Order Status</h2>
                </div>
                
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-input" required>
                                <option value="Pending" <?php echo ($order['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Processing" <?php echo ($order['status'] == 'Processing') ? 'selected' : ''; ?>>Processing</option>
                                <option value="Shipped" <?php echo ($order['status'] == 'Shipped') ? 'selected' : ''; ?>>Shipped</option>
                                <option value="Delivered" <?php echo ($order['status'] == 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
                                <option value="Cancelled" <?php echo ($order['status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="status_description" class="form-label">Description</label>
                            <input type="text" name="status_description" id="status_description" class="form-input" required placeholder="Enter status update details">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
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
            
            // Print order functionality
            const printButton = document.getElementById('printButton');
            if (printButton) {
                printButton.addEventListener('click', function() {
                    window.print();
                });
            }
        });
    </script>
</body>
</html>
