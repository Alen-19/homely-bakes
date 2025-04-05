<?php
session_start();
include '../connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] != 2) {
    header("Location: ../login.php");
    exit();
}

// Fetch overall platform statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM table_baker) as total_bakers,
    (SELECT COUNT(*) FROM table_login WHERE user_type = 1) as total_customers,
    (SELECT COUNT(*) FROM table_orders) as total_orders,
    (SELECT SUM(total_price) FROM table_orders WHERE status = 'completed') as total_revenue";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Fetch recent orders
$recent_orders_query = "SELECT o.*, r.first_name as customer_name, b.bakery_name 
    FROM table_orders o
    JOIN table_registration r ON o.user_id = r.user_id
    JOIN table_baker b ON o.baker_id = b.baker_id
    ORDER BY o.order_date DESC LIMIT 10";
$recent_orders = $conn->query($recent_orders_query);

// Fetch top performing bakers
$top_bakers_query = "SELECT 
    b.baker_id,
    b.bakery_name,
    COUNT(o.order_id) as total_orders,
    SUM(o.total_price) as total_revenue
    FROM table_baker b
    LEFT JOIN table_orders o ON b.baker_id = o.baker_id
    GROUP BY b.baker_id
    ORDER BY total_revenue DESC
    LIMIT 5";
$top_bakers = $conn->query($top_bakers_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Homely Bakes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
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

        /* Sidebar Styles */
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

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: var(--text-color);
            font-size: 24px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .stat-card .icon {
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 24px;
            font-weight: bold;
            color: var(--text-color);
            margin: 10px 0;
        }

        .stat-card .label {
            color: #666;
            font-size: 14px;
        }

        /* Table Styles */
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .table-title {
            font-size: 18px;
            color: var(--text-color);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        /* Status Badges */
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        /* Action Buttons */
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        /* Profile Section */
        .profile-section {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-image {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .profile-name {
            font-weight: 500;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                padding: 10px;
            }

            .main-content {
                padding: 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
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

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-birthday-cake"></i></div>
                    <div class="value"><?php echo $stats['total_bakers']; ?></div>
                    <div class="label">Total Bakers</div>
                </div>
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-users"></i></div>
                    <div class="value"><?php echo $stats['total_customers']; ?></div>
                    <div class="label">Total Customers</div>
                </div>
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-shopping-bag"></i></div>
                    <div class="value"><?php echo $stats['total_orders']; ?></div>
                    <div class="label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-rupee-sign"></i></div>
                    <div class="value">₹<?php echo number_format($stats['total_revenue'], 2); ?></div>
                    <div class="label">Total Revenue</div>
                </div>
            </div>

            <!-- Recent Orders Table -->
            <div class="table-container">
                <div class="table-header">
                    <h2 class="table-title">Recent Orders</h2>
                    <a href="manage_orders.php" class="btn btn-primary">View All Orders</a>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Baker</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($order = $recent_orders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $order['order_id']; ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['bakery_name']); ?></td>
                            <td>₹<?php echo number_format($order['total_price'], 2); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Top Performing Bakers Table -->
            <div class="table-container">
                <div class="table-header">
                    <h2 class="table-title">Top Performing Bakers</h2>
                    <a href="manage_bakers.php" class="btn btn-primary">View All Bakers</a>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Baker Name</th>
                            <th>Total Orders</th>
                            <th>Total Revenue</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($baker = $top_bakers->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($baker['bakery_name']); ?></td>
                            <td><?php echo $baker['total_orders']; ?></td>
                            <td>₹<?php echo number_format($baker['total_revenue'], 2); ?></td>
                            <td>
                                <a href="view_baker.php?id=<?php echo $baker['baker_id']; ?>" class="btn btn-primary">View Details</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Add any necessary JavaScript functionality here
        document.addEventListener('DOMContentLoaded', function() {
            // Navigation active state
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                item.addEventListener('click', function() {
                    navItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        });
    </script>
</body>
</html> 