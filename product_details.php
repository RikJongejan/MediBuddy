<?php
$current_page = 'products';
$page_title = 'Product Details';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$product_id = intval($_GET['id']);

try {
    // Get product details
    $query = "SELECT p.*, c.name as category_name, c.icon as category_icon 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE p.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Product not found
        header("Location: products.php?error=product_not_found");
        exit();
    }

    $product = $result->fetch_assoc();

    // Get related products from same category
    $related_query = "SELECT * FROM products 
                     WHERE category_id = ? AND id != ? 
                     ORDER BY RAND() 
                     LIMIT 4";
    $related_stmt = $conn->prepare($related_query);
    $related_stmt->bind_param("ii", $product['category_id'], $product_id);
    $related_stmt->execute();
    $related_result = $related_stmt->get_result();
    $related_products = [];
    
    while ($related = $related_stmt->get_result()->fetch_assoc()) {
        $related_products[] = $related;
    }
    
    $page_title = $product['name'] . ' - MediBuddy';
} catch (Exception $e) {
    // Log the error but don't show it to users
    error_log("Product details error: " . $e->getMessage());
    header("Location: products.php?error=database");
    exit();
}

// Include the header with extra CSS for notifications and product styling
$extra_css = '<link rel="stylesheet" href="css/notifications.css">
              <link rel="stylesheet" href="css/product-page.css">';
$extra_css = isset($extra_css) ? $extra_css . '<link rel="stylesheet" href="css/chatbot.css">' : '<link rel="stylesheet" href="css/chatbot.css">';
require_once 'includes/header.php';
?>

<!-- Breadcrumbs -->
<div class="breadcrumbs">
    <div class="container">
        <a href="index.php">Home</a> &gt; 
        <a href="products.php">Products</a> &gt; 
        <?php if(!empty($product['category_name'])): ?>
            <a href="products.php?category=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a> &gt; 
        <?php endif; ?>
        <?php echo htmlspecialchars($product['name']); ?>
    </div>
</div>

