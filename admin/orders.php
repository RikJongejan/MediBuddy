<?php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

// Check if user is admin
requireAdmin();

// Process order status update
if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['status'])) {
    $orderId = intval($_POST['order_id']);
    $status = sanitizeInput($conn, $_POST['status']);
    $description = "Order status updated to " . $status;
    
    try {
        $conn->begin_transaction();
        
        // Update order status
        $updateQuery = "UPDATE orders SET status = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("si", $status, $orderId);
        $updateStmt->execute();
        
        // Add tracking entry
        $trackingQuery = "INSERT INTO order_tracking (order_id, status, description, tracking_date) VALUES (?, ?, ?, NOW())";
        $trackingStmt = $conn->prepare($trackingQuery);
        $trackingStmt->bind_param("iss", $orderId, $status, $description);
        $trackingStmt->execute();
        
        $conn->commit();
        $statusMessage = "Order status updated successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $errorMessage = "Failed to update order status: " . $e->getMessage();
    }
}

// Get status filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Get search query
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination setup
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query with filters
$whereClause = [];
$params = [];
$types = '';

if (!empty($statusFilter)) {
    $whereClause[] = "o.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

if (!empty($searchQuery)) {
    $whereClause[] = "(o.order_number LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
    $searchParam = "%$searchQuery%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}

// Construct the WHERE clause
$whereStr = '';
if (!empty($whereClause)) {
    $whereStr = ' WHERE ' . implode(' AND ', $whereClause);
}

// Count total orders with filter
$countQuery = "SELECT COUNT(*) as total FROM orders o 
               JOIN users u ON o.user_id = u.id" . $whereStr;

try {
    $countStmt = $conn->prepare($countQuery);
    
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalRows = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRows / $limit);
} catch (Exception $e) {
    $errorMessage = "Error counting orders: " . $e->getMessage();
    $totalRows = 0;
    $totalPages = 0;
}

// Get orders with pagination
$query = "SELECT o.*, u.username, u.email FROM orders o 
          JOIN users u ON o.user_id = u.id" . $whereStr . "
          ORDER BY o.order_date DESC LIMIT ?, ?";

try {
    $stmt = $conn->prepare($query);
    
    // Add pagination parameters
    $paginationTypes = $types . 'ii';
    $paginationParams = array_merge($params, [$offset, $limit]);
    
    if (!empty($paginationParams)) {
        $stmt->bind_param($paginationTypes, ...$paginationParams);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
} catch (Exception $e) {
    $errorMessage = "Error retrieving orders: " . $e->getMessage();
    $result = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - MediBuddy Admin</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/responsive.css">
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
                <h1 class="admin-title" data-aos="fade-right">Orders Management</h1>
                <div class="admin-actions" data-aos="fade-left">
                    <form action="" method="GET" class="search-form">
                        <div class="search-container">
                            <input type="text" name="search" placeholder="Search orders..." value="<?php echo htmlspecialchars($searchQuery); ?>" class="form-input">
                            <button type="submit" class="btn btn-small"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if (isset($statusMessage)): ?>
                <div class="alert alert-success" data-aos="fade-up">
                    <?php echo $statusMessage; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($errorMessage)): ?>
                <div class="alert alert-error" data-aos="fade-up">
                    <?php echo $errorMessage; ?>
                </div>
            <?php endif; ?>
            
            <!-- Order Filters -->
            <div class="filter-tabs" data-aos="fade-up">
                <a href="orders.php" class="filter-tab <?php echo empty($statusFilter) ? 'active' : ''; ?>">
                    All Orders
                </a>
                <a href="orders.php?status=Pending<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="filter-tab <?php echo $statusFilter === 'Pending' ? 'active' : ''; ?>">
                    Pending
                </a>
                <a href="orders.php?status=Processing<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="filter-tab <?php echo $statusFilter === 'Processing' ? 'active' : ''; ?>">
                    Processing
                </a>
                <a href="orders.php?status=Shipped<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="filter-tab <?php echo $statusFilter === 'Shipped' ? 'active' : ''; ?>">
                    Shipped
                </a>
                <a href="orders.php?status=Delivered<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="filter-tab <?php echo $statusFilter === 'Delivered' ? 'active' : ''; ?>">
                    Delivered
                </a>
                <a href="orders.php?status=Cancelled<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="filter-tab <?php echo $statusFilter === 'Cancelled' ? 'active' : ''; ?>">
                    Cancelled
                </a>
            </div>
            
            <!-- Orders Table -->
            <div class="admin-card" data-aos="fade-up">
                <div class="table-responsive">
                    <table class="order-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Order Number</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($result && $result->num_rows > 0): 
                                while ($order = $result->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($order['username']); ?><br>
                                        <small><?php echo htmlspecialchars($order['email']); ?></small>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                    <td><?php echo formatCurrency($order['total_amount']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo getStatusClass($order['status']); ?>">
                                            <?php echo htmlspecialchars($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-small"><i class="fas fa-eye"></i> View</a>
                                        
                                        <button type="button" class="btn btn-small" onclick="openStatusModal(<?php echo $order['id']; ?>, '<?php echo htmlspecialchars($order['status']); ?>')">
                                            <i class="fas fa-edit"></i> Status
                                        </button>
                                    </td>
                                </tr>
                            <?php 
                                endwhile; 
                            elseif(isset($errorMessage)): 
                            ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">Error loading orders.</td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">No orders found with the selected criteria.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if (isset($totalPages) && $totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="page-link">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Update Status Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Update Order Status</h2>
            <form action="" method="post">
                <input type="hidden" name="order_id" id="modalOrderId">
                
                <div class="form-group">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="modalStatus" class="form-input" required>
                        <option value="Pending">Pending</option>
                        <option value="Processing">Processing</option>
                        <option value="Shipped">Shipped</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="form-group" style="text-align: right;">
                    <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
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
    
    <!-- Add CSS for modal -->
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: var(--card-bg);
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
            max-width: 90%;
            box-shadow: var(--shadow-lg);
        }
        
        .close-modal {
            color: var(--text-secondary);
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .filter-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .filter-tab {
            padding: 0.5rem 1rem;
            background-color: var(--bg-secondary);
            border-radius: 4px;
            color: var(--text-secondary);
            transition: all 0.2s ease;
        }
        
        .filter-tab.active, .filter-tab:hover {
            background-color: var(--accent-color);
            color: white;
        }
    </style>
    
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
        });
        
        // Modal functions
        const modal = document.getElementById("statusModal");
        const modalOrderId = document.getElementById("modalOrderId");
        const modalStatus = document.getElementById("modalStatus");
        const closeBtn = document.querySelector(".close-modal");
        
        function openStatusModal(orderId, currentStatus) {
            modalOrderId.value = orderId;
            modalStatus.value = currentStatus;
            modal.style.display = "block";
        }
        
        function closeModal() {
            modal.style.display = "none";
        }
        
        closeBtn.onclick = closeModal;
        
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
