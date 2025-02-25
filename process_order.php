<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['product_id'])) {
    header("Location: product.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = mysqli_real_escape_string($conn, $_POST['product_id']);
$size = mysqli_real_escape_string($conn, $_POST['size']);
$delivery_address = mysqli_real_escape_string($conn, $_POST['delivery_address']);
$city = mysqli_real_escape_string($conn, $_POST['city']);
$state = mysqli_real_escape_string($conn, $_POST['state']);
$country = mysqli_real_escape_string($conn, $_POST['country']);

// Get product details
$product_query = "SELECT * FROM table_product WHERE product_id = '$product_id'";
$product_result = mysqli_query($conn, $product_query);
$product = mysqli_fetch_assoc($product_result);

if (!$product) {
    $_SESSION['error'] = "Product not found";
    header("Location: product.php");
    exit();
}

// Calculate total price
$total_price = $product['price'] * $size;

// Create order
$order_query = "INSERT INTO table_orders (
    user_id, 
    product_id, 
    size_kg,
    total_price,
    delivery_address,
    city,
    state,
    country,
    status,
    created_at
) VALUES (
    '$user_id',
    '$product_id',
    '$size',
    '$total_price',
    '$delivery_address',
    '$city',
    '$state',
    '$country',
    'pending',
    NOW()
)";

if (mysqli_query($conn, $order_query)) {
    $_SESSION['success'] = "Order placed successfully! You can track your order in your dashboard.";
    header("Location: orders.php");
} else {
    $_SESSION['error'] = "Failed to place order. Please try again.";
    header("Location: place_order.php?product_id=" . $product_id);
}

mysqli_close($conn);
