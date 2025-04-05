<?php
session_start();
include '../connect.php';

header('Content-Type: application/json');

// Check if baker is logged in
if (!isset($_SESSION['baker_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit();
}

// Get and decode the JSON data
$data = json_decode(file_get_contents('php://input'), true);
$order_id = $data['order_id'] ?? null;
$status = $data['status'] ?? null;

// Validate input - add 'delivered' to allowed statuses
if (!$order_id || !$status || !in_array($status, ['preparing', 'prepared', 'out_for_delivery', 'delivered'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Update order status
    $update_stmt = $conn->prepare("UPDATE table_orders SET status = ? WHERE order_id = ? AND baker_id = ?");
    $update_stmt->bind_param("sii", $status, $order_id, $_SESSION['baker_id']);
    
    if (!$update_stmt->execute()) {
        throw new Exception("Failed to update order status: " . $update_stmt->error);
    }

    if ($update_stmt->affected_rows === 0) {
        throw new Exception("Order not found or you're not authorized to update it");
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => "Order status updated to $status successfully"]);

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}