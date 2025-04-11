<?php
/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 * 
 * @return bool True if admin, false otherwise
 */
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Require user to be logged in
 * Redirects to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = "You must be logged in to access this page.";
        header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
}

/**
 * Require user to be admin
 * Redirects to login page if not admin
 */
function requireAdmin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = "You must be logged in to access this page.";
        if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
            header("Location: ../login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        } else {
            header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        }
        exit();
    }
    
    if (!isAdmin()) {
        $_SESSION['error'] = "You don't have permission to access this page.";
        if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
            header("Location: ../index.php");
        } else {
            header("Location: index.php");
        }
        exit();
    }
}

/**
 * Format a number as currency
 * 
 * @param float $amount The amount to format
 * @return string Formatted currency string
 */
function formatCurrency($amount) {
    return '$' . number_format((float)$amount, 2, '.', ',');
}

/**
 * Get product by ID
 * 
 * @param mysqli $conn Database connection
 * @param int $id Product ID
 * @return array|null Product details or null if not found
 */
function getProductById($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return null;
    }
}

/**
 * Get category by ID
 * 
 * @param mysqli $conn Database connection
 * @param int $id Category ID
 * @return array|null Category details or null if not found
 */
function getCategoryById($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return null;
    }
}

/**
 * Get all categories
 * 
 * @param mysqli $conn Database connection
 * @return array List of categories
 */
function getAllCategories($conn) {
    $query = "SELECT * FROM categories ORDER BY name";
    $result = $conn->query($query);
    
    $categories = array();
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    return $categories;
}

/**
 * Get class name for order status badges
 * 
 * @param string $status Order status
 * @return string CSS class name
 */
function getStatusClass($status) {
    switch (strtolower($status)) {
        case 'pending':
            return 'status-pending';
        case 'processing':
            return 'status-processing';
        case 'shipped':
            return 'status-shipped';
        case 'delivered':
            return 'status-delivered';
        case 'cancelled':
            return 'status-cancelled';
        default:
            return '';
    }
}

/**
 * Get order details with product information
 * 
 * @param mysqli $conn Database connection
 * @param int $orderId Order ID
 * @return array Order details
 */
function getOrderDetails($conn, $orderId) {
    $items = [];
    try {
        $query = "SELECT oi.*, p.name as product_name, p.image as product_image 
                  FROM order_items oi 
                  JOIN products p ON oi.product_id = p.id 
                  WHERE oi.order_id = ?";
                  
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    } catch (Exception $e) {
        // Log error (ideally to a file)
        error_log("Error getting order details: " . $e->getMessage());
    }
    
    return $items;
}

/**
 * Get order tracking history
 * 
 * @param mysqli $conn Database connection
 * @param int $orderId Order ID
 * @return array Order tracking history
 */
function getOrderTracking($conn, $orderId) {
    $tracking = [];
    try {
        $query = "SELECT * FROM order_tracking WHERE order_id = ? ORDER BY tracking_date DESC";
                  
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $tracking[] = $row;
        }
    } catch (Exception $e) {
        // Log error (ideally to a file)
        error_log("Error getting order tracking: " . $e->getMessage());
    }
    
    return $tracking;
}

/**
 * Add order tracking entry
 * 
 * @param mysqli $conn Database connection
 * @param int $orderId Order ID
 * @param string $status Tracking status
 * @param string $description Tracking description
 * @return bool True on success, false on failure
 */
function addOrderTracking($conn, $orderId, $status, $description) {
    $stmt = $conn->prepare("
        INSERT INTO order_tracking (order_id, status, description, tracking_date) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->bind_param("iss", $orderId, $status, $description);
    return $stmt->execute();
}

/**
 * Update order status
 * 
 * @param mysqli $conn Database connection
 * @param int $orderId Order ID
 * @param string $status New status
 * @return bool True on success, false on failure
 */
function updateOrderStatus($conn, $orderId, $status) {
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $orderId);
    return $stmt->execute();
}

