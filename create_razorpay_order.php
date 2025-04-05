<?php
header('Content-Type: application/json');
require('connect.php');
require('vendor/autoload.php');

use Razorpay\Api\Api;

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'payment_errors.log');

// Get the JSON POST data
$input = json_decode(file_get_contents('php://input'), true);
$order_id = $input['order_id'];
$amount = $input['amount'];

// Log the order creation request
error_log("Creating Razorpay order for Order ID: " . $order_id . ", Amount: " . $amount);

try {
    // Initialize Razorpay API
    $api = new Api('rzp_test_ap7WDK9hfctDgW', '6AeqXGfkT6W21GW7hBwnqOYw');

    // Create Razorpay Order
    $orderData = [
        'receipt'         => 'order_' . $order_id,
        'amount'          => $amount * 100, // Convert to paise
        'currency'        => 'INR',
        'payment_capture' => 1 // auto capture
    ];

    $razorpayOrder = $api->order->create($orderData);
    
    // Log the created order details
    error_log("Razorpay order created successfully: " . print_r($razorpayOrder->toArray(), true));

    // Return the order details to the client
    echo json_encode([
        'success' => true,
        'razorpay_order_id' => $razorpayOrder['id'],
        'amount' => $amount * 100,
        'currency' => 'INR'
    ]);

} catch(Exception $e) {
    error_log("Error creating Razorpay order: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 