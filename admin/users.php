<?php
$current_page = 'admin';
$current_admin_page = 'users';
$page_title = 'Manage Users';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

// Initialize variables
$search = isset($_GET['search']) ? sanitizeInput($conn, $_GET['search']) : '';
$filter = isset($_GET['filter']) ? sanitizeInput($conn, $_GET['filter']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10; // 10 users per page
$offset = ($page - 1) * $limit;

// Process bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action']) && isset($_POST['selected_users'])) {
    $action = $_POST['bulk_action'];
    $selectedUsers = $_POST['selected_users'];
    
    if (!empty($selectedUsers)) {
        // Exclude current user from bulk actions
        $selectedUsers = array_filter($selectedUsers, function($id) {
            return $id != $_SESSION['user_id'];
        });
        
        if (!empty($selectedUsers)) {
            $userIds = implode(',', array_map('intval', $selectedUsers));
            
            switch ($action) {
                case 'activate':
                    $conn->query("UPDATE users SET is_active = 1 WHERE id IN ($userIds)");
                    $successMsg = count($selectedUsers) . " users have been activated.";
                    break;
                case 'deactivate':
                    $conn->query("UPDATE users SET is_active = 0 WHERE id IN ($userIds)");
                    $successMsg = count($selectedUsers) . " users have been deactivated.";
                    break;
                case 'delete':
                    // Check if users have orders before deletion
                    $checkOrdersQuery = "SELECT COUNT(*) as count FROM orders WHERE user_id IN ($userIds)";
                    $checkResult = $conn->query($checkOrdersQuery);
                    $orderCount = $checkResult->fetch_assoc()['count'];
                    
                    if ($orderCount > 0) {
                        $errorMsg = "Cannot delete users with existing orders. Please transfer or delete their orders first.";
                    } else {
                        $conn->query("DELETE FROM users WHERE id IN ($userIds)");
                        $successMsg = count($selectedUsers) . " users have been deleted.";
                    }
                    break;
            }
        } else {
            $warningMsg = "You cannot perform bulk actions on your own account.";
        }
    } else {
        $warningMsg = "No users selected for bulk action.";
    }
}

// Build the query
$queryConditions = [];
$queryParams = [];
$paramTypes = '';

