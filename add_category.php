<?php
session_start();
include '../connect.php';

header('Content-Type: application/json');

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

// Get the baker_id from session
$baker_id = $_SESSION['baker_id'] ?? null;

if (!$baker_id) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Debug logging
error_log('Received POST data: ' . print_r($_POST, true));

$categoryName = trim($_POST['category_name'] ?? '');
$categoryDescription = trim($_POST['category_description'] ?? '');

if (empty($categoryName)) {
    echo json_encode(['error' => 'Category name is required']);
    exit;
}

try {
    // Check if category already exists for this baker
    $checkQuery = "SELECT COUNT(*) as count FROM table_category WHERE category_name = ? AND baker_id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("si", $categoryName, $baker_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        // Category already exists
        echo json_encode([
            'success' => false,
            'message' => 'Category already exists for this baker'
        ]);
        exit;
    }

    // If category doesn't exist, proceed with insertion
    $insertQuery = "INSERT INTO table_category (category_name, category_description, baker_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("ssi", $categoryName, $categoryDescription, $baker_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception($conn->error);
    }

} catch (Exception $e) {
    error_log('Error in add_category.php: ' . $e->getMessage());
    echo json_encode([
        'error' => 'insert_failed',
        'message' => 'Failed to add category: ' . $e->getMessage()
    ]);
}

$stmt->close();
$conn->close();
?> 