<?php
$current_page = 'admin';
$current_admin_page = 'dashboard';
$page_title = 'Admin Dashboard';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

// Add Chart.js for graphs
$extra_head = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
$extra_css = '<link rel="stylesheet" href="../css/admin.css">';

// Get dashboard statistics
try {
    // Total products
    $productsQuery = "SELECT COUNT(*) as total FROM products";
    $productsResult = $conn->query($productsQuery);
    $totalProducts = $productsResult->fetch_assoc()['total'];

    // Total categories
    $categoriesQuery = "SELECT COUNT(*) as total FROM categories";
    $categoriesResult = $conn->query($categoriesQuery);
    $totalCategories = $categoriesResult->fetch_assoc()['total'];

    // Total users
    $usersQuery = "SELECT COUNT(*) as total FROM users WHERE is_admin = 0";
    $usersResult = $conn->query($usersQuery);
    $totalUsers = $usersResult->fetch_assoc()['total'];

    // Total orders
    $ordersQuery = "SELECT COUNT(*) as total FROM orders";
    $ordersResult = $conn->query($ordersQuery);
    $totalOrders = $ordersResult->fetch_assoc()['total'];
    
    // Total revenue
    $revenueQuery = "SELECT SUM(total_amount) as total FROM orders";
    $revenueResult = $conn->query($revenueQuery);
    $totalRevenue = $revenueResult->fetch_assoc()['total'] ?? 0;

    // Order status counts
    $statusQuery = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
    $statusResult = $conn->query($statusQuery);
    $statusCounts = [];
    while ($row = $statusResult->fetch_assoc()) {
        $statusCounts[$row['status']] = $row['count'];
    }

    // Recent orders
    $recentOrdersQuery = "SELECT o.*, u.username FROM orders o 
                          JOIN users u ON o.user_id = u.id 
                          ORDER BY o.order_date DESC LIMIT 5";
    $recentOrdersResult = $conn->query($recentOrdersQuery);
    $recentOrders = [];
    while ($order = $recentOrdersResult->fetch_assoc()) {
        $recentOrders[] = $order;
    }

    // Low stock products
    $lowStockQuery = "SELECT * FROM products WHERE stock_quantity <= 10 ORDER BY stock_quantity ASC LIMIT 5";
    $lowStockResult = $conn->query($lowStockQuery);
    $lowStockProducts = [];
    while ($product = $lowStockResult->fetch_assoc()) {
        $lowStockProducts[] = $product;
    }
    
    // Get monthly revenue data (last 6 months)
    $monthlyRevenueQuery = "SELECT 
                              DATE_FORMAT(order_date, '%Y-%m') AS month,
                              SUM(total_amount) AS revenue
                           FROM orders
                           WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                           GROUP BY month
                           ORDER BY month ASC";
    $monthlyRevenueResult = $conn->query($monthlyRevenueQuery);
    $revenueMonths = [];
    $revenueData = [];
    
    while ($row = $monthlyRevenueResult->fetch_assoc()) {
        $date = date('M Y', strtotime($row['month'] . '-01'));
        $revenueMonths[] = $date;
        $revenueData[] = round($row['revenue'], 2);
    }
    
    // Get monthly user registrations (last 6 months)
    $userGrowthQuery = "SELECT 
                          DATE_FORMAT(register_date, '%Y-%m') AS month,
                          COUNT(*) AS new_users
                       FROM users
                       WHERE register_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                       GROUP BY month
                       ORDER BY month ASC";
    $userGrowthResult = $conn->query($userGrowthQuery);
    $userMonths = [];
    $userCounts = [];
    
    while ($row = $userGrowthResult->fetch_assoc()) {
        $date = date('M Y', strtotime($row['month'] . '-01'));
        $userMonths[] = $date;
        $userCounts[] = (int)$row['new_users'];
    }
    
    // Calculate trends (compare to previous period)
    // Revenue trend
    $currentPeriodRevenueQuery = "SELECT SUM(total_amount) AS revenue FROM orders 
                                  WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    $previousPeriodRevenueQuery = "SELECT SUM(total_amount) AS revenue FROM orders 
                                   WHERE order_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 60 DAY) 
                                   AND DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                                   
    $currentRevenueResult = $conn->query($currentPeriodRevenueQuery);
    $previousRevenueResult = $conn->query($previousPeriodRevenueQuery);
    
    $currentRevenue = $currentRevenueResult->fetch_assoc()['revenue'] ?? 0;
    $previousRevenue = $previousRevenueResult->fetch_assoc()['revenue'] ?? 0;
    
    $revenueTrend = 0;
    if ($previousRevenue > 0) {
        $revenueTrend = round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 1);
    } elseif ($currentRevenue > 0) {
        $revenueTrend = 100;
    }
    
    // Orders trend
    $currentPeriodOrdersQuery = "SELECT COUNT(*) AS count FROM orders 
                                WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    $previousPeriodOrdersQuery = "SELECT COUNT(*) AS count FROM orders 
                                 WHERE order_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 60 DAY) 
                                 AND DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                                 
    $currentOrdersResult = $conn->query($currentPeriodOrdersQuery);
    $previousOrdersResult = $conn->query($previousPeriodOrdersQuery);
    
    $currentOrders = $currentOrdersResult->fetch_assoc()['count'] ?? 0;
    $previousOrders = $previousOrdersResult->fetch_assoc()['count'] ?? 0;
    
    $ordersTrend = 0;
    if ($previousOrders > 0) {
        $ordersTrend = round((($currentOrders - $previousOrders) / $previousOrders) * 100, 1);
    } elseif ($currentOrders > 0) {
        $ordersTrend = 100;
    }
    
    // User trend
    $currentPeriodUsersQuery = "SELECT COUNT(*) AS count FROM users 
                              WHERE register_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    $previousPeriodUsersQuery = "SELECT COUNT(*) AS count FROM users 
                               WHERE register_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 60 DAY) 
                               AND DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                               
    $currentUsersResult = $conn->query($currentPeriodUsersQuery);
    $previousUsersResult = $conn->query($previousPeriodUsersQuery);
    
    $currentUsers = $currentUsersResult->fetch_assoc()['count'] ?? 0;
    $previousUsers = $previousUsersResult->fetch_assoc()['count'] ?? 0;
    
    $usersTrend = 0;
    if ($previousUsers > 0) {
        $usersTrend = round((($currentUsers - $previousUsers) / $previousUsers) * 100, 1);
    } elseif ($currentUsers > 0) {
        $usersTrend = 100;
    }
} catch (Exception $e) {
    $error = "Error retrieving dashboard data: " . $e->getMessage();
}

