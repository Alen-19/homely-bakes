<?php
session_start();
include 'connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to check if a product is in wishlist
function isInWishlist($conn, $product_id, $user_id) {
    $query = "SELECT * FROM table_wishlist WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Function to fetch products and return HTML
function fetchProducts($conn) {
    $query = "
        SELECT 
            p.*, 
            b.user_id, 
            r.first_name as baker_name,
            AVG(rev.rating) as average_rating,
            COUNT(rev.review_id) as review_count
        FROM table_product p
        LEFT JOIN table_baker b ON p.baker_id = b.baker_id
        LEFT JOIN table_registration r ON b.user_id = r.user_id
        LEFT JOIN table_orders o ON p.product_id = o.product_id
        LEFT JOIN table_reviews rev ON o.order_id = rev.order_id
        GROUP BY p.product_id, p.product_name, p.price, p.image_url, p.is_active, b.user_id, r.first_name
    ";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return ['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)];
    }
    
    if (mysqli_num_rows($result) === 0) {
        return ['success' => false, 'message' => 'No products available at the moment.'];
    }
    
    $output = '';
    while ($product = mysqli_fetch_assoc($result)) {
        $imageUrl = $product['image_url'] ? htmlspecialchars($product['image_url']) : 'assets/images/default-product.jpg';
        $isInWishlist = isset($_SESSION['user_id']) ? isInWishlist($conn, $product['product_id'], $_SESSION['user_id']) : false;
        $wishlistClass = $isInWishlist ? 'in-wishlist' : '';
        $wishlistIcon = $isInWishlist ? 'fas' : 'far';
        $isActive = $product['is_active'] == 1;
        $unavailableMessage = !$isActive ? '<span class="unavailable-message">Unavailable</span>' : '';
        $buttonDisabled = !$isActive ? 'disabled' : '';
        
        // Calculate and format the average rating
        $average_rating = $product['average_rating'] ? number_format($product['average_rating'], 1) : 0; // Default to 0 if no rating
        $rating_count = $product['average_rating'] ? ($product['review_count'] ?? 0) : 0; // Use review_count as rating_count if there are ratings, else 0
        $review_count = $product['review_count'] ?? 0;

        $output .= '
        <div class="product-card ' . (!$isActive ? 'unavailable' : '') . '">
            ' . ($isActive ? '' : '<span class="unavailable-message">Unavailable</span>') . '
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
                <div class="rating">
                    <span class="average-rating">' . $average_rating . '</span>
                    <span class="star-rating">★</span>
                    <span class="rating-count">' . $rating_count . ' Ratings & ' . $review_count . ' Reviews</span>
                </div>
                <div class="product-buttons">
                    <button class="add-to-cart me-2" data-product-id="' . $product['product_id'] . '" ' . $buttonDisabled . '>
                        Add to Cart
                    </button>
                    <button class="buy-now" onclick="window.location.href=\'place_order.php?product_id=' . $product['product_id'] . '\'" ' . $buttonDisabled . '>
                        Buy Now
                    </button>
                </div>
            </div>
        </div>';
    }
    
    mysqli_free_result($result);
    return ['success' => true, 'html' => $output];
}

// Main logic
$response = fetchProducts($conn);
mysqli_close($conn);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>