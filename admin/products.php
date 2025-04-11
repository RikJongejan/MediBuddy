<?php
$current_page = 'admin';
$current_admin_page = 'products';
$page_title = 'Manage Products';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

// Add admin.css to admin pages
$extra_css = '<link rel="stylesheet" href="../css/admin.css">';

session_start();
requireAdmin();

// Handle product deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $productId = intval($_GET['id']);
    
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    
    if ($stmt->execute()) {
        $successMessage = "Product deleted successfully.";
    } else {
        $errorMessage = "Failed to delete product: " . $conn->error;
    }
}

// Pagination setup
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search functionality
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$searchFilter = '';
if (!empty($searchTerm)) {
    $searchTerm = $conn->real_escape_string($searchTerm);
    $searchFilter = " WHERE name LIKE '%$searchTerm%' OR description LIKE '%$searchTerm%'";
}

// Get total products
$countQuery = "SELECT COUNT(*) as total FROM products" . $searchFilter;
$countResult = $conn->query($countQuery);
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// Get products with pagination
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          $searchFilter 
          ORDER BY p.id DESC LIMIT $offset, $limit";
$result = $conn->query($query);

require_once '../includes/admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - MediBuddy Admin</title>
    <link rel="stylesheet" href="../css/main.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- AOS Animation Framework CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->  
        <!-- Main Content -->
        <main class="admin-content">
            <div class="admin-header">
                <h1 class="admin-title" data-aos="fade-right">Product Management</h1>
                <div class="admin-actions" data-aos="fade-left">
                    <a href="add_product.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Product</a>
                </div>
            </div>
            
            <?php if (isset($successMessage)): ?>
                <div class="alert alert-success" data-aos="fade-up">
                    <?php echo $successMessage; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($errorMessage)): ?>
                <div class="alert alert-error" data-aos="fade-up">
                    <?php echo $errorMessage; ?>
                </div>
            <?php endif; ?>
            
            <div class="admin-card" data-aos="fade-up">
                <div class="card-header">
                    <h2 class="card-title">Products</h2>
                    <form action="" method="GET" class="search-form">
                        <div class="search-container">
                            <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($searchTerm); ?>" class="form-input">
                            <button type="submit" class="btn btn-small"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                </div>
                
                <div class="table-responsive">
                    <table class="product-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Featured</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($product = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $product['id']; ?></td>
                                        <td>
                                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="image-preview" style="width: 50px; height: 50px;">
                                        </td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                        <td>$<?php echo number_format($product['price'], 2); ?></td>
                                        <td><?php echo $product['stock_quantity']; ?></td>
                                        <td>
                                            <?php if ($product['featured']): ?>
                                                <i class="fas fa-star" style="color: gold;"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-small"><i class="fas fa-edit"></i> Edit</a>
                                            <a href="products.php?action=delete&id=<?php echo $product['id']; ?>" class="btn btn-small btn-danger" onclick="return confirm('Are you sure you want to delete this product?');"><i class="fas fa-trash"></i> Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center;">No products found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination" style="margin-top: 20px; text-align: center;">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1 . (!empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''); ?>" class="btn btn-small">Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i . (!empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''); ?>" 
                               class="btn btn-small <?php echo $i == $page ? 'btn-primary' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1 . (!empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''); ?>" class="btn btn-small">Next</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Footer -->
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
