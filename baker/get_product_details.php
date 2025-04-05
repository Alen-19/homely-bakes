<?php
session_start();

// Database connection
include '../connect.php';

// Set headers before any output
header('Content-Type: application/json');

// Check for any output buffering
ob_clean(); // Clear any previous output
ob_start(); // Start fresh output buffer

// Check if baker is logged in
if (!isset($_SESSION['baker_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$product_id = $_GET['product_id'] ?? 0;
$baker_id = $_SESSION['baker_id'];

// Validate product_id
if (!$product_id || !is_numeric($product_id)) {
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

try {
    // Fetch product details
    $query = "SELECT * FROM table_product WHERE product_id = ? AND baker_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        throw new Exception('Database error: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "ii", $product_id, $baker_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Database error: ' . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    
    if ($product = mysqli_fetch_assoc($result)) {
        // Base URL for the project
        $baseUrl = 'http://localhost/HomelyBakes/'; // Adjust this based on your domain

        // Ensure all fields exist, even if null
        $product_data = [
            'product_id' => $product['product_id'] ?? null,
            'product_name' => $product['product_name'] ?? '',
            'description' => $product['description'] ?? '',
            'price' => $product['price'] ?? '',
            'weight' => $product['weight'] ?? '',
            'category_id' => $product['category_id'] ?? '',
            'image_url' => $product['image_url'] ? $baseUrl . $product['image_url'] : '', // Use absolute URL
            'product_type' => $product['product_type'] ?? '',
            'cake_type' => $product['cake_type'] ?? '',
            'is_active' => $product['is_active'] ?? 0
        ];
        echo json_encode($product_data);
    } else {
        echo json_encode(['error' => 'Product not found or you don\'t have permission to access it']);
    }
    
    mysqli_stmt_close($stmt);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    mysqli_close($conn);
    ob_end_flush(); // Flush and end output buffer
}
?>