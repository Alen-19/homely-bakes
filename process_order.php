<?php
session_start();
include('connect.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php");
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

if (!isset($_POST['delivery_address']) || empty(trim($_POST['delivery_address']))) {
    $_SESSION['error'] = "Delivery address is required!";
    header("Location: place_order.php?product_id=" . $product_id);
    exit();
}

// Get order details from form
$product_id = $_POST['product_id'];
$quantity = floatval($_POST['quantity']);
$total_price = floatval($_POST['total_price']);
$delivery_address = $_POST['delivery_address'];
$special_instructions = $_POST['special_instructions'] ?? '';
$delivery_charge = floatval($_POST['delivery_charge'] ?? 0);
$distance = floatval($_POST['distance'] ?? 0);

// Get product details to determine baker_id
$query = "SELECT baker_id FROM table_product WHERE product_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    $_SESSION['error'] = "Product not found!";
    header("Location: product.php");
    exit();
}

$baker_id = $product['baker_id'];

$query = "INSERT INTO table_orders (user_id, baker_id, product_id, quantity, total_price, delivery_address, special_instructions, delivery_charge, distance) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
          
// Check if delivery address is valid before inserting
if (empty($delivery_address)) {
    $_SESSION['error'] = "Delivery address is required!";
    header("Location: place_order.php?product_id=" . $product_id);
    exit();
}
$stmt = $conn->prepare($query);
$stmt->bind_param("iiiddssdd", $user_id, $baker_id, $product_id, $quantity, $total_price, $delivery_address, $special_instructions, $delivery_charge, $distance);

if ($stmt->execute()) {
    $order_id = $stmt->insert_id;
    $_SESSION['success'] = "Order placed successfully! Your order ID is #" . $order_id;
    header("Location: order_confirmation.php?order_id=" . $order_id);
    exit();
} else {
    $_SESSION['error'] = "Failed to place order. Please try again.";
    header("Location: place_order.php?product_id=" . $product_id);
    exit();
}
?>
