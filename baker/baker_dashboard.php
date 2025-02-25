<?php
session_start();
include '../connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get baker_id from table_baker using user_id
if (!isset($_SESSION['baker_id'])) {
    $user_id = $_SESSION['user_id'];
    $query = "SELECT baker_id FROM table_baker WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($baker = mysqli_fetch_assoc($result)) {
        $_SESSION['baker_id'] = $baker['baker_id'];
    } else {
        // Create a new baker entry for the user
        $insert_query = "INSERT INTO table_baker (user_id) VALUES (?)";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, "i", $user_id);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            $_SESSION['baker_id'] = mysqli_insert_id($conn);
            $_SESSION['new_baker'] = true;  // Flag to indicate new baker
            header("Location: baker_profile.php?new=1");
            exit();
        } else {
            // If baker creation fails, redirect to login
            $_SESSION['error'] = "Failed to create baker profile. Please try again.";
            header("Location: ../login.php");
            exit();
        }
    }
}

// Check if baker is logged in and profile is complete
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] != 0) {
    header("Location: ../login.php");
    exit();
}

// Redirect to profile if new baker hasn't completed their profile
if (isset($_SESSION['new_baker']) && !isset($_GET['new'])) {
    header("Location: baker_profile.php?new=1");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Baker Dashboard - Homely Bakes</title>
    <link rel="stylesheet" href="baker_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="baker_dashboard.js" defer></script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <img src="../img/logo.png" alt="Homely Bakes" style="border-radius :10px;">
            </div>
            <nav>
                <ul>
                    <li class="active" data-section="products">
                        <i class="icon-products"></i>My Products
                    </li>
                    <li data-section="add-product">
                        <i class="icon-add"></i>Add Product
                    </li>
                    <li data-section="add-category">
                        <i class="icon-add"></i>Add Category
                    </li>
                    <li data-section="orders">
                        <i class="icon-orders"></i>Orders
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Profile Container -->
            <div class="profile-container">
                <button type="button" class="profile-button" id="profile-button">
                    <?php
                    // Get baker's profile image
                    $baker_id = $_SESSION['baker_id'];
                    $query = "SELECT profile_image FROM table_baker WHERE baker_id = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "i", $baker_id);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $baker = mysqli_fetch_assoc($result);
                    
                    if ($baker && !empty($baker['profile_image'])) {
                        echo '<img src="' . htmlspecialchars($baker['profile_image']) . '" alt="Profile" class="profile-image">';
                    } else {
                        echo '<i class="fas fa-user-circle profile-icon"></i>';
                    }
                    ?>
                    <span class="username"><?php echo htmlspecialchars($_SESSION['firstname'] ?? ''); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="profile-dropdown">
                    <a href="baker_profile.php" class="profile-link">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="/homelybakes/logout.php" class="profile-link logout-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Products Section -->
            <section id="products-section" class="section active">
                <h2>My Products</h2>
                <div class="products-grid" id="baker-products">
                    <?php
                    // Get baker_id from session
                    $baker_id = $_SESSION['baker_id'];
                    
                    // Fetch products for this baker
                    $query = "SELECT * FROM table_product WHERE baker_id = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "i", $baker_id);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);

                    if (!$result) {
                        echo '<p class="error">Error loading products: ' . mysqli_error($conn) . '</p>';
                    } else {
                        while ($product = mysqli_fetch_assoc($result)) {
                            $imageUrl = $product['image_url'] ? htmlspecialchars($product['image_url']) : 'assets/images/default-product.jpg';
                            ?>
                            <div class="product-card">
                                <img src="<?php echo $imageUrl; ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                                <p>₹<?php echo number_format($product['price'], 2); ?></p>
                                <div class="product-actions">
                                    <button onclick="editProduct(<?php echo $product['product_id']; ?>)">Edit</button>
                                    <button onclick="deleteProduct(<?php echo $product['product_id']; ?>)">Delete</button>
                                </div>
                            </div>
                            <?php
                        }
                        mysqli_free_result($result);
                    }
                    ?>
                </div>
            </section>

            <!-- Add Product Section -->
            <section id="add-product-section" class="section">
                <h2>Add New Product</h2>
                <form id="add-product-form" method="POST" enctype="multipart/form-data">
                    <input type="text" name="name" placeholder="Product Name" required>
                    <select name="category" required>
                        <option value="">Select Category</option>
                        <!-- Categories will be dynamically populated -->
                    </select>
                    <input type="number" name="price" placeholder="Price" min="1" max="10000"step="0.01" required>
                    <input type="number" name="stock" placeholder="Stock Quantity" min="1" max="100" required>
                    <textarea name="description" placeholder="Product Description" required></textarea>
                    <div class="image-upload-container">
                        <input type="file" name="image" id="product-image" accept="image/*" required>
                        <div class="image-preview-container" style="display: none;">
                            <img id="image-preview" src="#" alt="Product preview" style="max-width: 200px; max-height: 200px; margin: 10px 0;">
                            <button type="button" id="remove-image" class="remove-image-btn">Remove Image</button>
                        </div>
                    </div>
                    <button type="submit">Add Product</button>
                </form>
            </section>

            <!-- Add Category Section -->
            <section id="add-category-section" class="section">
                <h2>Add New Category</h2>
                <?php
                if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['category_name'])) {
                    // Enable error reporting
                    error_reporting(E_ALL);
                    ini_set('display_errors', 1);
                    
                    $category_name = trim($_POST['category_name']);
                    $category_description = trim($_POST['category_description']);
                    
                    // Use baker_id from session
                    if (isset($_SESSION['baker_id'])) {
                        $baker_id = $_SESSION['baker_id'];
                        
                        // Check if category name already exists for this baker
                        $check_stmt = $conn->prepare("SELECT category_id FROM table_category WHERE category_name = ? AND baker_id = ?");
                        $check_stmt->bind_param("si", $category_name, $baker_id);
                        $check_stmt->execute();
                        $check_result = $check_stmt->get_result();
                        
                        if ($check_result->num_rows > 0) {
                            echo '<script>alert("Category name already exists! Please choose a different name.");</script>';
                            $check_stmt->close();
                        } else {
                            $check_stmt->close();
                            
                            // Now insert the category
                            $stmt = $conn->prepare("INSERT INTO table_category (category_name, description, baker_id) VALUES (?, ?, ?)");
                            if (!$stmt) {
                                echo '<script>alert("Prepare failed: ' . addslashes($conn->error) . '");</script>';
                            } else {
                                $stmt->bind_param("ssi", $category_name, $category_description, $baker_id);
                                
                                if ($stmt->execute()) {
                                    echo '<script>
                                        alert("Category added successfully!");
                                        document.getElementById("add-category-form").reset();
                                    </script>';
                                } else {
                                    echo '<script>alert("Error adding category: ' . addslashes($stmt->error) . '");</script>';
                                }
                                $stmt->close();
                            }
                        }
                    } else {
                        echo '<script>
                            alert("Baker profile not found. Please complete your baker profile first.");
                            window.location.href = "baker_profile.php?new=1";
                        </script>';
                    }
                }
                ?>
                <form id="add-category-form" method="POST">
                    <input type="text" name="category_name" placeholder="Category Name" required>
                    <textarea name="category_description" placeholder="Category Description" required></textarea>
                    <button type="submit">Add Category</button>
                </form>
            </section>

            <!-- Orders Section -->
            <section id="orders-section" class="section">
                <h2>Orders</h2>
                <div class="orders-list" id="baker-orders">
                    <!-- Dynamic order list will be inserted here -->
                </div>
            </section>

            <!-- Profile Section -->
            <section id="profile-section" class="section">
                <h2>Baker Profile</h2>
                <form id="profile-form" method="POST">
                    <input type="text" name="bakery_name" placeholder="Bakery Name" required>
                    <input type="tel" name="contact_number" placeholder="Contact Number" required>
                    <textarea name="bakery_description" placeholder="Bakery Description" required></textarea>
                    <button type="submit">Update Profile</button>
                </form>
            </section>
        </main>
    </div>
</body>
</html>