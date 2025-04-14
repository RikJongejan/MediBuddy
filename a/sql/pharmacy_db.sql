CREATE DATABASE IF NOT EXISTS pharmacy_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pharmacy_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS order_tracking;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS chat_history;
SET FOREIGN_KEY_CHECKS = 1;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    zipcode VARCHAR(20),
    is_admin TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    register_date DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50)
);

-- Create products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    image VARCHAR(255),
    category_id INT,
    featured TINYINT(1) DEFAULT 0,
    contents VARCHAR(255),
    dosage VARCHAR(255),
    expiry_date DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Create orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(50) NOT NULL,
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10, 2) NOT NULL,
    shipping_fee DECIMAL(10, 2) DEFAULT 0.00,
    payment_method VARCHAR(50) NOT NULL,
    shipping_address TEXT NOT NULL,
    status ENUM('Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled') DEFAULT 'Pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create order_items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create order_tracking table
CREATE TABLE order_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    description TEXT,
    tracking_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Create chat_history table
CREATE TABLE chat_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    message TEXT NOT NULL,
    is_user TINYINT(1) DEFAULT 1,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert dummy data

-- Users
INSERT INTO users (username, email, password, first_name, last_name, phone, address, city, state, zipcode, is_admin, is_active) VALUES
('admin', 'admin@medibuddy.com', '$2y$10$wDmD3IpLKnWUAzZfQMhY5.H.6h0irXZr5TbZa5F.9kdlF.Qc5Bfw.', 'Admin', 'User', '555-123-4567', '123 Admin St', 'Admin City', 'AS', '12345', 1, 1),
('johndoe', 'john@example.com', '$2y$10$m.5ThwIr/PvivoUZ18lLD.QUXpGkYDzaDOBQnM8NRchexLbbpHtIO', 'John', 'Doe', '555-987-6543', '456 Main St', 'Springfield', 'IL', '62701', 0, 1),
('janesmith', 'jane@example.com', '$2y$10$m.5ThwIr/PvivoUZ18lLD.QUXpGkYDzaDOBQnM8NRchexLbbpHtIO', 'Jane', 'Smith', '555-456-7890', '789 Oak Ave', 'Riverdale', 'NY', '10471', 0, 1),
('robertjohnson', 'robert@example.com', '$2y$10$m.5ThwIr/PvivoUZ18lLD.QUXpGkYDzaDOBQnM8NRchexLbbpHtIO', 'Robert', 'Johnson', '555-345-6789', '321 Pine St', 'Oakville', 'CA', '94562', 0, 1),
('emilywilson', 'emily@example.com', '$2y$10$m.5ThwIr/PvivoUZ18lLD.QUXpGkYDzaDOBQnM8NRchexLbbpHtIO', 'Emily', 'Wilson', '555-234-5678', '567 Maple Dr', 'Lakeside', 'MI', '49127', 0, 1);

-- Categories
INSERT INTO categories (name, description, icon) VALUES
('Pain Relief', 'Medications to alleviate pain and reduce inflammation', '💊'),
('Fever & Cold', 'Remedies for fever, colds, and flu symptoms', '🌡️'),
('Allergy', 'Products to relieve allergy symptoms', '🤧'),
('Stomach Care', 'Medicines for digestive issues and stomach problems', '🧬'),
('Vitamins & Supplements', 'Nutritional supplements to promote health', '🍊'),
('First Aid', 'Essential supplies for treating minor injuries', '🩹'),
('Baby Care', 'Products designed specifically for babies and infants', '👶'),
('Skin Care', 'Products for skin health and treatment', '🧴'),
('Eye Care', 'Medications and products for eye health', '👁️'),
('Diabetes Care', 'Products for diabetes management and care', '💉');

