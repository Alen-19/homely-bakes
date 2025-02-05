<?php
// db_connection.php
$conn = mysqli_connect("localhost", "username", "password", "homely_bakes");

// add_product.php
<?php
include 'db_connection.php';

$baker_id = $_SESSION['baker_id']; // Assume session management is in place
$product_name = $_POST['name'];
$category_id = $_POST['category'];
$price = $_POST['price'];
$description = $_POST['description'];

// File upload logic
$image_path = uploadImage($_FILES['image']);

$query = "INSERT INTO table_products (baker_id, category_id) VALUES ($baker_id, $category_id)";
$result = mysqli_query($conn, $query);

$cake_query = "INSERT INTO table_cake_details (product_id, cake_name, price, description, image_url) 
               VALUES (LAST_INSERT_ID(), '$product_name', $price, '$description', '$image_path')";
$cake_result = mysqli_query($conn, $cake_query);

// get_baker_products.php
<?php
include 'db_connection.php';

$baker_id = $_SESSION['baker_id'];
$query = "SELECT cd.*, p.category_id 
          FROM table_cake_details cd
          JOIN table_products p ON cd.product_id = p.product_id
          WHERE p.baker_id = $baker_id";
$result = mysqli_query($conn, $query);

$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}

echo json_encode($products);

// get_baker_orders.php
<?php
include 'db_connection.php';

$baker_id = $_SESSION['baker_id'];
$query = "SELECT o.*, cd.cake_name, r.first_name, r.last_name
          FROM table_orders o
          JOIN table_products p ON o.product_id = p.product_id
          JOIN table_cake_details cd ON p.product_id = cd.product_id
          JOIN table_customer c ON o.customer_id = c.customer_id
          JOIN table_registration r ON c.user_id = r.user_id
          WHERE p.baker_id = $baker_id";
$result = mysqli_query($conn, $query);

$orders = [];
while ($row = mysqli_fetch_assoc($result)) {
    $orders[] = $row;
}

echo json_encode($orders);
?>