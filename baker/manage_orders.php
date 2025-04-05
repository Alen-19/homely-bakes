<?php
session_start();
include '../connect.php';

if (!isset($_SESSION['baker_id'])) {
    header("Location: login.php");
    exit();
}

$baker_id = $_SESSION['baker_id'];

// Handle order status updates
if (isset($_POST['order_id']) && isset($_POST['action'])) {
    $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
    $action = mysqli_real_escape_string($conn, $_POST['action']);
    
    if ($action === 'accept' || $action === 'reject') {
        $status = $action === 'accept' ? 'accepted' : 'rejected';
        $update_query = "UPDATE table_orders SET status = '$status' WHERE order_id = '$order_id'";
        mysqli_query($conn, $update_query);
    }
}

// Get orders for baker's products
$query = "SELECT o.*, p.product_name, p.image_url, u.name as customer_name 
          FROM table_orders o 
          JOIN table_product p ON o.product_id = p.product_id 
          JOIN users u ON o.user_id = u.user_id
          WHERE p.baker_id = '$baker_id' 
          ORDER BY o.created_at DESC";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Baker Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .orders-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 1rem;
        }
        .order-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            padding: 1rem;
        }
        .order-header {
            display: grid;
            grid-template-columns: 100px 1fr auto;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1rem;
        }
        .order-image img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
        }
        .order-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-accept {
            background: #28a745;
            color: white;
        }
        .btn-reject {
            background: #dc3545;
            color: white;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            text-align: center;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-accepted {
            background: #d4edda;
            color: #155724;
        }
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <?php include "baker_header.php"; ?>

    <div class="orders-container">
        <h2>Manage Orders</h2>

        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($order = mysqli_fetch_assoc($result)): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-image">
                            <img src="<?php echo htmlspecialchars($order['image_url'] ?: '../assets/images/default-product.jpg'); ?>" 
                                 alt="<?php echo htmlspecialchars($order['product_name']); ?>">
                        </div>
                        <div class="order-details">
                            <h3><?php echo htmlspecialchars($order['product_name']); ?></h3>
                            <p>Customer: <?php echo htmlspecialchars($order['customer_name']); ?></p>
                            <p>Size: <?php echo $order['size_kg']; ?>kg</p>
                            <p>Total: ₹<?php echo number_format($order['total_price'], 2); ?></p>
                            <p>Delivery to: <?php echo htmlspecialchars($order['delivery_address']); ?>, 
                               <?php echo htmlspecialchars($order['city']); ?>, 
                               <?php echo htmlspecialchars($order['state']); ?>, 
                               <?php echo htmlspecialchars($order['country']); ?></p>
                            <p>Ordered on: <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
                        </div>
                        <?php if ($order['status'] === 'pending'): ?>
                            <div class="order-actions">
                                <form method="POST">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <button type="submit" name="action" value="accept" class="btn btn-accept">Accept</button>
                                    <button type="submit" name="action" value="reject" class="btn btn-reject">Reject</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No orders found.</p>
        <?php endif; ?>
    </div>

    <script>
        // Add confirmation before rejecting orders
        document.querySelectorAll('.btn-reject').forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to reject this order?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html><?php
mysqli_close($conn);
?>