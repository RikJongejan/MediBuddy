</main>
    </div>
    
    <!-- Admin Notifications Panel -->
    <div class="admin-notifications-panel" id="adminNotificationsPanel">
        <div class="panel-header">
            <h3>Notifications</h3>
            <div class="panel-header-actions">
                <button id="clearAllNotifications" title="Clear All Notifications" class="clear-all-btn">
                    <i class="fas fa-trash-alt"></i>
                </button>
                <button id="closeNotifications" title="Close Panel">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="panel-content">
            <?php
            // Get notifications data
            $notificationsQuery = "SELECT id, status, order_date FROM orders WHERE status = 'Pending' ORDER BY order_date DESC LIMIT 5";
            $notificationsResult = $conn->query($notificationsQuery);
            
            if ($notificationsResult && $notificationsResult->num_rows > 0):
            ?>
                <ul class="notifications-list">
                <?php while($notification = $notificationsResult->fetch_assoc()): ?>
                    <li data-notification-id="<?php echo $notification['id']; ?>">
                        <div class="notification-icon"><i class="fas fa-shopping-bag"></i></div>
                        <div class="notification-content">
                            <div class="notification-title">New Order #<?php echo $notification['id']; ?></div>
                            <div class="notification-text">A new order is waiting for processing.</div>
                            <div class="notification-time">
                                <?php 
                                // Safely call timeAgo function
                                if (function_exists('timeAgo')) {
                                    echo timeAgo($notification['order_date']);
                                } else {
                                    echo date('M j, Y g:i a', strtotime($notification['order_date']));
                                }
                                ?>
                            </div>
                        </div>
                        <div class="notification-actions">
                            <button class="clear-notification-btn" title="Clear this notification" data-id="<?php echo $notification['id']; ?>">
                                <i class="fas fa-times"></i>
                            </button>
                            <a href="order_details.php?id=<?php echo $notification['id']; ?>" class="notification-action" title="View Order">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </li>
                <?php endwhile; ?>
                </ul>
                <a href="orders.php?filter=pending" class="view-all-link">View All Pending Orders</a>
            <?php else: ?>
                <div class="empty-notifications">
                    <i class="fas fa-check-circle"></i>
                    <p>No new notifications</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Admin Mobile Toggle -->
    <button class="admin-mobile-toggle" id="adminMobileToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Footer -->
    <footer class="footer admin-footer">
        <div class="container footer-grid">
            <div>
                <h3 class="footer-title">MediBuddy Admin</h3>
                <p class="footer-text">Manage your online pharmacy efficiently.</p>
            </div>
            <div>
                <h3 class="footer-subtitle">Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="dashboard.php" class="footer-link">Dashboard</a></li>
                    <li><a href="../index.php" class="footer-link">View Store</a></li>
                </ul>
            </div>
            <div>
                <h3 class="footer-subtitle">Support</h3>
                <ul class="footer-links">
                    <li><a href="documentation.php" class="footer-link">Documentation</a></li>
                    <li><a href="support.php" class="footer-link">Get Help</a></li>
                </ul>
            </div>
            <div>
                <h3 class="footer-subtitle">System Info</h3>
                <p class="footer-text">
                    <i class="fas fa-code-branch"></i> Version 1.0.0
                </p>
            </div>
        </div>
        <div class="footer-copyright">© <?php echo date('Y'); ?> MediBuddy. All rights reserved.</div>
    </footer>
    
    <!-- JavaScript -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize AOS
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
            
            // Admin mobile sidebar toggle
            const adminMobileToggle = document.getElementById('adminMobileToggle');
            const adminSidebar = document.querySelector('.admin-sidebar');
            
            if (adminMobileToggle && adminSidebar) {
                adminMobileToggle.addEventListener('click', function() {
                    adminSidebar.classList.toggle('active');
                    this.classList.toggle('active');
                });
            }
            
            // Notifications panel
            const notificationsBtn = document.getElementById('adminNotificationsBtn');
            const notificationsPanel = document.getElementById('adminNotificationsPanel');
            const closeNotifications = document.getElementById('closeNotifications');
            
            if (notificationsBtn && notificationsPanel) {
                notificationsBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    notificationsPanel.classList.toggle('show');
                });
            }
            
            if (closeNotifications && notificationsPanel) {
                closeNotifications.addEventListener('click', function() {
                    notificationsPanel.classList.remove('show');
                });
            }
            
            // Close notifications when clicking outside
            document.addEventListener('click', function(event) {
                if (notificationsPanel && notificationsBtn) {
                    if (!notificationsPanel.contains(event.target) && !notificationsBtn.contains(event.target)) {
                        notificationsPanel.classList.remove('show');
                    }
                }
            });
            
            // Clear single notification
            const clearButtons = document.querySelectorAll('.clear-notification-btn');
            clearButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const notificationId = this.getAttribute('data-id');
                    clearNotification(notificationId);
                });
            });
            
            // Clear all notifications
            const clearAllBtn = document.getElementById('clearAllNotifications');
            if (clearAllBtn) {
                clearAllBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    clearAllNotifications();
                });
            }
            
            // Function to clear a single notification
            function clearNotification(id) {
                fetch('../admin/clear_notification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=clear_one&id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the notification from the UI
                        const notificationElement = document.querySelector(`li[data-notification-id="${id}"]`);
                        if (notificationElement) {
                            notificationElement.remove();
                        }
                        
                        // Update badge count
                        updateNotificationBadge(data.count);
                        
                        // If no more notifications, show empty state
                        const notificationsList = document.querySelector('.notifications-list');
                        if (notificationsList && notificationsList.children.length === 0) {
                            showEmptyNotificationsState();
                        }
                    }
                })
                .catch(error => {
                    console.error('Error clearing notification:', error);
                });
            }
            
            // Function to clear all notifications
            function clearAllNotifications() {
                fetch('../admin/clear_notification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=clear_all'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show empty state
                        showEmptyNotificationsState();
                        
                        // Update badge count
                        updateNotificationBadge(0);
                    }
                })
                .catch(error => {
                    console.error('Error clearing notifications:', error);
                });
            }
            
            // Function to show empty notifications state
            function showEmptyNotificationsState() {
                const panelContent = document.querySelector('.panel-content');
                panelContent.innerHTML = `
                    <div class="empty-notifications">
                        <i class="fas fa-check-circle"></i>
                        <p>No new notifications</p>
                    </div>
                `;
            }
            
            // Function to update notification badge
            function updateNotificationBadge(count) {
                const badge = document.querySelector('.notification-badge');
                if (badge) {
                    if (count > 0) {
                        badge.textContent = count;
                    } else {
                        badge.remove();
                    }
                }
            }
        });
    </script>
    
    <?php if (isset($extra_js)) { echo $extra_js; } ?>
</body>
</html>
