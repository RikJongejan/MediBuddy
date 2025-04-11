<?php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

// Set header for JSON response
header('Content-Type: application/json');

// Initialize empty arrays for fallback data
$dates = [];
$revenue = [];
$orders = [];

try {
    // Build basic data for the past 30 days
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime('-30 days'));
    
    // Generate date range
    for ($i = 30; $i >= 0; $i--) {
        $dates[] = date('M d', strtotime("-$i days"));
    }
    
    // Get revenue data
    $revenueQuery = "SELECT DATE(order_date) as order_day, SUM(total_amount) as daily_revenue 
                     FROM orders 
                     WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                     GROUP BY order_day 
                     ORDER BY order_day";
                     
    $revenueData = $conn->query($revenueQuery);
    
    // Get order count data
    $ordersQuery = "SELECT DATE(order_date) as order_day, COUNT(*) as order_count 
                    FROM orders 
                    WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                    GROUP BY order_day 
                    ORDER BY order_day";
                    
    $ordersData = $conn->query($ordersQuery);
    
    // Create associative arrays for easy lookup
    $revenueByDay = [];
    $ordersByDay = [];
    
    if ($revenueData) {
        while ($row = $revenueData->fetch_assoc()) {
            $revenueByDay[date('M d', strtotime($row['order_day']))] = (float)$row['daily_revenue'];
        }
    }
    
    if ($ordersData) {
        while ($row = $ordersData->fetch_assoc()) {
            $ordersByDay[date('M d', strtotime($row['order_day']))] = (int)$row['order_count'];
        }
    }
    
    // Fill in data for all days, using 0 for days with no data
    foreach ($dates as $date) {
        $revenue[] = isset($revenueByDay[$date]) ? $revenueByDay[$date] : 0;
        $orders[] = isset($ordersByDay[$date]) ? $ordersByDay[$date] : 0;
    }
    
    // Get summary data
    $totalQuery = "SELECT 
                    COUNT(*) as totalOrders,
                    SUM(total_amount) as totalRevenue
                   FROM orders";
    $totalResult = $conn->query($totalQuery);
    $totalData = $totalResult->fetch_assoc();
    
    $customersQuery = "SELECT COUNT(*) as count FROM users WHERE is_admin = 0";
    $customersResult = $conn->query($customersQuery);
    $totalCustomers = $customersResult->fetch_assoc()['count'] ?? 0;
    
    $productsQuery = "SELECT COUNT(*) as count FROM products";
    $productsResult = $conn->query($productsQuery);
    $totalProducts = $productsResult->fetch_assoc()['count'] ?? 0;
    
    // Simplified trend data - just calculate basic increase/decrease
    $midPoint = floor(count($revenue) / 2);
    $recentRevenue = array_sum(array_slice($revenue, -$midPoint));
    $prevRevenue = array_sum(array_slice($revenue, 0, $midPoint));
    
    $revenueTrend = 0;
    if ($prevRevenue > 0) {
        $revenueTrend = round((($recentRevenue - $prevRevenue) / $prevRevenue) * 100);
    } elseif ($recentRevenue > 0) {
        $revenueTrend = 100;
    }
    
    $recentOrders = array_sum(array_slice($orders, -$midPoint));
    $prevOrders = array_sum(array_slice($orders, 0, $midPoint));
    
    $ordersTrend = 0;
    if ($prevOrders > 0) {
        $ordersTrend = round((($recentOrders - $prevOrders) / $prevOrders) * 100);
    } elseif ($recentOrders > 0) {
        $ordersTrend = 100;
    }
    
    // Create response data
    $response = [
        'success' => true,
        'labels' => $dates,
        'revenue' => $revenue,
        'orders' => $orders,
        'trends' => [
            'revenue_trend' => $revenueTrend,
            'orders_trend' => $ordersTrend,
            'customers_trend' => 0,  // Simplified to 0 for now
            'products_trend' => 0    // Simplified to 0 for now
        ],
        'summary' => [
            'totalRevenue' => (float)($totalData['totalRevenue'] ?? 0),
            'totalOrders' => (int)($totalData['totalOrders'] ?? 0),
            'totalCustomers' => (int)$totalCustomers,
            'totalProducts' => (int)$totalProducts
        ]
    ];
    
    // Return success response
    echo json_encode($response);

} catch (Exception $e) {
    // Simple fallback data in case of error
    $fallbackDates = [];
    $fallbackData = [];
    
    for ($i = 0; $i < 31; $i++) {
        $fallbackDates[] = date('M d', strtotime("-$i days"));
        $fallbackData[] = 0;
    }
    
    $errorResponse = [
        'success' => false,
        'error' => $e->getMessage(),
        'labels' => $fallbackDates,
        'revenue' => $fallbackData,
        'orders' => $fallbackData,
        'trends' => [
            'revenue_trend' => 0,
            'orders_trend' => 0,
            'customers_trend' => 0,
            'products_trend' => 0
        ],
        'summary' => [
            'totalRevenue' => 0,
            'totalOrders' => 0,
            'totalCustomers' => 0,
            'totalProducts' => 0
        ]
    ];
    
    echo json_encode($errorResponse);
}
