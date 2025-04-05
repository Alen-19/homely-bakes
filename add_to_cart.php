<?php
session_start();
include('connect.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
    exit();
}

if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$product_id = $_POST['product_id'];
$quantity = $_POST['quantity'];
$user_id = $_SESSION['user_id'];

// Check if product exists
$check_query = "SELECT * FROM table_product WHERE product_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit();
}

// Insert or update cart
$cart_query = "INSERT INTO table_cart (user_id, product_id, quantity) 
               VALUES (?, ?, ?) 
               ON DUPLICATE KEY UPDATE quantity = quantity + ?";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param("iiii", $user_id, $product_id, $quantity, $quantity);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Product added to cart successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add product to cart']);
}
?>