// Add search condition if provided
if (!empty($search)) {
    $queryConditions[] = "(username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $searchTerm = "%$search%";
    $queryParams = array_merge($queryParams, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $paramTypes .= 'ssss';
}

// Add filter condition if provided
if (!empty($filter)) {
    switch ($filter) {
        case 'admin':
            $queryConditions[] = "is_admin = 1";
            break;
        case 'customer':
            $queryConditions[] = "is_admin = 0";
            break;
        case 'active':
            $queryConditions[] = "is_active = 1";
            break;
        case 'inactive':
            $queryConditions[] = "is_active = 0";
            break;
        case 'recent':
            $queryConditions[] = "register_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
}

// Build the final query
$whereClause = !empty($queryConditions) ? "WHERE " . implode(" AND ", $queryConditions) : "";
$query = "SELECT * FROM users $whereClause ORDER BY id DESC LIMIT ?, ?";

// Add pagination parameters
$queryParams[] = $offset;
$queryParams[] = $limit;
$paramTypes .= 'ii';

// Count total records for pagination
$countQuery = "SELECT COUNT(*) as total FROM users $whereClause";
$countStmt = $conn->prepare($countQuery);

if (!empty($queryParams) && !empty(str_replace('ii', '', $paramTypes))) {
    // Remove the pagination parameter types
    $countParamTypes = str_replace('ii', '', $paramTypes);
    // Remove the pagination parameters
    $countParams = array_slice($queryParams, 0, -2);
    
    $countStmt->bind_param($countParamTypes, ...$countParams);
}

$countStmt->execute();
$totalResult = $countStmt->get_result();
$totalRecords = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Fetch users
$stmt = $conn->prepare($query);
if (!empty($queryParams)) {
    $stmt->bind_param($paramTypes, ...$queryParams);
}
$stmt->execute();
$result = $stmt->get_result();

// Add custom CSS for the user management page
$extra_css = '
<style>
    .user-status {
        padding: 0.35rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .status-active {
        background-color: var(--green-light);
        color: var(--green-dark);
    }
    .status-inactive {
        background-color: var(--gray-200);
        color: var(--gray-600);
    }
    .user-role {
        padding: 0.35rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .role-admin {
        background-color: var(--purple-light);
        color: var(--purple-dark);
    }
    .role-customer {
        background-color: var(--blue-light);
        color: var(--blue-dark);
    }
    .filters-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    .filter-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.35rem 0.75rem;
        border-radius: 0.35rem;
        font-size: 0.85rem;
        background-color: var(--bg-tertiary);
        color: var(--text-secondary);
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .filter-badge:hover,
    .filter-badge.active {
        background-color: var(--accent-light);
        color: var(--accent-color);
    }
    .filter-badge i {
        font-size: 0.8rem;
    }
    .bulk-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }
    .bulk-form {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
    }
    .select-all-container {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }
    .clickable-row {
        cursor: pointer;
        transition: background-color 0.2s ease;
    }
    .clickable-row:hover {
        background-color: var(--bg-tertiary);
    }
    .search-form {
        display: flex;
        align-items: stretch;
        gap: 0.5rem;
    }
    .search-form .form-control {
        border-radius: 0.375rem;
        border: 1px solid var(--border-color);
        padding: 0.5rem 1rem;
        min-width: 250px;
    }
    .action-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
    }
    @media (max-width: 768px) {
        .search-form {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>';

// Include header
require_once '../includes/admin_header.php';
?>

<div class="admin-header">
    <div>
        <h1 class="admin-title" data-aos="fade-right">User Management</h1>
        <p class="admin-subtitle">Manage user accounts and permissions</p>
    </div>
    <div class="admin-actions" data-aos="fade-left">
        <a href="add_user.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add New User</a>
        <a href="export_users.php" class="btn btn-secondary"><i class="fas fa-file-export"></i> Export Users</a>
    </div>
</div>

<?php if (isset($successMsg)): ?>
    <div class="alert alert-success" data-aos="fade-up">
        <i class="fas fa-check-circle"></i> <?php echo $successMsg; ?>
    </div>
<?php endif; ?>

<?php if (isset($errorMsg)): ?>
    <div class="alert alert-error" data-aos="fade-up">
        <i class="fas fa-exclamation-circle"></i> <?php echo $errorMsg; ?>
    </div>
<?php endif; ?>

<?php if (isset($warningMsg)): ?>
    <div class="alert alert-warning" data-aos="fade-up">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $warningMsg; ?>
    </div>
<?php endif; ?>

<div class="admin-card" data-aos="fade-up">
    <div class="card-header">
        <div class="search-and-filters">
            <form action="users.php" method="GET" class="search-form">
                <input type="text" name="search" class="form-control" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                <button type="submit" class="btn"><i class="fas fa-search"></i> Search</button>
                <?php if (!empty($search) || !empty($filter)): ?>
                    <a href="users.php" class="btn btn-secondary"><i class="fas fa-times"></i> Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <div class="filters-row">
        <a href="users.php" class="filter-badge <?php echo empty($filter) ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> All Users
        </a>
        <a href="users.php?filter=admin<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
           class="filter-badge <?php echo $filter === 'admin' ? 'active' : ''; ?>">
            <i class="fas fa-user-shield"></i> Administrators
        </a>
        <a href="users.php?filter=customer<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
           class="filter-badge <?php echo $filter === 'customer' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i> Customers
        </a>
        <a href="users.php?filter=active<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
           class="filter-badge <?php echo $filter === 'active' ? 'active' : ''; ?>">
            <i class="fas fa-check-circle"></i> Active
        </a>
        <a href="users.php?filter=inactive<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
           class="filter-badge <?php echo $filter === 'inactive' ? 'active' : ''; ?>">
            <i class="fas fa-times-circle"></i> Inactive
        </a>
        <a href="users.php?filter=recent<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
           class="filter-badge <?php echo $filter === 'recent' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i> New (30 days)
        </a>
    </div>
    
    <?php if ($result->num_rows > 0): ?>
        <form action="users.php" method="POST">
            <div class="bulk-actions">
                <div class="bulk-form">
                    <select name="bulk_action" class="form-select">
                        <option value="">Select Bulk Action</option>
                        <option value="activate">Activate Selected</option>
                        <option value="deactivate">Deactivate Selected</option>
                        <option value="delete">Delete Selected</option>
                    </select>
                    <button type="submit" class="btn btn-secondary" onclick="return confirm('Are you sure you want to perform this action on the selected users?')">Apply</button>
                </div>
            </div>
            
            <div class="select-all-container">
                <input type="checkbox" id="selectAll" class="form-check-input">
                <label for="selectAll" class="form-check-label">Select All</label>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $result->fetch_assoc()): ?>
                            <tr class="<?php echo $user['id'] == $_SESSION['user_id'] ? 'highlight-row' : 'clickable-row'; ?>" 
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                onclick="window.location='view_user.php?id=<?php echo $user['id']; ?>'"
                                <?php endif; ?>>
                                <td onclick="event.stopPropagation();">
                                    <input type="checkbox" name="selected_users[]" value="<?php echo $user['id']; ?>" class="user-checkbox form-check-input"
                                           <?php echo $user['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                </td>
                                <td>#<?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td>
                                    <span class="user-role <?php echo $user['is_admin'] ? 'role-admin' : 'role-customer'; ?>">
                                        <?php echo $user['is_admin'] ? '<i class="fas fa-user-shield"></i> Admin' : '<i class="fas fa-user"></i> Customer'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="user-status <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $user['is_active'] ? '<i class="fas fa-check-circle"></i> Active' : '<i class="fas fa-times-circle"></i> Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['register_date'])); ?></td>
                                <td onclick="event.stopPropagation();">
                                    <div class="action-buttons">
                                        <a href="view_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-icon" title="View User">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-icon" title="Edit User">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <?php if ($user['is_active']): ?>
                                                <a href="toggle_status.php?id=<?php echo $user['id']; ?>&action=deactivate" 
                                                   class="btn btn-sm btn-icon" title="Deactivate User"
                                                   onclick="return confirm('Are you sure you want to deactivate this user?')">
                                                    <i class="fas fa-user-slash"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="toggle_status.php?id=<?php echo $user['id']; ?>&action=activate" 
                                                   class="btn btn-sm btn-icon" title="Activate User">
                                                    <i class="fas fa-user-check"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </form>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="users.php?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter) ? '&filter=' . urlencode($filter) : ''; ?>" class="pagination-item">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="users.php?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter) ? '&filter=' . urlencode($filter) : ''; ?>" class="pagination-item">
                        <i class="fas fa-angle-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                if ($startPage > 1) {
                    echo '<span class="pagination-ellipsis">...</span>';
                }
                
                for ($i = $startPage; $i <= $endPage; $i++) {
                    $activeClass = ($i === $page) ? 'active' : '';
                    echo '<a href="users.php?page=' . $i . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($filter) ? '&filter=' . urlencode($filter) : '') . '" class="pagination-item ' . $activeClass . '">' . $i . '</a>';
                }
                
                if ($endPage < $totalPages) {
                    echo '<span class="pagination-ellipsis">...</span>';
                }
                ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="users.php?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter) ? '&filter=' . urlencode($filter) : ''; ?>" class="pagination-item">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="users.php?page=<?php echo $totalPages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter) ? '&filter=' . urlencode($filter) : ''; ?>" class="pagination-item">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="pagination-info">
            Showing <?php echo min(($page - 1) * $limit + 1, $totalRecords); ?> to <?php echo min($page * $limit, $totalRecords); ?> of <?php echo $totalRecords; ?> users
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-users"></i>
            <h3>No users found</h3>
            <p>No users match your search criteria. Try adjusting your filters or search term.</p>
            <?php if (!empty($search) || !empty($filter)): ?>
                <a href="users.php" class="btn">View All Users</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    const userCheckboxes = document.querySelectorAll('.user-checkbox:not([disabled])');
    
    if (selectAllCheckbox && userCheckboxes.length) {
        selectAllCheckbox.addEventListener('change', function() {
            userCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });
        
        // Update "select all" checkbox when individual checkboxes change
        userCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allChecked = Array.from(userCheckboxes).every(c => c.checked);
                const anyChecked = Array.from(userCheckboxes).some(c => c.checked);
                
                selectAllCheckbox.checked = allChecked;
                selectAllCheckbox.indeterminate = anyChecked && !allChecked;
            });
        });
    }
    
    // Prevent row click when clicking on checkboxes
    document.querySelectorAll('.user-checkbox').forEach(checkbox => {
        checkbox.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
});
</script>

