<?php
session_start();
include '../connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] != 2) {
    header("Location: ../login.php");
    exit();
}

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
if($search) {
    $search_condition = "AND (l.email LIKE ? OR r.first_name LIKE ? OR r.last_name LIKE ?)";
}

// Pagination
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get total number of records for pagination
$count_query = "SELECT COUNT(*) as total FROM table_registration r 
                JOIN table_login l ON r.user_id = l.login_id 
                WHERE l.user_type = 3 " . $search_condition;
$stmt = $conn->prepare($count_query);
if($search) {
    $search_param = "%$search%";
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
}
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get customers with pagination
$query = "SELECT r.*, l.email, r.mobile_number, r.street_address, r.city, r.district, r.state,
            (SELECT COUNT(*) FROM table_orders WHERE user_id = r.user_id) as total_orders,
            (SELECT COALESCE(SUM(total_price), 0) FROM table_orders WHERE user_id = r.user_id) as total_spent
          FROM table_registration r
          JOIN table_login l ON r.user_id = l.login_id
          WHERE l.user_type = 1 " . $search_condition . "
          ORDER BY r.user_id DESC
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
if($search) {
    $search_param = "%$search%";
    $stmt->bind_param("sssii", $search_param, $search_param, $search_param, $records_per_page, $offset);
} else {
    $stmt->bind_param("ii", $records_per_page, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Include your CSS here -->
    <style>
        /* Copy the CSS from manage_bakers.php */
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'admin_sidebar.php'; ?>

        <div class="main-content">
            <?php include 'admin_profile.php'; ?>
            <h1>Manage Customers</h1>

            <!-- Search Bar -->
            <div class="search-bar">
                <form method="GET" action="" style="width: 100%; display: flex; gap: 10px;">
                    <input type="text" name="search" placeholder="Search customers..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                </form>
            </div>

            <!-- Customers List -->
            <?php while($customer = $result->fetch_assoc()): ?>
                <div class="baker-card">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Customer Name</span>
                            <span class="info-value">
                                <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email</span>
                            <span class="info-value">
                                <?php echo htmlspecialchars($customer['email']); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Phone</span>
                            <span class="info-value">
                                <?php echo htmlspecialchars($customer['mobile_number']); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Address</span>
                            <span class="info-value">
                                <?php echo htmlspecialchars($customer['street_address'] . ', ' . $customer['city']); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total Orders</span>
                            <span class="info-value">
                                <?php echo $customer['total_orders']; ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total Spent</span>
                            <span class="info-value">
                                ₹<?php echo number_format($customer['total_spent'], 2); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div style="margin-top: 15px; display: flex; gap: 10px;">
                        <a href="view_customer.php?id=<?php echo $customer['user_id']; ?>" 
                           class="btn btn-primary">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>

            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <?php if($page > 1): ?>
                        <a href="?page=<?php echo ($page-1); ?>&search=<?php echo urlencode($search); ?>" 
                           class="btn btn-outline">
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
                        <a href="?page=<?php echo ($page+1); ?>&search=<?php echo urlencode($search); ?>" 
                           class="btn btn-outline">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 