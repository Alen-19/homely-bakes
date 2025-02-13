<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Baker Dashboard - Homely Bakes</title>
    <link rel="stylesheet" href="baker_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <img src="logo.png" alt="Homely Bakes">
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
                <button class="profile-button" id="profile-button">
                    <i class="fas fa-user-circle profile-icon"></i>
                    <span class="username"><?php echo htmlspecialchars($_SESSION['firstname'] ?? ''); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="profile-dropdown">
                    <a href="baker_profile.php" class="profile-link">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <?php if(isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'baker'): ?>
                        <a href="baker/" class="profile-link">
                            <i class="fas fa-store"></i> Dashboard
                        </a>
                    <?php endif; ?>
                    <a href="/homelybakes/logout.php" class="profile-link logout-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Products Section -->
            <section id="products-section" class="section active">
                <h2>My Products</h2>
                <div class="products-grid" id="baker-products">
                    <!-- Product Cards -->
                    <div class="product-card">
                        <img src="/api/placeholder/200/200" alt="Chocolate Cake">
                        <h3>Chocolate Cake</h3>
                        <p>₹499</p>
                        <div class="product-actions">
                            <button onclick="editProduct(1)">Edit</button>
                            <button onclick="deleteProduct(1)">Delete</button>
                        </div>
                    </div>
                    
                    <div class="product-card">
                        <img src="/api/placeholder/200/200" alt="Vanilla Cupcakes">
                        <h3>Vanilla Cupcakes</h3>
                        <p>₹299</p>
                        <div class="product-actions">
                            <button onclick="editProduct(2)">Edit</button>
                            <button onclick="deleteProduct(2)">Delete</button>
                        </div>
                    </div>
                    
                    <div class="product-card">
                        <img src="/api/placeholder/200/200" alt="Red Velvet Cake">
                        <h3>Red Velvet Cake</h3>
                        <p>₹599</p>
                        <div class="product-actions">
                            <button onclick="editProduct(3)">Edit</button>
                            <button onclick="deleteProduct(3)">Delete</button>
                        </div>
                    </div>
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
                    <input type="number" name="price" placeholder="Price" min="0" step="0.01" required>
                    <textarea name="description" placeholder="Product Description" required></textarea>
                    <input type="file" name="image" accept="image/*" required>
                    <button type="submit">Add Product</button>
                </form>
            </section>

            <!-- Add Category Section -->
            <section id="add-category-section" class="section">
                <h2>Add New Category</h2>
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

    <script src="baker_dashboard.js"></script>
</body>
</html>