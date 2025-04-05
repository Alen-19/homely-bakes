<?php
session_start();
include('../connect.php');

header('Content-Type: application/json');

// Log the session data for debugging
error_log('Session data: ' . print_r($_SESSION, true));

if (!isset($_SESSION['baker_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized: Baker ID not set']);
    exit;
}

try {
    // Get and decode the request body
    $input = file_get_contents('php://input');
    error_log('Received input: ' . $input); // Log the raw input
    
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }

    $newStatus = isset($data['status']) ? (int)$data['status'] : null;
    error_log('New status value: ' . var_export($newStatus, true)); // Log the status value

    if ($newStatus === null) {
        throw new Exception('Invalid status value provided');
    }

    // Start transaction
    $conn->begin_transaction();

    // Update baker status
    $bakerQuery = "UPDATE table_baker SET availability_status = ? WHERE baker_id = ?";
    $bakerStmt = $conn->prepare($bakerQuery);
    if (!$bakerStmt) {
        throw new Exception('Failed to prepare baker status update: ' . $conn->error);
    }
    
    $bakerStmt->bind_param("ii", $newStatus, $_SESSION['baker_id']);
    if (!$bakerStmt->execute()) {
        throw new Exception('Failed to update baker status: ' . $bakerStmt->error);
    }

    // Update all products status
    $productQuery = "UPDATE table_product SET is_active = ? WHERE baker_id = ?";
    $productStmt = $conn->prepare($productQuery);
    if (!$productStmt) {
        throw new Exception('Failed to prepare product status update: ' . $conn->error);
    }
    
    $productStmt->bind_param("ii", $newStatus, $_SESSION['baker_id']);
    if (!$productStmt->execute()) {
        throw new Exception('Failed to update product status: ' . $productStmt->error);
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'status' => $newStatus,
        'message' => 'Store and product status updated successfully',
        'baker_id' => $_SESSION['baker_id']
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    
    error_log('Error in toggle_baker_status.php: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'details' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?> 