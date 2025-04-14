<?php
// Start session at the beginning
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit();
}

$current_page = 'admin';
$current_admin_page = 'users';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$user_id = intval($_GET['id']);

// Get user details
$userQuery = "SELECT * FROM users WHERE id = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();

if ($userResult->num_rows === 0) {
    header("Location: users.php");
    exit();
}

$user = $userResult->fetch_assoc();

// Get user's orders
$ordersQuery = "SELECT o.*, SUM(oi.quantity * oi.unit_price) as total_amount 
                FROM orders o 
                LEFT JOIN order_items oi ON o.id = oi.order_id 
                WHERE o.user_id = ? 
                GROUP BY o.id 
                ORDER BY o.order_date DESC 
                LIMIT 5";
$ordersStmt = $conn->prepare($ordersQuery);
$ordersStmt->bind_param("i", $user_id);
$ordersStmt->execute();
$ordersResult = $ordersStmt->get_result();

$page_title = 'User Profile: ' . htmlspecialchars($user['username']);

// Add CSS for admin panel
$extra_css = '<link rel="stylesheet" href="../css/admin.css">';

require_once '../includes/admin_header.php';
?>

<div class="admin-header">
    <h1 class="admin-title" data-aos="fade-right">User Profile: <?php echo htmlspecialchars($user['username']); ?></h1>
    <div class="admin-actions" data-aos="fade-left">
        <a href="users.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Users</a>
        <a href="edit_user.php?id=<?php echo $user_id; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Edit User</a>
    </div>
</div>

<div class="admin-card" data-aos="fade-up">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-user"></i> User Information</h2>
    </div>
    
    <div class="card-body">
        <div class="user-profile">
            <div class="profile-section">
                <h3 class="section-title">Account Details</h3>
                <div class="profile-details">
                    <div class="detail-row">
                        <span class="detail-label">User ID:</span>
                        <span class="detail-value">#<?php echo $user['id']; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Username:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Full Name:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                    </div>
                    <?php if (!empty($user['address'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">Address:</span>
                        <span class="detail-value"><?php echo nl2br(htmlspecialchars($user['address'])); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="detail-row">
                        <span class="detail-label">Role:</span>
                        <span class="detail-value">
                            <?php if ($user['is_admin']): ?>
                                <span class="status-badge status-processing">Administrator</span>
                            <?php else: ?>
                                <span class="status-badge status-shipped">Customer</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">
                            <?php if ($user['is_active']): ?>
                                <span class="status-badge status-delivered">Active</span>
                            <?php else: ?>
                                <span class="status-badge status-cancelled">Inactive</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Registered:</span>
                        <span class="detail-value"><?php echo date('F j, Y, g:i a', strtotime($user['register_date'])); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User Recent Orders -->
<div class="admin-card" data-aos="fade-up">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-shopping-bag"></i> Recent Orders</h2>
        <a href="../admin/orders.php?user=<?php echo $user_id; ?>" class="btn btn-small">View All Orders</a>
    </div>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($ordersResult->num_rows > 0): ?>
                    <?php while ($order = $ordersResult->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                            <td><?php echo formatCurrency($order['total_amount']); ?></td>
                            <td>
                                <span class="status-badge <?php echo getStatusClass($order['status']); ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-icon" title="View Order">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">No orders found for this user.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
