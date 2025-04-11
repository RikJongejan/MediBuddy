<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | MediBuddy</title>
    <link rel="stylesheet" href="css/main.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- AOS Animation Framework CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        .error-container {
            text-align: center;
            padding: 5rem 0;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            color: var(--accent-color);
            margin-bottom: 1rem;
            line-height: 1;
        }
        
        .error-title {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }
        
        .error-text {
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }
        
        .error-icon {
            font-size: 6rem;
            color: var(--text-muted);
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="container header-container">
            <div class="logo-container">
                <a href="index.php"><span class="logo">MediBuddy</span></a>
            </div>
            
            <nav class="main-nav">
                <a href="index.php" class="nav-link">Home</a>
                <a href="products.php" class="nav-link">Products</a>
                <a href="categories.php" class="nav-link">Categories</a>
                <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <a href="admin/dashboard.php" class="nav-link">Admin Panel</a>
                <?php endif; ?>
            </nav>
            
            <button class="mobile-menu-button">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="search-bar">
                <form action="products.php" method="GET">
                    <input type="text" class="search-input" name="search" placeholder="Search for medicines...">
                    <button type="submit" class="search-icon"><i class="fas fa-search"></i></button>
                </form>
            </div>
            
            <div class="nav-icons">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="user-menu">
                        <a href="account.php"><span class="icon user-icon active"><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span></a>
                        <a href="logout.php" class="logout-btn">Logout</a>
                    </div>
                <?php else: ?>
                    <a href="login.php"><span class="icon user-icon"><i class="fas fa-user"></i></span></a>
                <?php endif; ?>
                <a href="cart.php">
                    <div class="icon cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-badge">
                            <?php
                                if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
                                    echo count($_SESSION['cart']);
                                } else {
                                    echo '0';
                                }
                            ?>
                        </span>
                    </div>
                </a>
            </div>
        </div>
    </header>
    
    <!-- Mobile Navigation Menu -->
    <nav class="mobile-nav">
        <a href="index.php" class="nav-link">Home</a>
        <a href="products.php" class="nav-link">Products</a>
        <a href="categories.php" class="nav-link">Categories</a>
        <a href="cart.php" class="nav-link">Cart</a>
        <a href="account.php" class="nav-link">Account</a>
        <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
            <a href="admin/dashboard.php" class="nav-link">Admin Panel</a>
        <?php endif; ?>
    </nav>
    
    <!-- Error Content -->
    <div class="container">
        <div class="error-container" data-aos="fade-up">
            <i class="fas fa-search error-icon"></i>
            <div class="error-code">404</div>
            <h1 class="error-title">Page Not Found</h1>
            <p class="error-text">Oops! The page you are looking for doesn't exist. It might have been moved or deleted, or perhaps you mistyped the URL.</p>
            <div>
                <a href="index.php" class="btn btn-primary">Back to Home</a>
                <a href="products.php" class="btn">Browse Products</a>
            </div>
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
                    <li><a href="#" class="footer-link">Blog</a></li>
                    <li><a href="#" class="footer-link">FAQs</a></li>
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
    </script>
</body>
</html>
