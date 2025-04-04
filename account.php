<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediBuddy - Online Pharmacy</title>
    <link rel="stylesheet" href="css/main.css">
    <!-- AOS Animation Framework CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
</head>
<body>
    <!-- Header - Bevat logo, zoekbalk en navigatie-iconen -->
    <header class="site-header">
        <div class="container header-container">
            <div class="logo-container">
                <span class="logo">MediBuddy</span>
            </div>
            <div class="search-bar">
                <input type="text" class="search-input" placeholder="Search for medicines...">
                <span class="search-icon">🔍</span>
            </div>
            <div class="nav-icons">
                <button id="theme-toggle" class="theme-toggle" aria-label="Toggle dark mode">
                    <span id="theme-icon" class="theme-icon">🌙</span>
                </button>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="user-menu">
                        <span class="icon user-icon active">👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <a href="logout.php" class="logout-btn">Logout</a>
                    </div>
                <?php else: ?>
                    <a href="login.php"><span class="icon user-icon">👤</span></a>
                <?php endif; ?>
                <div class="icon cart-icon">
                    <span>🛒</span>
                    <span class="cart-badge">3</span>
                </div>
            </div>
        </div>
    </header>
 
    <!-- Hero Sectie - Aandachttrekkende intro voor bezoekers -->
    <section class="hero">
        <div class="container hero-container">
            <div class="hero-content" data-aos="fade-right" data-aos-duration="1000">
                <h1 class="hero-title">Your trusted online pharmacy</h1>
                <p class="hero-description">Order your medicines easily and get them delivered to your home.</p>
                <a href="#" class="btn btn-primary hero-cta">Shop Now</a>
            </div>
            <div data-aos="fade-left" data-aos-duration="1000" data-aos-delay="300">
                <img src="img/HomePageIMG.png" alt="Pharmacy image" class="hero-image">
            </div>
        </div>
    </section>
 
    <!-- Categorieën Sectie - Toont productcategorieën voor eenvoudig browsen -->
    <section class="section">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">Shop by Category</h2>
            <div id="categories-container" class="categories-grid">
                <!-- Categorieën worden geladen door JavaScript -->
            </div>
        </div>
    </section>
 
    <!-- Uitgelichte Producten - Toont populaire of promotionele items -->
    <section class="section bg-light">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">Featured Products</h2>
            <div id="products-container" class="products-grid">
                <!-- Producten worden geladen door JavaScript -->
            </div>
        </div>
    </section>
 
    <!-- Footer - Bevat siteinformatie en nuttige links -->
    <footer class="footer">
        <div class="container footer-grid">
            <div data-aos="fade-up" data-aos-delay="100">
                <h3 class="footer-title">MediBuddy</h3>
                <p class="footer-text">Your reliable online pharmacy for all health needs.</p>
            </div>
            <div data-aos="fade-up" data-aos-delay="200">
                <h3 class="footer-subtitle">Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="#" class="footer-link">About Us</a></li>
                    <li><a href="#" class="footer-link">Contact</a></li>
                    <li><a href="#" class="footer-link">Blog</a></li>
                    <li><a href="#" class="footer-link">FAQs</a></li>
                </ul>
            </div>
            <div data-aos="fade-up" data-aos-delay="300">
                <h3 class="footer-subtitle">Policies</h3>
                <ul class="footer-links">
                    <li><a href="#" class="footer-link">Privacy Policy</a></li>
                    <li><a href="#" class="footer-link">Return Policy</a></li>
                </ul>
            </div>
            <div data-aos="fade-up" data-aos-delay="400">
                <h3 class="footer-subtitle">Follow Us</h3>
                <p class="footer-text">📘 Facebook | 🐦 Twitter | 📸 Instagram | 💼 LinkedIn</p>
            </div>
        </div>
        <div class="footer-copyright">© 2025 MediBuddy. All rights reserved.</div>
    </footer>
    
    <!-- JavaScript bestanden - Laden functionaliteit na HTML -->
    <script src="js/app.js"></script>
    
    <!-- AOS Animation Framework JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS animation library
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true,
                mirror: false,
                disable: window.innerWidth < 768
            });
            
            // Add AOS animation attributes to dynamically created elements
            document.addEventListener('DOMNodeInserted', function(e) {
                if (e.target.nodeType === 1) {  // Check if it's an element node
                    if (e.target.classList.contains('category-card')) {
                        e.target.setAttribute('data-aos', 'zoom-in');
                        e.target.setAttribute('data-aos-delay', Math.floor(Math.random() * 300).toString());
                    }
                    if (e.target.classList.contains('product-card')) {
                        e.target.setAttribute('data-aos', 'fade-up');
                        e.target.setAttribute('data-aos-delay', Math.floor(Math.random() * 400).toString());
                    }
                }
            });
        });
    </script>
</body>
</html>

