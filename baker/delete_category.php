<?php
session_start();
include '../connect.php';

// Check if baker is logged in
if (!isset($_SESSION['baker_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit();
}

// Get the request data
$data = json_decode(file_get_contents('php://input'), true);
$category_id = $data['category_id'] ?? null;

if (!$category_id) {
    echo json_encode(['success' => false, 'error' => 'Category ID is required']);
    exit();
}

// First check if the category belongs to this baker
$check_query = "SELECT category_id FROM table_category WHERE category_id = ? AND baker_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $category_id, $_SESSION['baker_id']);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Category not found or unauthorized']);
    exit();
}

// Check if there are any products using this category
$product_check = "SELECT product_id FROM table_product WHERE category_id = ?";
$product_stmt = $conn->prepare($product_check);
$product_stmt->bind_param("i", $category_id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();

if ($product_result->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Cannot delete category: There are products associated with this category']);
    exit();
}

// If all checks pass, delete the category
$delete_query = "DELETE FROM table_category WHERE category_id = ? AND baker_id = ?";
$delete_stmt = $conn->prepare($delete_query);
$delete_stmt->bind_param("ii", $category_id, $_SESSION['baker_id']);

if ($delete_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete category']);
}
?> 