/**
 * MediBuddy Cart Functions
 * This file contains functions for cart management
 */

/**
 * Add a product to the cart
 * @param {number} productId - The ID of the product
 * @param {number} quantity - The quantity to add
 */
function addToCart(productId, quantity = 1) {
    // Make sure quantity is a valid number
    quantity = parseInt(quantity) || 1;
    if (quantity < 1) quantity = 1;
    
    // Send AJAX request
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        // Update cart badge
        const cartBadge = document.getElementById('cart-count');
        if (cartBadge) {
            cartBadge.textContent = data.cart_count || 0;
        }
        
        // Show notification
        showNotification(data.message, data.success ? 'success' : 'error');
    })
    .catch(error => {
        console.error('Error adding to cart:', error);
        showNotification('There was an error adding the item to your cart.', 'error');
    });
}

/**
 * Show a notification message
 * @param {string} message - The message to display
 * @param {string} type - The notification type (success, error, info)
 */
function showNotification(message, type = 'success') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    // Add icon based on type
    let icon = 'check-circle';
    if (type === 'error') icon = 'exclamation-circle';
    if (type === 'info') icon = 'info-circle';
    
    // Set notification content
    notification.innerHTML = `
        <div class="notification-icon">
            <i class="fas fa-${icon}"></i>
        </div>
        <div class="notification-message">${message}</div>
        <button class="notification-close">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Add to document
    document.body.appendChild(notification);
    
    // Fade in
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Setup close button
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        closeNotification(notification);
    });
    
    // Auto-close after 5 seconds
    setTimeout(() => {
        closeNotification(notification);
    }, 5000);
}

/**
 * Close a notification
 * @param {HTMLElement} notification - The notification element to close
 */
function closeNotification(notification) {
    notification.classList.remove('show');
    setTimeout(() => {
        notification.remove();
    }, 300);
}
