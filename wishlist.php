<?php
session_start();
include('connect.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get wishlist items with product details
$query = "SELECT w.*, p.*, r.first_name as baker_name 
          FROM table_wishlist w 
          JOIN table_product p ON w.product_id = p.product_id 
          JOIN table_registration r ON p.baker_id = r.user_id 
          WHERE w.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Wishlist - Homely Bakes</title>
    <style>
        .wishlist-container {
            max-width: 1200px;
            margin: 100px auto;
            padding: 20px;
        }

        .wishlist-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .wishlist-item {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .wishlist-item:hover {
            transform: translateY(-5px);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .product-info {
            padding: 15px;
        }

        .product-name {
            font-size: 18px;
            color: #333;
            margin-bottom: 10px;
        }

        .baker-name {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .product-price {
            font-size: 20px;
            color: #4CAF50;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .order-btn, .remove-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
            text-decoration: none;
            text-align: center;
            flex: 1;
        }

        .order-btn {
            background-color: #4CAF50;
            color: white;
        }

        .order-btn:hover {
            background-color: #45a049;
        }

        .remove-btn {
            background-color: #ff4444;
            color: white;
        }

        .remove-btn:hover {
            background-color: #cc0000;
        }

        .empty-wishlist {
            text-align: center;
            padding: 50px;
            color: #666;
        }

        .empty-wishlist p {
            margin-bottom: 20px;
        }

        .browse-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .browse-btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <?php include('header.php'); ?>

    <div class="wishlist-container">
        <h2 class="wishlist-title">My Wishlist</h2>

        <?php if ($result->num_rows > 0): ?>
            <div class="wishlist-grid">
                <?php while ($item = $result->fetch_assoc()): ?>
                    <div class="wishlist-item">
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="product-image">
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                            <p class="baker-name">By <?php echo htmlspecialchars($item['baker_name']); ?></p>
                            <p class="product-price">₹<?php echo number_format($item['price'], 2); ?> /kg</p>
                            <div class="action-buttons">
                                <a href="place_order.php?product_id=<?php echo $item['product_id']; ?>" class="order-btn">Order Now</a>
                                <a href="add_to_wishlist.php?product_id=<?php echo $item['product_id']; ?>&action=remove" class="remove-btn">Remove</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-wishlist">
                <p>Your wishlist is empty!</p>
                <a href="product.php" class="browse-btn">Browse Products</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 