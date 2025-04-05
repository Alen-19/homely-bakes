<?php
session_start();
include '../connect.php';

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['baker_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not authorized']);
        exit();
    }

    $baker_id = $_SESSION['baker_id'];
    $category_name = trim($_POST['category_name']);
    $category_description = trim($_POST['category_description']);

    // Basic validation
    if (empty($category_name)) {
        echo json_encode(['success' => false, 'message' => 'Category name is required']);
        exit();
    }

    try {
        // First, check if the category exists for this baker
        $check_stmt = $conn->prepare("SELECT category_id FROM table_category WHERE LOWER(category_name) = LOWER(?) AND baker_id = ?");
        if (!$check_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $check_stmt->bind_param("si", $category_name, $baker_id);
        if (!$check_stmt->execute()) {
            throw new Exception("Execute failed: " . $check_stmt->error);
        }

        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Category already exists for this baker']);
            exit();
        }

        // If we get here, the category doesn't exist, so let's insert it
        $insert_stmt = $conn->prepare("INSERT INTO table_category (category_name, description, baker_id) VALUES (?, ?, ?)");
        if (!$insert_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $insert_stmt->bind_param("ssi", $category_name, $category_description, $baker_id);
        
        if ($insert_stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Category added successfully!',
                'category_id' => $conn->insert_id
            ]);
        } else {
            throw new Exception("Insert failed: " . $insert_stmt->error);
        }

    } catch (Exception $e) {
        error_log("Category addition error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error adding category: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 