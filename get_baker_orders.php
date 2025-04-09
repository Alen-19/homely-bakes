<?php
session_start();
include('connect.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$baker_id = $_SESSION['user_id'];

$query = "SELECT o.*, p.product_name, p.image_url, 
          c.first_name as customer_name, c.mobile_number as customer_phone,
          COALESCE(pay.payment_status, 'pending') as payment_status
          FROM table_orders o
          JOIN table_product p ON o.product_id = p.product_id
          JOIN table_registration c ON o.user_id = c.user_id
          LEFT JOIN table_payments pay ON o.order_id = pay.order_id
          WHERE o.baker_id = ?
          ORDER BY o.order_id DESC";

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