require_once '../includes/admin_header.php';
?>

<div class="admin-header">
    <div>
        <h1 class="admin-title" data-aos="fade-right">Admin Dashboard</h1>
        <p class="admin-subtitle">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
    </div>
    <div class="admin-actions" data-aos="fade-left">
        <a href="add_product.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Product</a>
        <a href="orders.php?filter=pending" class="btn btn-warning"><i class="fas fa-clock"></i> View Pending Orders</a>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-error" data-aos="fade-up">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- Stats Grid -->
<div class="stats-grid" data-aos="fade-up">
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-title">Total Revenue</div>
                <div class="stat-value revenue-value text-success"><?php echo formatCurrency($totalRevenue); ?></div>
            </div>
            <div class="stat-icon revenue">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>
        <div class="stat-footer">
            <div class="trend-indicator <?php echo $revenueTrend > 0 ? 'trend-up' : ($revenueTrend < 0 ? 'trend-down' : 'trend-neutral'); ?>">
                <i class="fas <?php echo $revenueTrend > 0 ? 'fa-arrow-up' : ($revenueTrend < 0 ? 'fa-arrow-down' : 'fa-minus'); ?>"></i>
                <?php echo $revenueTrend > 0 ? '+' . $revenueTrend : $revenueTrend; ?>%
            </div>
            <span>vs. previous period</span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-title">Total Orders</div>
                <div class="stat-value orders-value text-primary"><?php echo $totalOrders; ?></div>
            </div>
            <div class="stat-icon orders">
                <i class="fas fa-shopping-bag"></i>
            </div>
        </div>
        <div class="stat-footer">
            <div class="trend-indicator <?php echo $ordersTrend > 0 ? 'trend-up' : ($ordersTrend < 0 ? 'trend-down' : 'trend-neutral'); ?>">
                <i class="fas <?php echo $ordersTrend > 0 ? 'fa-arrow-up' : ($ordersTrend < 0 ? 'fa-arrow-down' : 'fa-minus'); ?>"></i>
                <?php echo $ordersTrend > 0 ? '+' . $ordersTrend : $ordersTrend; ?>%
            </div>
            <span>vs. previous period</span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-title">Total Customers</div>
                <div class="stat-value customers-value text-warning"><?php echo $totalUsers; ?></div>
            </div>
            <div class="stat-icon users">
                <i class="fas fa-users"></i>
            </div>
        </div>
        <div class="stat-footer">
            <div class="trend-indicator <?php echo $usersTrend > 0 ? 'trend-up' : ($usersTrend < 0 ? 'trend-down' : 'trend-neutral'); ?>">
                <i class="fas <?php echo $usersTrend > 0 ? 'fa-arrow-up' : ($usersTrend < 0 ? 'fa-arrow-down' : 'fa-minus'); ?>"></i>
                <?php echo $usersTrend > 0 ? '+' . $usersTrend : $usersTrend; ?>%
            </div>
            <span>vs. previous period</span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-title">Total Products</div>
                <div class="stat-value products-value text-info"><?php echo $totalProducts; ?></div>
            </div>
            <div class="stat-icon products">
                <i class="fas fa-boxes"></i>
            </div>
        </div>
        <div class="stat-footer">
            <div class="stat-categories">
                <span><?php echo $totalCategories; ?> Categories</span>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="charts-row" data-aos="fade-up">
    <!-- Revenue Chart -->
    <div class="chart-container half-width">
        <div class="chart-header">
            <h2 class="chart-title">Monthly Revenue</h2>
        </div>
        <canvas id="revenueChart" height="250"></canvas>
    </div>
    
    <!-- User Growth Chart -->
    <div class="chart-container half-width">
        <div class="chart-header">
            <h2 class="chart-title">New Users</h2>
        </div>
        <canvas id="userGrowthChart" height="250"></canvas>
    </div>
