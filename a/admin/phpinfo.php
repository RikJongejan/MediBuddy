<?php
// Check if user is admin
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../index.php");
    exit();
}

// Display PHP configuration info
phpinfo();
?>
