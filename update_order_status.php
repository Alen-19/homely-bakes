<?php
session_start();
include('connect.php');

// Check if user is logged in and is a baker
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'baker') {
    header("Location: login.php");
    exit();
}

// Check if order_id and status are provided
if (!isset($_GET['order_id']) || !isset($_GET['status'])) {
    header("Location: orders.php?error=missing_parameters");
    exit();
}

$order_id = $_GET['order_id'];
$new_status = $_GET['status'];
$baker_id = $_SESSION['user_id'];

// Validate the new status
$valid_statuses = ['preparing', 'prepared', 'out_for_delivery', 'delivered'];
if (!in_array($new_status, $valid_statuses)) {
    header("Location: orders.php?error=invalid_status");
    exit();
}

// Check if the order belongs to this baker
$query = "SELECT * FROM table_orders WHERE order_id = ? AND baker_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $order_id, $baker_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: orders.php?error=unauthorized");
    exit();
}

// Update the order status
$update_query = "UPDATE table_orders SET status = ? WHERE order_id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("si", $new_status, $order_id);

if ($stmt->execute()) {
    header("Location: orders.php?success=status_updated");
} else {
    header("Location: orders.php?error=update_failed");
}
exit();
?> 