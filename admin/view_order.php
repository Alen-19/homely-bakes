<?php
session_start();
include '../connect.php';

// Check if admin is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] != 2) {
    header("Location: ../login.php");
    exit();
}

// Check if order ID is provided
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$order_id) {
    header("Location: manage_orders.php");
    exit();
}

// Add this at the top of the file after session_start() and include
if (isset($_POST['delete_order'])) {
    $delete_query = "DELETE FROM table_orders WHERE order_id = ? AND payment_status != 'completed'";
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: manage_orders.php?success=deleted");
        exit();
    } else {
        $error = "Failed to delete order";
    }
}

// Fetch order details with customer and baker information from table_registration
$order_query = "SELECT o.*, p.product_name, p.image_url, p.price,
                customer.first_name as customer_first_name, 
                customer.last_name as customer_last_name,
                l.email,
                baker.first_name as baker_first_name, 
                baker.last_name as baker_last_name
                FROM table_orders o
                JOIN table_product p ON o.product_id = p.product_id
                JOIN table_registration customer ON o.user_id = customer.user_id
                JOIN table_login l ON o.user_id = l.user_id
                JOIN table_registration baker ON o.baker_id = baker.user_id
                WHERE o.order_id = ?";

$stmt = mysqli_prepare($conn, $order_query);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$order) {
    header("Location: manage_orders.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order_id; ?> Details - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-dark: #388E3C;
            --secondary-color: #2196F3;
            --danger-color: #f44336;
            --warning-color: #ff9800;
            --success-color: #4CAF50;
            --text-color: #333;
            --text-light: #666;
            --bg-color: #f8f9fa;
            --card-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            background-color: var(--background-color);
            color: var(--text-color);
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin-left: 250px;
            background: white;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: margin-left .5s;
        }

        .order-header {
            background: var(--primary-color);
            color: white;
            padding: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9em;
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .order-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .section {
            background: var(--bg-color);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .section-title {
            color: var(--primary-color);
            font-size: 1.2em;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
        }

        .product-image {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: var(--card-shadow);
        }

        .info-row {
            display: flex;
            margin-bottom: 12px;
            align-items: center;
        }

        .info-label {
            font-weight: 600;
            width: 140px;
            color: var(--text-light);
        }

        .info-value {
            flex: 1;
            color: var(--text-color);
        }

        .price-info {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        .action-buttons {
            padding: 20px;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            background: var(--bg-color);
            border-top: 1px solid var(--border-color);
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-back {
            background: var(--primary-color);
            color: white;
        }

        .btn-back:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.2);
        }

        .btn-delete {
            background: var(--danger-color);
            color: white;
        }

        .btn-delete:hover {
            background: #d32f2f;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(244, 67, 54, 0.2);
        }

        @media (max-width: 768px) {
            .container {
                margin-left: 0;
                margin: 15px;
            }
            
            .order-content {
                grid-template-columns: 1fr;
                padding: 15px;
            }
            
            .section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <?php include 'admin_profile.php'; ?>
    <div class="container" style="margin-left: 250px; transition: margin-left .5s;">
        <div class="order-header">
            <h1>Order #<?php echo htmlspecialchars($order_id); ?></h1>
            <span class="order-status">
                <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
            </span>
        </div>

        <div class="order-content">
            <div class="section">
                <h2 class="section-title">Product Details</h2>
                <img src="../<?php echo htmlspecialchars($order['image_url']); ?>" 
                     alt="<?php echo htmlspecialchars($order['product_name']); ?>"
                     class="product-image"
                     onerror="this.src='../img/placeholder.jpg'">
                
                <div class="info-row">
                    <span class="info-label">Product Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($order['product_name']); ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Quantity:</span>
                    <span class="info-value"><?php echo htmlspecialchars($order['quantity']); ?> kg</span>
                </div>

                <div class="price-info">
                    <div class="info-row">
                        <span class="info-label">Price per kg:</span>
                        <span class="info-value">₹<?php echo number_format($order['price'], 2); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total Price:</span>
                        <span class="info-value">₹<?php echo number_format($order['total_price'], 2); ?></span>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">Baker Information</h2>
                <div class="info-row">
                    <span class="info-label">Baker Name:</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($order['baker_first_name'] . ' ' . $order['baker_last_name']); ?>
                    </span>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">Customer Information</h2>
                <div class="info-row">
                    <span class="info-label">Customer Name:</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($order['customer_first_name'] . ' ' . $order['customer_last_name']); ?>
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($order['email']); ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Order Date:</span>
                    <span class="info-value">
                        <?php echo date('F j, Y, g:i a', strtotime($order['order_date'])); ?>
                    </span>
                </div>

                <h3 class="section-title" style="margin-top: 20px;">Delivery Details</h3>
                <div class="info-row">
                    <span class="info-label">Address:</span>
                    <span class="info-value">
                        <?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="action-buttons">
            <?php if ($order['payment_status'] !== 'completed'): ?>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="delete_order" class="btn btn-delete" 
                            onclick="return confirm('Are you sure you want to delete this order? This action cannot be undone.');">
                        <i class="fas fa-trash"></i> Delete Order
                    </button>
                </form>
            <?php endif; ?>
            <button onclick="window.location.href='manage_orders.php'" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </button>
        </div>
    </div>
</body>
</html> 