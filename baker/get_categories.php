<?php
session_start();
require_once '../connect.php';

// Check if baker is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] != 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    // Get baker_id from table_baker based on user_id
    $user_id = $_SESSION['user_id'];
    $baker_query = "SELECT baker_id FROM table_baker WHERE user_id = ?";
    $baker_stmt = $conn->prepare($baker_query);
    $baker_stmt->bind_param("i", $user_id);
    $baker_stmt->execute();
    $baker_result = $baker_stmt->get_result();
    
    if ($baker_result->num_rows > 0) {
        $baker = $baker_result->fetch_assoc();
        $baker_id = $baker['baker_id'];
        
        // Get categories for this baker
        $category_query = "SELECT category_id, category_name FROM table_category WHERE baker_id = ? ORDER BY category_name";
        $category_stmt = $conn->prepare($category_query);
        $category_stmt->bind_param("i", $baker_id);
        $category_stmt->execute();
        $result = $category_stmt->get_result();
        $categories = $result->fetch_all(MYSQLI_ASSOC);
        
        if (empty($categories)) {
            echo json_encode(['error' => 'No categories found']);
        } else {
            echo json_encode($categories);
        }
        $category_stmt->close();
    } else {
        echo json_encode(['error' => 'Baker profile not found']);
    }
    $baker_stmt->close();
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch categories',
        'message' => $e->getMessage()
    ]);
}