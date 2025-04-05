<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Map detail pages to their parent pages
$parent_pages = [
    'view_baker.php' => 'manage_bakers.php',
    'view_customer.php' => 'manage_customers.php',
    'view_order.php' => 'manage_orders.php'
];

// If the current page is a detail page, use its parent page for the active class
$active_page = isset($parent_pages[$current_page]) ? $parent_pages[$current_page] : $current_page;
?>
<div class="sidebar">
    <div class="logo">
        <img src="../img/logo.png" alt="Homely Bakes" style="width: 80px; height: auto;">
    </div>
    <nav class="nav-menu">
        <a href="admin_dashboard.php" class="nav-item <?php echo $active_page == 'admin_dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            Dashboard
        </a>
        <a href="manage_bakers.php" class="nav-item <?php echo $active_page == 'manage_bakers.php' ? 'active' : ''; ?>">
            <i class="fas fa-bread-slice"></i>
            Manage Bakers
        </a>
        <a href="manage_customers.php" class="nav-item <?php echo $active_page == 'manage_customers.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            Manage Customers
        </a>
        <a href="manage_orders.php" class="nav-item <?php echo $active_page == 'manage_orders.php' ? 'active' : ''; ?>">
            <i class="fas fa-shopping-bag"></i>
            Manage Orders
        </a>
        <a href="reports.php" class="nav-item <?php echo $active_page == 'reports.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            Reports
        </a>
        <a href="../logout.php" class="nav-item">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
    </nav>
</div>
<style>
    :root {
        --primary-color: #4CAF50;
        --secondary-color: #2196F3;
        --danger-color: #f44336;
        --warning-color: #ff9800;
        --success-color: #4CAF50;
        --text-color: #333;
        --bg-color: #f8f9fa;
        --border-color: #e9ecef;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background-color: var(--bg-color);
        color: var(--text-color);
    }

    .dashboard-container {
        display: flex;
        min-height: 100vh;
    }

    /* Sidebar Styles */
    .sidebar {
        width: 250px;
        background-color: #fff;
        box-shadow: 2px 0 5px rgba(0,0,0,0.05);
        padding: 20px;
        position: fixed;
        height: 100vh;
        left: 0;
        top: 0;
        z-index: 1000;
    }

    .logo {
        text-align: center;
        padding: 15px 0;
        border-bottom: 1px solid var(--border-color);
        margin-bottom: 20px;
    }

    .nav-menu {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .nav-item {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        color: #6c757d;
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.3s ease;
        font-size: 0.95rem;
    }

    .nav-item:hover {
        background-color: rgba(76, 175, 80, 0.08);
        color: var(--primary-color);
    }

    .nav-item.active {
        background-color: var(--primary-color);
        color: white;
        box-shadow: 0 2px 4px rgba(76, 175, 80, 0.2);
    }

    .nav-item i {
        margin-right: 12px;
        width: 20px;
        text-align: center;
        font-size: 1.1rem;
    }

    /* Main Content Styles */
    .main-content {
        flex: 1;
        margin-left: 250px;
        padding: 30px;
    }

    /* Card Styles */
    .baker-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        border: 1px solid var(--border-color);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .baker-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.08);
    }

    /* Search Bar Styles */
    .search-bar {
        background: white;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 25px;
        display: flex;
        gap: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.04);
    }

    .search-bar input {
        flex: 1;
        padding: 10px 15px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        font-size: 0.95rem;
        transition: border-color 0.3s ease;
    }

    .search-bar input:focus {
        outline: none;
        border-color: var(--primary-color);
    }

    .search-bar button {
        padding: 10px 20px;
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .search-bar button:hover {
        background: #43a047;
    }

    /* Status Badge Styles */
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .status-badge.available {
        background-color: #e8f5e9;
        color: #2e7d32;
    }

    .status-badge.unavailable {
        background-color: #ffebee;
        color: #c62828;
    }

    /* Button Styles */
    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary {
        background-color: var(--primary-color);
        color: white;
    }

    .btn-danger {
        background-color: var(--danger-color);
        color: white;
    }

    .btn:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }

    /* Table Styles */
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin: 15px 0;
    }

    .info-item {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .info-label {
        font-size: 0.85rem;
        color: #6c757d;
    }

    .info-value {
        font-size: 1rem;
        color: var(--text-color);
        font-weight: 500;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .sidebar {
            width: 200px;
        }
        .main-content {
            margin-left: 200px;
        }
    }

    @media (max-width: 576px) {
        .sidebar {
            width: 0;
            transform: translateX(-100%);
        }
        .main-content {
            margin-left: 0;
        }
    }
</style> 