<?php
/**
 * Helper functions for admin panel
 */

/**
 * Handle file upload for products
 * 
 * @param array $file The uploaded file array ($_FILES["fieldname"])
 * @param string $current_image Current image path (for edit functionality)
 * @return array Array with 'status', 'message', and 'path' keys
 */
function handleProductImageUpload($file, $current_image = null) {
    $result = [
        'status' => false,
        'message' => '',
        'path' => $current_image
    ];
    
    // Check if file was uploaded
    if (!isset($file) || $file['error'] != 0) {
        if ($file['error'] != UPLOAD_ERR_NO_FILE) {
            $result['message'] = getFileUploadErrorMessage($file['error']);
        }
        return $result;
    }
    
    $target_dir = "../uploads/";
    
    // Create upload directory if it doesn't exist
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0755, true)) {
            $result['message'] = "Failed to create upload directory. Please check permissions.";
            return $result;
        }
    }
    
    // Check if directory is writable
    if (!is_writable($target_dir)) {
        $result['message'] = "Upload directory is not writable. Please check permissions.";
        return $result;
    }
    
    // Validate file type
    $file_name = basename($file["name"]);
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_types = ["jpg", "jpeg", "png", "gif"];
    
    if (!in_array($file_ext, $allowed_types)) {
        $result['message'] = "Invalid file type. Allowed types: JPG, JPEG, PNG, GIF.";
        return $result;
    }
    
    // Validate file size (5MB max)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file["size"] > $max_size) {
        $result['message'] = "File is too large. Maximum size is 5MB.";
        return $result;
    }
    
    // Generate unique filename
    $new_filename = uniqid() . '.' . $file_ext;
    $target_file = $target_dir . $new_filename;
    
    // Attempt to upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        $result['status'] = true;
        $result['path'] = "uploads/" . $new_filename;
    } else {
        $result['message'] = "Failed to upload file. Please try again.";
    }
    
    return $result;
}

/**
 * Get meaningful error message for file upload errors
 * 
 * @param int $error_code PHP file upload error code
 * @return string Human-readable error message
 */
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
