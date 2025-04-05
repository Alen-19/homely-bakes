<?php
session_start();
include('connect.php');

if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

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

if (!$order) {
    echo json_encode(['error' => 'Order not found']);
    exit();
}

echo json_encode([
    'success' => true,
    'order' => [
        'status' => $order['status'],
        'payment_status' => $order['payment_status']
    ]
]);
?> 