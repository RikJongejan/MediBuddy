<?php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

// Check if user is admin
requireAdmin();

$message = '';
$success = false;
$debug_info = '';

// Process file upload test
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['test_file']) && $_FILES['test_file']['error'] == 0) {
        // Get file information
        $file_name = basename($_FILES['test_file']['name']);
        $file_size = $_FILES['test_file']['size'];
        $file_tmp = $_FILES['test_file']['tmp_name'];
        $file_type = $_FILES['test_file']['type'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $debug_info .= "File name: $file_name\n";
        $debug_info .= "File size: $file_size bytes\n";
        $debug_info .= "File type: $file_type\n";
        $debug_info .= "Temporary location: $file_tmp\n";
        
        // Check upload directory
        $target_dir = "../uploads/";
        $debug_info .= "\nUpload directory checks:\n";
        
        // Create directory if it doesn't exist
        if (!is_dir($target_dir)) {
            $debug_info .= "Directory doesn't exist, trying to create it...\n";
            if (mkdir($target_dir, 0777, true)) {
                $debug_info .= "Directory created successfully.\n";
            } else {
                $debug_info .= "Failed to create directory. Error: " . error_get_last()['message'] . "\n";
            }
        } else {
            $debug_info .= "Directory exists.\n";
        }
        
        // Check if directory is writable
        if (is_writable($target_dir)) {
            $debug_info .= "Directory is writable.\n";
        } else {
            $debug_info .= "Directory is not writable. Attempting to set permissions...\n";
            chmod($target_dir, 0777);
            
            if (is_writable($target_dir)) {
                $debug_info .= "Directory is now writable.\n";
            } else {
                $debug_info .= "Directory is still not writable after chmod.\n";
            }
        }
        
        // Get directory permissions
        $debug_info .= "Directory permissions: " . substr(sprintf('%o', fileperms($target_dir)), -4) . "\n";
        
        // Try to create a test file
        $test_file = $target_dir . 'test_' . time() . '.txt';
        $debug_info .= "\nAttempting to create test file: $test_file\n";
        
        $handle = @fopen($test_file, 'w');
        if ($handle) {
            fwrite($handle, "Test file created at " . date('Y-m-d H:i:s'));
            fclose($handle);
            $debug_info .= "Test file created successfully.\n";
            @unlink($test_file); // Clean up
            $debug_info .= "Test file deleted.\n";
        } else {
            $debug_info .= "Failed to create test file. Error: " . error_get_last()['message'] . "\n";
        }
        
        // Now try the actual upload
        $target_file = $target_dir . 'upload_test_' . time() . '.' . $file_ext;
        $debug_info .= "\nAttempting to upload file to: $target_file\n";
        
        if (move_uploaded_file($file_tmp, $target_file)) {
            $success = true;
            $message = "File uploaded successfully!";
            $debug_info .= "File uploaded successfully.\n";
            
            // Output file information
            $debug_info .= "\nUploaded file information:\n";
            $debug_info .= "File path: " . $target_file . "\n";
            $debug_info .= "File size: " . filesize($target_file) . " bytes\n";
            $debug_info .= "File permissions: " . substr(sprintf('%o', fileperms($target_file)), -4) . "\n";
        } else {
            $message = "Failed to upload file.";
            $debug_info .= "Failed to upload file. PHP Error: " . error_get_last()['message'] . "\n";
        }
    } else {
        $error_code = $_FILES['test_file']['error'] ?? 'No file selected';
        $message = "Upload error: " . getFileUploadErrorMessage($error_code);
        $debug_info .= "File upload error code: $error_code\n";
    }
}

// Helper function for file upload error messages
function getFileUploadErrorMessage($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return "The uploaded file exceeds the upload_max_filesize directive in php.ini.";
        case UPLOAD_ERR_FORM_SIZE:
            return "The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.";
        case UPLOAD_ERR_PARTIAL:
            return "The uploaded file was only partially uploaded.";
        case UPLOAD_ERR_NO_FILE:
            return "No file was uploaded.";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Missing a temporary folder.";
        case UPLOAD_ERR_CANT_WRITE:
            return "Failed to write file to disk.";
        case UPLOAD_ERR_EXTENSION:
            return "A PHP extension stopped the file upload.";
        default:
            return "Unknown upload error.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload Test - MediBuddy Admin</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .debug-info {
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            font-family: monospace;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <h3 class="sidebar-title">Admin Panel</h3>
            <nav class="admin-menu">
                <a href="dashboard.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="products.php" class="menu-item">
                    <i class="fas fa-pills"></i> Products
                </a>
                <a href="categories.php" class="menu-item">
                    <i class="fas fa-th-large"></i> Categories
                </a>
                <a href="orders.php" class="menu-item">
                    <i class="fas fa-shopping-bag"></i> Orders
                </a>
                <a href="users.php" class="menu-item">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="tracking.php" class="menu-item">
                    <i class="fas fa-shipping-fast"></i> Order Tracking
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-content">
            <div class="admin-header">
                <h1 class="admin-title">File Upload Test</h1>
                <div class="admin-actions">
                    <a href="edit_product.php?id=<?php echo isset($_GET['id']) ? $_GET['id'] : '1'; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Edit Product
                    </a>
                </div>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $success ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="admin-card">
                <div class="card-header">
                    <h2 class="card-title">Upload Test</h2>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="test_file" class="form-label">Select a file to test upload:</label>
                            <input type="file" id="test_file" name="test_file" class="form-input" required>
                            <small>Select any image file to test the upload functionality.</small>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Test Upload</button>
                        </div>
                    </form>
                    
                    <?php if (!empty($debug_info)): ?>
                        <h3>Debug Information</h3>
                        <div class="debug-info"><?php echo htmlspecialchars($debug_info); ?></div>
                    <?php endif; ?>
                    
                    <h3>PHP File Upload Configuration</h3>
                    <table class="table">
                        <tr>
                            <th>Setting</th>
                            <th>Value</th>
                        </tr>
                        <tr>
                            <td>upload_max_filesize</td>
                            <td><?php echo ini_get('upload_max_filesize'); ?></td>
                        </tr>
                        <tr>
                            <td>post_max_size</td>
                            <td><?php echo ini_get('post_max_size'); ?></td>
                        </tr>
                        <tr>
                            <td>max_file_uploads</td>
                            <td><?php echo ini_get('max_file_uploads'); ?></td>
                        </tr>
                        <tr>
                            <td>file_uploads enabled</td>
                            <td><?php echo ini_get('file_uploads') ? 'Yes' : 'No'; ?></td>
                        </tr>
                        <tr>
                            <td>Temporary upload directory</td>
                            <td><?php echo ini_get('upload_tmp_dir') ?: sys_get_temp_dir(); ?></td>
                        </tr>
                    </table>
                    
                    <h3>Server Environment</h3>
                    <table class="table">
                        <tr>
                            <th>Setting</th>
                            <th>Value</th>
                        </tr>
                        <tr>
                            <td>PHP Version</td>
                            <td><?php echo phpversion(); ?></td>
                        </tr>
                        <tr>
                            <td>Server Software</td>
                            <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
                        </tr>
                        <tr>
                            <td>Current User</td>
                            <td><?php echo get_current_user(); ?></td>
                        </tr>
                        <tr>
                            <td>Current Directory</td>
                            <td><?php echo getcwd(); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
