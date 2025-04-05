<?php
session_start();
include 'connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$search_term = isset($_POST['search_term']) ? trim($_POST['search_term']) : '';
$search_type = isset($_POST['search_type']) ? trim($_POST['search_type']) : 'product';

if (empty($search_term)) {
    echo json_encode(['success' => false, 'message' => 'Search term is required']);
    exit;
}

try {
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Base query (removed WHERE p.is_active = 1 to show all products)
    $query = "
        SELECT DISTINCT 
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
    ";

    // Modify query based on search type
    switch ($search_type) {
        case 'product':
            $query .= " WHERE (p.product_name LIKE ? OR p.description LIKE ?)";
            $search_param = "%$search_term%";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $search_param, $search_param);
            break;
            
        case 'baker':
            $query .= " WHERE (r.first_name LIKE ? OR r.last_name LIKE ?)";
            $search_param = "%$search_term%";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $search_param, $search_param);
            break;
            
        case 'category':
            $query .= " WHERE p.category_id IN (SELECT category_id FROM table_category WHERE category_name LIKE ?)";
            $search_param = "%$search_term%";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $search_param);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid search type']);
            exit;
    }

    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        // Check if product is in wishlist
        $in_wishlist = false;
        if ($user_id) {
            $wishlist_query = "SELECT * FROM table_wishlist WHERE user_id = ? AND product_id = ?";
            $wishlist_stmt = $conn->prepare($wishlist_query);
            $wishlist_stmt->bind_param("ii", $user_id, $row['product_id']);
            $wishlist_stmt->execute();
            $in_wishlist = $wishlist_stmt->get_result()->num_rows > 0;
        }
        
        $row['in_wishlist'] = $in_wishlist;
        $row['average_rating'] = $row['average_rating'] ? number_format($row['average_rating'], 1) : 'N/A';
        $row['review_count'] = $row['review_count'] ?? 0;
        $products[] = $row;
    }

    echo json_encode([
        'success' => true,
        'products' => $products,
        'message' => count($products) > 0 ? 'Products found' : 'No products found'
    ]);

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while searching']);
}

$conn->close();
?>