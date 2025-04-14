/**
 * MediBuddy Main JavaScript File
 * Handles dynamic content loading and interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    // Load categories for the homepage
    const categoriesContainer = document.getElementById('categories-container');
    if (categoriesContainer) {
        loadCategories();
    }
    
    // Load featured products for the homepage
    const productsContainer = document.getElementById('products-container');
    if (productsContainer) {
        loadFeaturedProducts();
    }
});

/**
 * Load categories from the server and display them on the homepage
 */
function loadCategories() {
    // Simulate API call with sample data (replace with actual API call)
    const categories = [
        { id: 1, name: 'Pain Relief', icon: '💊' },
        { id: 2, name: 'Fever & Cold', icon: '🌡️' },
        { id: 3, name: 'Allergy', icon: '🤧' },
        { id: 4, name: 'Stomach Care', icon: '🧬' },
        { id: 5, name: 'Vitamins', icon: '🍊' },
        { id: 6, name: 'First Aid', icon: '🩹' },
        { id: 7, name: 'Baby Care', icon: '👶' },
        { id: 8, name: 'Women\'s Health', icon: '👩' },
        { id: 9, name: 'Men\'s Health', icon: '👨' },
        { id: 10, name: 'Skincare', icon: '🧴' }
    ];
    
    const categoriesContainer = document.getElementById('categories-container');
    categoriesContainer.innerHTML = '';
    
    categories.forEach(category => {
        const categoryCard = document.createElement('a');
        categoryCard.href = `products.php?category=${category.id}`;
        categoryCard.className = 'category-card';
        
        categoryCard.innerHTML = `
            <div class="category-icon">${category.icon}</div>
            <h3 class="category-name">${category.name}</h3>
        `;
        
        categoriesContainer.appendChild(categoryCard);
    });
}

/**
 * Load featured products from the server and display them on the homepage
 */
function loadFeaturedProducts() {
    // Simulate API call with sample data (replace with actual API call)
    const featuredProducts = [
        {
            id: 1,
            name: 'Paracetamol 500mg',
            description: 'Fast pain relief for headaches, muscle aches, and fever reduction.',
            price: 4.99,
            image: 'uploads/paracetamol.jpg',
            rating: 4.8,
            reviews: 245
        },
        {
            id: 2,
            name: 'Vitamin C 1000mg',
            description: 'Support your immune system with daily vitamin C supplements.',
            price: 12.99,
            image: 'uploads/vitamin-c.jpg',
            rating: 4.7,
            reviews: 189
        },
        {
            id: 3,
            name: 'Allergy Relief Tablets',
            description: 'Fast-acting relief from seasonal allergies and hay fever symptoms.',
            price: 8.49,
            image: 'uploads/allergy-tablets.jpg',
            rating: 4.5,
            reviews: 132
        },
        {
            id: 4,
            name: 'First Aid Kit',
            description: 'Complete emergency kit with essential supplies for minor injuries.',
            price: 24.99,
            image: 'uploads/first-aid-kit.jpg',
            rating: 4.9,
            reviews: 78
        },
        {
            id: 5,
            name: 'Digital Thermometer',
            description: 'Accurate temperature readings with digital display.',
            price: 15.99,
            image: 'uploads/thermometer.jpg',
            rating: 4.6,
            reviews: 112
        },
        {
            id: 6,
            name: 'Omega-3 Fish Oil',
            description: 'Support heart health with premium quality Omega-3 supplements.',
            price: 19.99,
            image: 'uploads/omega-3.jpg',
            rating: 4.7,
            reviews: 95
        },
        {
            id: 7,
            name: 'Hand Sanitizer',
            description: 'Kill 99.9% of germs with this portable hand sanitizer.',
            price: 3.99,
            image: 'uploads/hand-sanitizer.jpg',
            rating: 4.4,
            reviews: 220
        },
        {
            id: 8,
            name: 'Multivitamin Complex',
            description: 'Complete daily nutrition with essential vitamins and minerals.',
            price: 14.99,
            image: 'uploads/multivitamin.jpg',
            rating: 4.8,
            reviews: 167
        }
    ];
    
    const productsContainer = document.getElementById('products-container');
    productsContainer.innerHTML = '';
    
    featuredProducts.forEach(product => {
        const productCard = document.createElement('div');
        productCard.className = 'product-card';
        
        // Create rating stars
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            if (i <= Math.floor(product.rating)) {
                stars += '<i class="fas fa-star"></i>';
            } else if (i - product.rating < 1 && i - product.rating > 0) {
                stars += '<i class="fas fa-star-half-alt"></i>';
            } else {
                stars += '<i class="far fa-star"></i>';
            }
        }
        
        productCard.innerHTML = `
            <a href="product_details.php?id=${product.id}">
                <img src="${product.image}" alt="${product.name}" class="product-image">
            </a>
            <h3 class="product-title">
                <a href="product_details.php?id=${product.id}">${product.name}</a>
            </h3>
            <p class="product-description">${product.description}</p>
            <div class="product-rating">
                <span class="stars">${stars}</span>
                <span class="rating-count">(${product.reviews})</span>
            </div>
            <div class="product-footer">
                <span class="product-price">$${product.price.toFixed(2)}</span>
                <form action="add_to_cart.php" method="post">
                    <input type="hidden" name="product_id" value="${product.id}">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="add-to-cart">Add to Cart</button>
                </form>
            </div>
        `;
        
        productsContainer.appendChild(productCard);
    });
}

// Handle mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuButton = document.querySelector('.mobile-menu-button');
    const mobileNav = document.querySelector('.mobile-nav');
    
    if (mobileMenuButton && mobileNav) {
        mobileMenuButton.addEventListener('click', function() {
            mobileNav.classList.toggle('active');
        });
    }
});

// Handle quantity controls on product pages
function handleQuantityControls() {
    const decreaseBtn = document.querySelector('.decrease');
    const increaseBtn = document.querySelector('.increase');
    const quantityInput = document.querySelector('.qty-input');
    
    if (decreaseBtn && increaseBtn && quantityInput) {
        const maxQuantity = parseInt(quantityInput.getAttribute('max') || 99);
        
        decreaseBtn.addEventListener('click', function() {
            let currentValue = parseInt(quantityInput.value);
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });
        
        increaseBtn.addEventListener('click', function() {
            let currentValue = parseInt(quantityInput.value);
            if (currentValue < maxQuantity) {
                quantityInput.value = currentValue + 1;
            }
        });
    }
}

// Initialize product page specific functionality
if (document.querySelector('.product-detail')) {
    handleQuantityControls();
}
