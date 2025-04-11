<?php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

// Require admin authentication
requireAdmin();

// Set header for JSON response
header('Content-Type: application/json');

// Initialize response
$response = array(
    'success' => false,
    'message' => 'No action specified',
    'count' => 0
);

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

// Process actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Clear a single notification
    if ($action === 'clear_one' && isset($_POST['id'])) {
        $orderId = intval($_POST['id']);
        
        // Update order status from 'Pending' to 'Processing'
        // This keeps the order in the system but removes it from notifications
        $stmt = $conn->prepare("UPDATE orders SET status = 'Processing' WHERE id = ? AND status = 'Pending'");
        $stmt->bind_param("i", $orderId);
        
        if ($stmt->execute()) {
            // Add tracking entry for the status change
            $description = "Order marked as processing from notification panel";
            addOrderTracking($conn, $orderId, 'Processing', $description);
            
            // Get updated notification count
            $countQuery = "SELECT COUNT(*) as count FROM orders WHERE status = 'Pending'";
            $countResult = $conn->query($countQuery);
            $notifCount = 0;
            if ($countResult && $countResult->num_rows > 0) {
                $notifCount = $countResult->fetch_assoc()['count'];
            }
            
            $response = array(
                'success' => true,
                'message' => 'Notification cleared',
                'count' => $notifCount
            );
        } else {
            $response['message'] = 'Failed to clear notification: ' . $conn->error;
        }
    }
    // Clear all notifications
    else if ($action === 'clear_all') {
        // Update all pending orders to processing
        $stmt = $conn->prepare("UPDATE orders SET status = 'Processing' WHERE status = 'Pending'");
        
        if ($stmt->execute()) {
            // Add tracking entries for all updated orders
            $description = "Order marked as processing from notification panel (bulk update)";
            
            // Get all the orders that were just updated
            $ordersQuery = "SELECT id FROM orders WHERE status = 'Processing' AND 
                           id NOT IN (SELECT order_id FROM order_tracking WHERE status = 'Processing')";
            $ordersResult = $conn->query($ordersQuery);
            
            if ($ordersResult) {
                while ($order = $ordersResult->fetch_assoc()) {
                    addOrderTracking($conn, $order['id'], 'Processing', $description);
                }
            }
            
            $response = array(
                'success' => true,
                'message' => 'All notifications cleared',
                'count' => 0
            );
        } else {
            $response['message'] = 'Failed to clear notifications: ' . $conn->error;
        }
    }
}

echo json_encode($response);
exit;
