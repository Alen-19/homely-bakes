<?php
session_start();
include('../connect.php');

if (!isset($_SESSION['baker_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $order_id = $data['order_id'];
    $status = $data['status'];
    $baker_id = $_SESSION['baker_id'];

    // Verify order belongs to baker
    $check_query = "SELECT * FROM table_orders WHERE order_id = ? AND baker_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $order_id, $baker_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('Order not found');
    }

    // Update order status
    $update_query = "UPDATE table_orders SET status = ? WHERE order_id = ? AND baker_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sii", $status, $order_id, $baker_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to update order');
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 