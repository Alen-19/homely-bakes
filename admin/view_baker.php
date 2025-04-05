<?php
session_start();
include '../connect.php';

// Get baker_id from URL parameter
$baker_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$baker_id) {
    header("Location: ../index.php");
    exit();
}

// Fetch baker details with correct table joins
$baker_query = "SELECT b.*, r.first_name, r.last_name, l.email 
                FROM table_baker b 
                JOIN table_registration r ON b.user_id = r.user_id 
                JOIN table_login l ON b.user_id = l.user_id 
                WHERE b.baker_id = ?";
$stmt = mysqli_prepare($conn, $baker_query);
mysqli_stmt_bind_param($stmt, "i", $baker_id);
mysqli_stmt_execute($stmt);
$baker_result = mysqli_stmt_get_result($stmt);
$baker = mysqli_fetch_assoc($baker_result);

if (!$baker) {
    header("Location: ../index.php");
    exit();
}

// Fetch baker's products
$products_query = "SELECT p.*, c.category_name 
                  FROM table_product p 
                  LEFT JOIN table_category c ON p.category_id = c.category_id 
                  WHERE p.baker_id = ? AND p.is_active = 1 
                  ORDER BY p.created_at DESC";
$stmt = mysqli_prepare($conn, $products_query);
mysqli_stmt_bind_param($stmt, "i", $baker_id);
mysqli_stmt_execute($stmt);
$products_result = mysqli_stmt_get_result($stmt);

// Fetch baker's categories
$categories_query = "SELECT * FROM table_category WHERE baker_id = ?";
$stmt = mysqli_prepare($conn, $categories_query);
mysqli_stmt_bind_param($stmt, "i", $baker_id);
mysqli_stmt_execute($stmt);
$categories_result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($baker['bakery_name'] ?? 'Baker Profile'); ?> - Homely Bakes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #2196F3;
            --background-color: #f5f5f5;
            --text-color: #333;
            --border-color: #ddd;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            background-color: var(--background-color);
            color: var(--text-color);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .baker-profile {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .baker-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
        }

        .baker-info h1 {
            font-size: 2em;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .baker-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .status-open {
            background-color: #d4edda;
            color: #155724;
        }

        .status-closed {
            background-color: #f8d7da;
            color: #721c24;
        }

        .baker-contact {
            margin-top: 20px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .contact-item i {
            color: var(--primary-color);
        }

        .categories-nav {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            overflow-x: auto;
            padding-bottom: 10px;
        }

        .category-btn {
            padding: 8px 20px;
            border: none;
            border-radius: 20px;
            background: white;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.3s ease;
        }

        .category-btn.active {
            background: var(--primary-color);
            color: white;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .product-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .product-card:hover {
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
            font-size: 1.2em;
            margin-bottom: 10px;
            color: var(--text-color);
        }

        .product-category {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 5px;
        }

        .product-price {
            font-size: 1.1em;
            color: var(--primary-color);
            font-weight: bold;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid var(--border-color);
        }

        .product-type {
            font-size: 0.9em;
            color: #666;
        }

        .order-btn {
            padding: 8px 15px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .order-btn:hover {
            background: #45a049;
        }

        .allergens {
            font-size: 0.85em;
            color: #e74c3c;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .baker-header {
                flex-direction: column;
                text-align: center;
            }

            .categories-nav {
                padding-bottom: 5px;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_profile.php'; ?>
    <?php include 'admin_sidebar.php'; ?>
    <div class="container" style="margin-left: 250px; transition: margin-left .5s;">
        <div class="baker-profile">
            <div class="baker-header">
                <?php
                // Add '../baker/' to the path since we're in the admin directory
                $image_path = "../baker/" . $baker['profile_image'];
                ?>
                <img src="<?php echo htmlspecialchars($image_path); ?>" 
                     alt="<?php echo htmlspecialchars($baker['bakery_name']); ?>" 
                     class="profile-image"
                     onerror="this.src='../assets/images/default-profile.jpg'">
                <div class="baker-info">
                    <h1><?php echo htmlspecialchars($baker['bakery_name']); ?></h1>
                    <div class="baker-status <?php echo $baker['availability_status'] == 'available' ? 'status-open' : 'status-closed'; ?>">
                        <?php echo $baker['availability_status'] == 'available' ? 'Open' : 'Closed'; ?>
                    </div>
                    <p><?php echo htmlspecialchars($baker['description'] ?? ''); ?></p>
                    <div class="baker-contact">
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($baker['business_license'] ?? 'Not available'); ?></span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($baker['email']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="categories-nav">
            <button class="category-btn active" data-category="all">All Products</button>
            <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                <button class="category-btn" data-category="<?php echo $category['category_id']; ?>">
                    <?php echo htmlspecialchars($category['category_name']); ?>
                </button>
            <?php endwhile; ?>
        </div>

        <div class="products-grid">
            <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                <div class="product-card" data-category="<?php echo $product['category_id']; ?>">
                    <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                         class="product-image"
                         onerror="this.src='../assets/images/placeholder.jpg'">
                    <div class="product-info">
                        <h3 class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        <p class="product-category"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></p>
                        <p class="product-price">₹<?php echo number_format($product['price'], 2); ?></p>
                        <div class="product-meta">
                            <span class="product-type">
                                <?php echo ucfirst($product['product_type']); ?> | 
                                <?php echo ucfirst($product['cake_type']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script>
        // Category filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const categoryButtons = document.querySelectorAll('.category-btn');
            const products = document.querySelectorAll('.product-card');

            categoryButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const category = this.dataset.category;

                    // Update active button
                    categoryButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');

                    // Filter products
                    products.forEach(product => {
                        if (category === 'all' || product.dataset.category === category) {
                            product.style.display = 'block';
                        } else {
                            product.style.display = 'none';
                        }
                    });
                });
            });
        });
    </script>
</body>
</html> 