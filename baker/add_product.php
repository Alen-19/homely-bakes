<?php
session_start();
require_once '../connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Check if baker is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] != 0) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
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
        echo json_encode(['success' => false, 'error' => 'Baker profile not found']);
        exit();
    }
    
    $baker = $baker_result->fetch_assoc();
    $baker_id = $baker['baker_id'];
    
    // Handle file upload
    $target_dir = "../uploads/products/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
    $file_name = uniqid('product_') . '.' . $file_extension;
    $target_file = $target_dir . $file_name;
    $image_url = "uploads/products/" . $file_name;
    
    // Check if image file is valid
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_extension, $allowed_types)) {
        echo json_encode(['success' => false, 'error' => 'Only JPG, JPEG, PNG & GIF files are allowed']);
        exit();
    }
    
    // Move uploaded file
    if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        echo json_encode(['success' => false, 'error' => 'Failed to upload image']);
        exit();
    }
    
    // Insert product into database
    $insert_query = "INSERT INTO table_product (baker_id, product_name, category_id, price, description, image_url) VALUES (?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("isidss", 
        $baker_id,
        $_POST['name'],
        $_POST['category'],
        $_POST['price'],
        $_POST['description'],
        $image_url
    );
    
    if ($insert_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Product added successfully',
            'product_id' => $insert_stmt->insert_id
        ]);
    } else {
        // If insert fails, delete the uploaded image
        unlink($target_file);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to add product: ' . $insert_stmt->error
        ]);
    }
    
    $insert_stmt->close();
    $baker_stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error adding product: ' . $e->getMessage()
    ]);
}
