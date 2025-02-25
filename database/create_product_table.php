<?php
require_once '../connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Read the SQL file
    $sql = file_get_contents('table_product.sql');
    
    // Execute the SQL
    if ($conn->multi_query($sql)) {
        echo "Table 'table_product' created successfully!";
    } else {
        echo "Error creating table: " . $conn->error;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
