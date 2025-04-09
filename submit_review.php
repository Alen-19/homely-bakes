<?php
session_start();
include('connect.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit();
}

// Check if it's a POST request with required data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'] ?? null;
    $rating = $_POST['rating'] ?? null;
    $review_text = $_POST['review_text'] ?? '';
    $user_id = $_SESSION['user_id'];

    // Validate inputs
    if (!$order_id || !$rating || $rating < 1 || $rating > 5) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid input data']);
        exit();
    }

    try {
        // Start transaction
        $conn->begin_transaction();

        // Insert review
        $stmt = $conn->prepare("INSERT INTO table_reviews (order_id, user_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiis", $order_id, $user_id, $rating, $review_text);
        $stmt->execute();

        // Update order status to indicate review has been submitted
        $stmt = $conn->prepare("UPDATE table_orders SET review_submitted = 1 WHERE order_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        // Redirect to orders page with success message
        $_SESSION['review_success'] = true;
        header("Location: orders.php?review_success=true");
        exit();

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit();
    }
}

// If we get here, something went wrong
header('Content-Type: application/json');
echo json_encode(['success' => false, 'error' => 'Invalid request']);
exit();
?> 