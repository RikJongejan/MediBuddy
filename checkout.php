<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// Redirect to login if not authenticated
requireLogin();

// Redirect to cart if cart is empty
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

// Calculate cart totals
$cart_items = [];
$subtotal = 0;
$shipping_fee = 5.00; // Fixed shipping fee

if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    $types = str_repeat('i', count($product_ids));
    
    $stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$product_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($product = $result->fetch_assoc()) {
        $quantity = $_SESSION['cart'][$product['id']];
        $price = $product['price'];
        $item_total = $price * $quantity;
        
        $cart_items[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'image' => $product['image'],
            'price' => $price,
            'quantity' => $quantity,
            'total' => $item_total
        ];
        
        $subtotal += $item_total;
    }
}

$total_price = $subtotal + $shipping_fee;

// Get user information
$user_id = $_SESSION['user_id'];
$userQuery = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();

// Process checkout form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $firstName = sanitizeInput($conn, $_POST['first_name']);
    $lastName = sanitizeInput($conn, $_POST['last_name']);
    $email = sanitizeInput($conn, $_POST['email']);
    $phone = sanitizeInput($conn, $_POST['phone']);
    $address = sanitizeInput($conn, $_POST['address']);
    $city = sanitizeInput($conn, $_POST['city']);
    $state = sanitizeInput($conn, $_POST['state']);
    $zipcode = sanitizeInput($conn, $_POST['zipcode']);
    $paymentMethod = sanitizeInput($conn, $_POST['payment_method']);
    
    // Create shipping address
    $shippingAddress = "$firstName $lastName\n$address\n$city, $state $zipcode\nPhone: $phone";
    
    // Generate order number
    $orderNumber = generateOrderNumber();
    
    // Create order in database
    $conn->begin_transaction();
    
    try {
        // Insert order
        $orderStmt = $conn->prepare("INSERT INTO orders (user_id, order_number, total_amount, shipping_fee, payment_method, shipping_address, status, order_date) VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())");
        $orderStmt->bind_param("isddss", $user_id, $orderNumber, $total_price, $shipping_fee, $paymentMethod, $shippingAddress);
        $orderStmt->execute();
        
        $orderId = $conn->insert_id;
        
        // Insert order items
        foreach ($cart_items as $item) {
            $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
            $itemStmt->bind_param("iiddd", $orderId, $item['id'], $item['quantity'], $item['price'], $item['total']);
            $itemStmt->execute();
            
            // Update product stock
            $updateStockStmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
            $updateStockStmt->bind_param("ii", $item['quantity'], $item['id']);
            $updateStockStmt->execute();
        }
        
        // Add initial order tracking
        $trackingStmt = $conn->prepare("INSERT INTO order_tracking (order_id, status, description, tracking_date) VALUES (?, 'Pending', 'Order placed successfully', NOW())");
        $trackingStmt->bind_param("i", $orderId);
        $trackingStmt->execute();
        
        $conn->commit();
        
        // Clear cart
        $_SESSION['cart'] = [];
        
        // Redirect to order confirmation
        header("Location: order_confirmation.php?id=$orderId");
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "An error occurred while processing your order. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - MediBuddy</title>
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
                            <?php echo count($_SESSION['cart']); ?>
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
        <a href="cart.php" class="nav-link active">Cart</a>
        <a href="account.php" class="nav-link">Account</a>
        <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
            <a href="admin/dashboard.php" class="nav-link">Admin Panel</a>
        <?php endif; ?>
    </nav>
    
    <!-- Breadcrumbs -->
    <div class="breadcrumbs">
        <div class="container">
            <a href="index.php">Home</a> &gt; <a href="cart.php">Cart</a> &gt; Checkout
        </div>
    </div>
    
    <!-- Checkout Section -->
    <section class="section">
        <div class="container">
            <h1 class="page-title" data-aos="fade-up">Checkout</h1>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error" data-aos="fade-up">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="checkout-container" data-aos="fade-up">
                <div class="checkout-form">
                    <form method="post" action="">
                        <div class="admin-card">
                            <h2 class="card-title">Shipping Information</h2>
                            
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
                            
                            <div class="form-group form-full">
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
                        </div>
                        
                        <div class="admin-card">
                            <h2 class="card-title">Payment Method</h2>
                            
                            <div class="form-group">
                                <div class="payment-options">
                                    <div class="payment-option">
                                        <input type="radio" id="credit_card" name="payment_method" value="Credit Card" checked>
                                        <label for="credit_card">Credit Card</label>
                                    </div>
                                    
                                    <div class="payment-option">
                                        <input type="radio" id="paypal" name="payment_method" value="PayPal">
                                        <label for="paypal">PayPal</label>
                                    </div>
                                    
                                    <div class="payment-option">
                                        <input type="radio" id="cash_on_delivery" name="payment_method" value="Cash on Delivery">
                                        <label for="cash_on_delivery">Cash on Delivery</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="credit_card_details" class="payment-details">
                                <div class="form-row">
                                    <div class="form-group form-full">
                                        <label for="card_number" class="form-label">Card Number</label>
                                        <input type="text" id="card_number" class="form-input" placeholder="**** **** **** ****">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="expiry_date" class="form-label">Expiry Date</label>
                                        <input type="text" id="expiry_date" class="form-input" placeholder="MM/YY">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="cvv" class="form-label">CVV</label>
                                        <input type="text" id="cvv" class="form-input" placeholder="***">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="checkout-actions">
                            <button type="submit" name="place_order" class="btn btn-primary checkout-btn">Place Order</button>
                            <a href="cart.php" class="continue-shopping">Return to Cart</a>
                        </div>
                    </form>
                </div>
                
                <div class="order-summary">
                    <div class="admin-card">
                        <h2 class="card-title">Order Summary</h2>
                        
                        <div class="order-items">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="order-item">
                                    <div class="order-item-image">
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    </div>
                                    <div class="order-item-details">
                                        <h3 class="order-item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                                        <div class="order-item-meta">
                                            <span class="quantity">x<?php echo $item['quantity']; ?></span>
                                            <span class="price"><?php echo formatCurrency($item['price']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="summary-divider"></div>
                        
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span><?php echo formatCurrency($subtotal); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span><?php echo formatCurrency($shipping_fee); ?></span>
                        </div>
                        
                        <div class="summary-total">
                            <span>Total</span>
                            <span><?php echo formatCurrency($total_price); ?></span>
                        </div>
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
            
            // Payment method toggle
            const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
            const creditCardDetails = document.getElementById('credit_card_details');
            
            paymentMethods.forEach(function(method) {
                method.addEventListener('change', function() {
                    if (this.value === 'Credit Card') {
                        creditCardDetails.style.display = 'block';
                    } else {
                        creditCardDetails.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>
