<?php
$current_page = 'cart';
$page_title = 'Your Cart';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Initialize variables
$cart_items = [];
$subtotal = 0;
$shipping_fee = 5.00; // Fixed shipping fee
$free_shipping_threshold = 50.00; // Free shipping for orders over $50

// Process cart updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart']) && isset($_POST['quantity']) && is_array($_POST['quantity'])) {
        foreach ($_POST['quantity'] as $product_id => $quantity) {
            $product_id = (int)$product_id;
            $quantity = (int)$quantity;
            
            if ($quantity > 0) {
                $_SESSION['cart'][$product_id] = $quantity;
            } else {
                unset($_SESSION['cart'][$product_id]);
            }
        }
        
        // Show success message
        $_SESSION['cart_message'] = "Cart updated successfully!";
        header("Location: cart.php");
        exit;
    }
}

// Remove item from cart
if (isset($_GET['remove']) && isset($_SESSION['cart'][$_GET['remove']])) {
    $product_id = (int)$_GET['remove'];
    unset($_SESSION['cart'][$product_id]);
    $_SESSION['cart_message'] = "Item removed from cart!";
    header("Location: cart.php");
    exit;
}

// Get cart items from database
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    
    if (!empty($product_ids)) {
        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
        $types = str_repeat('i', count($product_ids));
        
        $stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->bind_param($types, ...$product_ids);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($product = $result->fetch_assoc()) {
            $product_id = $product['id'];
            // Make sure the quantity is an integer
            $quantity = isset($_SESSION['cart'][$product_id]) ? (int)$_SESSION['cart'][$product_id] : 0;
            $price = (float)$product['price'];
            $item_total = $price * $quantity; // Now multiplying a float by an int, which is supported
            
            // Limit to available stock
            if ($quantity > $product['stock_quantity']) {
                $quantity = $product['stock_quantity'];
                $_SESSION['cart'][$product_id] = $quantity;
                $_SESSION['cart_warning'] = "Some items in your cart were adjusted due to stock limitations.";
            }
            
            $cart_items[] = [
                'id' => $product_id,
                'name' => $product['name'],
                'image' => $product['image'],
                'price' => $price,
                'stock_quantity' => $product['stock_quantity'],
                'quantity' => $quantity,
                'total' => $item_total
            ];
            
            $subtotal += $item_total;
        }
    }
}

// Calculate shipping and total
$shipping = ($subtotal >= $free_shipping_threshold) ? 0 : $shipping_fee;
$total = $subtotal + $shipping;
?>

<!-- Breadcrumbs -->
<div class="breadcrumbs">
    <div class="container">
        <a href="index.php">Home</a> &gt; Shopping Cart
    </div>
</div>

