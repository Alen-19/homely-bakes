<?php
session_start();
include '../connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] != 2) {
    header("Location: ../login.php");
    exit();
}

// Add this code after session_start() and before the queries
if (isset($_POST['toggle_status'])) {
    $baker_id = $_POST['baker_id'];
    $new_status = $_POST['new_status'];
    
    // Validate the new status
    if (!in_array($new_status, ['available', 'unavailable'])) {
        $_SESSION['error'] = "Invalid status value";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    $update_query = "UPDATE table_baker SET availability_status = ? WHERE baker_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_status, $baker_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Baker status updated successfully";
    } else {
        $_SESSION['error'] = "Failed to update baker status";
    }
    
    // Redirect to the same page while preserving search and pagination
    $redirect_url = $_SERVER['PHP_SELF'];
    if (isset($_GET['search'])) {
        $redirect_url .= '?search=' . urlencode($_GET['search']);
        if (isset($_GET['page'])) {
            $redirect_url .= '&page=' . (int)$_GET['page'];
        }
    } elseif (isset($_GET['page'])) {
        $redirect_url .= '?page=' . (int)$_GET['page'];
    }
    
    header("Location: " . $redirect_url);
    exit();
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
if($search) {
    $search_condition = "AND (b.bakery_name LIKE ? OR l.email LIKE ? OR r.first_name LIKE ? OR r.last_name LIKE ?)";
}

// Number of records per page
$records_per_page = 10;

// Get current page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get total number of records for pagination
$count_query = "SELECT COUNT(*) as total FROM table_baker b 
                JOIN table_registration r ON b.user_id = r.user_id 
                JOIN table_login l ON b.user_id = l.user_id 
                WHERE 1=1 " . $search_condition;
$stmt = $conn->prepare($count_query);
if($search) {
    $search_param = "%$search%";
    $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
}
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];

// Calculate total pages
$total_pages = ceil($total_records / $records_per_page);

