<?php
session_start();
include '../connect.php';

header('Content-Type: application/json');

// Check if user is logged in and is a baker
if (!isset($_SESSION['baker_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['product_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required data']);
    exit;
}

$product_id = intval($data['product_id']);
$new_status = $data['status'] ? 1 : 0;
$baker_id = $_SESSION['baker_id'];

// Verify the product belongs to this baker
$check_query = "SELECT product_id FROM table_product WHERE product_id = ? AND baker_id = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, "ii", $product_id, $baker_id);
mysqli_stmt_execute($check_stmt);
$result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'error' => 'Product not found or unauthorized']);
    exit;
}

// Update product status
$query = "UPDATE table_product SET is_active = ?, updated_at = NOW() WHERE product_id = ? AND baker_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iii", $new_status, $product_id, $baker_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}

mysqli_close($conn);
?> 