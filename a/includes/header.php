<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Use absolute path to ensure auth.php can be found
$auth_path = __DIR__ . '/auth.php';
if (file_exists($auth_path)) {
    require_once $auth_path;
} else {
    // Fallback if auth.php doesn't exist
    function check_auth() {
        return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
}

// Initialize cart in session if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get cart count
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = count($_SESSION['cart']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>MediBuddy</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/responsive.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- AOS Animation Framework CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <?php if (isset($extra_css)) { echo $extra_css; } ?>
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="container header-container">
            <div class="logo-container">
                <a href="index.php"><span class="logo">MediBuddy</span></a>
            </div>
            
            <nav class="main-nav">
                <a href="index.php" class="nav-link <?php echo ($current_page == 'home') ? 'active' : ''; ?>">Home</a>
                <a href="products.php" class="nav-link <?php echo ($current_page == 'products') ? 'active' : ''; ?>">Products</a>
                <a href="categories.php" class="nav-link <?php echo ($current_page == 'categories') ? 'active' : ''; ?>">Categories</a>
                <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <a href="admin/dashboard.php" class="nav-link <?php echo ($current_page == 'admin') ? 'active' : ''; ?>">Admin Panel</a>
                <?php endif; ?>
            </nav>
            
            <button class="mobile-menu-button">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="search-bar">
                <form action="products.php" method="GET">
                    <input type="text" class="search-input" name="search" placeholder="Search for medicines..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit" class="search-icon"><i class="fas fa-search"></i></button>
                </form>
            </div>
            
            <div class="nav-icons">
                <?php if(isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                    <div class="user-menu">
                        <a href="account.php">
                            <span class="icon user-icon <?php echo ($current_page == 'account') ? 'active' : ''; ?>">
                                <i class="fas fa-user"></i> 
                                <?php echo htmlspecialchars(isset($_SESSION['username']) ? $_SESSION['username'] : 'User'); ?>
                            </span>
                        </a>
                        <a href="logout.php" class="logout-btn" title="Logout">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                <?php else: ?>
                    <a href="login.php">
                        <span class="icon user-icon <?php echo ($current_page == 'login') ? 'active' : ''; ?>">
                            <i class="fas fa-user"></i>
                        </span>
                    </a>
                <?php endif; ?>
                <a href="cart.php">
                    <div class="icon cart-icon <?php echo ($current_page == 'cart') ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-badge" id="cart-count"><?php echo $cart_count; ?></span>
                    </div>
                </a>
            </div>
        </div>
    </header>
    
    <!-- Mobile Navigation Menu -->
    <nav class="mobile-nav">
        <a href="index.php" class="nav-link <?php echo ($current_page == 'home') ? 'active' : ''; ?>">Home</a>
        <a href="products.php" class="nav-link <?php echo ($current_page == 'products') ? 'active' : ''; ?>">Products</a>
        <a href="categories.php" class="nav-link <?php echo ($current_page == 'categories') ? 'active' : ''; ?>">Categories</a>
        <a href="cart.php" class="nav-link <?php echo ($current_page == 'cart') ? 'active' : ''; ?>">Cart</a>
        
        <?php if(isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <a href="account.php" class="nav-link <?php echo ($current_page == 'account') ? 'active' : ''; ?>">My Account</a>
            <a href="logout.php" class="nav-link">Logout</a>
        <?php else: ?>
            <a href="login.php" class="nav-link <?php echo ($current_page == 'login') ? 'active' : ''; ?>">Login</a>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
            <a href="admin/dashboard.php" class="nav-link <?php echo ($current_page == 'admin') ? 'active' : ''; ?>">Admin Panel</a>
        <?php endif; ?>
    </nav>
    
    <?php if(isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error container" style="margin-top: 1rem;">
            <?php 
                echo $_SESSION['error_message']; 
                unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($_GET['logout']) && $_GET['logout'] == 'success'): ?>
        <div class="alert alert-success container" style="margin-top: 1rem;">
            You have been successfully logged out.
        </div>
    <?php endif; ?>
