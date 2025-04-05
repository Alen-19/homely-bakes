<?php
session_start();
include '../connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] != 2) {
    header("Location: ../login.php");
    exit();
}

// Add this near the top of the file, after session_start()
if (isset($_GET['success']) && $_GET['success'] == 'order_deleted') {
    echo '<div class="alert alert-success">Order has been successfully deleted.</div>';
}
if (isset($_GET['error'])) {
    $error_message = '';
    switch($_GET['error']) {
        case 'cannot_delete':
            $error_message = 'Cannot delete this order. It may be completed or already deleted.';
            break;
        case 'delete_failed':
            $error_message = 'Failed to delete the order. Please try again.';
            break;
    }
    if ($error_message) {
        echo '<div class="alert alert-danger">' . $error_message . '</div>';
    }
}

// Add this near the top of the file after session_start()
if (isset($_GET['success']) && $_GET['success'] === 'deleted') {
    echo '<div id="alert" class="alert alert-success">Order has been successfully deleted.</div>';
}

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build search condition
$search_condition = '';
$params = [];
$param_types = '';

if($search) {
    $search_condition .= " AND (o.razorpay_order_id LIKE ? OR r.first_name LIKE ? OR r.last_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= 'sss';
}

if($status_filter) {
    $search_condition .= " AND o.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

// Pagination
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get total number of records for pagination
$count_query = "SELECT COUNT(*) as total FROM table_orders o
                JOIN table_registration r ON o.user_id = r.user_id
                WHERE 1=1" . $search_condition;

$stmt = $conn->prepare($count_query);
if(!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get orders with pagination
$query = "SELECT o.*, r.first_name, r.last_name, r.mobile_number,
            b.bakery_name, p.product_name, p.price
          FROM table_orders o
          JOIN table_registration r ON o.user_id = r.user_id
          JOIN table_baker b ON o.baker_id = b.baker_id
          JOIN table_product p ON o.product_id = p.product_id
          WHERE 1=1" . $search_condition . "
          ORDER BY o.order_date DESC
          LIMIT ? OFFSET ?";

$params[] = $records_per_page;
$params[] = $offset;
$param_types .= 'ii';

$stmt = $conn->prepare($query);
if(!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Root Variables */
        :root {
            --primary-color: #4CAF50;
            --primary-dark: #388E3C;
            --secondary-color: #2196F3;
            --danger-color: #f44336;
            --warning-color: #ff9800;
            --success-color: #4CAF50;
            --text-color: #333;
            --text-light: #666;
            --bg-color: #f5f5f5;
            --card-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }

        /* Main Container Styles */
        .dashboard-container {
            min-height: 100vh;
            background-color: #f8f9fa;
        }

        .main-content {
            padding: 30px;
            margin-left: 250px;
        }

        /* Search Container Styles */
        .search-container {
            margin-bottom: 40px;
        }

        .search-container h1 {
            margin-bottom: 25px;
            color: var(--text-color);
            font-size: 2em;
            font-weight: 600;
        }

        .search-wrapper {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
        }

        /* Search Form Elements */
        .search-input-wrapper {
            position: relative;
            flex: 1;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            z-index: 1;
        }

        .search-input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }

        .status-select {
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            min-width: 180px;
            cursor: pointer;
            background-color: white;
            transition: all 0.3s ease;
        }

        .status-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }

        .search-button {
            padding: 12px 30px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.2);
        }

        /* Order Card Styles */
        .order-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease;
        }

        .order-card:hover {
            transform: translateY(-3px);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .info-label {
            color: var(--text-light);
            font-size: 0.9em;
            font-weight: 500;
        }

        .info-value {
            color: var(--text-color);
            font-size: 1.1em;
            font-weight: 500;
        }

        /* Status Badge Styles */
        .status-badge {
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.9em;
            font-weight: 600;
            display: inline-block;
            text-transform: capitalize;
        }

        .status-pending {
            background: #fff3e0;
            color: #e65100;
            border: 1px solid #ffe0b2;
        }

        .status-accepted {
            background: #e3f2fd;
            color: #1565c0;
            border: 1px solid #bbdefb;
        }

        .status-delivered {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .status-rejected {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        /* Action Buttons */
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.95em;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.2);
        }

        /* Pagination Styles */
        .pagination {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 30px;
            padding: 20px 0;
        }

        .pagination .btn {
            padding: 8px 16px;
            background: white;
            color: var(--text-color);
            border: 2px solid #e0e0e0;
        }

        .pagination .btn-primary {
            background: var(--primary-color);
            color: white;
            border: none;
        }

        .pagination .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        /* Alert Styles */
        .alert {
            padding: 15px 25px;
            margin-bottom: 20px;
            border-radius: 10px;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideIn 0.5s ease-in-out;
        }

        .alert-success {
            background-color: #4CAF50;
            color: white;
            border-left: 4px solid #388E3C;
        }

        .alert-danger {
            background-color: #f44336;
            color: white;
            border-left: 4px solid #d32f2f;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 48px;
            color: var(--text-light);
            margin-bottom: 20px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .search-form {
                flex-direction: column;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .pagination {
                flex-wrap: wrap;
            }
        }

        .search-form {
            display: flex;
            gap: 15px;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'admin_sidebar.php'; ?>

        <div class="main-content">
            <?php include 'admin_profile.php'; ?>
            <div class="search-container">
                <h1>Manage Orders</h1>
                <div class="search-wrapper">
                    <form method="GET" action="" class="search-form">
                        <div class="search-input-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" 
                                   placeholder="Search orders by ID, customer name..." 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   class="search-input">
                        </div>
                        <select name="status" class="status-select">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="accepted" <?php echo $status_filter == 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                            <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                        <button type="submit" class="search-button">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </form>
                </div>
            </div>

            <!-- Orders List -->
            <?php if($result->num_rows > 0): ?>
                <?php while($order = $result->fetch_assoc()): ?>
                    <div class="order-card">
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Order ID</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['razorpay_order_id']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Customer</span>
                                <span class="info-value">
                                    <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Baker</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['bakery_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Product</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['product_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Quantity</span>
                                <span class="info-value"><?php echo $order['quantity']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Total Price</span>
                                <span class="info-value">₹<?php echo number_format($order['total_price'], 2); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Order Date</span>
                                <span class="info-value">
                                    <?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Status</span>
                                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div style="margin-top: 15px; display: flex; gap: 10px;">
                            <a href="view_order.php?id=<?php echo $order['order_id']; ?>" 
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
                            <a href="?page=<?php echo ($page-1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                               class="btn btn-outline">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                               class="btn <?php echo $page == $i ? 'btn-primary' : 'btn-outline'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if($page < $total_pages): ?>
                            <a href="?page=<?php echo ($page+1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                               class="btn btn-outline">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>No Orders Found</h3>
                    <p>There are no orders matching your search criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const alert = document.getElementById('alert');
        if (alert) {
            setTimeout(function() {
                alert.style.animation = 'fadeOut 0.5s ease-in-out';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            }, 3000);
        }
    });
    </script>
</body>
</html> 