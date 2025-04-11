<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// Initialize response array for AJAX requests
$response = [
    'success' => false,
    'message' => '',
    'redirect' => '',
    'productName' => '',
    'count' => 0
];

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check if request is AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if product_id and quantity are provided
    if (isset($_POST['product_id']) && isset($_POST['quantity'])) {
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        
        // Validate quantity
        if ($quantity <= 0) {
            $quantity = 1;
        }
        
        try {
            // Check if product exists and has enough stock
            $product = checkProductStock($conn, $product_id, $quantity);
            
            if ($product) {
                // Check if product is already in cart
                $current_quantity = isset($_SESSION['cart'][$product_id]) ? intval($_SESSION['cart'][$product_id]) : 0;
                $new_quantity = $current_quantity + $quantity;
                
                // Check if new quantity exceeds stock
                if ($new_quantity > $product['stock_quantity']) {
                    $new_quantity = $product['stock_quantity'];
                    $message = "Added maximum available quantity to cart.";
                    
                    if ($isAjax) {
                        $response['message'] = $message;
                        $response['success'] = true;
                    } else {
                        $_SESSION['cart_warning'] = $message;
                    }
                } else {
                    $message = "Added " . htmlspecialchars($product['name']) . " to cart!";
                    
                    if ($isAjax) {
                        $response['message'] = $message;
                        $response['success'] = true;
                    } else {
                        $_SESSION['cart_message'] = $message;
                    }
                }
                
                // Update cart in session
                $_SESSION['cart'][$product_id] = $new_quantity;
                
                // Set product name and count for AJAX response
                if ($isAjax) {
                    $response['productName'] = $product['name'];
                    $response['count'] = count($_SESSION['cart']);
                }
                
                // Log the activity
                logActivity("Added product ID: $product_id, Quantity: $quantity to cart");
            } else {
                $message = "Sorry, the product is not available in the requested quantity.";
                
                if ($isAjax) {
                    $response['message'] = $message;
                } else {
                    $_SESSION['cart_error'] = $message;
                }
                
                // Log the error
                logActivity("Failed to add product ID: $product_id to cart - insufficient stock", "warning");
            }
        } catch (Exception $e) {
            $message = "An error occurred. Please try again.";
            
            if ($isAjax) {
                $response['message'] = $message;
            } else {
                $_SESSION['cart_error'] = $message;
            }
            
            // Log the error
            logActivity("Error adding to cart: " . $e->getMessage(), "error");
        }
    } else {
        $message = "Invalid request. Product ID and quantity are required.";
        
        if ($isAjax) {
            $response['message'] = $message;
        } else {
            $_SESSION['cart_error'] = $message;
        }
    }
    
    if ($isAjax) {
        // Return JSON response for AJAX requests
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        // Redirect for regular form submissions
        $redirect_to = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'products.php';
        header("Location: $redirect_to");
        exit;
    }
}

// Handle AJAX sync requests
if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);
    
    if ($data && isset($data['cart'])) {
        // Sync localStorage cart with PHP session
        foreach ($data['cart'] as $productId => $quantity) {
            $productId = intval($productId);
            $quantity = intval($quantity);
            
            if ($quantity > 0) {
                // Check if product exists and has enough stock
                try {
                    $stmt = $conn->prepare("SELECT id, stock_quantity FROM products WHERE id = ?");
                    $stmt->bind_param("i", $productId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $product = $result->fetch_assoc();
                        
                        // Limit quantity to available stock
                        $finalQuantity = min($quantity, $product['stock_quantity']);
                        $_SESSION['cart'][$productId] = $finalQuantity;
                    }
                } catch (Exception $e) {
                    // Log error
                    error_log("Error syncing cart: " . $e->getMessage());
                }
            }
        }
        
        $response['success'] = true;
        $response['message'] = 'Cart synchronized successfully';
        $response['count'] = count($_SESSION['cart']);
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// If we get here, something went wrong with the request
if ($isAjax) {
    $response['message'] = "Invalid request.";
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    $_SESSION['cart_error'] = "Invalid request.";
    header("Location: products.php");
}
exit;
