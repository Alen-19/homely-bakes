<?php
session_start();
include '../connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['baker_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$new_status = $data['status'] ? 1 : 0;
$baker_id = $_SESSION['baker_id'];

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Update baker status
    $baker_query = "UPDATE table_baker SET availability_status = ? WHERE baker_id = ?";
    $baker_stmt = mysqli_prepare($conn, $baker_query);
    mysqli_stmt_bind_param($baker_stmt, "ii", $new_status, $baker_id);
    $baker_result = mysqli_stmt_execute($baker_stmt);

    // Update all products status
    $products_query = "UPDATE table_product SET is_active = ? WHERE baker_id = ?";
    $products_stmt = mysqli_prepare($conn, $products_query);
    mysqli_stmt_bind_param($products_stmt, "ii", $new_status, $baker_id);
    $products_result = mysqli_stmt_execute($products_stmt);

    if ($baker_result && $products_result) {
        mysqli_commit($conn);
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to update status');
    }
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

mysqli_close($conn);
?> 