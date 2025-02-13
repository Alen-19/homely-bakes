<?php include "header.php"?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homely Bakes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="product.css">
    <script src="product.js"></script>
</head>
<body>
    <?php include "header.php"?>
    
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
                <!-- Products will be dynamically loaded here -->
            </div>
        </div>
    </section>

    <div id="cart-count">0</div>


</body>
</html>