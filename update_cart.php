<?php
session_start();
include('connect.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit();
}

$product_id = $_POST['product_id'];
$new_quantity = $_POST['quantity'];
$user_id = $_SESSION['user_id'];

// Update the quantity in the cart
$query = "UPDATE table_cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $new_quantity, $user_id, $product_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Cart updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
}
?>