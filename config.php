<?php
// Database configuration
$db_host = 'localhost';       // Database host (usually localhost for XAMPP)
$db_user = 'root';            // Database username (default is root for XAMPP)
$db_pass = '';                // Database password (default is blank for XAMPP)
$db_name = 'medibuddy';       // Database name - you need to create this database in phpMyAdmin

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to UTF-8
$conn->set_charset("utf8");

// Define site URL (optional but useful)
$site_url = 'http://localhost/medipil/';

// Define other constants as needed
define('SITE_NAME', 'Medipil');
?>
