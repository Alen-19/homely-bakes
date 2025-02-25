<?php
session_start();

// Set headers for JSON response
header('Content-Type: application/json');

// Check if user is logged in by verifying session variables
$isLoggedIn = false;
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // You can add additional checks here if needed
    // For example, verify if the user still exists in the database
    $isLoggedIn = true;
}

// Return JSON response
echo json_encode([
    'isLoggedIn' => $isLoggedIn,
    'status' => 'success'
]);
