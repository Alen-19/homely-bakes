<?php
include 'connect.php'; // Make sure this includes your DB connection settings

// SQL to create table_baker
$sql = "CREATE TABLE IF NOT EXISTS table_baker (
    baker_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bakery_name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    business_license VARCHAR(100),
    profile_image VARCHAR(255),
    availability_status ENUM('available', 'busy', 'closed') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES table_registration(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;";

if ($conn->query($sql) === TRUE) {
    echo "Table 'table_baker' created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
