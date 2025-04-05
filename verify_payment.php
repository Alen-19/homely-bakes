<?php
header('Content-Type: application/json');
require('connect.php');
require('vendor/autoload.php');

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'payment_errors.log');

// Get and decode the input data
$rawInput = file_get_contents('php://input');
error_log("Raw input received: " . $rawInput);

$input = json_decode($rawInput, true);
error_log("Decoded input: " . print_r($input, true));

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON decode error: " . json_last_error_msg());
    echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
    exit();
}

// Validate required fields
if (empty($input['razorpay_payment_id']) || empty($input['razorpay_order_id']) || 
    empty($input['razorpay_signature']) || empty($input['order_id'])) {
    $missing = [];
    if (empty($input['razorpay_payment_id'])) $missing[] = 'razorpay_payment_id';
    if (empty($input['razorpay_order_id'])) $missing[] = 'razorpay_order_id';
    if (empty($input['razorpay_signature'])) $missing[] = 'razorpay_signature';
    if (empty($input['order_id'])) $missing[] = 'order_id';
    
    error_log("Missing required fields: " . implode(', ', $missing));
    echo json_encode(['success' => false, 'error' => 'Missing required fields: ' . implode(', ', $missing)]);
    exit();
}

try {
    // Initialize Razorpay API
    $api = new Api('rzp_test_ap7WDK9hfctDgW', '6AeqXGfkT6W21GW7hBwnqOYw');

    // Verify the payment signature
    $attributes = [
        'razorpay_order_id' => $input['razorpay_order_id'],
        'razorpay_payment_id' => $input['razorpay_payment_id'],
        'razorpay_signature' => $input['razorpay_signature']
    ];

    $api->utility->verifyPaymentSignature($attributes);
    
    // Get payment details from Razorpay
    $payment = $api->payment->fetch($input['razorpay_payment_id']);
    error_log("Payment details fetched from Razorpay: " . print_r($payment->toArray(), true));
    
    // Start transaction
    $conn->begin_transaction();

    try {
        // Check for existing payment
        $check_stmt = $conn->prepare("SELECT payment_id FROM table_payments WHERE razorpay_payment_id = ?");
        $check_stmt->bind_param("s", $input['razorpay_payment_id']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            error_log("Payment already processed: " . $input['razorpay_payment_id']);
            echo json_encode(['success' => true, 'message' => 'Payment already processed']);
            exit();
        }

        // Generate unique payment ID
        $payment_id = 'PAY' . time() . rand(1000, 9999);
        $amount = $payment->amount / 100; // Convert from paise to rupees
        $payment_status = 'completed';
        
        // Prepare and execute the insert statement
        $stmt = $conn->prepare("INSERT INTO table_payments (payment_id, order_id, amount, payment_status, razorpay_payment_id, razorpay_order_id, razorpay_signature) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("sidssss",
            $payment_id,
            $input['order_id'],
            $amount,
            $payment_status,
            $input['razorpay_payment_id'],
            $input['razorpay_order_id'],
            $input['razorpay_signature']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        // Verify the insertion
        if ($stmt->affected_rows === 0) {
            throw new Exception("Payment record not inserted");
        }

        // Update order status
        $update_stmt = $conn->prepare("UPDATE table_orders SET  razorpay_order_id = ?, payment_status = 'completed' WHERE order_id = ?");
        $update_stmt->bind_param("si", $input['razorpay_order_id'], $input['order_id']);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update order status: " . $update_stmt->error);
        }

        // Commit transaction
        $conn->commit();
        
        // Log success
        error_log("Payment successfully processed and stored:");
        error_log("Payment ID: " . $payment_id);
        error_log("Razorpay Payment ID: " . $input['razorpay_payment_id']);
        error_log("Razorpay Order ID: " . $input['razorpay_order_id']);
        error_log("Amount: " . $amount);

        echo json_encode([
            'success' => true,
            'message' => 'Payment processed successfully',
            'payment_id' => $payment_id
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Database error: " . $e->getMessage());
        throw $e;
    }

} catch(SignatureVerificationError $e) {
    error_log("Signature verification failed: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid payment signature'
    ]);
} catch(Exception $e) {
    error_log("Payment processing error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 