-- Products
INSERT INTO products (name, description, price, stock_quantity, image, category_id, featured, contents, dosage) VALUES
('Paracetamol 500mg', 'Fast pain relief for headaches, muscle aches, and fever reduction. Suitable for adults and children over 12 years.', 4.99, 150, 'uploads/paracetamol.jpg', 1, 1, 'Paracetamol 500mg', '1-2 tablets every 4-6 hours as needed'),
('Vitamin C 1000mg', 'Support your immune system with daily vitamin C supplements. These chewable tablets have a pleasant orange flavor.', 12.99, 85, 'uploads/vitamin-c.jpg', 5, 1, 'Vitamin C (as ascorbic acid) 1000mg', '1 tablet daily with food'),
('Allergy Relief Tablets', 'Fast-acting relief from seasonal allergies and hay fever symptoms. Non-drowsy formula.', 8.49, 65, 'uploads/allergy-tablets.jpg', 3, 1, 'Cetirizine Hydrochloride 10mg', '1 tablet daily'),
('First Aid Kit', 'Complete emergency kit with essential supplies for minor injuries. Includes bandages, antiseptic wipes, and more.', 24.99, 30, 'uploads/first-aid-kit.jpg', 6, 1, 'Assorted first aid supplies', 'Use as needed for minor injuries'),
('Digital Thermometer', 'Accurate temperature readings with digital display. Features fever alert and memory recall.', 15.99, 45, 'uploads/thermometer.jpg', 2, 1, 'Digital thermometer with battery', 'Follow instructions for use'),
('Omega-3 Fish Oil', 'Support heart health with premium quality Omega-3 supplements. Triple strength formula.', 19.99, 70, 'uploads/omega-3.jpg', 5, 1, 'EPA 900mg, DHA 600mg per serving', '1 capsule daily with meals'),
('Hand Sanitizer', 'Kill 99.9% of germs with this portable hand sanitizer. Non-drying formula with aloe vera.', 3.99, 200, 'uploads/hand-sanitizer.jpg', 6, 1, '70% ethyl alcohol, aloe vera, vitamin E', 'Apply to hands as needed'),
('Multivitamin Complex', 'Complete daily nutrition with essential vitamins and minerals. Supports immune system health.', 14.99, 90, 'uploads/multivitamin.jpg', 5, 1, 'Multiple vitamins and minerals', '1 tablet daily with food'),
('Cough Syrup', 'Effective relief from dry and wet coughs. Soothes throat irritation and suppresses cough.', 7.49, 60, 'uploads/cough-syrup.jpg', 2, 0, 'Dextromethorphan, Guaifenesin', '10ml every 4 hours as needed'),
('Ibuprofen 200mg', 'Anti-inflammatory pain reliever for headaches, dental pain, and muscle aches.', 5.99, 120, 'uploads/ibuprofen.jpg', 1, 0, 'Ibuprofen 200mg', '1-2 tablets every 4-6 hours with food'),
('Baby Diaper Rash Cream', 'Soothes and prevents diaper rash. Forms a protective barrier against moisture.', 8.99, 40, 'uploads/diaper-cream.jpg', 7, 0, 'Zinc oxide, lanolin', 'Apply thin layer to affected area at diaper changes'),
('Eye Drops for Dry Eyes', 'Provides immediate relief from dry, irritated eyes. Preservative-free formula.', 9.99, 75, 'uploads/eye-drops.jpg', 9, 0, 'Sodium hyaluronate, glycerin', '1-2 drops in affected eye as needed'),
('Antacid Tablets', 'Fast relief from heartburn, acid indigestion, and upset stomach.', 6.49, 85, 'uploads/antacid.jpg', 4, 0, 'Calcium carbonate, magnesium hydroxide', 'Chew 2 tablets as symptoms occur'),
('Diabetes Test Strips', 'Compatible with most glucose meters for accurate blood sugar readings.', 29.99, 25, 'uploads/test-strips.jpg', 10, 0, 'Test strips for glucose monitoring', 'Use as directed with glucose meter'),
('Hydrating Face Wash', 'Gentle daily cleanser that removes impurities without drying skin.', 11.99, 55, 'uploads/face-wash.jpg', 8, 0, 'Hyaluronic acid, glycerin, aloe vera', 'Use morning and night on damp skin'),
('Antiseptic Cream', 'Prevents infection in minor cuts, burns, and abrasions. Promotes healing.', 6.99, 70, 'uploads/antiseptic-cream.jpg', 6, 0, 'Benzalkonium chloride, lidocaine', 'Apply small amount to affected area up to 3 times daily'),
('Children\'s Fever Reducer', 'Specially formulated for children to reduce fever and relieve pain.', 7.99, 45, 'uploads/childrens-fever.jpg', 2, 0, 'Paracetamol 120mg/5ml', 'Dose according to child\'s weight, as directed'),
('Nasal Spray Decongestant', 'Fast relief from nasal congestion due to colds or allergies.', 8.49, 60, 'uploads/nasal-spray.jpg', 3, 0, 'Oxymetazoline hydrochloride 0.05%', '1-2 sprays per nostril every 12 hours'),
('Probiotic Capsules', 'Supports digestive and immune health with beneficial bacteria.', 16.99, 40, 'uploads/probiotics.jpg', 4, 0, '10 billion CFU, multiple probiotic strains', '1 capsule daily with food'),
('Muscle Pain Relief Gel', 'Provides cooling relief for sore muscles and joint pain.', 10.99, 50, 'uploads/muscle-gel.jpg', 1, 0, 'Menthol, camphor, arnica', 'Apply to affected areas up to 4 times daily');

