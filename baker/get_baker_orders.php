<?php
session_start();
include('../connect.php');

if (!isset($_SESSION['baker_id'])) {
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

$status = $_GET['status'] ?? 'pending';
$baker_id = $_SESSION['baker_id'];

$query = "SELECT o.*, p.product_name, p.image_url, 
          r.first_name, r.last_name, r.mobile_number 
          FROM table_orders o 
          JOIN table_product p ON o.product_id = p.product_id 
          JOIN table_registration r ON o.user_id = r.user_id 
          WHERE p.baker_id = ? AND o.status = ?
          ORDER BY o.order_id ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("is", $baker_id, $status);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

header('Content-Type: application/json');
echo json_encode($orders);
?> 