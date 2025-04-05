<?php
// Ensure this is the first line, no whitespace before it
session_start();

// Clear any previous output
ob_clean();
ob_start();

// Set JSON header
header('Content-Type: application/json');

// Check if baker is logged in
if (!isset($_SESSION['baker_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Include database connection
    include '../connect.php';

    // Get baker ID from session
    $baker_id = $_SESSION['baker_id'];
    
    // Get all form data
    $product_id = $_POST['product_id'];
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $weight = $_POST['weight'];
    $description = $_POST['description'];
    $productType = $_POST['productType'];
    $cakeType = $_POST['cakeType'];
    
    // Start transaction
    mysqli_begin_transaction($conn);

    // Update main product details
    $update_query = "UPDATE table_product SET 
        product_name = ?,
        category_id = ?,
        price = ?,
        weight = ?,
        description = ?,
        product_type = ?,
        cake_type = ?,
        updated_at = CURRENT_TIMESTAMP
        WHERE product_id = ? AND baker_id = ?";

    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "ssddsssii", 
        $name,
        $category,
        $price,
        $weight,
        $description,
        $productType,
        $cakeType,
        $product_id,
        $baker_id
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error updating product: " . mysqli_error($conn));
    }

    // Handle image upload if new image is provided
    if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
        $image = $_FILES['image'];
        $image_path = '../uploads/' . basename($image['name']);
        
        if (move_uploaded_file($image['tmp_name'], $image_path)) {
            // Update image path in database
            $update_image = "UPDATE table_product SET image_url = ? WHERE product_id = ?";
            $stmt = mysqli_prepare($conn, $update_image);
            $relative_path = 'uploads/' . basename($image['name']);
            mysqli_stmt_bind_param($stmt, "si", $relative_path, $product_id);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error updating image path: " . mysqli_error($conn));
            }
        }
    }

    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Product updated successfully!'
    ]);

} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} finally {
    mysqli_close($conn);
    ob_end_flush();
}
?> 