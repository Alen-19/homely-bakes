<?php
include 'connect.php';
header('Content-Type: application/json');

if (isset($_GET['field']) && isset($_GET['value'])) {
    $field = $_GET['field'];
    $value = $_GET['value'];
    
    // Determine which table and column to check based on the field
    if ($field === 'email') {
        $sql = "SELECT COUNT(*) as count FROM table_login WHERE email = ?";
    } elseif ($field === 'mobileNumber') {
        $sql = "SELECT COUNT(*) as count FROM table_registration WHERE mobile_number = ?";
    } else {
        echo json_encode(['error' => 'Invalid field']);
        exit;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $value);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo json_encode([
        'exists' => $row['count'] > 0
    ]);
    
    $stmt->close();
} else {
    echo json_encode(['error' => 'Missing parameters']);
}
$conn->close();