<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = array();

// Get session parameters for proper cookie deletion
$params = session_get_cookie_params();

// Delete the session cookie
setcookie(session_name(), '', time() - 42000,
    $params["path"], 
    $params["domain"],
    $params["secure"], 
    $params["httponly"]
);

// Destroy session
session_destroy();

// Set headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

// Clear any other cookies that might be set
if (isset($_SERVER['HTTP_COOKIE'])) {
    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    foreach($cookies as $cookie) {
        $parts = explode('=', $cookie);
        $name = trim($parts[0]);
        setcookie($name, '', time() - 3600, '/');
    }
}

// Output HTML with simple toast notification
echo '<!DOCTYPE html>
<html>
<head>
    <title>Logging out...</title>
    <style>
        .toast {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 12px 24px;
            border-radius: 4px;
            font-family: Arial, sans-serif;
            font-size: 15px;
            opacity: 0;
            animation: toast 1.5s ease;
        }
        
        @keyframes toast {
            0% { opacity: 0; }
            20% { opacity: 1; }
            80% { opacity: 1; }
            100% { opacity: 0; }
        }
    </style>
    <script>
        window.onload = function() {
            document.body.innerHTML = "<div class=\'toast\'>You have successfully logged out</div>";
            setTimeout(function() {
                window.location.href = "login.php";
            }, 1200);
        }
    </script>
</head>
<body>
</body>
</html>';
exit();
?>
