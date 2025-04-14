<?php
$current_page = 'admin';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

// Only allow admin access
requireAdmin();

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d_His') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel recognition
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add column headers
fputcsv($output, [
    'ID', 
    'Username', 
    'Email', 
    'First Name', 
    'Last Name', 
    'Role', 
    'Status', 
    'Registration Date',
    'Last Login'
]);

// Get users from database
$query = "SELECT * FROM users ORDER BY id";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($user = $result->fetch_assoc()) {
        // Format data for export
        $role = $user['is_admin'] ? 'Administrator' : 'Customer';
        $status = $user['is_active'] ? 'Active' : 'Inactive';
        
        // Export row
        fputcsv($output, [
            $user['id'],
            $user['username'],
            $user['email'],
            $user['first_name'],
            $user['last_name'],
            $role,
            $status,
            date('Y-m-d H:i:s', strtotime($user['register_date'])),
            !empty($user['last_login']) ? date('Y-m-d H:i:s', strtotime($user['last_login'])) : 'Never'
        ]);
    }
}

// Close the file
fclose($output);
exit;
?>