-- Orders
INSERT INTO orders (user_id, order_number, order_date, total_amount, shipping_fee, payment_method, shipping_address, status) VALUES
(2, 'ORD-ABCDEF1234', '2023-01-15 10:23:45', 54.95, 5.00, 'Credit Card', 'John Doe\n456 Main St\nSpringfield, IL 62701\nPhone: 555-987-6543', 'Delivered'),
(3, 'ORD-BCDEFG2345', '2023-01-20 14:30:22', 42.47, 5.00, 'PayPal', 'Jane Smith\n789 Oak Ave\nRiverdale, NY 10471\nPhone: 555-456-7890', 'Delivered'),
(2, 'ORD-CDEFGH3456', '2023-02-03 09:14:36', 38.97, 5.00, 'Credit Card', 'John Doe\n456 Main St\nSpringfield, IL 62701\nPhone: 555-987-6543', 'Delivered'),
(4, 'ORD-DEFGHI4567', '2023-02-18 16:45:08', 73.45, 0.00, 'Cash on Delivery', 'Robert Johnson\n321 Pine St\nOakville, CA 94562\nPhone: 555-345-6789', 'Shipped'),
(5, 'ORD-EFGHIJ5678', '2023-03-05 11:20:54', 29.97, 5.00, 'Credit Card', 'Emily Wilson\n567 Maple Dr\nLakeside, MI 49127\nPhone: 555-234-5678', 'Processing'),
(3, 'ORD-FGHIJK6789', '2023-03-22 08:10:42', 62.93, 0.00, 'PayPal', 'Jane Smith\n789 Oak Ave\nRiverdale, NY 10471\nPhone: 555-456-7890', 'Processing'),
(2, 'ORD-GHIJKL7890', '2023-04-01 13:37:29', 19.98, 5.00, 'Credit Card', 'John Doe\n456 Main St\nSpringfield, IL 62701\nPhone: 555-987-6543', 'Pending');

-- Order Items
INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES
(1, 1, 2, 4.99, 9.98),
(1, 5, 1, 15.99, 15.99),
(1, 8, 1, 14.99, 14.99),
(1, 7, 2, 3.99, 7.98),
(2, 3, 1, 8.49, 8.49),
(2, 6, 1, 19.99, 19.99),
(2, 7, 1, 3.99, 3.99),
(3, 2, 1, 12.99, 12.99),
(3, 10, 2, 5.99, 11.98),
(3, 9, 1, 7.49, 7.49),
(4, 4, 1, 24.99, 24.99),
(4, 8, 1, 14.99, 14.99),
(4, 12, 2, 9.99, 19.98),
(4, 15, 1, 11.99, 11.99),
(5, 10, 2, 5.99, 11.98),
(5, 7, 3, 3.99, 11.97),
(6, 2, 2, 12.99, 25.98),
(6, 6, 1, 19.99, 19.99),
(6, 16, 2, 6.99, 13.98),
(7, 1, 2, 4.99, 9.98),
(7, 9, 1, 7.49, 7.49);

