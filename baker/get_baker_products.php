<?php
session_start();
require_once '../connect.php';

header('Content-Type: application/json');

// Check if baker is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] != 0) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

try {
    // Get baker_id from table_baker based on user_id
    $user_id = $_SESSION['user_id'];
    $baker_query = "SELECT baker_id FROM table_baker WHERE user_id = ?";
    $baker_stmt = $conn->prepare($baker_query);
    $baker_stmt->bind_param("i", $user_id);
    $baker_stmt->execute();
    $baker_result = $baker_stmt->get_result();
    
    if ($baker_result->num_rows === 0) {
        echo json_encode(['error' => 'Baker profile not found']);
        exit();
    }
    
    $baker = $baker_result->fetch_assoc();
    $baker_id = $baker['baker_id'];
    
    // Get all products for this baker with category names
    $query = "SELECT p.*, c.category_name 
              FROM table_product p 
              LEFT JOIN table_category c ON p.category_id = c.category_id 
              WHERE p.baker_id = ? 
              ORDER BY p.created_at DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $baker_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode($products);
    
    $stmt->close();
    $baker_stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error fetching products: ' . $e->getMessage()
    ]);
}
