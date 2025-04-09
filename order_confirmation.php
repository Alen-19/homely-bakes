<?php
session_start();
include('connect.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Get order details
$query = "SELECT o.*, p.product_name, p.image_url, r.first_name as baker_name, o.payment_status 
          FROM table_orders o 
          JOIN table_product p ON o.product_id = p.product_id 
          JOIN table_registration r ON o.baker_id = r.user_id 
          WHERE o.order_id = ? AND o.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

// If order not found or doesn't belong to user
if (!$order) {
    header("Location: orders.php");
    exit();
}

// Set page title for header
$pageTitle = "Order Confirmation - Homely Bakes";

// Include the header after all potential redirects
include('header.php');
?>

<!-- Add your CSS in the head section or in a separate file -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    .order-container {
        max-width: 800px;
        margin: 150px auto 40px;
        padding: 20px;
        text-align: center;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .check-container {
        width: 80px;
        height: 80px;
        background: #4CAF50;
        border-radius: 50%;
        margin: -60px auto 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .check-container.rejected {
        background: #dc3545;
    }

    .check-container i {
        font-size: 40px;
        color: white;
    }

    h2 {
        color: #333;
        margin-bottom: 10px;
    }

    .order-id {
        color: #666;
        margin-bottom: 30px;
    }

    .status-banner {
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .status-banner.pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-banner.rejected {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .status-banner.accepted {
        background: #c6efce;
        color: #2e865f;
    }

    .status-banner.preparing {
        background: #f7d2c4;
        color: #e2893d;
    }

    .status-banner.prepared {
        background: #d4edda;
        color: #155724;
    }

    .order-progress {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 40px 0;
        position: relative;
    }

    .progress-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        z-index: 1;
        flex: 1;
    }

    .step-icon {
        width: 40px;
        height: 40px;
        background: #ddd;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #666;
    }

    .progress-step.completed .step-icon {
        background: #4CAF50;
        color: white;
    }

    .progress-step.rejected .step-icon {
        background: #dc3545;
        color: white;
    }

    .progress-line {
        height: 2px;
        background: #ddd;
        flex: 1;
        margin: 0 10px;
    }

    .progress-line.completed {
        background: #4CAF50;
    }

    .progress-line.rejected {
        background: #dc3545;
    }

    .what-next {
        background: #e8f5e9;
        padding: 20px;
        border-radius: 4px;
        margin: 30px 0;
        text-align: left;
    }

    .what-next.rejected {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
    }

    .what-next h3 {
        color: #2e7d32;
        margin-bottom: 15px;
    }

    .what-next.rejected h3 {
        color: #721c24;
    }

    .what-next ul {
        margin: 0;
        padding-left: 20px;
    }

    .what-next li {
        margin-bottom: 10px;
        color: #333;
    }

    .what-next.rejected li {
        color: #721c24;
    }

    .action-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-top: 20px;
    }

    .btn {
        padding: 10px 20px;
        border-radius: 4px;
        text-decoration: none;
        font-weight: bold;
        transition: all 0.3s ease;
    }

    .primary-btn {
        background: #4CAF50;
        color: white;
    }

    .primary-btn:hover {
        background: #45a049;
    }

    .secondary-btn {
        background: #6c757d;
        color: white;
    }

    .secondary-btn:hover {
        background: #5a6268;
    }

    .order-details {
        margin-top: 40px;
        text-align: left;
    }

    .detail-section {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 25px;
        margin-bottom: 25px;
    }

    .section-title {
        color: #333;
        font-size: 1.25rem;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .section-title i {
        color: #4CAF50;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding: 8px 0;
    }

    .detail-label {
        color: #666;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .detail-label i {
        color: #4CAF50;
        font-size: 0.9em;
    }

    .detail-value {
        color: #333;
        font-weight: 500;
    }

    .delivery-address {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 6px;
        margin: 15px 0;
        line-height: 1.6;
    }
    
    .price-breakdown {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-top: 20px;
    }

    .price-breakdown .detail-row {
        margin-bottom: 10px;
        padding: 5px 0;
    }

    .price-breakdown .total-price {
        border-top: 2px solid #e0e0e0;
        margin-top: 15px;
        padding-top: 15px;
    }

    .total-price .detail-label,
    .total-price .detail-value {
        color: #4CAF50;
        font-size: 1.2em;
        font-weight: bold;
    }

    .action-buttons {
        margin-top: 30px;
        display: flex;
        gap: 15px;
        justify-content: center;
    }
    
    .action-btn {
        padding: 12px 24px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .view-orders-btn {
        background-color: #4CAF50;
        color: white;
    }
    
    .view-orders-btn:hover {
        background-color: #45a049;
        transform: translateY(-2px);
    }
    
    .continue-shopping-btn {
        background-color: #6c757d;
        color: white;
    }
    
    .continue-shopping-btn:hover {
        background-color: #5a6268;
        transform: translateY(-2px);
    }
    
    .download-pdf-btn {
        background-color: #4CAF50;
        color: white;
        padding: 10px 20px;
        border-radius: 4px;
        text-decoration: none;
        font-weight: bold;
        transition: all 0.3s ease;
        margin-bottom: 20px;
        display: block;
    }
    
    .download-pdf-btn:hover {
        background-color: #45a049;
    }

    /* Chat styles */
    .chat-section {
        margin-top: 30px;
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .chat-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    .chat-messages {
        height: 300px;
        overflow-y: auto;
        padding: 15px;
        background: #f9f9f9;
        border-radius: 8px;
        margin-bottom: 15px;
    }

    .message {
        margin-bottom: 10px;
        max-width: 80%;
    }

    .message.sent {
        margin-left: auto;
        background: #4CAF50;
        color: white;
        padding: 8px 12px;
        border-radius: 15px 15px 0 15px;
    }

    .message.received {
        margin-right: auto;
        background: #f0f0f0;
        padding: 8px 12px;
        border-radius: 15px 15px 15px 0;
    }

    .chat-input {
        display: flex;
        gap: 10px;
    }

    .chat-input input {
        flex-grow: 1;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 20px;
        outline: none;
    }

    .chat-input button {
        padding: 10px 20px;
        background: #4CAF50;
        color: white;
        border: none;
        border-radius: 20px;
        cursor: pointer;
    }

    .chat-input button:hover {
        background: #45a049;
    }
</style>

<div class="order-container">
    <div class="check-container <?php echo $order['status']; ?>">
        <?php if ($order['status'] === 'rejected'): ?>
            <i class="fas fa-times-circle"></i>
        <?php else: ?>
            <i class="fas fa-check-circle"></i>
        <?php endif; ?>
    </div>

    <?php if ($order['status'] === 'rejected'): ?>
        <h2>Order Rejected</h2>
    <?php else: ?>
        <h2>Order Placed Successfully!</h2>
    <?php endif; ?>
    <p class="order-id">Order ID: #<?php echo $order_id; ?></p>

    <?php if ($order['status'] === 'rejected'): ?>
        <div class="status-banner rejected">
            <i class="fas fa-times-circle"></i> Order has been rejected by the baker
        </div>
    <?php else: ?>
        <div class="status-banner <?php echo $order['status']; ?>">
            <?php if ($order['status'] === 'delivered'): ?>
                <i class="fas fa-check-circle"></i> Order Successfully Delivered
            <?php elseif ($order['status'] === 'accepted' && $order['payment_status'] !== 'completed'): ?>
                <i class="fas fa-check-circle"></i> Order Accepted - Awaiting Payment
            <?php elseif ($order['status'] === 'accepted' && $order['payment_status'] === 'completed'): ?>
                <i class="fas fa-check-circle"></i> Payment Completed - Order Processing
            <?php elseif ($order['status'] === 'preparing'): ?>
                <i class="fas fa-bread-slice"></i> Order is Being Prepared
            <?php elseif ($order['status'] === 'prepared'): ?>
                <i class="fas fa-check-circle"></i> Order is Prepared - Ready for Delivery
            <?php elseif ($order['status'] === 'out_for_delivery'): ?>
                <i class="fas fa-shipping-fast"></i> Out for Delivery
            <?php elseif ($order['status'] === 'completed'): ?>
                <i class="fas fa-check-circle"></i> Order Completed and Delivered
            <?php else: ?>
                <i class="fas fa-clock"></i> Awaiting Baker's Confirmation
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="order-progress">
        <?php
        $steps = [
            'placed' => ['icon' => 'shopping-cart', 'label' => 'Order Placed'],
            'confirmation' => ['icon' => 'user-check', 'label' => 'Baker Confirmation'],
            'payment' => ['icon' => 'credit-card', 'label' => 'Payment'],
            'preparation' => ['icon' => 'bread-slice', 'label' => 'Preparing'],
            'prepared' => ['icon' => 'check-circle', 'label' => 'Prepared'],
            'out_delivery' => ['icon' => 'shipping-fast', 'label' => 'Out for Delivery'],
            'delivery' => ['icon' => 'truck', 'label' => 'Delivered']
        ];
        
        $currentStep = 0;
        if ($order['status'] === 'rejected') {
            $currentStep = 1;
        } elseif ($order['status'] === 'accepted' && $order['payment_status'] !== 'completed') {
            $currentStep = 2; // Order accepted but payment pending
        } elseif ($order['status'] === 'accepted' && $order['payment_status'] === 'completed') {
            $currentStep = 3; // Payment completed
        } elseif ($order['status'] === 'preparing') {
            $currentStep = 4;
        } elseif ($order['status'] === 'prepared') {
            $currentStep = 5;
        } elseif ($order['status'] === 'out_for_delivery') {
            $currentStep = 6;
        } elseif ($order['status'] === 'delivered') {
            $currentStep = 7; // Set to maximum steps to mark all as completed
        } else {
            $currentStep = 1;
        }
        
        $stepCount = 1;
        foreach ($steps as $step => $info):
            $stepClass = '';
            if ($order['status'] === 'rejected' && $stepCount === 2) {
                $stepClass = 'rejected';
            } elseif ($order['status'] === 'delivered') {
                // Mark all steps as completed when order is delivered
                $stepClass = 'completed';
            } elseif ($stepCount <= $currentStep) {
                $stepClass = 'completed';
            }
        ?>
            <div class="progress-step <?php echo $stepClass; ?>">
                <div class="step-icon">
                    <i class="fas fa-<?php echo $info['icon']; ?>"></i>
                </div>
                <div class="step-label"><?php echo $info['label']; ?></div>
            </div>
            <?php if ($stepCount < count($steps)): ?>
                <div class="progress-line <?php 
                    if ($order['status'] === 'delivered') {
                        echo 'completed';
                    } else {
                        echo $stepClass;
                    }
                ?>"></div>
            <?php endif; ?>
        <?php 
            $stepCount++;
        endforeach; 
        ?>
    </div>

    <?php if ($order['status'] === 'rejected'): ?>
        <div class="what-next rejected">
            <h3>What happens next?</h3>
            <ul>
                <li>The baker was unable to accept your order at this time</li>
                <li>You can try placing a new order with a different baker</li>
                <li>Or browse other similar products</li>
            </ul>
            <div class="action-buttons">
                <a href="product.php" class="btn primary-btn">Browse Products</a>
                <a href="orders.php" class="btn secondary-btn">View All Orders</a>
            </div>
        </div>
    <?php else: ?>
        <div class="what-next">
            <h3>What happens next?</h3>
            <ul>
                <?php if ($order['status'] === 'delivered'): ?>
                    <li>Your order has been successfully delivered!</li>
                    <li>We hope you enjoyed your delicious baked goods</li>
                    <li>Please consider leaving a review for the baker</li>
                    <li>Feel free to place another order anytime</li>
                    <div class="action-buttons">
                        <a href="review.php?order_id=<?php echo $order_id; ?>" class="btn primary-btn">
                            <i class="fas fa-star"></i> Leave a Review
                        </a>
                        <a href="product.php" class="btn secondary-btn">
                            <i class="fas fa-shopping-cart"></i> Place New Order
                        </a>
                    </div>
                <?php elseif ($order['status'] === 'pending'): ?>
                    <li>The baker will review your order details</li>
                    <li>Once accepted, you'll receive a confirmation notification</li>
                    <li>You'll need to complete the payment</li>
                    <li>The baker will start preparing your order</li>
                <?php elseif ($order['status'] === 'accepted' && $order['payment_status'] !== 'completed'): ?>
                    <li>Please complete the payment for your order</li>
                    <li>Once payment is confirmed, the baker will start preparation</li>
                    <li>You'll receive updates about your order status</li>
                    <div class="action-buttons">
                        <button onclick="makePayment(<?php echo $order['order_id']; ?>, <?php echo $order['total_price']; ?>, '<?php echo addslashes($order['product_name']); ?>')" class="btn primary-btn">Proceed to Payment</button>
                        <a href="orders.php" class="btn secondary-btn">View All Orders</a>
                    </div>
                <?php elseif ($order['status'] === 'accepted' && $order['payment_status'] === 'completed'): ?>
                    <li>Your payment has been confirmed</li>
                    <li>The baker has been notified to start preparation</li>
                    <li>You'll receive updates about your order status</li>
                <?php elseif ($order['status'] === 'preparing'): ?>
                    <li>Your payment has been confirmed</li>
                    <li>The baker has started preparing your order</li>
                    <li>You'll receive updates about the preparation progress</li>
                    <li>We'll notify you when the order is ready for delivery</li>
                <?php elseif ($order['status'] === 'prepared'): ?>
                    <li>Your order has been prepared</li>
                    <li>The baker has completed making your order</li>
                    <li>Your order is ready for delivery</li>
                    <li>Our delivery partner will pick it up soon</li>
                <?php elseif ($order['status'] === 'out_for_delivery'): ?>
                    <li>Your order is being prepared for delivery</li>
                    <li>Our delivery partner is on their way</li>
                    <li>You'll receive updates about the delivery progress</li>
                <?php elseif ($order['status'] === 'completed'): ?>
                    <li>Your order has been delivered successfully</li>
                    <li>Thank you for shopping with us!</li>
                    <li>Feel free to place another order</li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="order-details">
        <div class="detail-section">
            <?php if ($order['payment_status'] === 'completed'): ?>
                <a href="generate_order_pdf.php?order_id=<?php echo $order_id; ?>" class="download-pdf-btn">
                    <i class="fas fa-file-pdf"></i> Download Order Details
                </a>
            <?php endif; ?>
            <h3 class="section-title">
                <i class="fas fa-box"></i>
                Product Details
            </h3>
            <div class="detail-row">
                <span class="detail-label">
                    <i class="fas fa-tag"></i>
                    Product Name
                </span>
                <span class="detail-value"><?php echo htmlspecialchars($order['product_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">
                    <i class="fas fa-user"></i>
                    Baker
                </span>
                <span class="detail-value"><?php echo htmlspecialchars($order['baker_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">
                    <i class="fas fa-weight"></i>
                    Quantity
                </span>
                <span class="detail-value"><?php echo $order['quantity']; ?> kg</span>
            </div>
        </div>

        <div class="detail-section">
            <h3 class="section-title">
                <i class="fas fa-truck"></i>
                Delivery Details
            </h3>
            <div class="delivery-address">
                <i class="fas fa-map-marker-alt"></i>
                <?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?>
            </div>
            <div class="detail-row">
                <span class="detail-label">
                    <i class="fas fa-route"></i>
                    Estimated Distance
                </span>
                <span class="detail-value"><?php echo number_format($order['distance'], 2); ?> km</span>
            </div>
            <?php if ($order['special_instructions']): ?>
            <div class="detail-row">
                <span class="detail-label">
                    <i class="fas fa-info-circle"></i>
                    Special Instructions
                </span>
                <span class="detail-value"><?php echo nl2br(htmlspecialchars($order['special_instructions'])); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="detail-section">
            <h3 class="section-title">
                <i class="fas fa-file-invoice-dollar"></i>
                Price Details
            </h3>
            <div class="price-breakdown">
                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fas fa-shopping-cart"></i>
                        Product Price
                    </span>
                    <span class="detail-value">Rs.<?php echo number_format($order['total_price'] - $order['delivery_charge'], 2); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fas fa-truck"></i>
                        Delivery Charge
                    </span>
                    <span class="detail-value">Rs.<?php echo number_format($order['delivery_charge'], 2); ?></span>
                </div>
                <div class="total-price detail-row">
                    <span class="detail-label">
                        <i class="fas fa-coins"></i>
                        Total Amount
                    </span>
                    <span class="detail-value">Rs.<?php echo number_format($order['total_price'], 2); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="action-buttons">
        <a href="orders.php" class="action-btn view-orders-btn">
            <i class="fas fa-shopping-bag"></i>
            View My Orders
        </a>
        <a href="product.php" class="action-btn continue-shopping-btn">
            <i class="fas fa-arrow-right"></i>
            Continue Shopping
        </a>
    </div>

    <!-- Chat Section -->
    <div class="chat-section">
        <div class="chat-header">
            <h3>Chat with Baker</h3>
        </div>
        <div class="chat-messages" id="chatMessages">
            <!-- Messages will be loaded here -->
        </div>
        <div class="chat-input">
            <input type="text" id="messageInput" placeholder="Type your message...">
            <button onclick="sendMessage()">Send</button>
        </div>
    </div>
</div>

<script>
// First, add these variables at the top of your script section, right after the opening <script> tag
const orderId = <?php echo $order_id; ?>;
const orderAmount = <?php echo $order['total_price']; ?>;
const orderName = '<?php echo addslashes($order['product_name']); ?>';

function makePayment(orderId, amount, productName) {
    fetch('create_razorpay_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_id: orderId,
            amount: amount
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            throw new Error(data.error || 'Failed to create order');
        }
        var options = {
            "key": "rzp_test_ap7WDK9hfctDgW",
            "amount": amount * 100, // Convert to paise
            "currency": "INR",
            "name": "Homely Bakes",
            "description": "Payment for " + productName,
            "order_id": data.razorpay_order_id,
            "handler": function (response) {
                // Handle the success payment
                verifyPayment(response, orderId);
            },
            "prefill": {
                "name": "<?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : ''; ?>",
                "email": "<?php echo isset($_SESSION['email']) ? $_SESSION['email'] : ''; ?>"
            },
            "theme": {
                "color": "#2196F3"
            },
            "modal": {
                "ondismiss": function() {
                    console.log("Payment modal closed");
                }
            }
        };
        var rzp1 = new Razorpay(options);
        rzp1.on('payment.failed', function (response) {
            console.error('Payment failed:', response.error);
            Swal.fire({
                title: 'Payment Failed',
                text: response.error.description,
                icon: 'error',
                confirmButtonText: 'OK'
            });
        });
        rzp1.open();
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            title: 'Error',
            text: 'Error creating payment: ' + error.message,
            icon: 'error',
            confirmButtonText: 'OK'
        });
    });
}

function verifyPayment(response, orderId) {
    const paymentData = {
        razorpay_payment_id: response.razorpay_payment_id,
        razorpay_order_id: response.razorpay_order_id,
        razorpay_signature: response.razorpay_signature,
        order_id: orderId
    };

    Swal.fire({
        title: 'Processing Payment',
        text: 'Please wait...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch('verify_payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(paymentData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Success!',
                text: 'Payment processed successfully',
                icon: 'success',
                confirmButtonText: 'OK'
            });
            updateOrderStatus(); // Update the order status immediately
        } else {
            throw new Error(data.error || 'Payment verification failed');
        }
    })
    .catch(error => {
        Swal.fire({
            title: 'Error!',
            text: error.message || 'Payment verification failed',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    });
}

function updateOrderStatus() {
    fetch(`get_order_status.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const order = data.order;
                
                // Update status banner
                updateStatusBanner(order.status, order.payment_status);
                
                // Update progress steps
                updateProgressSteps(order.status, order.payment_status);
                
                // Update what's next section
                updateWhatsNext(order.status, order.payment_status);
                
                // Continue polling if order is not in final state
                if (!['delivered', 'rejected', 'completed'].includes(order.status)) {
                    setTimeout(updateOrderStatus, 10000); // Poll every 10 seconds
                }
            }
        })
        .catch(error => console.error('Error updating order status:', error));
}

function updateStatusBanner(status, paymentStatus) {
    const statusBanner = document.querySelector('.status-banner');
    let bannerContent = '';
    
    switch(status) {
        case 'delivered':
            bannerContent = '<i class="fas fa-check-circle"></i> Order Successfully Delivered';
            break;
        case 'accepted':
            bannerContent = paymentStatus !== 'completed' ? 
                '<i class="fas fa-check-circle"></i> Order Accepted - Awaiting Payment' :
                '<i class="fas fa-check-circle"></i> Payment Completed - Order Processing';
            break;
        case 'preparing':
            bannerContent = '<i class="fas fa-bread-slice"></i> Order is Being Prepared';
            break;
        case 'prepared':
            bannerContent = '<i class="fas fa-check-circle"></i> Order is Prepared - Ready for Delivery';
            break;
        case 'out_for_delivery':
            bannerContent = '<i class="fas fa-shipping-fast"></i> Out for Delivery';
            break;
        case 'completed':
            bannerContent = '<i class="fas fa-check-circle"></i> Order Completed and Delivered';
            break;
        default:
            bannerContent = '<i class="fas fa-clock"></i> Awaiting Baker\'s Confirmation';
    }
    
    statusBanner.innerHTML = bannerContent;
    statusBanner.className = `status-banner ${status}`;
}

function updateProgressSteps(status, paymentStatus) {
    const steps = document.querySelectorAll('.progress-step');
    const progressLines = document.querySelectorAll('.progress-line');
    
    let currentStep = 0;
    
    switch(status) {
        case 'rejected':
            currentStep = 1;
            break;
        case 'accepted':
            currentStep = paymentStatus !== 'completed' ? 2 : 3;
            break;
        case 'preparing':
            currentStep = 4;
            break;
        case 'prepared':
            currentStep = 5;
            break;
        case 'out_for_delivery':
            currentStep = 6;
            break;
        case 'delivered':
        case 'completed':
            currentStep = 7;
            break;
        default:
            currentStep = 1;
    }
    
    steps.forEach((step, index) => {
        if (status === 'rejected' && index === 1) {
            step.className = 'progress-step rejected';
        } else if (status === 'delivered') {
            step.className = 'progress-step completed';
        } else if (index < currentStep) {
            step.className = 'progress-step completed';
        } else {
            step.className = 'progress-step';
        }
    });
    
    progressLines.forEach((line, index) => {
        if (status === 'delivered') {
            line.className = 'progress-line completed';
        } else if (index < currentStep - 1) {
            line.className = 'progress-line completed';
        } else {
            line.className = 'progress-line';
        }
    });
}

function updateWhatsNext(status, paymentStatus) {
    const whatNext = document.querySelector('.what-next');
    let content = '<h3>What happens next?</h3><ul>';
    
    switch(status) {
        case 'delivered':
            content += `
                <li>Your order has been successfully delivered!</li>
                <li>We hope you enjoyed your delicious baked goods</li>
                <li>Please consider leaving a review for the baker</li>
                <li>Feel free to place another order anytime</li>
                </ul>
                <div class="action-buttons">
                    <a href="review.php?order_id=${orderId}" class="btn primary-btn">
                        <i class="fas fa-star"></i> Leave a Review
                    </a>
                    <a href="product.php" class="btn secondary-btn">
                        <i class="fas fa-shopping-cart"></i> Place New Order
                    </a>
                </div>`;
            break;

        case 'pending':
            content += `
                <li>The baker will review your order details</li>
                <li>Once accepted, you'll receive a confirmation notification</li>
                <li>You'll need to complete the payment</li>
                <li>The baker will start preparing your order</li>
                </ul>`;
            break;

        case 'accepted':
            if (paymentStatus !== 'completed') {
                content += `
                    <li>Please complete the payment for your order</li>
                    <li>Once payment is confirmed, the baker will start preparation</li>
                    <li>You'll receive updates about your order status</li>
                    </ul>
                    <div class="action-buttons">
                        <button onclick="makePayment(${orderId}, ${orderAmount}, '${orderName}')" class="btn primary-btn">
                            <i class="fas fa-credit-card"></i> Proceed to Payment
                        </button>
                        <a href="orders.php" class="btn secondary-btn">View All Orders</a>
                    </div>`;
            } else {
                content += `
                    <li>Your payment has been confirmed</li>
                    <li>The baker has been notified to start preparation</li>
                    <li>You'll receive updates about your order status</li>
                    </ul>`;
            }
            break;

        case 'preparing':
            content += `
                <li>Your payment has been confirmed</li>
                <li>The baker has started preparing your order</li>
                <li>You'll receive updates about the preparation progress</li>
                <li>We'll notify you when the order is ready for delivery</li>
                </ul>`;
            break;

        case 'prepared':
            content += `
                <li>Your order has been prepared</li>
                <li>The baker has completed making your order</li>
                <li>Your order is ready for delivery</li>
                <li>Our delivery partner will pick it up soon</li>
                </ul>`;
            break;

        case 'out_for_delivery':
            content += `
                <li>Your order is being prepared for delivery</li>
                <li>Our delivery partner is on their way</li>
                <li>You'll receive updates about the delivery progress</li>
                </ul>`;
            break;

        case 'completed':
            content += `
                <li>Your order has been delivered successfully</li>
                <li>Thank you for shopping with us!</li>
                <li>Feel free to place another order</li>
                </ul>`;
            break;

        case 'rejected':
            whatNext.classList.add('rejected');
            content += `
                <li>The baker was unable to accept your order at this time</li>
                <li>You can try placing a new order with a different baker</li>
                <li>Or browse other similar products</li>
                </ul>
                <div class="action-buttons">
                    <a href="product.php" class="btn primary-btn">Browse Products</a>
                    <a href="orders.php" class="btn secondary-btn">View All Orders</a>
                </div>`;
            break;
    }

    whatNext.innerHTML = content;
    whatNext.className = `what-next${status === 'rejected' ? ' rejected' : ''}`;
}

// Start the polling when the page loads
document.addEventListener('DOMContentLoaded', function() {
    updateOrderStatus();
});

// Add this after your existing scripts
let chatInterval = null;

function loadMessages() {
    fetch(`chat_api.php?order_id=<?php echo $order_id; ?>`)
        .then(response => response.json())
        .then(data => {
            if (data.messages) {
                const chatMessages = document.getElementById('chatMessages');
                chatMessages.innerHTML = '';
                
                data.messages.forEach(message => {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = `message ${message.sender_id === <?php echo $user_id; ?> ? 'sent' : 'received'}`;
                    messageDiv.textContent = message.message;
                    chatMessages.appendChild(messageDiv);
                });
                
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        })
        .catch(error => console.error('Error loading messages:', error));
}

function sendMessage() {
    const messageInput = document.getElementById('messageInput');
    const message = messageInput.value.trim();
    
    if (!message) return;
    
    fetch('chat_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            order_id: <?php echo $order_id; ?>,
            receiver_id: <?php echo $order['baker_id']; ?>,
            message: message
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageInput.value = '';
            loadMessages();
        }
    })
    .catch(error => console.error('Error sending message:', error));
}

// Add event listener for Enter key in message input
document.getElementById('messageInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        sendMessage();
    }
});

// Load messages immediately and start polling
loadMessages();
chatInterval = setInterval(loadMessages, 5000);

// Clean up interval when leaving the page
window.addEventListener('beforeunload', function() {
    if (chatInterval) {
        clearInterval(chatInterval);
    }
});
</script>

