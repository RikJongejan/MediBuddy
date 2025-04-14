<?php
$current_page = 'home';
$page_title = 'MediBuddy - Online Pharmacy';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// Get featured products for the homepage
$query = "SELECT * FROM products WHERE featured = 1 ORDER BY name LIMIT 8";
$featuredResult = $conn->query($query);
$featuredProducts = [];
while ($product = $featuredResult->fetch_assoc()) {
    $featuredProducts[] = $product;
}

// Get categories for the homepage
$catQuery = "SELECT * FROM categories ORDER BY name LIMIT 10";
$catResult = $conn->query($catQuery);
$categories = [];
while ($category = $catResult->fetch_assoc()) {
    $categories[] = $category;
}

// Include header with extra CSS
$extra_css = '<link rel="stylesheet" href="css/notifications.css">';
require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container hero-container">
        <div class="hero-content" data-aos="fade-right" data-aos-duration="1000">
            <h1 class="hero-title">Your trusted online pharmacy</h1>
            <p class="hero-description">Order your medicines easily and get them delivered to your home.</p>
            <a href="products.php" class="btn btn-primary hero-cta">Shop Now</a>
        </div>
        <div class="hero-image" data-aos="fade-left" data-aos-duration="1000">
            <img src="uploads/HomePageIMG (1).png" alt="Hero Image" class="hero-img">
    </div>
</section>

<!-- Categories Section -->
<section class="section">
    <div class="container">
        <h2 class="section-title" data-aos="fade-up">Shop by Category</h2>
        
        <div class="categories-grid">
            <?php foreach($categories as $index => $category): ?>
                <a href="products.php?category=<?php echo $category['id']; ?>" 
                   class="category-card" 
                   data-aos="zoom-in" 
                   data-aos-delay="<?php echo $index * 50; ?>">
                    <div class="category-icon"><?php echo htmlspecialchars($category['icon']); ?></div>
                    <h3 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="section bg-light">
    <div class="container">
        <h2 class="section-title" data-aos="fade-up">Featured Products</h2>
        
        <div class="products-grid">
            <?php foreach($featuredProducts as $index => $product): ?>
                <div class="product-card" 
                     data-aos="fade-up" 
                     data-aos-delay="<?php echo $index * 50; ?>">
                    <a href="product_details.php?id=<?php echo $product['id']; ?>">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-image">
                    </a>
                    <h3 class="product-title">
                        <a href="product_details.php?id=<?php echo $product['id']; ?>">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </a>
                    </h3>
                    <p class="product-description">
                        <?php echo substr(htmlspecialchars($product['description']), 0, 80) . '...'; ?>
                    </p>
                    <div class="product-footer">
                        <span class="product-price"><?php echo formatCurrency($product['price']); ?></span>
                        <form action="add_to_cart.php" method="post" class="add-to-cart-form">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="add-to-cart">Add to Cart</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php 
// Include footer with extra JavaScript for AJAX cart functionality
$extra_js = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    const addToCartForms = document.querySelectorAll(".add-to-cart-form");
    
    addToCartForms.forEach(form => {
        form.addEventListener("submit", function(e) {
            e.preventDefault();
            
            const productId = this.querySelector("input[name=\'product_id\']").value;
            const quantity = parseInt(this.querySelector("input[name=\'quantity\']").value) || 1;
            
            // Call the function from cart.js
            addToCart(productId, quantity);
        });
    });
});
</script>';

require_once 'includes/footer.php';
?>
