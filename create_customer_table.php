<?php
include 'connect.php';

// SQL to create table_customer
$sql = "CREATE TABLE IF NOT EXISTS table_customer (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    alt_phone VARCHAR(15),
    profile_photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES table_registration(user_id) ON DELETE CASCADE
)";

// Execute the query
if ($conn->query($sql) === TRUE) {
    echo "Table 'table_customer' created successfully or already exists";
} else {
    echo "Error creating table: " . $conn->error;
}

// Create a folder for profile photos if it doesn't exist
$uploadDir = 'uploads/profile_photos';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Close the connection
$conn->close();
?>
