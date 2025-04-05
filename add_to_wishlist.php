<?php
session_start();
include('connect.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response = array(
        'success' => false,
        'message' => 'Please login to add items to wishlist'
    );
    echo json_encode($response);
    exit();
}

// Check if product_id is provided
if (!isset($_GET['product_id'])) {
    $response = array(
        'success' => false,
        'message' => 'Invalid request'
    );
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = $_GET['product_id'];
$action = isset($_GET['action']) ? $_GET['action'] : 'add';

// Check if product exists
$check_product = "SELECT product_id FROM table_product WHERE product_id = ?";
$stmt = $conn->prepare($check_product);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response = array(
        'success' => false,
        'message' => 'Product not found'
    );
    echo json_encode($response);
    exit();
}

if ($action === 'add') {
    // Check if product is already in wishlist
    $check_wishlist = "SELECT * FROM table_wishlist WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($check_wishlist);
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $response = array(
            'success' => false,
            'message' => 'Product already in wishlist'
        );
        echo json_encode($response);
        exit();
    }

    // Add to wishlist
    $add_query = "INSERT INTO table_wishlist (user_id, product_id) VALUES (?, ?)";
    $stmt = $conn->prepare($add_query);
    $stmt->bind_param("ii", $user_id, $product_id);
    
    if ($stmt->execute()) {
        $response = array(
            'success' => true,
            'message' => 'Product added to wishlist'
        );
        echo json_encode($response);
    } else {
        $response = array(
            'success' => false,
            'message' => 'Failed to add product to wishlist'
        );
        echo json_encode($response);
    }
} else if ($action === 'remove') {
    // Remove from wishlist
    $remove_query = "DELETE FROM table_wishlist WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($remove_query);
    $stmt->bind_param("ii", $user_id, $product_id);
    
    if ($stmt->execute()) {
        // If called from wishlist page, redirect back
        if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'wishlist.php') !== false) {
            header("Location: wishlist.php");
            exit();
        }
        
        $response = array(
            'success' => true,
            'message' => 'Product removed from wishlist'
        );
        echo json_encode($response);
    } else {
        $response = array(
            'success' => false,
            'message' => 'Failed to remove product from wishlist'
        );
        echo json_encode($response);
    }
} else {
    $response = array(
        'success' => false,
        'message' => 'Invalid action'
    );
    echo json_encode($response);
} 