</div>

<div class="charts-row" data-aos="fade-up">
    <!-- Order Status Chart -->
    <div class="chart-container half-width">
        <div class="chart-header">
            <h2 class="chart-title">Order Status</h2>
        </div>
        <canvas id="orderStatusChart" height="250"></canvas>
    </div>
    
    <!-- Quick Stats -->
    <div class="chart-container half-width">
        <div class="chart-header">
            <h2 class="chart-title">Quick Actions</h2>
        </div>
        <div class="quick-actions-grid">
            <a href="orders.php" class="quick-action-card">
                <div class="quick-action-icon"><i class="fas fa-shopping-bag"></i></div>
                <div class="quick-action-text">Manage Orders</div>
            </a>
            <a href="products.php" class="quick-action-card">
                <div class="quick-action-icon"><i class="fas fa-pills"></i></div>
                <div class="quick-action-text">Manage Products</div>
            </a>
            <a href="users.php" class="quick-action-card">
                <div class="quick-action-icon"><i class="fas fa-users"></i></div>
                <div class="quick-action-text">Manage Users</div>
            </a>
            <a href="categories.php" class="quick-action-card">
                <div class="quick-action-icon"><i class="fas fa-th-large"></i></div>
                <div class="quick-action-text">Manage Categories</div>
            </a>
            <a href="add_product.php" class="quick-action-card">
                <div class="quick-action-icon"><i class="fas fa-plus-circle"></i></div>
                <div class="quick-action-text">Add Product</div>
            </a>
            <a href="tracking.php" class="quick-action-card">
                <div class="quick-action-icon"><i class="fas fa-shipping-fast"></i></div>
                <div class="quick-action-text">Order Tracking</div>
            </a>
        </div>
    </div>
