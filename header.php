<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="header.css">
    <script src="header.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<header class="header">
    <div class="container1">
        <div class="logo">
            <img src="img/logo.png" alt="Homely bakes">
        </div>
        <nav class="nav">
            <a href="index.php">Home</a>
            <a href="product.php">Products</a>
            <a href="#order">Order</a>
            <a href="contact.php">Contact</a>
            
            <div class="auth-section">
                <?php if(!isset($_SESSION['logged_in'])): ?>
                    <!-- Show when logged out -->
                    <div class="login-container">
                        <button class="login-button" id="login-button" onclick="redirectToLogin()">Login</button>
                    </div>
                       
                    
                    <div class="signup-container">
                        <button class="signup-button" id="sign-button">SignUp</button>
                        <div class="dropdown-content">
                            <button class="dropdown-btn">Baker</button>
                            <button class="dropdown-btn">Customer</button>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Show when logged in -->
                    <div class="profile-container">
                        <button class="profile-button" id="profile-button">
                            <i class="fas fa-user-circle profile-icon"></i>
                            <span class="username"><?php echo htmlspecialchars($_SESSION['firstname']); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="profile-dropdown">
                            <a href="profile.php" class="profile-link">
                                <i class="fas fa-user"></i> Profile
                            </a>
                            <?php if($_SESSION['user_type'] === 'baker'): ?>
                                <a href="baker/" class="profile-link">
                                    <i class="fas fa-store"></i> Dashboard
                                </a>
                            <?php endif; ?>
                            <a href="logout.php" class="profile-link logout-link">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>
</body>
</html>