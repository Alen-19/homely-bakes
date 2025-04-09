<?php
include '../connect.php';

$sql = "CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES table_orders(order_id),
    FOREIGN KEY (sender_id) REFERENCES table_registration(user_id),
    FOREIGN KEY (receiver_id) REFERENCES table_registration(user_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table chat_messages created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
