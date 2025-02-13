<?php
// get_products.php
include 'connect.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : 'all';

$query = "SELECT p.*, b.name as baker_name 
          FROM products p 
          JOIN bakers b ON p.baker_id = b.id 
          WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
}

if ($category !== 'all') {
    $query .= " AND p.category = ?";
}

$stmt = $conn->prepare($query);

if (!empty($search) && $category !== 'all') {
    $searchParam = "%$search%";
    $stmt->bind_param("sss", $searchParam, $searchParam, $category);
} elseif (!empty($search)) {
    $searchParam = "%$search%";
    $stmt->bind_param("ss", $searchParam, $searchParam);
} elseif ($category !== 'all') {
    $stmt->bind_param("s", $category);
}

$stmt->execute();
$result = $stmt->get_result();
$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

header('Content-Type: application/json');
echo json_encode($products);
?>