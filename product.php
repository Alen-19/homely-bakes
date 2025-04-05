<?php
include 'header.php';
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

// Get all products with their availability status
$query = "SELECT p.*, r.first_name as baker_name, 
          COALESCE(AVG(rev.rating), 0) as average_rating,
          COUNT(DISTINCT rev.review_id) as review_count,
          p.is_active,
          CASE WHEN w.wishlist_id IS NOT NULL THEN 1 ELSE 0 END as in_wishlist
          FROM table_product p
          JOIN table_registration r ON p.baker_id = r.user_id
          LEFT JOIN table_orders o ON p.product_id = o.product_id
          LEFT JOIN table_reviews rev ON o.order_id = rev.order_id
          LEFT JOIN table_wishlist w ON p.product_id = w.product_id 
            AND w.user_id = ?
          GROUP BY p.product_id, r.first_name, p.is_active
          ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
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
    <style>
        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 8px;
        }

        .products-container {
            display: flex;
            gap: 2rem;
            margin-top: 2rem;
        }

        .filter-sidebar {
            width: 250px;
            background: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            height: fit-content;
        }

        .filter-group {
            margin-bottom: 1.5rem;
        }

        .filter-group h4 {
            margin-bottom: 0.8rem;
            color: #333;
            font-size: 1rem;
        }

        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .price-range {
            padding: 0.5rem 0;
        }

        .price-range input[type="range"] {
            width: 100%;
            margin-bottom: 0.5rem;
        }

        .price-inputs {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #666;
        }

        .filter-btn {
            width: 100%;
            padding: 0.8rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        #reset-filters {
            background-color: #f5f5f5;
            color: #333;
        }

        .filter-btn:hover {
            opacity: 0.9;
        }

        .baker-grid {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .product-card {
            position: relative;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.2s;
            display: flex;
            flex-direction: column;
            height: fit-content;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .product-info {
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .product-info h3 {
            margin: 0;
            font-size: 1.1rem;
            color: #333;
        }

        .baker-name {
            margin: 0;
            font-size: 0.9rem;
            color: #666;
        }

        .price {
            margin: 0;
            font-weight: 600;
            color: #2c3e50;
        }

        .product-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
            width: 100%;
        }

        .add-to-cart, .buy-now {
            flex: 1;
            padding: 0.6rem 0.8rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.2s ease;
            white-space: nowrap;
            text-align: center;
            min-width: 100px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .add-to-cart {
            background: linear-gradient(145deg, #4CAF50, #45a049);
            color: white;
            border: 1px solid #45a049;
        }

        .buy-now {
            background: linear-gradient(145deg, #2196F3, #1e88e5);
            color: white;
            border: 1px solid #1e88e5;
        }

        .add-to-cart:hover {
            background: linear-gradient(145deg, #45a049, #4CAF50);
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
        }

        .buy-now:hover {
            background: linear-gradient(145deg, #1e88e5, #2196F3);
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
        }

        .add-to-cart:active, .buy-now:active {
            transform: translateY(1px);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .wishlist-btn {
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 5px;
            font-size: 20px;
            transition: transform 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .wishlist-btn:hover {
            background: transparent;
            transform: scale(1.1);
        }

        .wishlist-btn i {
            color: #666;
            transition: color 0.2s ease;
        }

        .wishlist-btn:hover i.far {
            color: #ff4444;
        }

        .wishlist-btn.in-wishlist i {
            color: #ff4444;
        }

        .wishlist-btn i {
            pointer-events: none;
        }

        #toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #333;
            color: white;
            padding: 15px 25px;
            border-radius: 5px;
            display: none;
            z-index: 1000;
        }

        .search-bar {
            width: 100%;
            max-width: 800px;
            margin: 20px auto;
            padding: 0 15px;
        }

        .search-container {
            display: flex;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
        }

        .search-select {
            min-width: 180px;
            padding: 15px;
            border: none;
            background: #f8f9fa;
            font-size: 15px;
            color: #333;
            cursor: pointer;
            border-right: 1px solid #e0e0e0;
            transition: background-color 0.3s ease;
            font-weight: 500;
            padding-right: 35px;
        }

        .search-select:hover {
            background-color: #f0f0f0;
        }

        .search-select:focus {
            outline: none;
            background-color: #f0f0f0;
        }

        .search-input-wrapper {
            display: flex;
            flex: 1;
            align-items: center;
        }

        #search-input {
            flex: 1;
            padding: 15px 20px;
            border: none;
            font-size: 15px;
            color: #333;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        #search-input::placeholder {
            color: #999;
        }

        #search-input:focus {
            outline: none;
            background-color: #f8f9fa;
        }

        .search-button {
            padding: 12px 30px;
            margin: 5px;
            border: none;
            background: #4CAF50;
            color: white;
            font-size: 15px;
            font-weight: 500;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-button:hover {
            background: #45a049;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
        }

        .search-button:active {
            transform: translateY(0);
            box-shadow: none;
        }

        .unavailable-message {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #e74c3c;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
            z-index: 3;
        }

        .unavailable-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(231, 76, 60, 0.7); /* Semi-transparent red */
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .product-card:not(.unavailable):hover .unavailable-overlay {
            opacity: 0; /* Ensure overlay doesn’t show on hover for available products */
        }

        .product-card.unavailable .unavailable-overlay {
            opacity: 1; /* Show overlay for unavailable products */
        }

        .product-card.unavailable {
            pointer-events: none; /* Disable interactions with the entire card for unavailable products */
        }

        button:disabled {
            background: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .rating {
            display: flex;
            align-items: center;
            gap: 5px;
            margin: 5px 0;
            font-size: 0.9rem;
            color: #666;
        }

        .average-rating {
            background-color: #4CAF50; /* Green background like in the image */
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 600;
        }

        .star-rating {
            color: white; /* White star */
            font-size: 0.9rem;
        }

        .rating-count {
            font-size: 0.85rem;
            color: #666;
        }

        @media (max-width: 768px) {
            .search-container {
                flex-direction: column;
            }

            .search-select {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #e0e0e0;
            }

            .search-input-wrapper {
                flex-direction: column;
                width: 100%;
            }

            #search-input {
                width: 100%;
                border-bottom: 1px solid #e0e0e0;
            }

            .search-button {
                width: calc(100% - 10px);
                margin: 5px;
                justify-content: center;
            }
        }

        .search-container:focus-within {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            transform: translateY(-1px);
            transition: all 0.3s ease;
        }

        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0px 1000px white inset;
            transition: background-color 5000s ease-in-out 0s;
        }

        .search-bar input:hover,
        .search-bar select:hover {
            border-color: transparent !important;
        }

        .search-bar input,
        .search-bar select {
            padding: 12px !important;
            border: none !important;
        }
    </style>
</head>
<body>
    <div id="toast"></div>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-content">
            <h2>Connecting You to Homemade Goodness</h2>
            <p>Find the best homemade bakers near you.</p>
            <div class="search-bar">
                <div class="search-container">
                    <select id="search-type" class="search-select">
                        <option value="product">Search by Product</option>
                        <option value="baker">Search by Baker</option>
                        <option value="category">Search by Category</option>
                    </select>
                    <div class="search-input-wrapper">
                        <input type="text" id="search-input" placeholder="Enter your search term...">
                        <button id="search-button" class="search-button">
                            Search
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section id="products" class="section">
        <div class="container">
            <h2>Our Products</h2>
            <div class="products-container">
                <!-- Filter Sidebar -->
                <div class="filter-sidebar">
                    <h3>Filters</h3>
                    
                    <!-- Cake Type Filter -->
                    <div class="filter-group">
                        <h4>Cake Type</h4>
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" name="cake_type" value="regular"> Regular
                            </label>
                            <label>
                                <input type="checkbox" name="cake_type" value="premium"> Premium
                            </label>
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div class="filter-group">
                        <h4>Categories</h4>
                        <div class="checkbox-group" id="category-filters">
                            <?php
                            $category_query = "SELECT DISTINCT category_name FROM table_category";
                            $category_result = mysqli_query($conn, $category_query);
                            while ($category = mysqli_fetch_assoc($category_result)) {
                                echo '<label>
                                    <input type="checkbox" name="category" value="' . htmlspecialchars($category['category_name']) . '"> 
                                    ' . htmlspecialchars($category['category_name']) . '
                                </label>';
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Dietary Preference Filter -->
                    <div class="filter-group">
                        <h4>Dietary Preference</h4>
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" name="egg_preference" value="egg"> Contains Egg
                            </label>
                            <label>
                                <input type="checkbox" name="egg_preference" value="eggless"> Eggless
                            </label>
                        </div>
                    </div>

                    <!-- Reset Filters Button -->
                    <button id="reset-filters" class="filter-btn">Reset Filters</button>
                </div>

                <!-- Products Grid -->
                <div id="products-grid" class="baker-grid">
                    <!-- Products will be loaded here via AJAX -->
                </div>
            </div>
        </div>
    </section>

    <!-- Scripts -->
    <script src="header.js"></script>
    <script src="product.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Function to fetch and display products via AJAX
            function fetchProducts() {
                const productsGrid = document.getElementById('products-grid');
                productsGrid.style.opacity = '0.5'; // Show loading state

                fetch('fetch_products.php', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        productsGrid.innerHTML = data.html;
                        initializeProductButtons(); // Reinitialize buttons after updating the grid
                    } else {
                        productsGrid.innerHTML = `<p class="error">${data.message}</p>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching products:', error);
                    productsGrid.innerHTML = '<p class="error">Error loading products. Please try again later.</p>';
                })
                .finally(() => {
                    productsGrid.style.opacity = '1'; // Remove loading state
                });
            }

            // Initial fetch when the page loads
            fetchProducts();

            // Polling: Fetch products every 10 seconds to check for updates
            setInterval(fetchProducts, 10000);

            function applyFilters() {
                const selectedFilters = {
                    cakeTypes: Array.from(document.querySelectorAll('input[name="cake_type"]:checked')).map(cb => cb.value),
                    categories: Array.from(document.querySelectorAll('input[name="category"]:checked')).map(cb => cb.value),
                    dietary: Array.from(document.querySelectorAll('input[name="egg_preference"]:checked')).map(cb => cb.value)
                };

                // Show loading state
                document.getElementById('products-grid').style.opacity = '0.5';

                // Send filter data to server
                fetch('filter_products.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(selectedFilters)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('products-grid').innerHTML = data.html;
                        initializeProductButtons();
                    } else {
                        showToast(data.message || 'Error applying filters');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred while applying filters');
                })
                .finally(() => {
                    // Remove loading state
                    document.getElementById('products-grid').style.opacity = '1';
                });
            }

            // Add event listeners for all filter inputs
            document.querySelectorAll('input[name="cake_type"]').forEach(checkbox => {
                checkbox.addEventListener('change', applyFilters);
            });

            document.querySelectorAll('input[name="category"]').forEach(checkbox => {
                checkbox.addEventListener('change', applyFilters);
            });

            document.querySelectorAll('input[name="egg_preference"]').forEach(checkbox => {
                checkbox.addEventListener('change', applyFilters);
            });

            document.getElementById('reset-filters').addEventListener('click', function() {
                document.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                    cb.checked = false;
                });
                applyFilters();
            });

            function initializeProductButtons() {
                document.querySelectorAll('.wishlist-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const productId = this.dataset.productId;
                        const isInWishlist = this.classList.contains('in-wishlist');
                        const action = isInWishlist ? 'remove' : 'add';

                        fetch(`add_to_wishlist.php?product_id=${productId}&action=${action}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    if (action === 'add') {
                                        this.classList.add('in-wishlist');
                                        this.querySelector('i').classList.replace('far', 'fas');
                                    } else {
                                        this.classList.remove('in-wishlist');
                                        this.querySelector('i').classList.replace('fas', 'far');
                                    }
                                    showToast(data.message);
                                } else {
                                    if (data.message === 'Please login to add items to wishlist') {
                                        window.location.href = 'login.php';
                                    } else {
                                        showToast(data.message);
                                    }
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                showToast('An error occurred. Please try again.');
                            });
                    });
                });

                document.querySelectorAll('.add-to-cart:not([disabled])').forEach(button => {
                    button.addEventListener('click', function() {
                        const productId = this.getAttribute('data-product-id');
                        
                        const formData = new FormData();
                        formData.append('product_id', productId);
                        formData.append('quantity', 1);

                        fetch('add_to_cart.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showToast('Product added to cart successfully!');
                                setTimeout(() => {
                                    window.location.href = 'cart.php';
                                }, 1500);
                            } else {
                                if (data.message === 'Please login to add items to cart') {
                                    window.location.href = 'login.php';
                                } else {
                                    showToast(data.message);
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showToast('An error occurred. Please try again.');
                        });
                    });
                });
            }

            initializeProductButtons();

            const searchInput = document.getElementById('search-input');
            const searchButton = document.getElementById('search-button');
            const searchType = document.getElementById('search-type');
            const productsGrid = document.getElementById('products-grid');

            function performSearch() {
                const searchTerm = searchInput.value.trim();
                const searchCategory = searchType.value;

                if (!searchTerm) {
                    showToast('Please enter a search term');
                    return;
                }

                productsGrid.style.opacity = '0.5';

                const formData = new FormData();
                formData.append('search_term', searchTerm);
                formData.append('search_type', searchCategory);

                fetch('search_products.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        if (data.products && data.products.length > 0) {
                            productsGrid.innerHTML = '';
                            
                            data.products.forEach(product => {
                                const average_rating = product.average_rating !== 'N/A' ? product.average_rating : 0;
                                const rating_count = product.average_rating !== 'N/A' ? product.review_count : 0;
                                const review_count = product.review_count || 0;
                                const isUnavailable = !product.is_active;

                                const productCard = `
                                    <div class="product-card ${isUnavailable ? 'unavailable' : ''}">
                                        <div class="unavailable-overlay">${isUnavailable ? 'Unavailable' : ''}</div>
                                        ${isUnavailable ? '<span class="unavailable-message">Unavailable</span>' : ''}
                                        <img src="${product.image_url || 'assets/images/default-product.jpg'}" alt="${product.product_name}">
                                        <div class="product-info">
                                            <div class="product-header">
                                                <h3>${product.product_name}</h3>
                                                <button class="wishlist-btn ${product.in_wishlist ? 'in-wishlist' : ''}" data-product-id="${product.product_id}">
                                                    <i class="${product.in_wishlist ? 'fas' : 'far'} fa-heart"></i>
                                                </button>
                                            </div>
                                            <p class="baker-name">By: ${product.baker_name}</p>
                                            <p class="price">₹${parseFloat(product.price).toFixed(2)} /kg</p>
                                            <div class="rating">
                                                <span class="average-rating">${average_rating}</span>
                                                <span class="star-rating">★</span>
                                                <span class="rating-count">${rating_count} Ratings & ${review_count} Reviews</span>
                                            </div>
                                            <div class="product-buttons">
                                                <button class="add-to-cart me-2" data-product-id="${product.product_id}" ${!product.is_active ? 'disabled' : ''}>
                                                    Add to Cart
                                                </button>
                                                <button class="buy-now" onclick="window.location.href='place_order.php?product_id=${product.product_id}'" ${!product.is_active ? 'disabled' : ''}>
                                                    Buy Now
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                `;
                                productsGrid.innerHTML += productCard;
                            });
                            
                            initializeProductButtons();
                        } else {
                            productsGrid.innerHTML = '<p class="no-results">No products found matching your search.</p>';
                        }
                    } else {
                        showToast(data.message || 'Error performing search');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred while searching. Please try again.');
                })
                .finally(() => {
                    productsGrid.style.opacity = '1';
                });
            }

            if (searchButton) {
                searchButton.addEventListener('click', performSearch);
            }

            if (searchInput) {
                searchInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        performSearch();
                    }
                });
            }
        });
    </script>
</body>
</html>