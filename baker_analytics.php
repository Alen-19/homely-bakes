<?php
ob_start();
session_start();
include('connect.php');

// Check if user is logged in and is a baker
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== '0') {
    header("Location: login.php");
    exit();
}

$baker_id = $_SESSION['user_id'];

// Get baker's total orders
$orders_query = "SELECT 
    COUNT(*) as total_orders,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_orders,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
    COUNT(CASE WHEN status = 'accepted' THEN 1 END) as accepted_orders,
    SUM(total_price) as total_revenue,
    AVG(total_price) as average_order_value
FROM table_orders 
WHERE baker_id = ?";

$stmt = $conn->prepare($orders_query);
$stmt->bind_param("i", $baker_id);
$stmt->execute();
$orders_result = $stmt->get_result();
$analytics = $orders_result->fetch_assoc();

// Get monthly revenue data for the chart
$monthly_revenue_query = "SELECT 
    DATE_FORMAT(order_date, '%Y-%m') as month,
    COUNT(*) as order_count,
    SUM(total_price) as revenue
FROM table_orders 
WHERE baker_id = ? 
    AND order_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
GROUP BY DATE_FORMAT(order_date, '%Y-%m')
ORDER BY month ASC";

$stmt = $conn->prepare($monthly_revenue_query);
$stmt->bind_param("i", $baker_id);
$stmt->execute();
$monthly_result = $stmt->get_result();
$monthly_data = [];
while ($row = $monthly_result->fetch_assoc()) {
    $monthly_data[] = $row;
}

// Get popular products
$popular_products_query = "SELECT 
    p.product_name,
    COUNT(*) as order_count,
    SUM(o.quantity) as total_quantity,
    SUM(o.total_price) as total_revenue
FROM table_orders o
JOIN table_product p ON o.product_id = p.product_id
WHERE o.baker_id = ?
GROUP BY p.product_id
ORDER BY order_count DESC
LIMIT 5";

$stmt = $conn->prepare($popular_products_query);
$stmt->bind_param("i", $baker_id);
$stmt->execute();
$popular_products_result = $stmt->get_result();

include('header.php');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Baker Analytics - Homely Bakes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-container {
            max-width: 1200px;
            margin: 100px auto 40px;
            padding: 20px;
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
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card .icon {
            font-size: 2em;
            color: #4CAF50;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 1.8em;
            font-weight: bold;
            color: #333;
            margin: 10px 0;
        }

        .stat-card .label {
            color: #666;
            font-size: 0.9em;
        }

        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .chart-title {
            font-size: 1.2em;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .popular-products {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .product-list {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .product-list th,
        .product-list td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .product-list th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .product-list tr:hover {
            background: #f8f9fa;
        }

        .section-title {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #4CAF50;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
        }

        .status-completed { background: #e8f5e9; color: #2e7d32; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-accepted { background: #cce5ff; color: #004085; }
    </style>
</head>
<body>
    <div class="analytics-container">
        <h2 class="section-title">
            <i class="fas fa-chart-line"></i>
            Baker Analytics Dashboard
        </h2>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon"><i class="fas fa-shopping-bag"></i></div>
                <div class="value"><?php echo $analytics['total_orders']; ?></div>
                <div class="label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-check-circle"></i></div>
                <div class="value"><?php echo $analytics['completed_orders']; ?></div>
                <div class="label">Completed Orders</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-rupee-sign"></i></div>
                <div class="value">₹<?php echo number_format($analytics['total_revenue'], 2); ?></div>
                <div class="label">Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-chart-bar"></i></div>
                <div class="value">₹<?php echo number_format($analytics['average_order_value'], 2); ?></div>
                <div class="label">Average Order Value</div>
            </div>
        </div>

        <div class="chart-container">
            <h3 class="chart-title">Monthly Revenue & Orders</h3>
            <canvas id="revenueChart"></canvas>
        </div>

        <div class="popular-products">
            <h3 class="section-title">
                <i class="fas fa-star"></i>
                Popular Products
            </h3>
            <table class="product-list">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Orders</th>
                        <th>Total Quantity</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($product = $popular_products_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                        <td><?php echo $product['order_count']; ?></td>
                        <td><?php echo $product['total_quantity']; ?> kg</td>
                        <td>₹<?php echo number_format($product['total_revenue'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Prepare data for the chart
        const monthlyData = <?php echo json_encode($monthly_data); ?>;
        const labels = monthlyData.map(item => {
            const [year, month] = item.month.split('-');
            return new Date(year, month - 1).toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        });
        const revenue = monthlyData.map(item => item.revenue);
        const orderCounts = monthlyData.map(item => item.order_count);

        // Create the chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: revenue,
                    backgroundColor: 'rgba(76, 175, 80, 0.2)',
                    borderColor: 'rgba(76, 175, 80, 1)',
                    borderWidth: 1,
                    yAxisID: 'y'
                }, {
                    label: 'Number of Orders',
                    data: orderCounts,
                    type: 'line',
                    borderColor: '#2196F3',
                    backgroundColor: 'transparent',
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    intersect: false,
                    mode: 'index'
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
<?php ob_end_flush(); ?> 