-- Order Tracking
INSERT INTO order_tracking (order_id, status, description, tracking_date) VALUES
(1, 'Pending', 'Order placed successfully', '2023-01-15 10:23:45'),
(1, 'Processing', 'Payment confirmed, preparing order', '2023-01-15 14:30:22'),
(1, 'Shipped', 'Order shipped via Express Delivery (Tracking: TRACK123456)', '2023-01-16 10:15:30'),
(1, 'Delivered', 'Order delivered successfully', '2023-01-18 14:20:45'),
(2, 'Pending', 'Order placed successfully', '2023-01-20 14:30:22'),
(2, 'Processing', 'Payment confirmed, preparing order', '2023-01-20 16:45:10'),
(2, 'Shipped', 'Order shipped via Standard Delivery (Tracking: TRACK234567)', '2023-01-21 11:25:18'),
(2, 'Delivered', 'Order delivered successfully', '2023-01-24 15:40:22'),
(3, 'Pending', 'Order placed successfully', '2023-02-03 09:14:36'),
(3, 'Processing', 'Payment confirmed, preparing order', '2023-02-03 11:30:45'),
(3, 'Shipped', 'Order shipped via Express Delivery (Tracking: TRACK345678)', '2023-02-04 09:18:24'),
(3, 'Delivered', 'Order delivered successfully', '2023-02-06 13:15:30'),
(4, 'Pending', 'Order placed successfully', '2023-02-18 16:45:08'),
(4, 'Processing', 'Preparing order for shipment', '2023-02-19 10:15:22'),
(4, 'Shipped', 'Order shipped via Express Delivery (Tracking: TRACK456789)', '2023-02-20 11:30:15'),
(5, 'Pending', 'Order placed successfully', '2023-03-05 11:20:54'),
(5, 'Processing', 'Payment confirmed, preparing order', '2023-03-05 13:45:30'),
(6, 'Pending', 'Order placed successfully', '2023-03-22 08:10:42'),
(6, 'Processing', 'Payment confirmed, preparing order', '2023-03-22 09:30:15'),
(7, 'Pending', 'Order placed successfully', '2023-04-01 13:37:29');

-- Chat History
INSERT INTO chat_history (user_id, message, is_user, timestamp) VALUES
(2, 'Hello, I have a question about my order #ORD-ABCDEF1234', 1, '2023-01-17 09:15:30'),
(2, 'Hi John, how can I help you with your order?', 0, '2023-01-17 09:16:45'),
(2, 'I was wondering when my order will be delivered?', 1, '2023-01-17 09:17:30'),
(2, 'Your order has been shipped and should arrive by tomorrow. The tracking number is TRACK123456.', 0, '2023-01-17 09:18:45'),
(2, 'Great, thank you for the information!', 1, '2023-01-17 09:19:30'),
(3, 'Hi, do you carry specialized vitamins for pregnant women?', 1, '2023-02-10 14:22:15'),
(3, 'Hello Jane, yes we do have prenatal vitamins available. You can find them in our Vitamins & Supplements category.', 0, '2023-02-10 14:23:30'),
(3, 'Can you recommend a specific brand?', 1, '2023-02-10 14:24:15'),
(3, 'Our most popular prenatal vitamin is PreMama Complete, which contains folic acid, iron, and other essential nutrients for pregnancy. Would you like me to send you a link?', 0, '2023-02-10 14:25:30'),
(3, 'Yes, please send me the link.', 1, '2023-02-10 14:26:10');