<!-- Product Detail -->
<section class="product-detail">
    <div class="container">
        <?php if(isset($_SESSION['cart_message'])): ?>
            <div class="alert alert-success" data-aos="fade-up">
                <?php echo $_SESSION['cart_message']; ?>
                <?php unset($_SESSION['cart_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['cart_error'])): ?>
            <div class="alert alert-error" data-aos="fade-up">
                <?php echo $_SESSION['cart_error']; ?>
                <?php unset($_SESSION['cart_error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="product-detail-grid" data-aos="fade-up">
            <div class="product-image-container">
                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-detail-image">
                
                <!-- Product badges -->
                <div class="product-badges">
                    <?php if($product['featured']): ?>
                        <span class="badge featured-badge">Featured</span>
                    <?php endif; ?>
                    
                    <?php if($product['stock_quantity'] < 10 && $product['stock_quantity'] > 0): ?>
                        <span class="badge low-stock-badge">Low Stock</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="product-info">
                <h1 class="product-detail-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <?php if(!empty($product['category_name'])): ?>
                    <div class="product-category">
                        <?php if(!empty($product['category_icon'])): ?>
                            <span class="category-icon"><?php echo $product['category_icon']; ?></span>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="product-price"><?php echo formatCurrency($product['price']); ?></div>
                
                <div class="product-availability">
                    <?php if($product['stock_quantity'] > 0): ?>
                        <span class="in-stock"><i class="fas fa-check-circle"></i> In Stock (<?php echo $product['stock_quantity']; ?> available)</span>
                    <?php else: ?>
                        <span class="out-of-stock"><i class="fas fa-times-circle"></i> Out of Stock</span>
                    <?php endif; ?>
                </div>
                
                <div class="product-description-short"><?php echo nl2br(htmlspecialchars($product['description'])); ?></div>
                
                <?php if($product['stock_quantity'] > 0): ?>
                    <form action="add_to_cart.php" method="post" class="product-actions add-to-cart-form">
                        <div class="quantity-selector">
                            <button type="button" class="qty-btn decrease">-</button>
                            <input type="number" name="quantity" class="qty-input" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                            <button type="button" class="qty-btn increase">+</button>
                        </div>
                        
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <button type="submit" class="btn btn-primary add-to-cart-btn">
                            <i class="fas fa-shopping-cart"></i>
                            Add to Cart
                        </button>
                    </form>
                <?php else: ?>
                    <button class="btn btn-primary add-to-cart-btn" disabled>Out of Stock</button>
                <?php endif; ?>
                
                <div class="delivery-info">
                    <div class="info-item">
                        <i class="fas fa-truck"></i>
                        <span>Free shipping on orders over $50</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-undo"></i>
                        <span>30-day return policy</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Secure checkout</span>
                    </div>
                    <?php if(!empty($product['expiry_date'])): ?>
                        <div class="info-item">
                            <i class="fas fa-calendar-check"></i>
                            <span>Expiry: <?php echo date('M Y', strtotime($product['expiry_date'])); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Product Description and Details -->
<section class="product-description">
    <div class="container">
        <div class="description-tabs" data-aos="fade-up">
            <div class="tab-headers">
                <button class="tab-btn active" data-tab="description">Description</button>
                <button class="tab-btn" data-tab="details">Product Details</button>
                <button class="tab-btn" data-tab="delivery">Shipping & Returns</button>
            </div>
            
            <div class="tab-content">
                <div class="tab-pane active" id="description-tab">
                    <h3>Product Description</h3>
                    <div class="rich-text-content">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </div>
                </div>
                
                <div class="tab-pane" id="details-tab">
                    <h3>Product Details</h3>
                    <ul class="product-specs">
                        <li><strong>SKU:</strong> MED-<?php echo $product['id']; ?></li>
                        <li><strong>Category:</strong> <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></li>
                        <?php if(!empty($product['contents'])): ?>
                            <li><strong>Contents:</strong> <?php echo htmlspecialchars($product['contents']); ?></li>
                        <?php endif; ?>
                        <?php if(!empty($product['dosage'])): ?>
                            <li><strong>Dosage:</strong> <?php echo htmlspecialchars($product['dosage']); ?></li>
                        <?php endif; ?>
                        <?php if(!empty($product['expiry_date'])): ?>
                            <li><strong>Expiry:</strong> <?php echo date('M Y', strtotime($product['expiry_date'])); ?></li>
                        <?php endif; ?>
                        <li><strong>Last Updated:</strong> <?php echo date('M j, Y', strtotime($product['updated_at'] ?? $product['created_at'])); ?></li>
                    </ul>
                </div>
                
                <div class="tab-pane" id="delivery-tab">
                    <h3>Shipping Information</h3>
                    <p>We offer fast and reliable shipping services:</p>
                    <ul class="product-specs">
                        <li><strong>Standard Delivery:</strong> 3-5 business days ($4.99)</li>
                        <li><strong>Express Delivery:</strong> 1-2 business days ($9.99)</li>
                        <li><strong>Free Shipping:</strong> On orders over $50</li>
                    </ul>
                    
                    <h3>Return Policy</h3>
                    <p>If you're not completely satisfied with your purchase, you can return it within 30 days for a full refund.</p>
                    <p>Please note that all returned products must be unused and in their original packaging.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Related Products -->
<section class="section related-products bg-light">
    <div class="container">
        <h2 class="section-title" data-aos="fade-up">Related Products</h2>
        
        <?php if($related_result->num_rows > 0): ?>
            <div class="products-grid">
                <?php while($related = $related_result->fetch_assoc()): ?>
                    <div class="product-card" data-aos="fade-up">
                        <a href="product_details.php?id=<?php echo $related['id']; ?>">
                            <img src="<?php echo htmlspecialchars($related['image']); ?>" alt="<?php echo htmlspecialchars($related['name']); ?>" class="product-image">
                        </a>
                        <h3 class="product-title">
                            <a href="product_details.php?id=<?php echo $related['id']; ?>">
                                <?php echo htmlspecialchars($related['name']); ?>
                            </a>
                        </h3>
                        <p class="product-description">
                            <?php echo substr(htmlspecialchars($related['description']), 0, 80) . '...'; ?>
                        </p>
                        <div class="product-footer">
                            <span class="product-price"><?php echo formatCurrency($related['price']); ?></span>
                            <form action="add_to_cart.php" method="post" class="add-to-cart-form">
                                <input type="hidden" name="product_id" value="<?php echo $related['id']; ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="add-to-cart">Add to Cart</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-related-message">
                <p>No related products found for this category.</p>
                <a href="products.php" class="btn btn-primary">Browse All Products</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
// Include additional JavaScript for the product details page
$extra_js = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Handle Add to Cart form submission with AJAX
    const addToCartForm = document.querySelector(".add-to-cart-form");
    
    if (addToCartForm) {
        addToCartForm.addEventListener("submit", function(e) {
            e.preventDefault();
            
            const productId = this.querySelector("input[name=\'product_id\']").value;
            const quantity = parseInt(this.querySelector("input[name=\'quantity\']").value) || 1;
            
            addToCart(productId, quantity);
        });
    }
    
    // Handle quantity buttons
    const decreaseBtn = document.querySelector(".decrease");
    const increaseBtn = document.querySelector(".increase");
    const quantityInput = document.querySelector(".qty-input");
    
    if (decreaseBtn && increaseBtn && quantityInput) {
        const maxQuantity = parseInt(quantityInput.getAttribute("max"));
        
        decreaseBtn.addEventListener("click", function() {
            let currentValue = parseInt(quantityInput.value);
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });
        
        increaseBtn.addEventListener("click", function() {
            let currentValue = parseInt(quantityInput.value);
            if (currentValue < maxQuantity) {
                quantityInput.value = currentValue + 1;
            }
        });
    }
    
    // Handle tabs
    const tabButtons = document.querySelectorAll(".tab-btn");
    const tabPanes = document.querySelectorAll(".tab-pane");
    
    tabButtons.forEach(button => {
        button.addEventListener("click", function() {
            // Remove active class from all buttons and panes
            tabButtons.forEach(btn => btn.classList.remove("active"));
            tabPanes.forEach(pane => pane.classList.remove("active"));
            
            // Add active class to clicked button
            this.classList.add("active");
            
            // Show corresponding tab content
            const tabId = this.getAttribute("data-tab");
            document.getElementById(tabId + "-tab").classList.add("active");
        });
    });
    
    // Image zoom effect on hover
    const productImage = document.querySelector(".product-detail-image");
    
    if (productImage) {
        productImage.addEventListener("mouseover", function() {
            this.style.transform = "scale(1.05)";
        });
        
        productImage.addEventListener("mouseout", function() {
            this.style.transform = "scale(1)";
        });
    }
});
</script>';
include_once 'includes/chatbot.php';
$extra_js = isset($extra_js) ? $extra_js . '<script src="js/chatbot.js"></script>' : '<script src="js/chatbot.js"></script>';

require_once 'includes/footer.php';
?>
