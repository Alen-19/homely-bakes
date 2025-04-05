<?php
session_start();
include 'connect.php';

header('Content-Type: application/json');

function isInWishlist($conn, $product_id, $user_id) {
    $query = "SELECT * FROM table_wishlist WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

function generateProductCard($product, $isInWishlist) {
    $imageUrl = $product['image_url'] ? htmlspecialchars($product['image_url']) : 'assets/images/default-product.jpg';
    $wishlistClass = $isInWishlist ? 'in-wishlist' : '';
    $wishlistIcon = $isInWishlist ? 'fas' : 'far';
    
    return '
    <div class="product-card">
        <img src="' . $imageUrl . '" alt="' . htmlspecialchars($product['product_name']) . '">
        <div class="product-info">
            <div class="product-header">
                <h3>' . htmlspecialchars($product['product_name']) . '</h3>
                <button class="wishlist-btn ' . $wishlistClass . '" data-product-id="' . $product['product_id'] . '">
                    <i class="' . $wishlistIcon . ' fa-heart"></i>
                </button>
            </div>
            <p class="baker-name">By: ' . htmlspecialchars($product['baker_name']) . '</p>
            <p class="price">₹' . number_format($product['price'], 2) . ' /kg</p>
            <div class="product-buttons">
                <button class="add-to-cart" data-product-id="' . $product['product_id'] . '">
                    Add to Cart
                </button>
                <button class="buy-now" onclick="window.location.href=\'place_order.php?product_id=' . $product['product_id'] . '\'">
                    Buy Now
                </button>
            </div>
        </div>
    </div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Build the query
    $query = "SELECT p.*, b.user_id, r.first_name as baker_name 
              FROM table_product p
              LEFT JOIN table_baker b ON p.baker_id = b.baker_id
              LEFT JOIN table_registration r ON b.user_id = r.user_id
              WHERE p.is_active = 1";
    $params = [];
    $types = "";

    // Add price filter
    if (isset($data['price']) && $data['price'] > 0) {
        $query .= " AND p.price <= ?";
        $params[] = $data['price'];
        $types .= "d";
    }

    // Add cake type filter
    if (!empty($data['cakeTypes'])) {
        $placeholders = str_repeat('?,', count($data['cakeTypes']) - 1) . '?';
        $query .= " AND p.cake_type IN ($placeholders)";
        foreach ($data['cakeTypes'] as $type) {
            $params[] = $type;
            $types .= "s";
        }
    }

    // Add category filter
    if (!empty($data['categories'])) {
        $placeholders = str_repeat('?,', count($data['categories']) - 1) . '?';
        $query .= " AND p.category_id IN (SELECT category_id FROM table_category WHERE category_name IN ($placeholders))";
        foreach ($data['categories'] as $category) {
            $params[] = $category;
            $types .= "s";
        }
    }

    // Add dietary preference filter
    if (!empty($data['dietary'])) {
        $placeholders = str_repeat('?,', count($data['dietary']) - 1) . '?';
        $query .= " AND p.product_type IN ($placeholders)";
        foreach ($data['dietary'] as $type) {
            $params[] = $type;
            $types .= "s";
        }
    }

    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $output = '';
    while ($product = mysqli_fetch_assoc($result)) {
        $isInWishlist = isset($_SESSION['user_id']) ? isInWishlist($conn, $product['product_id'], $_SESSION['user_id']) : false;
        $output .= generateProductCard($product, $isInWishlist);
    }

    echo json_encode([
        'success' => true,
        'html' => $output,
        'count' => mysqli_num_rows($result)
    ]);

} else {
    // For GET requests (reset), return all active products
    $query = "SELECT p.*, b.user_id, r.first_name as baker_name 
              FROM table_product p
              LEFT JOIN table_baker b ON p.baker_id = b.baker_id
              LEFT JOIN table_registration r ON b.user_id = r.user_id
              WHERE p.is_active = 1";
    
    $result = mysqli_query($conn, $query);
    
    $output = '';
    while ($product = mysqli_fetch_assoc($result)) {
        $isInWishlist = isset($_SESSION['user_id']) ? isInWishlist($conn, $product['product_id'], $_SESSION['user_id']) : false;
        $output .= generateProductCard($product, $isInWishlist);
    }

    echo json_encode([
        'success' => true,
        'html' => $output,
        'count' => mysqli_num_rows($result)
    ]);
}

mysqli_close($conn);
?>