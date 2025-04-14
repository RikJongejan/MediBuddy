<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// Initialize response array for AJAX requests
$response = [
    'success' => false,
    'message' => '',
    'count' => 0,
    'productName' => ''
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
            $stmt = $conn->prepare("SELECT id, name, stock_quantity FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $product = $result->fetch_assoc();
                
                // Check if product is in stock
                if ($product['stock_quantity'] > 0) {
                    // Check if adding to existing quantity
                    $currentQuantity = isset($_SESSION['cart'][$product_id]) ? intval($_SESSION['cart'][$product_id]) : 0;
                    $newQuantity = $currentQuantity + $quantity;
                    
                    // Check if new quantity exceeds stock
                    if ($newQuantity > $product['stock_quantity']) {
                        $newQuantity = $product['stock_quantity'];
                        $message = "Added maximum available quantity to cart.";
                    } else {
                        $message = "Added " . htmlspecialchars($product['name']) . " to cart!";
                    }
                    
                    // Update cart in session
                    $_SESSION['cart'][$product_id] = $newQuantity;
                    
                    $response['success'] = true;
                    $response['message'] = $message;
                    $response['productName'] = $product['name'];
                    $response['count'] = count($_SESSION['cart']);
                } else {
                    $response['message'] = "Sorry, this product is out of stock.";
                }
            } else {
                $response['message'] = "Product not found.";
            }
        } catch (Exception $e) {
            $response['message'] = "An error occurred while processing your request. Please try again.";
            error_log("Cart error: " . $e->getMessage());
        }
    } else {
        $response['message'] = "Invalid request. Product ID and quantity are required.";
    }
    
    // Return JSON response for AJAX requests
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        // For non-AJAX requests, set session message and redirect
        if ($response['success']) {
            $_SESSION['cart_message'] = $response['message'];
        } else {
            $_SESSION['cart_error'] = $response['message'];
        }
        
        // Redirect back to referring page
        $redirect_to = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'products.php';
        header("Location: $redirect_to");
        exit;
    }
}

// If we reach here, it's an invalid request
$response['message'] = "Invalid request method.";
header('Content-Type: application/json');
echo json_encode($response);
exit;