<!-- Shopping Cart Section -->
<section class="section">
    <div class="container">
        <h1 class="page-title" data-aos="fade-up">Your Shopping Cart</h1>
        
        <?php if (isset($_SESSION['cart_message'])): ?>
            <div class="alert alert-success" data-aos="fade-up">
                <?php 
                echo $_SESSION['cart_message']; 
                unset($_SESSION['cart_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['cart_warning'])): ?>
            <div class="alert alert-warning" data-aos="fade-up">
                <?php 
                echo $_SESSION['cart_warning']; 
                unset($_SESSION['cart_warning']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($cart_items)): ?>
            <div class="cart-container" data-aos="fade-up">
                <div class="cart-items">
                    <div class="cart-header">
                        <div>Product</div>
                        <div>Price</div>
                        <div>Quantity</div>
                        <div>Total</div>
                        <div></div>
                    </div>
                    
                    <form method="post" id="update-cart-form">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item">
                                <div class="item-product">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image">
                                    <div>
                                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <div class="item-category">In stock: <?php echo $item['stock_quantity']; ?></div>
                                    </div>
                                </div>
                                
                                <div class="item-price" data-title="Price:">
                                    <?php echo formatCurrency($item['price']); ?>
                                </div>
                                
                                <div class="item-quantity" data-title="Quantity:">
                                    <div class="quantity-form">
                                        <div class="quantity-controls">
                                            <button type="button" class="qty-btn-cart decrease-qty" data-id="<?php echo $item['id']; ?>">-</button>
                                            <input type="number" name="quantity[<?php echo $item['id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_quantity']; ?>" class="quantity-input" id="qty-<?php echo $item['id']; ?>">
                                            <button type="button" class="qty-btn-cart increase-qty" data-id="<?php echo $item['id']; ?>" data-max="<?php echo $item['stock_quantity']; ?>">+</button>
                                        </div>
                                        <button type="button" class="update-btn update-item-qty" data-id="<?php echo $item['id']; ?>">Update</button>
                                    </div>
                                </div>
                                
                                <div class="item-total" data-title="Total:">
                                    <span id="total-<?php echo $item['id']; ?>"><?php echo formatCurrency($item['total']); ?></span>
                                </div>
                                
                                <div class="item-actions">
                                    <a href="cart.php?remove=<?php echo $item['id']; ?>" class="remove-btn remove-item" data-id="<?php echo $item['id']; ?>" title="Remove item">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div style="padding: 1.5rem; text-align: right;">
                            <button type="submit" name="update_cart" class="btn btn-primary">Update Cart</button>
                        </div>
                    </form>
                </div>
                
                <div class="cart-summary">
                    <h2 class="summary-title">Order Summary</h2>
                    
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="cart-subtotal"><?php echo formatCurrency($subtotal); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span id="cart-shipping"><?php echo $shipping > 0 ? formatCurrency($shipping) : 'FREE'; ?></span>
                    </div>
                    
                    <div class="summary-total">
                        <span>Total</span>
                        <span id="cart-total"><?php echo formatCurrency($total); ?></span>
                    </div>
                    
                    <div class="promo-code">
                        <input type="text" placeholder="Promo code" class="promo-input">
                        <button class="btn-small">Apply</button>
                    </div>
                    
                    <div class="cart-actions">
                        <a href="checkout.php" class="btn btn-primary checkout-btn">Proceed to Checkout</a>
                        <a href="products.php" class="continue-shopping">Continue Shopping</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-cart" data-aos="fade-up">
                <div class="empty-cart-icon"><i class="fas fa-shopping-cart"></i></div>
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added any products to your cart yet.</p>
                <a href="products.php" class="btn btn-primary">Start Shopping</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
$extra_js = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Update quantity
    const decreaseBtns = document.querySelectorAll(".decrease-qty");
    const increaseBtns = document.querySelectorAll(".increase-qty");
    const quantities = document.querySelectorAll(".quantity-input");
    const updateBtns = document.querySelectorAll(".update-item-qty");
    const removeItems = document.querySelectorAll(".remove-item");
    
    // Initialize cart from localStorage if empty
    syncCartWithLocalStorage();
    
    // Decrease quantity
    decreaseBtns.forEach(btn => {
        btn.addEventListener("click", function() {
            const id = this.getAttribute("data-id");
            const qtyInput = document.getElementById(`qty-${id}`);
            let currentQty = parseInt(qtyInput.value);
            
            if (currentQty > 1) {
                qtyInput.value = currentQty - 1;
                updateItemTotal(id);
            }
        });
    });
    
    // Increase quantity
    increaseBtns.forEach(btn => {
        btn.addEventListener("click", function() {
            const id = this.getAttribute("data-id");
            const maxQty = parseInt(this.getAttribute("data-max"));
            const qtyInput = document.getElementById(`qty-${id}`);
            let currentQty = parseInt(qtyInput.value);
            
            if (currentQty < maxQty) {
                qtyInput.value = currentQty + 1;
                updateItemTotal(id);
            }
        });
    });
    
    // Update quantity manually
    quantities.forEach(input => {
        input.addEventListener("change", function() {
            const id = this.name.match(/\\d+/)[0];
            updateItemTotal(id);
        });
    });
    
    // Update single item
    updateBtns.forEach(btn => {
        btn.addEventListener("click", function() {
            const id = this.getAttribute("data-id");
            const quantity = document.getElementById(`qty-${id}`).value;
            
            updateCartItem(id, quantity);
        });
    });
    
    // Remove items
    removeItems.forEach(link => {
        link.addEventListener("click", function(e) {
            e.preventDefault();
            const id = this.getAttribute("data-id");
            
            // Remove from localStorage
            removeFromCart(id);
            
            // Submit form to update server-side cart
            window.location.href = `cart.php?remove=${id}`;
        });
    });
    
    // Update item total when quantity changes
    function updateItemTotal(id) {
        const qtyInput = document.getElementById(`qty-${id}`);
        const quantity = parseInt(qtyInput.value);
        const priceText = qtyInput.closest(".cart-item").querySelector(".item-price").textContent.trim();
        const price = parseFloat(priceText.replace(/[^0-9.-]+/g, ""));
        const totalElement = document.getElementById(`total-${id}`);
        
        if (totalElement) {
            const total = price * quantity;
            totalElement.textContent = formatCurrency(total);
        }
    }
    
    // Format currency
    function formatCurrency(amount) {
        return "$" + amount.toFixed(2);
    }
});

// Cart localStorage functions
function syncCartWithLocalStorage() {
    if (localStorage.getItem("cart")) {
        const localCart = JSON.parse(localStorage.getItem("cart"));
        
        // Send to server via AJAX to update PHP session
        fetch("ajax_update_cart.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ cart: localCart })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.count !== undefined) {
                document.getElementById("cart-count").textContent = data.count;
            }
        });
    }
}

function updateCartItem(id, quantity) {
    // Update localStorage
    let cart = {};
    if (localStorage.getItem("cart")) {
        cart = JSON.parse(localStorage.getItem("cart"));
    }
    
    cart[id] = parseInt(quantity);
    localStorage.setItem("cart", JSON.stringify(cart));
    
    // Update server via AJAX
    fetch("ajax_update_cart.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ 
            updateItem: true,
            productId: id,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById("cart-subtotal").textContent = data.subtotal;
            document.getElementById("cart-shipping").textContent = data.shipping;
            document.getElementById("cart-total").textContent = data.total;
            document.getElementById("cart-count").textContent = data.count;
            
            // Show success message
            showNotification("Cart updated successfully!");
        }
    });
}

function removeFromCart(id) {
    // Remove from localStorage
    let cart = {};
    if (localStorage.getItem("cart")) {
        cart = JSON.parse(localStorage.getItem("cart"));
        delete cart[id];
        localStorage.setItem("cart", JSON.stringify(cart));
    }
}

function showNotification(message) {
    // Create notification element
    const notification = document.createElement("div");
    notification.className = "notification";
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Show and then hide after 3 seconds
    setTimeout(() => {
        notification.classList.add("show");
        setTimeout(() => {
            notification.classList.remove("show");
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 500);
        }, 3000);
    }, 100);
}
</script>';

require_once 'includes/footer.php';
?>
