<?php
$current_page = 'categories';
$page_title = 'Categories';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// Get all categories with product counts
$query = "SELECT c.*, COUNT(p.id) as product_count FROM categories c 
          LEFT JOIN products p ON c.id = p.category_id 
          GROUP BY c.id 
          ORDER BY c.name ASC";
$result = $conn->query($query);

$categories = [];
while($category = $result->fetch_assoc()) {
    $categories[] = $category;
}

// Include header with category-specific styling
$extra_css = '<link rel="stylesheet" href="css/notifications.css">
              <link rel="stylesheet" href="css/product-page.css">';
require_once 'includes/header.php';
?>

<!-- Breadcrumbs -->
<div class="breadcrumbs">
    <div class="container">
        <a href="index.php">Home</a> &gt; Categories
    </div>
</div>

<!-- Categories Section -->
<section class="section">
    <div class="container">
        <h1 class="page-title" data-aos="fade-up">Categories</h1>
        <p class="section-description" data-aos="fade-up">Browse our products by category to find exactly what you need.</p>
        
        <div class="categories-grid large-grid" data-aos="fade-up">
            <?php foreach($categories as $index => $category): ?>
                <a href="products.php?category=<?php echo $category['id']; ?>" 
                   class="category-card" 
                   data-aos="zoom-in"
                   data-aos-delay="<?php echo $index * 50; ?>">
                    <div class="category-icon"><?php echo htmlspecialchars($category['icon']); ?></div>
                    <h3 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h3>
                    <p class="category-count"><?php echo $category['product_count']; ?> Products</p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
