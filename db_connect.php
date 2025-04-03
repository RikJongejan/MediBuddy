<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "medibuddy";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    // Select the database
    $conn->select_db($dbname);
    
    // Create users table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        is_admin TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
    
    // Create products table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS products (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        image VARCHAR(255),
        category VARCHAR(50),
        stock INT(11) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
    
    // Create orders table with simplified structure
    $sql = "CREATE TABLE IF NOT EXISTS orders (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        shipping_name VARCHAR(100) NOT NULL,
        shipping_address VARCHAR(255) NOT NULL,
        shipping_city VARCHAR(100) NOT NULL,
        shipping_postal_code VARCHAR(20) NOT NULL,
        shipping_phone VARCHAR(20) NOT NULL,
        payment_method VARCHAR(50) NOT NULL DEFAULT 'cash',
        delivery_method VARCHAR(20) NOT NULL DEFAULT 'pickup',
        delivery_time VARCHAR(50) NULL,
        delivery_notes TEXT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'processing',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB";
    
    if (!$conn->query($sql)) {
        echo "Error creating orders table: " . $conn->error;
    }
    
    // Create order items table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS order_items (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        order_id INT(11) NOT NULL,
        product_id INT(11) NOT NULL,
        quantity INT(11) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id)
    ) ENGINE=InnoDB";
    
    if (!$conn->query($sql)) {
        echo "Error creating order_items table: " . $conn->error;
    }
    
    // Create chat_history table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS chat_history (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        message TEXT NOT NULL,
        response TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    $conn->query($sql);
    
    // Insert sample products if none exist
    $result = $conn->query("SELECT COUNT(*) as count FROM products");
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        $products = [
            ['Aspirin', 'Pain reliever and fever reducer. Aspirin is used to treat mild to moderate pain, and also to reduce fever or inflammation. It works by blocking a certain natural substance in your body to reduce pain and inflammation.', 8.99, 'aspirin.jpg', 'Pain Relief', 100],
            ['Ibuprofen', 'Anti-inflammatory medication used to reduce fever and treat pain or inflammation caused by many conditions such as headache, toothache, back pain, arthritis, or minor injury.', 9.99, 'ibuprofen.jpg', 'Pain Relief', 150],
            ['Acetaminophen', 'Fever reducer and pain reliever used to treat many conditions such as headache, muscle aches, arthritis, backache, toothaches, colds, and fevers.', 7.99, 'acetaminophen.jpg', 'Pain Relief', 200],
            ['Allergy Relief', 'Provides temporary relief from allergies including runny nose, sneezing, itchy and watery eyes, and itchy throat or nose. Contains loratadine, a non-drowsy antihistamine.', 12.99, 'allergy.jpg', 'Allergies', 75],
            ['Cough Syrup', 'Relieves cough and soothes irritated throat tissues. Contains dextromethorphan which suppresses cough and guaifenesin which loosens congestion in your chest and throat.', 11.99, 'cough.jpg', 'Cold & Flu', 80],
            ['Multivitamin', 'Complete daily multivitamin and mineral supplement to support overall health. Contains essential vitamins and minerals including Vitamin A, C, D, E and B-complex.', 14.99, 'vitamin.jpg', 'Vitamins & Supplements', 120],
            ['Hand Sanitizer', 'Kills 99.9% of germs without water. Enriched with moisturizers to keep hands soft even with frequent use.', 5.99, 'sanitizer.jpg', 'Personal Care', 200],
            ['Digital Thermometer', 'Fast, accurate readings with digital display. Features fever alert, memory function and automatic shut-off to conserve battery.', 19.99, 'thermometer.jpg', 'Medical Devices', 50],
            ['Adhesive Bandages', 'Flexible fabric bandages that stay on securely to protect minor cuts, scrapes and blisters. Includes assorted sizes for various needs.', 6.99, 'bandages.jpg', 'First Aid', 150],
            ['Vitamin C', 'Supports immune health with high-potency Vitamin C. Antioxidant formula helps protect cells from free radical damage.', 13.99, 'vitaminc.jpg', 'Vitamins & Supplements', 100]
        ];
        
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, image, category, stock) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($products as $product) {
            $stmt->bind_param("ssdssi", $product[0], $product[1], $product[2], $product[3], $product[4], $product[5]);
            $stmt->execute();
        }
    }
    
    // Insert admin user if no users exist
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        // Username: admin, Password: admin123
        $admin_username = "admin";
        $admin_email = "admin@medibuddy.com";
        $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
        $is_admin = 1;
        
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $admin_username, $admin_email, $admin_password, $is_admin);
        $stmt->execute();
    }
    
} else {
    echo "Error creating database: " . $conn->error;
}

// Fix function redeclaration issue with conditional declaration
if (!function_exists('createOrder')) {
    /**
     * Helper function to create a new order
     * 
     * @param mysqli $conn Database connection
     * @param int $user_id User ID making the order
     * @param float $total_amount Total order amount
     * @param array $orderData Order data (shipping info, etc)
     * @param array $cart_items Cart items
     * @return int|false The new order ID or false on failure
     */
    function createOrder($conn, $user_id, $total_amount, $orderData, $cart_items) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert order record - fixed parameter binding
            $stmt = $conn->prepare("INSERT INTO orders 
                (user_id, total_amount, shipping_name, shipping_address, shipping_city, 
                 shipping_postal_code, shipping_phone, payment_method, delivery_method, 
                 delivery_time, delivery_notes, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'processing')");
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            // Fix: The bind_param should have 11 parameters (i, d, and 9 s's for strings)
            $stmt->bind_param("idssssssss", 
                $user_id,
                $total_amount,
                $orderData['name'],
                $orderData['address'],
                $orderData['city'], 
                $orderData['postal_code'],
                $orderData['phone'],
                $orderData['payment_method'],
                $orderData['delivery_method'],
                $orderData['delivery_time'],
                $orderData['delivery_notes']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $order_id = $conn->insert_id;
            
            // Add order items
            foreach ($cart_items as $item) {
                // Validate item data
                if (!isset($item['id']) || !isset($item['quantity']) || !isset($item['price'])) {
                    throw new Exception("Invalid cart item data: " . print_r($item, true));
                }
                
                $product_id = $item['id'];
                $quantity = $item['quantity'];
                $price = $item['price'];
                
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiid", $order_id, $product_id, $quantity, $price);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to add order item: " . $stmt->error);
                }
                
                // Update product stock
                $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt->bind_param("ii", $quantity, $product_id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update stock: " . $stmt->error);
                }
            }
            
            // Commit transaction
            $conn->commit();
            return $order_id;
            
        } catch (Exception $e) {
            $conn->rollback();
            // Log the detailed error and return the message for debugging
            error_log("Order creation error: " . $e->getMessage());
            return false;
        }
    }
}
?>
