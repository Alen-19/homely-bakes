<?php
session_start();

// Database connection
include '../connect.php';

// Check if baker is logged in
if (!isset($_SESSION['baker_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['product_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit;
}

$product_id = $_GET['product_id'];
$baker_id = $_SESSION['baker_id'];

// Fetch product details
$query = "SELECT * FROM table_product WHERE product_id = ? AND baker_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $product_id, $baker_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($product = mysqli_fetch_assoc($result)) {
    echo json_encode(['success' => true, 'product' => $product]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Product not found or not authorized']);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
