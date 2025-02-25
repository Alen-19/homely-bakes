<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base URL for consistent path handling
$base_url = '';  // You can set this to your domain if needed

// Set cache control headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

// Verify session is valid when user is logged in
if (isset($_SESSION['logged_in'])) {
    // Additional session validation
    if (!isset($_SESSION['login_time']) || !isset($_SESSION['last_activity'])) {
        // Invalid session state
        session_unset();
        session_destroy();
        header("Location: login.php?invalid_session=1");
        exit();
    }

    // Check session timeout (30 minutes)
    $inactive_duration = time() - $_SESSION['last_activity'];
    if ($inactive_duration > 1800) {
        session_unset();
        session_destroy();
        header("Location: login.php?session_expired=1");
        exit();
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();
}

// Add no-cache meta tags for all pages
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Force no caching -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="stylesheet" href="<?= $base_url ?>header.css">
    <script src="<?= $base_url ?>header.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php if (isset($_SESSION['logged_in'])): ?>
    <?php endif; ?>
</head>
<body>
    <header class="header">
        <div class="container1">
            <div class="logo">
                <img src="<?= $base_url ?>img/logo.png" alt="Homely Bakes">
            </div>
            <nav class="nav">
                <a href="<?= $base_url ?>index.php" <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : '' ?>>Home</a>
                <a href="<?= $base_url ?>product.php" <?= basename($_SERVER['PHP_SELF']) == 'product.php' ? 'class="active"' : '' ?>>Products</a>
                <a href="<?= $base_url ?>orders.php" <?= basename($_SERVER['PHP_SELF']) == 'order.php' ? 'class="active"' : '' ?>>Order</a>
                <a href="<?= $base_url ?>contact.php" <?= basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'class="active"' : '' ?>>Contact</a>
                
                <div class="auth-section">
                    <?php if (!isset($_SESSION['logged_in'])): ?>
                        <!-- Show when logged out -->
                        <div class="login-container">
                            <button class="login-button" id="login-button" onclick="redirectToLogin()">Login</button>
                        </div>
                        
                        <div class="signup-container">
                            <button class="signup-button" id="sign-button">SignUp</button>
                            <div class="dropdown-content">
                                <button class="dropdown-btn" data-type="0">Baker</button>
                                <button class="dropdown-btn" data-type="1">Customer</button>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Show when logged in -->
                        <div class="profile-container">
                            <button class="profile-button" id="profile-button">
                                <span class="username"><?php echo htmlspecialchars($_SESSION['firstname']); ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </button>

                            <!-- Profile Dropdown -->
                            <div class="profile-dropdown" id="profileDropdown">
                                <ul>
                                    <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === '0'): ?>
                                        <li>
                                            <a href="<?= $base_url ?>baker/baker_dashboard.php">
                                                <i class="fas fa-home"></i> Dashboard
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?= $base_url ?>baker/baker_profile.php">
                                                <i class="fas fa-user"></i> Profile
                                            </a>
                                        </li>
                                    <?php else: ?>
                                        <li>
                                            <a href="<?= $base_url ?>profile.php">
                                                <i class="fas fa-user"></i> My Profile
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?= $base_url ?>orders.php">
                                                <i class="fas fa-shopping-bag"></i> My Orders
                                            </a>
                                        </li>
                                        <li>
                                            <a href="<?= $base_url ?>wishlist.php">
                                                <i class="fas fa-heart"></i> Wishlist
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <li class="divider"></li>
                                    <li>
                                        <a href="<?= $base_url ?>logout.php" class="logout-link">
                                            <i class="fas fa-sign-out-alt"></i> Logout
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>
</body>
</html>