/**
 * Sanitize input data
 * 
 * @param mysqli $conn Database connection
 * @param string $data Data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($conn, $data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    if ($conn) {
        $data = $conn->real_escape_string($data);
    }
    return $data;
}

/**
 * Generate random order number
 * 
 * @return string Order number
 */
function generateOrderNumber() {
    $prefix = 'ORD-';
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $orderNumber = $prefix;
    
    for ($i = 0; $i < 10; $i++) {
        $orderNumber .= $chars[rand(0, strlen($chars) - 1)];
    }
    
    return $orderNumber;
}

/**
 * Get featured products
 * 
 * @param mysqli $conn Database connection
 * @param int $limit Number of products to fetch
 * @return array List of featured products
 */
function getFeaturedProducts($conn, $limit = 8) {
    $query = "SELECT * FROM products WHERE featured = 1 LIMIT ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = array();
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    return $products;
}

/**
 * Display error message
 * 
 * @param string $message Error message to display
 */
function displayError($message) {
    echo '<div class="alert alert-error">' . $message . '</div>';
}

/**
 * Display success message
 * 
 * @param string $message Success message to display
 */
function displaySuccess($message) {
    echo '<div class="alert alert-success">' . $message . '</div>';
}

/**
 * Check if a product exists and is in stock
 * 
 * @param mysqli $conn Database connection
 * @param int $productId Product ID
 * @param int $quantity Requested quantity
 * @return array|bool Product data if available, false otherwise
 */
function checkProductStock($conn, $productId, $quantity) {
    try {
        $stmt = $conn->prepare("SELECT id, name, price, stock_quantity FROM products WHERE id = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false; // Product not found
        }
        
        $product = $result->fetch_assoc();
        
        if ($product['stock_quantity'] < $quantity) {
            return false; // Not enough stock
        }
        
        return $product;
    } catch (Exception $e) {
        error_log("Error checking product stock: " . $e->getMessage());
        return false;
    }
}

/**
 * Log activity
 * 
 * @param string $message Activity message
 * @param string $type Type of activity (info, warning, error)
 */
function logActivity($message, $type = 'info') {
    $logFile = __DIR__ . '/../logs/activity.log';
    $timestamp = date('Y-m-d H:i:s');
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'guest';
    $userName = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    
    $logEntry = "[$timestamp] [$type] User $userId ($userName) from $ipAddress: $message\n";
    
    // Create logs directory if it doesn't exist
    $logsDir = dirname($logFile);
    if (!is_dir($logsDir)) {
        mkdir($logsDir, 0755, true);
    }
    
    // Write to log file
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Convert timestamp to "time ago" format
 * @param string $timestamp The timestamp to convert
 * @return string Formatted time ago string
 */
function timeAgo($timestamp) {
    $timestamp = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $timestamp;
    
    // Time constants
    $minute = 60;
    $hour = $minute * 60;
    $day = $hour * 24;
    $week = $day * 7;
    $month = $day * 30;
    $year = $day * 365;
    
    // Calculate time ago
    if ($time_difference < $minute) {
        return "just now";
    } else if ($time_difference < $hour) {
        $minutes = round($time_difference / $minute);
        return $minutes . " " . ($minutes == 1 ? "minute" : "minutes") . " ago";
    } else if ($time_difference < $day) {
        $hours = round($time_difference / $hour);
        return $hours . " " . ($hours == 1 ? "hour" : "hours") . " ago";
    } else if ($time_difference < $week) {
        $days = round($time_difference / $day);
        return $days . " " . ($days == 1 ? "day" : "days") . " ago";
    } else if ($time_difference < $month) {
        $weeks = round($time_difference / $week);
        return $weeks . " " . ($weeks == 1 ? "week" : "weeks") . " ago";
    } else if ($time_difference < $year) {
        $months = round($time_difference / $month);
        return $months . " " . ($months == 1 ? "month" : "months") . " ago";
    } else {
        $years = round($time_difference / $year);
        return $years . " " . ($years == 1 ? "year" : "years") . " ago";
    }
}
?>