<?php
// Create toggle_status.php file if it doesn't exist
$toggleStatusFile = __DIR__ . '/toggle_status.php';
if (!file_exists($toggleStatusFile)) {
    $toggleStatusContent = '<?php
$current_page = \'admin\';
require_once \'../includes/db_connection.php\';
require_once \'../includes/functions.php\';

// Check if user is admin
requireAdmin();

// Check if user ID and action are provided
if (!isset($_GET[\'id\']) || empty($_GET[\'id\']) || !isset($_GET[\'action\']) || empty($_GET[\'action\'])) {
    header("Location: users.php");
    exit();
}

$userId = intval($_GET[\'id\']);
$action = $_GET[\'action\'];

// Make sure user isn\'t trying to deactivate their own account
if ($userId === $_SESSION[\'user_id\']) {
    $_SESSION[\'error_message\'] = "You cannot change the status of your own account.";
    header("Location: users.php");
    exit();
}

// Update user status based on action
if ($action === \'activate\') {
    $stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
    $message = "User activated successfully.";
} elseif ($action === \'deactivate\') {
    $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
    $message = "User deactivated successfully.";
} else {
    header("Location: users.php");
    exit();
}

$stmt->bind_param("i", $userId);
$stmt->execute();

if ($conn->affected_rows > 0) {
    $_SESSION[\'success_message\'] = $message;
} else {
    $_SESSION[\'error_message\'] = "Failed to update user status.";
}

header("Location: users.php");
exit();
?>';
    file_put_contents($toggleStatusFile, $toggleStatusContent);
}

require_once '../includes/admin_footer.php';
?>
