<?php
session_start();
include '../connect.php';

// Check if baker is logged in
if (!isset($_SESSION['baker_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$baker_id = $_SESSION['baker_id'];

// Query to get orders that have been paid
$query = "SELECT o.*, p.product_name
          FROM table_orders o
          JOIN table_product p ON o.product_id = p.product_id
          JOIN table_payments tp ON o.order_id = tp.order_id
          WHERE o.baker_id = ? 
          AND tp.payment_status = 'completed'
          AND o.status != 'preparing'";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $baker_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

echo json_encode($orders);
?>