<?php
session_start();
include('../includes/connect.php');

if (!isset($_SESSION['baker_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit;
}

try {
    $baker_id = $_SESSION['baker_id'];
    $order_id = $_POST['order_id'];
    $status = $_POST['status']; // 'accepted' or 'rejected'

    // Verify order belongs to baker
    $check_query = "SELECT * FROM table_orders WHERE order_id = ? AND baker_id = ?";
    $stmt = $con->prepare($check_query);
    $stmt->bind_param("ii", $order_id, $baker_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('Order not found');
    }

    // Update order status
    $update_query = "UPDATE table_orders SET status = ? WHERE order_id = ? AND baker_id = ?";
    $stmt = $con->prepare($update_query);
    $stmt->bind_param("sii", $status, $order_id, $baker_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Order ' . $status . ' successfully']);
    } else {
        throw new Exception('Failed to update order status');
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 