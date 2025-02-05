<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "homely_bakers";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch bakers from the database
$sql = "SELECT name, menu, location, description, image_url FROM bakers";
$result = $conn->query($sql);

$bakers = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bakers[] = $row;
    }
}

$conn->close();

// Return data as JSON
header('Content-Type: application/json');
echo json_encode($bakers);
?>
