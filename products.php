<?php
$current_page = 'products';
$page_title = 'Products';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// Get category filter
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;

// Get search query
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination setup
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 12; // 12 products per page (3x4 grid)
$offset = ($page - 1) * $limit;

// Build query condition based on filters
$condition = "";
$params = [];
$types = "";

if ($category_id > 0) {
    $condition .= "category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

if (!empty($search)) {
    if (!empty($condition)) {
        $condition .= " AND ";
    }
    $condition .= "(name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

// Build full query with condition
$countQuery = "SELECT COUNT(*) as total FROM products";
$query = "SELECT * FROM products";

if (!empty($condition)) {
    $countQuery .= " WHERE $condition";
    $query .= " WHERE $condition";
}

$query .= " ORDER BY name LIMIT ?, ?";  // Fixed the missing $ in $query
$params[] = $offset;
$params[] = $limit;
$types .= "ii";

// Count total products
$countStmt = $conn->prepare($countQuery);
if (!empty($params) && !empty($condition)) {
    $countParams = $params;
    array_splice($countParams, -2); // Remove the last two parameters (offset and limit)
    $countTypes = substr($types, 0, -2); // Remove the last two types
    $countStmt->bind_param($countTypes, ...$countParams);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// Get products
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get categories for filter
$categoriesQuery = "SELECT * FROM categories ORDER BY name";
$categoriesResult = $conn->query($categoriesQuery);
$categories = [];
while ($category = $categoriesResult->fetch_assoc()) {
    $categories[] = $category;
}

// Include header
$extra_css = '<link rel="stylesheet" href="css/notifications.css">
              <link rel="stylesheet" href="css/product-page.css">';
$extra_css = isset($extra_css) ? $extra_css . '<link rel="stylesheet" href="css/chatbot.css">' : '<link rel="stylesheet" href="css/chatbot.css">';
require_once 'includes/header.php';
?>

<!-- Breadcrumbs -->
<div class="breadcrumbs">
    <div class="container">
        <a href="index.php">Home</a> &gt; Products
        <?php
        if ($category_id > 0) {
            $catQuery = "SELECT name FROM categories WHERE id = ?";
            $catStmt = $conn->prepare($catQuery);
            $catStmt->bind_param("i", $category_id);
            $catStmt->execute();
            $catResult = $catStmt->get_result();
            if ($catResult->num_rows > 0) {
                $categoryName = $catResult->fetch_assoc()['name'];
                echo " &gt; " . htmlspecialchars($categoryName);
            }
        }
        ?>
    </div>
</div>

<section class="section">
    <div class="container">
        <h1 class="page-title" data-aos="fade-up">Our Products</h1>
        
        <?php if (isset($_SESSION['cart_message'])): ?>
            <div class="alert alert-success" data-aos="fade-up">
                <?php 
                echo $_SESSION['cart_message']; 
                unset($_SESSION['cart_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['cart_error'])): ?>
            <div class="alert alert-error" data-aos="fade-up">
                <?php 
                echo $_SESSION['cart_error']; 
                unset($_SESSION['cart_error']);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="products-container">
            <div class="filters-sidebar" data-aos="fade-right">
                <div class="filter-section">
                    <h3 class="filter-title">Categories</h3>
                    <ul class="filter-list">
                        <li>
                            <a href="products.php<?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?>" 
                               class="filter-item <?php if($category_id == 0) echo 'active'; ?>">
                                All Categories
                            </a>
                        </li>
                        <?php foreach($categories as $category): ?>
                            <li>
                                <a href="products.php?category=<?php echo $category['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="filter-item <?php if($category_id == $category['id']) echo 'active'; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <div class="products-main" data-aos="fade-up">
                <?php if($result->num_rows > 0): ?>
                    <div class="products-grid">
                        <?php while($product = $result->fetch_assoc()): ?>
                            <div class="product-card" data-aos="fade-up">
                                <a href="product_details.php?id=<?php echo $product['id']; ?>">
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
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
                                    <span class="product-price">$<?php echo number_format($product['price'], 2); ?></span>
                                    <form action="add_to_cart.php" method="post" class="add-to-cart-form">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="add-to-cart">Add to Cart</button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if($totalPages > 1): ?>
                        <div class="pagination" data-aos="fade-up">
                            <?php if($page > 1): ?>
                                <a href="products.php?page=<?php echo $page - 1; ?><?php echo $category_id > 0 ? '&category=' . $category_id : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link prev">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="products.php?page=<?php echo $i; ?><?php echo $category_id > 0 ? '&category=' . $category_id : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="page-link <?php if($i == $page) echo 'active'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if($page < $totalPages): ?>
                                <a href="products.php?page=<?php echo $page + 1; ?><?php echo $category_id > 0 ? '&category=' . $category_id : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link next">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-products-found" data-aos="fade-up">
                        <div class="empty-cart-icon"><i class="fas fa-search"></i></div>
                        <h2>No Products Found</h2>
                        <p>We couldn't find any products matching your criteria.</p>
                        <a href="products.php" class="btn btn-primary">View All Products</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php 
// Include chatbot component
include_once 'includes/chatbot.php';

// Include footer with extra JavaScript
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
$extra_js = isset($extra_js) ? $extra_js . '<script src="js/chatbot.js"></script>' : '<script src="js/chatbot.js"></script>';

require_once 'includes/footer.php';
?>
