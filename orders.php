<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

session_start();
include('connect.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Updated query to include payment status and review information
$query = "SELECT o.*, p.product_name, p.image_url, 
          r.first_name as baker_name, r.mobile_number as baker_phone,
          COALESCE(pay.payment_status, 'pending') as payment_status,
          pay.razorpay_order_id,
          rev.rating, rev.comment, rev.created_at
          FROM table_orders o
          JOIN table_product p ON o.product_id = p.product_id
          JOIN table_registration r ON o.baker_id = r.user_id
          LEFT JOIN table_payments pay ON o.order_id = pay.order_id
          LEFT JOIN table_reviews rev ON o.order_id = rev.order_id
          WHERE o.user_id = ? ";

if (isset($_GET['filter']) && $_GET['filter'] === 'paid') {
    $query .= "AND (pay.payment_status = 'completed' OR o.status = 'preparing') ";
}

$query .= "ORDER BY o.order_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

// Add this near the top of the file, after session_start()
$review_success = isset($_GET['review_success']) && $_GET['review_success'] === 'true';
$already_reviewed = isset($_GET['already_reviewed']) && $_GET['already_reviewed'] === 'true';
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Orders - Homely Bakes</title>
    <style>
        .orders-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }

        .order-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px;
            transition: transform 0.3s ease;
            width: 100%;
            border: 1px solid #e0e0e0;
            margin-bottom: 0;
        }

        .order-card.rejected {
            border-left: 4px solid #dc3545;
            background: #fff8f8;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #4CAF50;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .order-id {
            font-size: 1em;
        }

        .order-date {
            color: #666;
        }

        .order-details {
            display: grid;
            grid-template-columns: 100px 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
        }

        .product-info {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .product-info h3 {
            font-size: 1.1em;
            margin: 0 0 8px 0;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: capitalize;
        }

        .status-badge i {
            font-size: 14px;
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
            border: 1px solid #f5c6cb;
            padding: 8px 15px;
            font-size: 0.95em;
        }

        .status-completed {
            background: #cce5ff;
            color: #004085;
        }

        .status-preparing {
            background: #e3f2fd;
            color: #1565c0;
        }

        .baker-info {
            margin-top: 10px;
            padding-top: 10px;
            gap: 10px;
        }

        .baker-details {
            font-size: 0.9em;
        }

        .baker-contact {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .baker-contact i {
            color: #4CAF50;
        }

        .price-info {
            font-size: 0.95em;
        }

        .delivery-info {
            font-size: 0.9em;
            margin-top: 8px;
        }

        .no-orders {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .order-actions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
        }

        .view-details-btn {
            background-color: #4CAF50;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .view-details-btn:hover {
            background-color: #45a049;
            transform: translateY(-2px);
        }

        .view-details-btn i {
            font-size: 16px;
        }

        .rejection-info {
            margin-top: 15px;
            padding: 12px;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            color: #721c24;
        }

        /* Make the title and filter buttons span full width */
        h2, .filter-buttons {
            grid-column: 1 / -1;
        }

        /* Adjust the no-orders message to span full width */
        .no-orders {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            color: #666;
        }

        /* Add responsive design for smaller screens */
        @media (max-width: 768px) {
            .orders-container {
                grid-template-columns: 1fr;
            }
        }

        .review-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .review-header h4 {
            margin: 0;
            color: #333;
            font-size: 1em;
        }

        .review-date {
            color: #666;
            font-size: 0.9em;
        }

        .rating-stars {
            margin-bottom: 10px;
        }

        .rating-stars .fa-star {
            color: #ddd;
            margin-right: 2px;
        }

        .rating-stars .fa-star.active {
            color: #ffd700;
        }

        .review-text {
            color: #555;
            font-style: italic;
            line-height: 1.4;
            margin-top: 8px;
            font-size: 0.95em;
        }

        .review-prompt {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            text-align: center;
        }

        .review-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9em;
            transition: all 0.3s ease;
        }

        .review-btn:hover {
            background-color: #45a049;
            transform: translateY(-2px);
        }

        .review-btn i {
            font-size: 14px;
        }
    </style>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>
    <?php include('header.php'); ?>

    <!-- Add this where you want to show the success message -->
    <?php if ($review_success): ?>
        <div class="alert alert-success">
            Thank you for your review! Your feedback helps us improve our service.
        </div>
    <?php endif; ?>

    <?php if ($already_reviewed): ?>
        <div class="alert alert-info">
            You have already submitted a review for this order.
        </div>
    <?php endif; ?>

    <div class="orders-container">
        <h2>My Orders</h2>
        <div class="filter-buttons" style="margin-bottom: 20px;">
            <button class="action-btn" onclick="filterOrders('all')" id="all-btn">All Orders</button>
            <button class="action-btn" onclick="filterOrders('paid')" id="paid-btn">Paid Orders</button>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($order = $result->fetch_assoc()): ?>
                <div class="order-card <?php echo strtolower($order['status']); ?>">
                    <div class="order-header">
                        <span class="order-id">Order #<?php echo $order['order_id']; ?></span>
                        <span class="order-date">
                            <i class="far fa-calendar-alt"></i>
                            <?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?>
                        </span>
                    </div>

                    <div class="order-details">
                        <img src="<?php echo htmlspecialchars($order['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($order['product_name']); ?>"
                             class="product-image">
                        
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($order['product_name']); ?></h3>
                            <div class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                <?php
                                $statusIcon = [
                                    'pending' => 'clock',
                                    'accepted' => 'check-circle',
                                    'rejected' => 'times-circle',
                                    'completed' => 'check-double',
                                    'preparing' => 'cookie'
                                ];
                                $status = strtolower($order['status']);
                                $icon = $statusIcon[$status] ?? 'info-circle';
                                ?>
                                <i class="fas fa-<?php echo $icon; ?>"></i>
                                <?php echo ucfirst($order['status']); ?>
                            </div>
                            <div class="price-info">
                                <div><i class="fas fa-weight"></i> Quantity: <?php echo $order['quantity']; ?> kg</div>
                                <div><i class="fas fa-rupee-sign"></i> Total Price: ₹<?php echo number_format($order['total_price'], 2); ?></div>
                            </div>
                            <div class="delivery-info">
                                <strong><i class="fas fa-map-marker-alt"></i> Delivery Address:</strong><br>
                                <?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?>
                            </div>
                            <?php if ($order['special_instructions']): ?>
                                <div class="special-instructions">
                                    <strong><i class="fas fa-info-circle"></i> Special Instructions:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($order['special_instructions'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="baker-info">
                        <div class="baker-details">
                            <strong><i class="fas fa-user"></i> Baker Details:</strong><br>
                            <div class="baker-contact">
                                <i class="fas fa-user-circle"></i>
                                <?php echo htmlspecialchars($order['baker_name']); ?>
                            </div>
                            <div class="baker-contact">
                                <i class="fas fa-phone"></i>
                                <?php echo htmlspecialchars($order['baker_phone']); ?>
                            </div>
                        </div>
                        <div class="order-actions">
                            <?php if ($order['payment_status'] === 'completed' || $order['status'] === 'preparing'): ?>
                                <span class="status-badge status-completed">
                                    <i class="fas fa-check-circle"></i> Paid
                                </span>
                                <?php if ($order['status'] === 'preparing'): ?>
                                    <span class="status-badge" style="background: #e3f2fd; color: #1565c0;">
                                        <i class="fas fa-cookie"></i> Preparing
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a href="order_confirmation.php?order_id=<?php echo $order['order_id']; ?>" class="action-btn view-details-btn">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>

                    <?php if ($order['rating']): ?>
                        <div class="review-section">
                            <div class="review-header">
                                <h4>Your Review</h4>
                                <div class="review-date">
                                    <i class="far fa-clock"></i>
                                    <?php echo date('d M Y', strtotime($order['created_at'])); ?>
                                </div>
                            </div>
                            <div class="rating-stars">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $order['rating'] ? 'active' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <?php if ($order['comment']): ?>
                                <div class="review-text">
                                    "<?php echo htmlspecialchars($order['comment']); ?>"
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($order['status'] === 'delivered'): ?>
                        <div class="review-prompt">
                            <a href="review.php?order_id=<?php echo $order['order_id']; ?>" class="review-btn">
                                <i class="fas fa-star"></i> Write a Review
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-orders">
                <h3>No orders found</h3>
                <p>You haven't placed any orders yet.</p>
                <a href="product.php" class="action-btn view-details-btn">Start Shopping</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function filterOrders(type) {
        const url = new URL(window.location.href);
        if (type === 'paid') {
            url.searchParams.set('filter', 'paid');
        } else {
            url.searchParams.delete('filter');
        }
        window.location.href = url.toString();
    }

    // Highlight active filter button
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const filter = urlParams.get('filter');
        
        if (filter === 'paid') {
            document.getElementById('paid-btn').style.backgroundColor = '#45a049';
        } else {
            document.getElementById('all-btn').style.backgroundColor = '#45a049';
        }
    });
    </script>

    <!-- Add SweetAlert2 for better alerts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
</body>
</html>