</div>

<div class="two-column-grid" data-aos="fade-up">
    <!-- Recent Orders -->
    <div class="admin-card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-shopping-bag text-primary"></i> Recent Orders</h2>
            <a href="orders.php" class="btn btn-small btn-primary">View All</a>
        </div>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentOrders)): ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td class="text-primary">#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['username'] ?? 'Guest'); ?></td>
                                <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                <td class="text-success"><?php echo formatCurrency($order['total_amount']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo getStatusClass($order['status']); ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-icon" title="View Order"><i class="fas fa-eye"></i></a>
                                        <a href="update_order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-icon" title="Update Order"><i class="fas fa-edit"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No recent orders found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Low Stock Products -->
    <div class="admin-card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-exclamation-triangle text-warning"></i> Low Stock Products</h2>
            <a href="products.php?filter=low_stock" class="btn btn-small btn-warning">View All</a>
        </div>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Stock</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($lowStockProducts)): ?>
                        <?php foreach ($lowStockProducts as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td>
                                    <span class="<?php echo $product['stock_quantity'] <= 5 ? 'text-danger' : 'text-warning'; ?>">
                                        <?php echo $product['stock_quantity']; ?> remaining
                                    </span>
                                </td>
                                <td><a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-small btn-primary">Restock</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center;">No low stock products found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Charts JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($revenueMonths); ?>,
            datasets: [{
                label: 'Revenue',
                data: <?php echo json_encode($revenueData); ?>,
                backgroundColor: '#3b82f6',
                borderColor: '#2563eb',
                borderWidth: 1,
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 1.8,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Revenue: ' + new Intl.NumberFormat('en-US', {
                                style: 'currency',
                                currency: 'USD'
                            }).format(context.raw || 0);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    
    // User Growth Chart
    const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
    new Chart(userGrowthCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($userMonths); ?>,
            datasets: [{
                label: 'New Users',
                data: <?php echo json_encode($userCounts); ?>,
                backgroundColor: 'rgba(16, 185, 129, 0.2)',
                borderColor: '#10b981',
                borderWidth: 2,
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#10b981',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 1.8,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // Order Status Chart
    const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
    new Chart(orderStatusCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_keys($statusCounts)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($statusCounts)); ?>,
                backgroundColor: [
                    '#f59e0b', // Pending
                    '#3b82f6', // Processing
                    '#8b5cf6', // Shipped
                    '#10b981', // Delivered
                    '#ef4444'  // Cancelled
                ],
                borderWidth: 1,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 1.2,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.chart.data.datasets[0].data.reduce((a, b) => (a || 0) + (b || 0), 0);
                            const percentage = Math.round(((value || 0) / (total || 1)) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: '65%'
        }
    });
});
</script>

<?php 
// Add some custom CSS for the quick actions grid 
$extra_js = '
<style>
    .quick-actions-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        padding: 1rem;
    }
    
    .quick-action-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 1.5rem 1rem;
        background-color: var(--bg-tertiary);
        border-radius: 0.5rem;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .quick-action-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
        background-color: var(--accent-light);
        color: var(--accent-color);
    }
    
    .quick-action-icon {
        font-size: 1.75rem;
        margin-bottom: 1rem;
        color: var(--accent-color);
    }
    
    .quick-action-text {
        text-align: center;
        font-weight: 500;
    }
    
    @media (max-width: 768px) {
        .quick-actions-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 480px) {
        .quick-actions-grid {
            grid-template-columns: 1fr;
        }
    }
</style>';

require_once '../includes/admin_footer.php'; 
?>
