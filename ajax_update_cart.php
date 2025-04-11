<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// Set JSON response header
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => 'No action specified',
    'count' => 0
];

// Get JSON data from request
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Sync cart with localStorage
if (isset($data['sync']) && $data['sync'] === true && isset($data['cart'])) {
    // Merge localStorage cart with session cart (session takes precedence)
    foreach ($data['cart'] as $productId => $quantity) {
        if (!isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] = (int)$quantity;
        }
    }
    
    $response['success'] = true;
    $response['message'] = 'Cart synced with localStorage';
    $response['count'] = count($_SESSION['cart']);
    $response['serverCart'] = $_SESSION['cart'];
}

// Full cart update from localStorage
else if (isset($data['cart']) && is_array($data['cart'])) {
    $_SESSION['cart'] = array_map('intval', $data['cart']);
    
    $response['success'] = true;
    $response['message'] = 'Cart updated from localStorage';
    $response['count'] = count($_SESSION['cart']);
}

// Add item to cart
else if (isset($data['addItem']) && isset($data['productId']) && isset($data['quantity'])) {
    $productId = (int)$data['productId'];
    $quantity = (int)$data['quantity'];
    
    // Validate product exists and has enough stock
    $stmt = $conn->prepare("SELECT id, name, stock_quantity FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        
        // Check if adding to existing quantity
        $currentQuantity = isset($_SESSION['cart'][$productId]) ? (int)$_SESSION['cart'][$productId] : 0;
        $newQuantity = $currentQuantity + $quantity;
        
        // Check stock availability
        if ($newQuantity <= $product['stock_quantity']) {
            $_SESSION['cart'][$productId] = $newQuantity;
            
            $response['success'] = true;
            $response['message'] = 'Item added to cart';
            $response['productName'] = $product['name'];
            $response['count'] = count($_SESSION['cart']);
        } else {
            $response['message'] = 'Not enough stock available';
        }
    } else {
        $response['message'] = 'Product not found';
    }
}

// Update specific item
else if (isset($data['updateItem']) && isset($data['productId']) && isset($data['quantity'])) {
    $productId = (int)$data['productId'];
    $quantity = (int)$data['quantity'];
    
    // Validate product exists and has enough stock
    $stmt = $conn->prepare("SELECT id, stock_quantity FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        
        if ($quantity <= $product['stock_quantity']) {
            if ($quantity > 0) {
                $_SESSION['cart'][$productId] = $quantity;
            } else {
                unset($_SESSION['cart'][$productId]);
            }
            
            // Calculate new totals
            $subtotal = 0;
            $shipping = 0;
            $shipping_fee = 5.00;
            $free_shipping_threshold = 50.00;
            
            if (!empty($_SESSION['cart'])) {
                $product_ids = array_keys($_SESSION['cart']);
                $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
                $types = str_repeat('i', count($product_ids));
                
                $stmt = $conn->prepare("SELECT id, price FROM products WHERE id IN ($placeholders)");
                $stmt->bind_param($types, ...$product_ids);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($product = $result->fetch_assoc()) {
                    $product_id = $product['id'];
                    $quantity = (int)$_SESSION['cart'][$product_id];
                    $price = (float)$product['price'];
                    $subtotal += $price * $quantity;
                }
            }
            
            // Calculate shipping fee
            $shipping = ($subtotal >= $free_shipping_threshold) ? 0 : $shipping_fee;
            $total = $subtotal + $shipping;
            
            $response['success'] = true;
            $response['message'] = 'Cart updated';
            $response['count'] = count($_SESSION['cart']);
            $response['subtotal'] = formatCurrency($subtotal);
            $response['shipping'] = $shipping > 0 ? formatCurrency($shipping) : 'FREE';
            $response['total'] = formatCurrency($total);
        } else {
            $response['message'] = 'Not enough stock available';
        }
    } else {
        $response['message'] = 'Product not found';
    }
}

// Remove item from cart
else if (isset($data['removeItem']) && isset($data['productId'])) {
    $productId = (int)$data['productId'];
    
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        
        $response['success'] = true;
        $response['message'] = 'Item removed from cart';
        $response['count'] = count($_SESSION['cart']);
    } else {
        $response['message'] = 'Item not found in cart';
    }
}

echo json_encode($response);
exit;
?>
