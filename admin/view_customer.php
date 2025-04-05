<?php
session_start();
include '../connect.php';

// Check if admin is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] != 2) {
    header("Location: ../login.php");
    exit();
}

// Check if customer ID is provided
$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$customer_id) {
    header("Location: manage_customers.php");
    exit();
}

// Fetch customer details
$customer_query = "SELECT r.*, l.email, c.profile_photo,
                    (SELECT COUNT(*) FROM table_orders WHERE user_id = r.user_id) as total_orders,
                    (SELECT COALESCE(SUM(total_price), 0) FROM table_orders WHERE user_id = r.user_id) as total_spent
                  FROM table_registration r
                  JOIN table_login l ON r.user_id = l.login_id
                  LEFT JOIN table_customer c ON r.user_id = c.user_id
                  WHERE r.user_id = ? AND l.user_type = 1";

$stmt = mysqli_prepare($conn, $customer_query);
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$customer = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$customer) {
    header("Location: manage_customers.php");
    exit();
}

// Fetch customer's orders
$orders_query = "SELECT o.*, p.product_name, p.image_url,
                 b.first_name as baker_first_name, b.last_name as baker_last_name
                 FROM table_orders o
                 JOIN table_product p ON o.product_id = p.product_id
                 JOIN table_registration b ON p.baker_id = b.user_id
                 WHERE o.user_id = ?
                 ORDER BY o.order_date DESC";

$stmt = mysqli_prepare($conn, $orders_query);
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$orders = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Details - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #1a237e;
            --secondary-color: #4CAF50;
            --danger-color: #f44336;
            --text-color: #333;
            --border-color: #ddd;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            background-color: #f5f5f5;
            color: var(--text-color);
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .customer-header {
            display: flex;
            align-items: flex-start;
            gap: 30px;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .customer-profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .customer-info {
            flex: 1;
        }

        .customer-name {
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .customer-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
        }

        .stat-label {
            color: #666;
            margin-top: 5px;
        }

        .section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 20px;
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-item {
            margin-bottom: 15px;
        }

        .info-label {
            font-weight: bold;
            color: #666;
            display: block;
            margin-bottom: 5px;
        }

        .info-value {
            color: var(--text-color);
        }

        .orders-list {
            margin-top: 20px;
        }

        .order-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 20px;
            align-items: center;
        }

        .order-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }

        .order-details h3 {
            margin-bottom: 5px;
            color: var(--primary-color);
        }

        .order-meta {
            color: #666;
            font-size: 0.9em;
        }

        .order-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            text-transform: uppercase;
        }

        .status-pending { background: #fff3e0; color: #e65100; }
        .status-accepted { background: #e3f2fd; color: #1565c0; }
        .status-completed { background: #e8f5e9; color: #2e7d32; }
        .status-rejected { background: #ffebee; color: #c62828; }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-back {
            background: var(--primary-color);
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .order-card {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .order-image {
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_profile.php'; ?>
    <?php include 'admin_sidebar.php'; ?>
    <div class="container" style="margin-left: 250px; transition: margin-left .5s;">
        <div class="customer-header">
            <img src="<?php 
                if (!empty($customer['profile_photo'])) {
                    echo '../' . htmlspecialchars($customer['profile_photo']);
                } else {
                    echo '../assets/images/default-profile.jpg';
                }
            ?>" 
            alt="<?php echo htmlspecialchars($customer['first_name']); ?>'s profile" 
            class="customer-profile-image"
            onerror="this.src='../assets/images/default-profile.jpg'">
            
            <div class="customer-info">
                <h1 class="customer-name">
                    <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                </h1>
                
                <div class="customer-stats">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $customer['total_orders']; ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">₹<?php echo number_format($customer['total_spent'], 2); ?></div>
                        <div class="stat-label">Total Spent</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Personal Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?php echo htmlspecialchars($customer['email']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Phone</span>
                    <span class="info-value"><?php echo htmlspecialchars($customer['mobile_number']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Address</span>
                    <span class="info-value">
                        <?php 
                        echo htmlspecialchars($customer['street_address']) . ', ' . 
                             htmlspecialchars($customer['city']) . ', ' .
                             htmlspecialchars($customer['district']) . ', ' .
                             htmlspecialchars($customer['state']);
                        ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Order History</h2>
            <div class="orders-list">
                <?php if(mysqli_num_rows($orders) > 0): ?>
                    <?php while($order = mysqli_fetch_assoc($orders)): ?>
                        <div class="order-card">
                            <img src="../<?php echo htmlspecialchars($order['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($order['product_name']); ?>"
                                 class="order-image"
                                 onerror="this.src='../images/placeholder.jpg'">
                            
                            <div class="order-details">
                                <h3><?php echo htmlspecialchars($order['product_name']); ?></h3>
                                <div class="order-meta">
                                    <p>Baker: <?php echo htmlspecialchars($order['baker_first_name'] . ' ' . $order['baker_last_name']); ?></p>
                                    <p>Order Date: <?php echo date('F j, Y, g:i a', strtotime($order['order_date'])); ?></p>
                                    <p>Amount: ₹<?php echo number_format($order['total_price'], 2); ?></p>
                                </div>
                            </div>

                            <div class="order-status status-<?php echo strtolower($order['status']); ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #666;">No orders found for this customer.</p>
                <?php endif; ?>
            </div>
        </div>

        <div style="text-align: right; margin-top: 20px;">
            <a href="manage_customers.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back to Customers
            </a>
        </div>
    </div>
</body>
</html> 