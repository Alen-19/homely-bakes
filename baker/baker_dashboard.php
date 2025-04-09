<?php
session_start();
include '../connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if request is POST and user is logged in
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $data = json_decode(file_get_contents('php://input'), true);
    $order_id = $data['order_id'] ?? null;
    
    // Add missing closing brace here
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

// Add this near the top of the file after your session and database connection checks
if (isset($_SESSION['baker_id'])) {
    $baker_id = $_SESSION['baker_id'];
    
    // Update the main analytics query to include all orders
    $analytics_queries = "SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN status = 'delivered' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_orders,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
        SUM(total_price) as total_revenue,
        AVG(total_price) as average_order_value
    FROM table_orders 
    WHERE baker_id = ?";

    $stmt = $conn->prepare($analytics_queries);
    $stmt->bind_param("i", $baker_id);
    $stmt->execute();
    $analytics = $stmt->get_result()->fetch_assoc();

    // Update monthly revenue query to include all orders from table_orders and table_product
    $monthly_revenue_query = "SELECT 
        DATE_FORMAT(o.order_date, '%Y-%m') as month,
        COUNT(*) as order_count,
        SUM(o.total_price) as revenue
    FROM table_orders o
    JOIN table_product p ON o.product_id = p.product_id
    WHERE p.baker_id = ? 
        AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        AND o.status != 'rejected'
    GROUP BY DATE_FORMAT(o.order_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 6";

    $stmt = $conn->prepare($monthly_revenue_query);
    $stmt->bind_param("i", $_SESSION['baker_id']);
    $stmt->execute();
    $monthly_result = $stmt->get_result();
    $monthly_data = [];
    while ($row = $monthly_result->fetch_assoc()) {
        $monthly_data[] = $row;
    }

    // Reverse the array to show oldest to newest
    $monthly_data = array_reverse($monthly_data);

    // Update popular products query to include all orders
    $popular_products_query = "SELECT 
        p.product_name,
        COUNT(*) as order_count,
        SUM(o.quantity) as total_quantity,
        SUM(o.total_price) as total_revenue
    FROM table_orders o
    JOIN table_product p ON o.product_id = p.product_id
    WHERE o.baker_id = ?
    GROUP BY p.product_id, p.product_name
    ORDER BY order_count DESC
    LIMIT 5";

    $stmt = $conn->prepare($popular_products_query);
    $stmt->bind_param("i", $baker_id);
    $stmt->execute();
    $popular_products_result = $stmt->get_result();
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    /* Add to the :root section in each file */
    :root {
        --sidebar-width: 250px;
    }

    /* Add these new styles */
    body {
        margin: 0;
        padding: 0;
        min-height: 100vh;
        background-color: var(--background-color);
    }

    .container {
        padding: 20px;
        min-height: 100vh;
        transition: margin-left .5s;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .container {
            margin-left: 0 !important;
            padding: 15px;
        }
    }

    /* Add padding to account for the fixed header if you have one */
    .container {
        padding-top: 20px;
    }

    .order-tabs {
        margin-bottom: 20px;
        border-bottom: 1px solid #ddd;
        padding-bottom: 10px;
    }

    .tab-btn {
        padding: 8px 16px;
        margin-right: 10px;
        border: none;
        background: #f0f0f0;
        border-radius: 4px;
        cursor: pointer;
    }

    .tab-btn.active {
        background: #4CAF50;
        color: white;
    }

    .order-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        padding: 20px;
    }

    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    .order-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }

    .btn-accept {
        background: #4CAF50;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
    }

    .btn-reject {
        background: #f44336;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
    }

    .status-badge {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.9em;
        font-weight: bold;
    }

    .status-pending { background: #fff3cd; color: #856404; }
    .status-accepted { background: #d4edda; color: #155724; }
    .status-rejected { background: #f8d7da; color: #721c24; }
    .status-completed { background: #cce5ff; color: #004085; }
    .status-preparing { 
        background: #e3f2fd; 
        color: #1565c0; 
    }
    .status-prepared { 
        background: #d4edda; 
        color: #155724; 
    }

    .btn-complete {
        background: #2196F3;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .analytics-container {
        padding: 20px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-align: center;
    }

    .stat-card .icon {
        font-size: 2em;
        color: #4CAF50;
        margin-bottom: 10px;
    }

    .stat-card .value {
        font-size: 1.8em;
        font-weight: bold;
        color: #333;
        margin: 10px 0;
    }

    .stat-card .label {
        color: #666;
        font-size: 0.9em;
    }

    .chart-container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }

    .chart-title {
        font-size: 1.2em;
        color: #333;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    .popular-products {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .product-list {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .product-list th,
    .product-list td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .product-list th {
        background: #f8f9fa;
        font-weight: 600;
        color: #333;
    }

    .product-list tr:hover {
        background: #f8f9fa;
    }

    /* Add or update these styles */
    .product-card .status-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        /* Remove any animations if they exist */
        animation: none;
        /* Ensure no transitions are being applied */
        transition: none;
    }

    .product-card .status-badge.active {
        background-color: #4CAF50;
        color: white;
        /* Remove any animations if they exist */
        animation: none;
        /* Ensure no transitions are being applied */
        transition: none;
    }

    /* Add Category Section Styles */
    #add-category-section {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }

    #add-category-section h2 {
        color: #333;
        font-size: 24px;
        margin-bottom: 30px;
        padding-bottom: 10px;
        border-bottom: 2px solid #4CAF50;
    }

    #add-category-form {
        background: white;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    #add-category-form input[type="text"] {
        width: 100%;
        padding: 12px 15px;
        margin-bottom: 20px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
        transition: border-color 0.3s ease;
    }

    #add-category-form textarea {
        width: 100%;
        padding: 12px 15px;
        margin-bottom: 20px;
        border: 1px solid #ddd;
        border-radius: 4px;
        min-height: 120px;
        font-size: 16px;
        resize: vertical;
        font-family: inherit;
        transition: border-color 0.3s ease;
    }

    #add-category-form input[type="text"]:focus,
    #add-category-form textarea:focus {
        border-color: #4CAF50;
        outline: none;
        box-shadow: 0 0 5px rgba(76, 175, 80, 0.2);
    }

    #add-category-form button {
        background-color: #4CAF50;
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 500;
        transition: background-color 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    #add-category-form button:hover {
        background-color: #45a049;
    }

    #add-category-form button:active {
        transform: translateY(1px);
    }

    #add-category-form button::before {
        content: '+';
        font-size: 20px;
        font-weight: bold;
    }

    /* Add placeholder styling */
    #add-category-form input::placeholder,
    #add-category-form textarea::placeholder {
        color: #999;
    }

    /* Add success message styling */
    .success-message {
        background-color: #d4edda;
        color: #155724;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Add error message styling */
    .error-message {
        background-color: #f8d7da;
        color: #721c24;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Form field wrapper */
    .form-field {
        margin-bottom: 20px;
    }

    .form-field label {
        display: block;
        margin-bottom: 8px;
        color: #333;
        font-weight: 500;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        #add-category-section {
            padding: 15px;
        }
        
        #add-category-form {
            padding: 20px;
        }
        
        #add-category-form button {
            width: 100%;
            justify-content: center;
        }
    }

    .modal {
        display: block;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }

    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 600px;
        border-radius: 8px;
        max-height: 90vh;
        overflow-y: auto;
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }

    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        background-color: #ff6b6b;
        color: white;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        display: none;
        z-index: 1000;
        font-size: 16px;
        animation: slideIn 0.3s ease-out;
        min-width: 200px;
        text-align: center;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    /* Add new status badge styles */
    .status-prepared { 
        background: #e8f5e9; 
        color: #2e7d32; 
    }
    .status-out_for_delivery { 
        background: #e3f2fd; 
        color: #1565c0; 
    }

    /* Add new button styles */
    .btn-complete.out-for-delivery {
        background: #1565c0;
    }
    .btn-complete.out-for-delivery:hover {
        background: #0d47a1;
    }

    /* Add new CSS for delivered status */
    .status-delivered { 
        background: #cce5ff; 
        color: #004085; 
    }

    /* Update the orders list container */
    #orders-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        padding: 20px;
    }

    /* Update the order card styles */
    .order-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 15px;
        font-size: 0.9em;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        padding-bottom: 8px;
        border-bottom: 1px solid #eee;
    }

    .order-header h3 {
        font-size: 1.1em;
        margin: 0;
    }

    .order-details {
        flex-grow: 1;
    }

    .order-details p {
        margin: 5px 0;
        line-height: 1.4;
    }

    .order-actions {
        margin-top: 10px;
        display: flex;
        gap: 8px;
    }

    .btn-accept, .btn-reject, .btn-complete {
        padding: 6px 12px;
        font-size: 0.9em;
    }

    /* Update status badge styles */
    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.8em;
        font-weight: 500;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        #orders-list {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        }
    }

    @media (max-width: 480px) {
        #orders-list {
            grid-template-columns: 1fr;
        }
    }

    /* Chat styles */
    .chat-container {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 350px;
        height: 500px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        display: none;
        flex-direction: column;
        z-index: 1000;
    }

    .chat-header {
        padding: 15px;
        background: #4CAF50;
        color: white;
        border-radius: 10px 10px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .chat-messages {
        flex-grow: 1;
        padding: 15px;
        overflow-y: auto;
    }

    .message {
        margin-bottom: 10px;
        max-width: 80%;
    }

    .message.sent {
        margin-left: auto;
        background: #4CAF50;
        color: white;
        padding: 8px 12px;
        border-radius: 15px 15px 0 15px;
    }

    .message.received {
        margin-right: auto;
        background: #f0f0f0;
        padding: 8px 12px;
        border-radius: 15px 15px 15px 0;
    }

    .chat-input {
        padding: 15px;
        border-top: 1px solid #eee;
        display: flex;
        gap: 10px;
    }

    .chat-input input {
        flex-grow: 1;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 20px;
        outline: none;
    }

    .chat-input button {
        padding: 8px 15px;
        background: #4CAF50;
        color: white;
        border: none;
        border-radius: 20px;
        cursor: pointer;
    }

    .chat-input button:hover {
        background: #45a049;
    }

    .chat-toggle {
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 15px;
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        z-index: 999;
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .chat-toggle i {
        font-size: 24px;
    }

    .existing-categories {
        margin-top: 40px;
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .existing-categories h3 {
        color: #333;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #4CAF50;
    }

    .categories-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .category-card {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .category-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .category-content h4 {
        color: #333;
        margin: 0 0 10px 0;
        font-size: 1.1em;
    }

    .category-content p {
        color: #666;
        margin: 0;
        font-size: 0.9em;
        line-height: 1.4;
    }

    .delete-category-btn {
        margin-top: 15px;
        padding: 8px 12px;
        background-color: #dc3545;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        transition: background-color 0.2s ease;
    }

    .delete-category-btn:hover {
        background-color: #c82333;
    }

    .delete-category-btn i {
        font-size: 0.9em;
    }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header with Navigation -->
        <header class="main-header">
            <div class="logo">
                <img src="../img/logo.png" alt="Homely Bakes">
            </div>
            <nav class="main-nav">
                <ul>
                    <li class="active" data-section="products">
                        <i class="fas fa-box"></i>
                        <span>My Products</span>
                    </li>
                    <li data-section="add-product">
                        <i class="fas fa-plus-circle"></i>
                        <span>Add Product</span>
                    </li>
                    <li data-section="add-category">
                        <i class="fas fa-folder-plus"></i>
                        <span>Add Category</span>
                    </li>
                    <li data-section="orders">
                        <i class="fas fa-shopping-bag"></i>
                        <span>Orders</span>
                    </li>
                    <li data-section="analytics">
                        <i class="fas fa-chart-line"></i>
                        <span>Analytics</span>
                    </li>
                </ul>
            </nav>
            
            <!-- Availability toggle moved to the left of profile -->
            <div class="header-actions">
                <div class="availability-toggle">
                    <?php
                    // Get baker's current availability status
                    $query = "SELECT availability_status FROM table_baker WHERE baker_id = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "i", $_SESSION['baker_id']);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $baker = mysqli_fetch_assoc($result);
                    $isAvailable = $baker['availability_status'] ?? 1; // Default to available
                    ?>
                    <button type="button" id="statusToggleBtn" class="status-toggle-btn <?php echo $isAvailable ? 'available' : 'unavailable'; ?>" 
                            onclick="toggleBakerStatus(<?php echo $isAvailable; ?>)">
                        <i class="fas <?php echo $isAvailable ? 'fa-store' : 'fa-store-slash'; ?>"></i>
                        <span><?php echo $isAvailable ? 'Open' : 'Closed'; ?></span>
                    </button>
                </div>

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
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Products Section -->
            <section id="products-section" class="section active">
                <h2>My Products</h2>
                <div class="products-grid" id="baker-products">
                    <!-- Products will be loaded via JavaScript -->
                </div>
            </section>

            <!-- Add Product Section -->
            <section id="add-product-section" class="section">
                <h2>Add New Product</h2>
                <form id="add-product-form" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Cake Name</label>
                        <input type="text" id="name" name="name" placeholder="Enter cake name" required>
                    </div>

                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <?php
                            // Fetch categories for this baker
                            $query = "SELECT category_id, category_name FROM table_category WHERE baker_id = ?";
                            $stmt = mysqli_prepare($conn, $query);
                            mysqli_stmt_bind_param($stmt, "i", $_SESSION['baker_id']);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            
                            while ($category = mysqli_fetch_assoc($result)) {
                                echo '<option value="' . $category['category_id'] . '">' . htmlspecialchars($category['category_name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group half">
                            <label for="price">Price (₹)</label>
                            <input type="number" id="price" name="price" placeholder="Enter price" min="1" max="10000" step="0.01" required>
                        </div>
                        
                        <div class="form-group half">
                            <label for="weight">Weight (kg)</label>
                            <input type="number" id="weight" name="weight" placeholder="Enter weight" min="0.5" max="10" step="0.5" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Product Type</label>
                        <div class="radio-options">
                            <div class="radio-option">
                                <input type="radio" id="with-egg" name="productType" value="egg" checked>
                                <label for="with-egg">With Egg</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="eggless" name="productType" value="eggless">
                                <label for="eggless">Eggless</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Cake Type</label>
                        <div class="radio-options">
                            <div class="radio-option">
                                <input type="radio" id="regular" name="cakeType" value="regular" checked>
                                <label for="regular">Regular</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="premium" name="cakeType" value="premium">
                                <label for="premium">Premium</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" placeholder="Enter detailed description of the cake" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="product-image">Cake Image</label>
                        <div class="image-upload-container">
                            <input type="file" name="image" id="product-image" accept="image/*" required>
                            <div class="image-preview-container" style="display: none;">
                                <img id="image-preview" src="#" alt="Cake preview">
                                <button type="button" id="remove-image" class="remove-image-btn">Remove Image</button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">Add Cake</button>
                </form>
            </section>

            <!-- Add Category Section -->
            <section id="add-category-section" class="section">
                <h2>Categories Management</h2>
                
                <!-- Add Category Form -->
                <form id="add-category-form" method="POST">
                    <div class="form-field">
                        <label for="category_name">Category Name</label>
                        <input type="text" id="category_name" name="category_name" placeholder="Enter category name" required>
                    </div>
                    
                    <div class="form-field">
                        <label for="category_description">Category Description</label>
                        <textarea id="category_description" name="category_description" 
                            placeholder="Enter detailed description of the category" required></textarea>
                    </div>
                    
                    <button type="submit">Add Category</button>
                </form>

                <!-- Existing Categories List -->
                <div class="existing-categories">
                    <h3>Existing Categories</h3>
                    <div class="categories-grid" id="categories-list">
                        <?php
                        // Fetch categories for this baker
                        $query = "SELECT category_id, category_name, description FROM table_category WHERE baker_id = ?";
                        $stmt = mysqli_prepare($conn, $query);
                        mysqli_stmt_bind_param($stmt, "i", $_SESSION['baker_id']);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        
                        while ($category = mysqli_fetch_assoc($result)) {
                            echo '<div class="category-card">';
                            echo '<div class="category-content">';
                            echo '<h4>' . htmlspecialchars($category['category_name']) . '</h4>';
                            echo '<p>' . htmlspecialchars($category['description']) . '</p>';
                            echo '</div>';
                            echo '<button class="delete-category-btn" onclick="deleteCategory(' . $category['category_id'] . ')">';
                            echo '<i class="fas fa-trash"></i> Delete';
                            echo '</button>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </section>

            <!-- Orders Section -->
            <section id="orders-section" class="section">
                <h2>My Orders</h2>
                
                <div class="order-tabs">
                    <button class="tab-btn active" data-status="pending">Pending</button>
                    <button class="tab-btn" data-status="accepted">Accepted</button>
                    <button class="tab-btn" data-status="rejected">Rejected</button>
                    <button class="tab-btn" data-status="paid">Paid Orders</button>
                    <button class="tab-btn" data-status="preparing">Preparing</button>
                    <button class="tab-btn" data-status="prepared">Prepared</button>
                    <button class="tab-btn" data-status="out_for_delivery">Out for Delivery</button>
                    <button class="tab-btn" data-status="delivered">Delivered</button>
                </div>

                <div id="orders-list"></div>
            </section>

            <!-- Profile Section -->
            <section id="profile-section" class="section">
                <h2>Baker Profile</h2>
                <form id="profile-form" enctype="multipart/form-data">
                    <input type="file" id="profile_photo" name="profile_photo" accept="image/*">
                    <input type="text" name="bakery_name" placeholder="Bakery Name" required>
                    <input type="tel" name="contact_number" placeholder="Contact Number" required>
                    <textarea name="bakery_description" placeholder="Bakery Description" required></textarea>
                    <button type="submit">Update Profile</button>
                </form>
            </section>

            <!-- Analytics Section -->
            <div class="content-section" id="analytics-section">
                <div class="analytics-container">
                    <h2 class="section-title">
                        <i class="fas fa-chart-line"></i>
                        Analytics Dashboard
                    </h2>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="icon"><i class="fas fa-shopping-bag"></i></div>
                            <div class="value"><?php echo $analytics['total_orders']; ?></div>
                            <div class="label">Total Orders</div>
                        </div>
                        <div class="stat-card">
                            <div class="icon"><i class="fas fa-check-circle"></i></div>
                            <div class="value"><?php echo $analytics['completed_orders']; ?></div>
                            <div class="label">Completed Orders</div>
                        </div>
                        <div class="stat-card">
                            <div class="icon"><i class="fas fa-rupee-sign"></i></div>
                            <div class="value">₹<?php echo number_format($analytics['total_revenue'], 2); ?></div>
                            <div class="label">Total Revenue</div>
                        </div>
                        <div class="stat-card">
                            <div class="icon"><i class="fas fa-chart-bar"></i></div>
                            <div class="value">₹<?php echo number_format($analytics['average_order_value'], 2); ?></div>
                            <div class="label">Average Order Value</div>
                        </div>
                    </div>

                    <div class="chart-container" style="height: 400px;">
                        <h3 class="chart-title">Monthly Revenue & Orders</h3>
                        <canvas id="revenueChart"></canvas>
                    </div>

                    <div class="popular-products">
                        <h3 class="section-title">
                            <i class="fas fa-star"></i>
                            Popular Products
                        </h3>
                        <table class="product-list">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Orders</th>
                                    <th>Total Quantity</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($product = $popular_products_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td><?php echo $product['order_count']; ?></td>
                                    <td><?php echo $product['total_quantity']; ?> kg</td>
                                    <td>₹<?php echo number_format($product['total_revenue'], 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <div id="duplicateNotification" class="notification">
        Category already exists!
    </div>

    <!-- Chat Interface -->
    <button class="chat-toggle" onclick="toggleChat()">
        
    </button>

    <div class="chat-container" id="chatContainer">
        <div class="chat-header">
            <span>Chat with Customer</span>
            <i class="fas fa-times" onclick="toggleChat()" style="cursor: pointer;"></i>
        </div>
        <div class="chat-messages" id="chatMessages">
            <!-- Messages will be loaded here -->
        </div>
        <div class="chat-input">
            <input type="text" id="messageInput" placeholder="Type your message...">
            <button onclick="sendMessage()">Send</button>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        loadOrders('pending');
        
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                loadOrders(this.dataset.status);
            });
        });
        

        // Add analytics section to the sections list
        const sections = ['products', 'add-product', 'add-category', 'orders', 'analytics'];
        
        // Update the click handler for navigation items
        document.querySelectorAll('.main-nav li').forEach(item => {
            item.addEventListener('click', function() {
                const section = this.getAttribute('data-section');
                sections.forEach(s => {
                    const sectionElement = document.getElementById(`${s}-section`);
                    const navItem = document.querySelector(`[data-section="${s}"]`);
                    
                    if (sectionElement) {
                        if (s === section) {
                            sectionElement.style.display = 'block';
                            navItem.classList.add('active');
                            if (s === 'analytics') {
                                // Initialize chart when analytics section is shown
                                initializeAnalyticsChart();
                            }
                        } else {
                            sectionElement.style.display = 'none';
                            navItem.classList.remove('active');
                        }
                    }
                });
            });
        });

        // Initialize chart if analytics section is active on page load
        if (document.getElementById('analytics-section').style.display !== 'none') {
            initializeAnalyticsChart();
        }

        // Add click handlers for edit buttons
        document.querySelectorAll('[data-section]').forEach(item => {
            item.addEventListener('click', function() {
                const section = this.getAttribute('data-section');
                switchSection(section);
            });
        });

        // Initialize edit buttons
        initializeEditButtons();
    });

    function loadOrders(status) {
        let endpoint = `get_baker_orders.php?status=${status}`;
        
        // Use different endpoint for paid orders
        if (status === 'paid') {
            endpoint = 'get_paid_orders.php';
        }
        
        fetch(endpoint)
            .then(response => response.json())
            .then(orders => {
                const ordersList = document.getElementById('orders-list');
                ordersList.innerHTML = '';

                if (orders.length === 0) {
                    ordersList.innerHTML = `<p class="no-orders">No ${status} orders found</p>`;
                    return;
                }

                // Sort orders by order_id in ascending order
                orders.sort((a, b) => a.order_id - b.order_id);

                orders.forEach(order => {
                    const orderCard = createOrderCard(order, status);
                    ordersList.appendChild(orderCard);
                });
            })
            .catch(error => console.error('Error:', error));
    }

    function makeAsPrepared(orderId) {
        if (!confirm('Are you sure you want to mark this order as prepared?')) return;

        fetch('update_order_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId,
                status: 'prepared'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Order status updated to prepared');
                loadOrders('preparing'); // Refresh the preparing orders list
            } else {
                alert(data.error || 'Error updating order status');
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function makeAsPreparing(orderId) {
        if (!confirm('Are you sure you want to mark this order as preparing?')) return;

        fetch('update_order_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId,
                status: 'preparing'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Order status updated to preparing');
                loadOrders('paid'); // Refresh the paid orders list
            } else {
                alert(data.error || 'Error updating order status');
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function createOrderCard(order, status) {
        const card = document.createElement('div');
        card.className = 'order-card';
        
        card.innerHTML = `
            <div class="order-header">
                <h3>Order #${order.order_id}</h3>
                <span class="status-badge status-${order.status}">${order.status}</span>
            </div>
            <div class="order-details">
                <p><strong>Customer:</strong> ${order.first_name} ${order.last_name}</p>
                <p><strong>Mobile:</strong> ${order.mobile_number}</p>
                <p><strong>Product:</strong> ${order.product_name}</p>
                <p><strong>Quantity:</strong> ${order.quantity} kg</p>
                <p><strong>Total Price:</strong> ₹${order.total_price}</p>
                <p><strong>Order Date:</strong> ${new Date(order.order_date).toLocaleString()}</p>
                <p><strong>Delivery Address:</strong><br>${order.delivery_address}</p>
                ${order.special_instructions ? `
                    <p><strong>Special Instructions:</strong><br>${order.special_instructions}</p>
                ` : ''}
            </div>
            ${order.status === 'pending' ? `
                <div class="order-actions">
                    <button onclick="handleOrder(${order.order_id}, 'accepted')" class="btn-accept">Accept Order</button>
                    <button onclick="handleOrder(${order.order_id}, 'rejected')" class="btn-reject">Reject Order</button>
                </div>
            ` : ''}
            ${status === 'paid' ? `
                <div class="order-actions">
                    <button onclick="makeAsPreparing(${order.order_id})" class="btn-accept">Mark as Preparing</button>
                </div>
            ` : ''}
            ${order.status === 'preparing' ? `
                <div class="order-actions">
                    <button onclick="makeAsPrepared(${order.order_id})" class="btn-complete">
                        <i class="fas fa-check"></i> Mark as Prepared
                    </button>
                </div>
            ` : ''}
            ${order.status === 'prepared' ? `
                <div class="order-actions">
                    <button onclick="makeAsOutForDelivery(${order.order_id})" class="btn-complete">
                        <i class="fas fa-truck"></i> Mark as Out for Delivery
                    </button>
                </div>
            ` : ''}
            ${order.status === 'out_for_delivery' ? `
                <div class="order-actions">
                    <button onclick="makeAsDelivered(${order.order_id})" class="btn-complete">
                        <i class="fas fa-check-circle"></i> Mark as Delivered
                    </button>
                </div>
            ` : ''}
            <!-- Add chat button -->
            <button class="btn btn-primary" onclick="toggleChat(${order.order_id}, ${order.user_id})">
                <i class="fas fa-comments"></i> Chat with Customer
            </button>
        `;
        
        return card;
    }

    function handleOrder(orderId, status) {
        if (!confirm(`Are you sure you want to ${status} this order?`)) return;

        fetch('handle_baker_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId,
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Order ${status} successfully`);
                loadOrders('pending');
            } else {
                alert(data.error || 'Error updating order');
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function initializeAnalyticsChart() {
        const monthlyData = <?php echo json_encode($monthly_data); ?>;
        console.log('Monthly Data:', monthlyData); // Debug log

        if (!monthlyData || monthlyData.length === 0) {
            console.log('No monthly data available');
            return;
        }

        const ctx = document.getElementById('revenueChart');
        if (!ctx) {
            console.log('Canvas element not found');
            return;
        }

        const labels = monthlyData.map(item => {
            const [year, month] = item.month.split('-');
            const date = new Date(year, month - 1);
            return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        });
        
        const revenue = monthlyData.map(item => parseFloat(item.revenue) || 0);
        const orderCounts = monthlyData.map(item => parseInt(item.order_count) || 0);

        console.log('Labels:', labels);
        console.log('Revenue:', revenue);
        console.log('Order Counts:', orderCounts);

        // Destroy existing chart if it exists
        const existingChart = Chart.getChart(ctx);
        if (existingChart) {
            existingChart.destroy();
        }

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: revenue,
                    backgroundColor: 'rgba(76, 175, 80, 0.2)',
                    borderColor: 'rgba(76, 175, 80, 1)',
                    borderWidth: 1,
                    yAxisID: 'y'
                }, {
                    label: 'Number of Orders',
                    data: orderCounts,
                    type: 'line',
                    borderColor: '#2196F3',
                    backgroundColor: 'transparent',
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue (₹)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '₹' + value.toLocaleString();
                            }
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Number of Orders'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.dataset.label === 'Revenue (₹)') {
                                    label += '₹' + context.parsed.y.toLocaleString();
                                } else {
                                    label += context.parsed.y;
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }

    function initializeEditButtons() {
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.getAttribute('data-product-id');
                if (productId) {
                    editProduct(productId);
                }
            });
        });
    }

    function editProduct(productId) {
    // Switch to add product section
    switchSection('add-product');

    // Show loading state
    const titleElement = document.querySelector('#add-product-section h2');
    if (titleElement) {
        titleElement.textContent = 'Loading...';
    }

    // Fetch product details
    fetch(`get_product_details.php?product_id=${productId}`)
        .then(response => response.json())
        .then(response => {
            console.log('Product data received:', response); // Debug: Log the response

            if (!response || response.error) {
                throw new Error(response.error || 'Failed to load product details');
            }

            const product = response;

            // Update form title and button
            if (titleElement) {
                titleElement.textContent = 'Edit Product';
            }

            const submitBtn = document.querySelector('.submit-btn');
            if (submitBtn) {
                submitBtn.textContent = 'Update Product';
            }

            // Update form
            const form = document.getElementById('add-product-form');
            if (!form) return;

            form.setAttribute('action', 'update_product.php');
            form.setAttribute('method', 'POST');
            form.setAttribute('enctype', 'multipart/form-data');

            // Add or update product ID field
            let productIdInput = form.querySelector('input[name="product_id"]');
            if (!productIdInput) {
                productIdInput = document.createElement('input');
                productIdInput.type = 'hidden';
                productIdInput.name = 'product_id';
                form.appendChild(productIdInput);
            }
            productIdInput.value = productId;

            // Update form fields
            const fields = {
                'name': product.product_name || '',
                'category': product.category_id || '',
                'price': product.price || '',
                'weight': product.weight || '',
                'description': product.description || ''
            };

            console.log('Fields to set:', fields); // Debug: Log the fields being set

            // Safely update each field
            Object.entries(fields).forEach(([id, value]) => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = value;
                    console.log(`Set ${id} to: ${value}`); // Debug: Confirm each field is set
                } else {
                    console.warn(`Element with ID ${id} not found`); // Debug: Warn if field is missing
                }
            });

            // Update radio buttons
            if (product.product_type) {
                const productTypeRadio = form.querySelector(`input[name="productType"][value="${product.product_type}"]`);
                if (productTypeRadio) {
                    productTypeRadio.checked = true;
                    console.log(`Set productType to: ${product.product_type}`); // Debug
                }
            }

            if (product.cake_type) {
                const cakeTypeRadio = form.querySelector(`input[name="cakeType"][value="${product.cake_type}"]`);
                if (cakeTypeRadio) {
                    cakeTypeRadio.checked = true;
                    console.log(`Set cakeType to: ${product.cake_type}`); // Debug
                }
            }

            // Update image preview
            const imagePreviewContainer = form.querySelector('.image-preview-container');
            const imagePreview = form.querySelector('#image-preview');
            if (imagePreviewContainer && imagePreview) {
                if (product.image_url) {
                    // Adjust the base path to navigate up one directory from /HomelyBakes/baker/ to /HomelyBakes/
                    const basePath = '../'; // Navigate up one directory
                    const imageUrl = product.image_url.startsWith('http') ? product.image_url : basePath + product.image_url;
                    console.log('Setting image preview URL:', imageUrl); // Debug: Log the image URL

                    // Test the image URL by creating a temporary image element
                    const testImage = new Image();
                    testImage.src = imageUrl;
                    testImage.onload = () => {
                        console.log('Image loaded successfully:', imageUrl);
                        imagePreview.src = imageUrl;
                        imagePreviewContainer.style.display = 'block';
                    };
                    testImage.onerror = () => {
                        console.warn('Failed to load image:', imageUrl);
                        imagePreview.src = '../assets/images/placeholder.jpg'; // Fallback image
                        imagePreviewContainer.style.display = 'block';
                    };
                } else {
                    console.log('No image_url provided, hiding preview'); // Debug
                    imagePreview.src = '#';
                    imagePreviewContainer.style.display = 'none';
                }
            } else {
                console.warn('Image preview elements not found'); // Debug
            }

            // Make image upload optional
            const imageInput = form.querySelector('#product-image');
            if (imageInput) {
                imageInput.removeAttribute('required');
            }
        })
        .catch(error => {
            console.error('Error in editProduct:', error);
            alert('Error loading product details. Please try again.');
            if (titleElement) {
                titleElement.textContent = 'Add New Product';
            }
        });
    }
       
          

    function switchSection(sectionName) {
        const sections = ['products', 'add-product', 'add-category', 'orders', 'analytics'];
        sections.forEach(section => {
            const sectionElement = document.getElementById(`${section}-section`);
            const navItem = document.querySelector(`[data-section="${section}"]`);
            
            if (sectionElement) {
                sectionElement.style.display = section === sectionName ? 'block' : 'none';
            }
            
            if (navItem) {
                if (section === sectionName) {
                    navItem.classList.add('active');
                } else {
                    navItem.classList.remove('active');
                }
            }
        });
    }

    document.getElementById('add-category-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const categoryName = document.getElementById('category_name').value;
        const categoryDescription = document.getElementById('category_description').value;
        
        try {
            const response = await fetch('add_category.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `category_name=${encodeURIComponent(categoryName)}&category_description=${encodeURIComponent(categoryDescription)}`
            });
            
            const data = await response.json();
            console.log('Response:', data); // For debugging
            
            const notification = document.getElementById('duplicateNotification');
            notification.style.display = 'block';
            
            if (data.success === false) {
                notification.style.backgroundColor = '#ff6b6b';
                notification.textContent = 'Category already exists!';
            } else if (data.success === true) {
                this.reset();
                notification.style.backgroundColor = '#4CAF50';
                notification.textContent = 'Category added successfully!';
            } else {
                throw new Error(data.message || 'Failed to add category');
            }
            
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
            
        } catch (error) {
            console.error('Error:', error);
            const notification = document.getElementById('duplicateNotification');
            notification.style.display = 'block';
            notification.style.backgroundColor = '#ff6b6b';
            notification.textContent = 'Error adding category. Please try again.';
            
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }
    });

    document.getElementById('profile_photo').addEventListener('change', async function(e) {
        const formData = new FormData();
        formData.append('profile_photo', e.target.files[0]);
        
        try {
            const response = await fetch('update_profile.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            const notification = document.getElementById('duplicateNotification');
            notification.style.display = 'block';
            
            if (data.success) {
                notification.style.backgroundColor = '#4CAF50';
                notification.textContent = 'Profile photo updated successfully!';
                
                // Refresh the profile photo display if it exists
                const profileImg = document.querySelector('.profile-photo img');
                if (profileImg) {
                    profileImg.src = data.photo_url + '?t=' + new Date().getTime();
                }
            } else {
                notification.style.backgroundColor = '#ff6b6b';
                notification.textContent = data.message || 'Failed to update profile photo';
            }
            
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
            
        } catch (error) {
            console.error('Error:', error);
            const notification = document.getElementById('duplicateNotification');
            notification.style.display = 'block';
            notification.style.backgroundColor = '#ff6b6b';
            notification.textContent = 'Error updating profile photo. Please try again.';
            
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }
    });

    // Add new function for marking order as out for delivery
    function makeAsOutForDelivery(orderId) {
        if (!confirm('Are you sure you want to mark this order as out for delivery?')) return;

        fetch('update_order_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId,
                status: 'out_for_delivery'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Order status updated to out for delivery');
                loadOrders('prepared'); // Refresh the prepared orders list
            } else {
                alert(data.error || 'Error updating order status');
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Add new function for marking order as delivered
    function makeAsDelivered(orderId) {
        if (!confirm('Are you sure you want to mark this order as delivered?')) return;

        fetch('update_order_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId,
                status: 'delivered'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Order status updated to delivered');
                loadOrders('out_for_delivery'); // Refresh the out for delivery orders list
            } else {
                alert(data.error || 'Error updating order status');
            }
        })
        .catch(error => console.error('Error:', error));
    }

    let currentOrderId = null;
    let currentCustomerId = null;
    let chatInterval = null;

    function toggleChat(orderId = null, customerId = null) {
        const chatContainer = document.getElementById('chatContainer');
        const isVisible = chatContainer.style.display === 'flex';
        
        if (orderId) {
            currentOrderId = orderId;
            currentCustomerId = customerId;
            chatContainer.style.display = 'flex';
            loadMessages();
            // Start polling for new messages
            if (chatInterval) clearInterval(chatInterval);
            chatInterval = setInterval(loadMessages, 5000);
        } else {
            chatContainer.style.display = isVisible ? 'none' : 'flex';
            if (isVisible && chatInterval) {
                clearInterval(chatInterval);
                chatInterval = null;
            }
        }
    }

    function loadMessages() {
        if (!currentOrderId) return;
        
        fetch(`../chat_api.php?order_id=${currentOrderId}`)
            .then(response => response.json())
            .then(data => {
                if (data.messages) {
                    const chatMessages = document.getElementById('chatMessages');
                    chatMessages.innerHTML = '';
                    
                    data.messages.forEach(message => {
                        const messageDiv = document.createElement('div');
                        messageDiv.className = `message ${message.sender_id === <?php echo $_SESSION['user_id']; ?> ? 'sent' : 'received'}`;
                        messageDiv.textContent = message.message;
                        chatMessages.appendChild(messageDiv);
                    });
                    
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
            })
            .catch(error => console.error('Error loading messages:', error));
    }

    function sendMessage() {
        const messageInput = document.getElementById('messageInput');
        const message = messageInput.value.trim();
        
        if (!message || !currentOrderId || !currentCustomerId) return;
        
        fetch('../chat_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                order_id: currentOrderId,
                receiver_id: currentCustomerId,
                message: message
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageInput.value = '';
                loadMessages();
            }
        })
        .catch(error => console.error('Error sending message:', error));
    }

    // Add event listener for Enter key in message input
    document.getElementById('messageInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    // Add this JavaScript function for category deletion
    function deleteCategory(categoryId) {
        if (!confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
            return;
        }

        fetch('delete_category.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                category_id: categoryId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success notification
                const notification = document.getElementById('duplicateNotification');
                notification.textContent = 'Category deleted successfully!';
                notification.style.backgroundColor = '#4CAF50';
                notification.style.display = 'block';
                
                // Refresh the categories list
                location.reload();
            } else {
                throw new Error(data.error || 'Failed to delete category');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const notification = document.getElementById('duplicateNotification');
            notification.textContent = error.message || 'Error deleting category';
            notification.style.backgroundColor = '#ff6b6b';
            notification.style.display = 'block';
        })
        .finally(() => {
            setTimeout(() => {
                const notification = document.getElementById('duplicateNotification');
                notification.style.display = 'none';
            }, 3000);
        });
    }
    </script>
</body>
</html>