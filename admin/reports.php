<?php
session_start();
include '../connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] != 2) {
    header("Location: ../login.php");
    exit();
}

// Get date range filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Overall Statistics
$stats_query = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_orders,
    COALESCE(SUM(total_price), 0) as total_revenue
FROM table_orders
WHERE order_date BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$overall_stats = $stmt->get_result()->fetch_assoc();

// Top Bakers
$top_bakers_query = "SELECT 
    b.baker_id,
    b.bakery_name,
    COUNT(*) as total_orders,
    COALESCE(SUM(o.total_price), 0) as total_revenue
FROM table_orders o
JOIN table_baker b ON o.baker_id = b.baker_id
WHERE o.status = 'delivered' 
AND o.order_date BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
GROUP BY b.baker_id
ORDER BY total_revenue DESC
LIMIT 5";

$stmt = $conn->prepare($top_bakers_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$top_bakers = $stmt->get_result();

// Daily Revenue Chart Data
$daily_revenue_query = "SELECT 
    DATE(order_date) as date,
    COUNT(*) as orders,
    COALESCE(SUM(total_price), 0) as revenue
FROM table_orders
WHERE status = 'delivered'
AND order_date BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
GROUP BY DATE(order_date)
ORDER BY date";

$stmt = $conn->prepare($daily_revenue_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$daily_revenue = $stmt->get_result();

// Prepare chart data
$dates = [];
$revenues = [];
$orders = [];
while($row = $daily_revenue->fetch_assoc()) {
    $dates[] = date('d M', strtotime($row['date']));
    $revenues[] = $row['revenue'];
    $orders[] = $row['orders'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .stat-card .stat-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 24px;
            opacity: 1;
        }

        .stat-card:nth-child(1) .stat-icon {
            color: #4CAF50;
        }

        .stat-card:nth-child(2) .stat-icon {
            color: #2196F3;
        }

        .stat-card:nth-child(3) .stat-icon {
            color: #ff9800;
        }

        .stat-card:nth-child(4) .stat-icon {
            color: #4CAF50;
        }

        .stat-card .stat-title {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
            padding-right: 30px;
        }

        .stat-card .stat-value {
            font-size: 1.8rem;
            font-weight: 600;
            color: #333;
        }

        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            height: 400px;
            width: 70%;
            margin-left: auto;
            margin-right: auto;
        }

        .top-bakers {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .baker-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .baker-row:last-child {
            border-bottom: none;
        }

        .date-filter {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .date-filter form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .date-filter input[type="date"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'admin_sidebar.php'; ?>

        <div class="main-content">
            <?php include 'admin_profile.php'; ?>
            <h1>Reports & Analytics</h1>

            <!-- Date Filter -->
            <div class="date-filter">
                <form method="GET">
                    <label>
                        Start Date:
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                    </label>
                    <label>
                        End Date:
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                    </label>
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                </form>
            </div>

            <!-- Overall Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-shopping-cart stat-icon"></i>
                    <div class="stat-title">Total Orders</div>
                    <div class="stat-value"><?php echo $overall_stats['total_orders']; ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-check-circle stat-icon"></i>
                    <div class="stat-title">Completed Orders</div>
                    <div class="stat-value"><?php echo $overall_stats['completed_orders']; ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock stat-icon"></i>
                    <div class="stat-title">Pending Orders</div>
                    <div class="stat-value"><?php echo $overall_stats['pending_orders']; ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-rupee-sign stat-icon"></i>
                    <div class="stat-title">Total Revenue</div>
                    <div class="stat-value">₹<?php echo number_format($overall_stats['total_revenue'], 2); ?></div>
                </div>
            </div>

            <!-- Revenue Chart -->
            <div class="chart-container">
                <h2>Revenue & Orders Trend</h2>
                <canvas id="revenueChart"></canvas>
            </div>

            <!-- Top Bakers -->
            <div class="top-bakers">
                <h2>Top Performing Bakers</h2>
                <?php while($baker = $top_bakers->fetch_assoc()): ?>
                    <div class="baker-row">
                        <div>
                            <h3><?php echo htmlspecialchars($baker['bakery_name']); ?></h3>
                            <small><?php echo $baker['total_orders']; ?> orders</small>
                        </div>
                        <div>
                            <strong>₹<?php echo number_format($baker['total_revenue'], 2); ?></strong>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <script>
        // Initialize Revenue Chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: <?php echo json_encode($revenues); ?>,
                    backgroundColor: '#4CAF50',
                    yAxisID: 'y'
                }, {
                    label: 'Orders',
                    data: <?php echo json_encode($orders); ?>,
                    backgroundColor: '#2196F3',
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue (₹)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Number of Orders'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 