<?php
session_start();
include('connect.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit();
}

$product_id = $_POST['product_id'];
$user_id = $_SESSION['user_id'];

// Remove the product from the cart
$query = "DELETE FROM table_cart WHERE user_id = ? AND product_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user_id, $product_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Product removed from cart']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove product']);
}
?>