<?php
session_start();
include 'connect.php';

header('Content-Type: application/json');

// Function to send message
function sendMessage($conn, $order_id, $sender_id, $receiver_id, $message) {
    $query = "INSERT INTO table_chat (order_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiis", $order_id, $sender_id, $receiver_id, $message);
    return $stmt->execute();
}

// Function to get messages
function getMessages($conn, $order_id) {
    $query = "SELECT c.*, 
              CONCAT(r.first_name, ' ', r.last_name) as sender_name
              FROM table_chat c
              JOIN table_registration r ON c.sender_id = r.user_id
              WHERE c.order_id = ?
              ORDER BY c.timestamp ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Handle POST request for sending messages
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'User not authenticated']);
        exit;
    }

    if (isset($data['order_id']) && isset($data['receiver_id']) && isset($data['message'])) {
        $success = sendMessage(
            $conn,
            $data['order_id'],
            $_SESSION['user_id'],
            $data['receiver_id'],
            $data['message']
        );

        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to send message']);
        }
    } else {
        echo json_encode(['error' => 'Missing required fields']);
    }
}

// Handle GET request for retrieving messages
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'User not authenticated']);
        exit;
    }

    if (isset($_GET['order_id'])) {
        $messages = getMessages($conn, $_GET['order_id']);
        echo json_encode(['messages' => $messages]);
    } else {
        echo json_encode(['error' => 'Order ID is required']);
    }
}

// Mark messages as read
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['order_id'])) {
        $query = "UPDATE table_chat SET is_read = TRUE 
                 WHERE order_id = ? AND receiver_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $data['order_id'], $_SESSION['user_id']);
        $success = $stmt->execute();
        
        echo json_encode(['success' => $success]);
    } else {
        echo json_encode(['error' => 'Order ID is required']);
    }
}
?> 