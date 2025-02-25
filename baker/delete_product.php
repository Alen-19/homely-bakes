<?php
session_start();
require_once '../connect.php';

// Check if baker is logged in
if (!isset($_SESSION['baker_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if product_id is provided
if (!isset($_POST['product_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit;
}

$product_id = $_POST['product_id'];
$baker_id = $_SESSION['baker_id'];

// Verify the product belongs to the baker before deleting
$query = "DELETE FROM table_product WHERE product_id = ? AND baker_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $product_id, $baker_id);

if (mysqli_stmt_execute($stmt)) {
    if (mysqli_affected_rows($conn) > 0) {
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found or not authorized to delete']);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error deleting product: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