// Modify the main query to include LIMIT and OFFSET
$query = "SELECT b.*, l.email, r.first_name, r.last_name,
            (SELECT COUNT(*) FROM table_orders WHERE baker_id = b.baker_id) as total_orders,
            (SELECT COALESCE(SUM(total_price), 0) FROM table_orders WHERE baker_id = b.baker_id AND status = 'completed') as total_revenue
          FROM table_baker b
          JOIN table_registration r ON b.user_id = r.user_id
          JOIN table_login l ON b.user_id = l.user_id
          WHERE 1=1 " . $search_condition . "
          ORDER BY b.baker_id DESC
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
if($search) {
    $search_param = "%$search%";
    $stmt->bind_param("ssssii", $search_param, $search_param, $search_param, $search_param, $records_per_page, $offset);
} else {
    $stmt->bind_param("ii", $records_per_page, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

// Display status based on availability_status field
function getStatusBadge($status) {
    return $status === 'available' ? 'Available' : 'Unavailable';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bakers - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Copy all the styles from admin_dashboard.php */
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #2196F3;
            --danger-color: #f44336;
            --warning-color: #ff9800;
            --success-color: #4CAF50;
            --text-color: #333;
            --bg-color: #f5f5f5;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--bg-color);
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Copy all sidebar styles from admin_dashboard.php */
        .sidebar {
            width: 250px;
            background-color: #fff;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            padding: 20px;
        }

        .logo {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
        }

        .logo img {
            max-width: 150px;
        }

        .nav-menu {
            margin-top: 30px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 15px;
            color: var(--text-color);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .nav-item:hover {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--primary-color);
        }

        .nav-item.active {
            background-color: var(--primary-color);
            color: white;
        }

        .nav-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main content styles */
        .main-content {
            flex: 1;
            padding: 30px;
        }

        /* Additional styles for manage bakers page */
        .search-bar {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }

        .search-bar input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .search-bar button {
            padding: 10px 20px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .baker-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .baker-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .baker-header h3 {
            margin: 0;
        }

        .baker-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 15px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 12px;
            color: #666;
        }

        .info-value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }

        .baker-actions {
            display: flex;
            gap: 10px;
        }

        .status-toggle {
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .status-toggle.status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-toggle.status-active:hover {
            background-color: #c3e6cb;
        }

        .status-toggle.status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-toggle.status-inactive:hover {
            background-color: #f5c6cb;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .pagination .btn {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .pagination .btn-outline {
            border: 1px solid var(--border-color);
            color: var(--text-color);
            background: white;
        }

        .pagination .btn-outline:hover {
            background: var(--bg-color);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .pagination .btn-primary {
            background: var(--primary-color);
            color: white;
            border: 1px solid var(--primary-color);
        }

        .pagination .btn i {
            font-size: 0.8rem;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.9em;
            margin-right: 10px;
        }

        .status-available {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-unavailable {
            background-color: #ffebee;
            color: #c62828;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.9em;
        }

        .status-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-text {
            font-weight: 500;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.9em;
        }

        .text-available {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .text-unavailable {
            background-color: #ffebee;
            color: #c62828;
        }

        .status-button {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 0.9em;
        }

        .make-available {
            background-color: #4CAF50;
            color: white;
        }

        .make-unavailable {
            background-color: #f44336;
            color: white;
        }

        .status-button:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Include Sidebar -->
        <?php include 'admin_sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
        <?php include 'admin_profile.php'; ?>
            <h1>Manage Bakers</h1>

            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Search Bar -->
            <form class="search-bar" method="GET">
                <input type="text" name="search" placeholder="Search bakers..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit"><i class="fas fa-search"></i> Search</button>
            </form>

            <!-- Bakers List -->
            <?php while($baker = $result->fetch_assoc()): ?>
                <div class="baker-card">
                    <div class="baker-header">
                        <h3><?php echo htmlspecialchars($baker['bakery_name'] ?? 'Unnamed Bakery'); ?></h3>
                    </div>
                    <div class="baker-info">
                        <div class="info-item">
                            <span class="info-label">Baker Name</span>
                            <span class="info-value"><?php echo htmlspecialchars($baker['first_name'] . ' ' . $baker['last_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?php echo htmlspecialchars($baker['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total Orders</span>
                            <span class="info-value"><?php echo $baker['total_orders']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total Revenue</span>
                            <span class="info-value">₹<?php echo number_format($baker['total_revenue'], 2); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Availability Status</span>
                            <div class="status-container">
                                <span class="status-text <?php echo $baker['admin_override'] === 'allowed' ? 'text-available' : 'text-unavailable'; ?>">
                                    <?php echo $baker['admin_override'] === 'allowed' ? 'Available' : 'Unavailable'; ?>
                                </span>
                                <button onclick="updateBakerStatus(<?php echo $baker['baker_id']; ?>, '<?php echo $baker['admin_override'] === 'allowed' ? 'restricted' : 'allowed'; ?>')" 
                                        class="status-button <?php echo $baker['admin_override'] === 'allowed' ? 'make-unavailable' : 'make-available'; ?>">
                                    <?php echo $baker['admin_override'] === 'allowed' ? 'Make Unavailable' : 'Make Available'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="baker-actions">
                        <a href="view_baker.php?id=<?php echo $baker['baker_id']; ?>" class="btn btn-primary">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        <!-- <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this baker?');">
                            <input type="hidden" name="baker_id" value=">">
                            <button type="submit" name="delete_baker" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Delete
                            </button> -->
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>

            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <?php if($page > 1): ?>
                        <a href="?page=<?php echo ($page-1); ?>&search=<?php echo urlencode($search); ?>" class="btn btn-outline">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                           class="btn <?php echo $page == $i ? 'btn-primary' : 'btn-outline'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if($page < $total_pages): ?>
                        <a href="?page=<?php echo ($page+1); ?>&search=<?php echo urlencode($search); ?>" class="btn btn-outline">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Confirm delete
        function confirmDelete() {
            return confirm('Are you sure you want to delete this baker?');
        }

        function updateBakerStatus(bakerId, newStatus) {
            if (!confirm('Are you sure you want to update the baker status?')) {
                return;
            }

            // Create and submit a form instead of using fetch
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'update_baker_status.php';

            const bakerIdInput = document.createElement('input');
            bakerIdInput.type = 'hidden';
            bakerIdInput.name = 'baker_id';
            bakerIdInput.value = bakerId;

            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = newStatus;

            form.appendChild(bakerIdInput);
            form.appendChild(statusInput);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html> 