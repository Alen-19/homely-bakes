<?php
session_start();
include 'connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to safely display products
function displayProducts($conn) {
    // Try simple query first
    $query = "SELECT * FROM table_product WHERE stock > 0";
    
    // Debug info
    error_log("Executing query: " . $query);
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        $error = mysqli_error($conn);
        error_log("Query failed: " . $error);
        return "Database error: " . $error;
    }
    
    error_log("Query successful, found " . mysqli_num_rows($result) . " rows");
    
    if (mysqli_num_rows($result) === 0) {
        return "No products available at the moment.";
    }
    
    $output = '';
    while ($product = mysqli_fetch_assoc($result)) {
        $imageUrl = $product['image_url'] ? htmlspecialchars($product['image_url']) : 'assets/images/default-product.jpg';
        $output .= '
        <div class="product-card">
            <img src="' . $imageUrl . '" alt="' . htmlspecialchars($product['product_name']) . '">
            <div class="product-info">
                <h3>' . htmlspecialchars($product['product_name']) . '</h3>
                <p class="price">₹' . number_format($product['price'], 2) . ' /kg</p>
                <div class="product-buttons">
                    <button class="add-to-cart me-2" data-product-id="' . $product['product_id'] . '">
                        Add to Cart
                    </button>
                    <button class="buy-now" onclick="window.location.href=\'place_order.php?product_id=' . $product['product_id'] . '\'">
                        Buy Now
                    </button>
                </div>
            </div>
        </div>';
    }
    
    mysqli_free_result($result);
    return $output;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Homely Bakes</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="product.css">
</head>
<body>
    <?php include "header.php"; ?>
    
    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-content">
            <h2>Connecting You to Homemade Goodness</h2>
            <p>Find the best homemade bakers near you.</p>
            <div class="search-bar">
                <input type="text" id="search-input" placeholder="Search products...">
                <select id="filter-options">
                    <option value="all">All Categories</option>
                    <option value="cakes">Cakes</option>
                    <option value="pastries">Pastries</option>
                    <option value="bread">Bread</option>
                    <option value="cookies">Cookies</option>
                </select>
                <button id="search-button">Search</button>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section id="products" class="section">
        <div class="container">
            <h2>Our Products</h2>
            <div id="products-grid" class="baker-grid">
                <?php
                // Check if tables exist
                $tables_check = mysqli_query($conn, "
                    SELECT COUNT(*) as count 
                    FROM information_schema.tables 
                    WHERE table_schema = 'homely_bakes' 
                    AND table_name IN ('table_product', 'table_baker', 'table_category')
                ");
                
                if ($tables_check) {
                    $tables_count = mysqli_fetch_assoc($tables_check)['count'];
                    if ($tables_count < 3) {
                        echo '<p class="error">Database tables not properly set up. Missing required tables.</p>';
                        error_log("Missing tables in homely_bakes database. Found only {$tables_count} of 3 required tables.");
                        exit;
                    }
                }

                if (!$conn) {
                    echo '<p class="error">Database connection failed: ' . mysqli_connect_error() . '</p>';
                } else {
                    $output = displayProducts($conn);
                    if (strpos($output, 'Database error') === 0) {
                        // Show the actual error instead of generic message
                        echo '<p class="error">' . htmlspecialchars($output) . '</p>';
                    } else {
                        echo $output;
                    }
                    mysqli_close($conn);
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Scripts -->
    <script src="header.js"></script>
    <script src="product.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>