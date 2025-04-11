<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Require admin authentication
require_once __DIR__ . '/functions.php';
requireAdmin();

// Get the current page for navigation highlighting
$current_admin_page = $current_admin_page ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>MediBuddy Admin</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- AOS Animation Framework CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <?php if (isset($extra_css)) { echo $extra_css; } ?>
    <?php if (isset($extra_head)) { echo $extra_head; } ?>
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="container header-container">
            <div class="logo-container">
                <a href="../index.php"><span class="logo">MediBuddy Admin Panel</span></a>
            </div>
            
            <nav class="main-nav">
                <a href="../index.php" class="nav-link">Store Front</a>
                <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'admin') ? 'active' : ''; ?>">Admin Panel</a>
            </nav>
            
            <button class="mobile-menu-button">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="nav-icons">
                <div class="user-menu">
                    <a href="../account.php">
                        <span class="icon user-icon <?php echo ($current_page == 'account') ? 'active' : ''; ?>">
                            <i class="fas fa-user"></i> 
                            <?php echo htmlspecialchars(isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin'); ?>
                        </span>
                    </a>
                    <a href="../logout.php" class="logout-btn" title="Logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
                <a href="#" id="adminNotificationsBtn" class="admin-notification-icon">
                    <i class="fas fa-bell"></i>
                    <?php
                        // Count pending orders for notifications
                        $notifQuery = "SELECT COUNT(*) as count FROM orders WHERE status = 'Pending'";
                        $notifResult = $conn->query($notifQuery);
                        $notifCount = 0;
                        if ($notifResult && $notifResult->num_rows > 0) {
                            $notifCount = $notifResult->fetch_assoc()['count'];
                        }
                        if ($notifCount > 0):
                    ?>
                        <span class="notification-badge"><?php echo $notifCount; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </header>
    
    <!-- Mobile Navigation Menu -->
    <nav class="mobile-nav">
        <a href="../index.php" class="nav-link">Store Front</a>
        <a href="dashboard.php" class="nav-link">Dashboard</a>
        <a href="../logout.php" class="nav-link">Logout</a>
    </nav>
    
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <h3 class="sidebar-title">Admin Panel</h3>
            <nav class="admin-menu">
                <a href="dashboard.php" class="menu-item <?php echo ($current_admin_page == 'dashboard') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="products.php" class="menu-item <?php echo ($current_admin_page == 'products') ? 'active' : ''; ?>">
                    <i class="fas fa-pills"></i> Products
                </a>
                <a href="categories.php" class="menu-item <?php echo ($current_admin_page == 'categories') ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i> Categories
                </a>
                <a href="orders.php" class="menu-item <?php echo ($current_admin_page == 'orders') ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-bag"></i> Orders
                </a>
                <a href="users.php" class="menu-item <?php echo ($current_admin_page == 'users') ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="tracking.php" class="menu-item <?php echo ($current_admin_page == 'tracking') ? 'active' : ''; ?>">
                    <i class="fas fa-shipping-fast"></i> Order Tracking
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-content">
