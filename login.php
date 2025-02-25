<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear any existing session on login page
if (!isset($_POST['email'])) {
    session_unset();
    session_destroy();
    session_start();
}

include 'connect.php';

$error = "";

// API endpoint to check email existence
if (isset($_GET['check_email'])) {
    $email = trim($_GET['check_email']);
    
    $sql = "SELECT email FROM table_login WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo json_encode(['exists' => $result->num_rows > 0]);
    exit();
}

// API endpoint to check password
if (isset($_POST['check_password']) && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['check_password']);
    
    $sql = "SELECT password FROM table_login WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $isValid = password_verify($password, $row['password']);
        echo json_encode(['valid' => $isValid]);
    } else {
        echo json_encode(['valid' => false]);
    }
    exit();
}

// Handle different error states
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
} elseif (isset($_GET['session_expired'])) {
    $error = "Your session has expired. Please log in again.";
} elseif (isset($_GET['invalid_session'])) {
    $error = "Invalid session. Please log in again.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Input validation
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Please enter both email and password.";
        header("Location: /HomelyBakes/login.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Please enter a valid email address.";
        header("Location: /HomelyBakes/login.php");
        exit();
    }

    $sql = "SELECT 
                t_reg.first_name,
                t_log.user_type,
                t_reg.user_id,
                t_log.password
            FROM 
                table_login t_log
            INNER JOIN 
                table_registration t_reg 
            ON 
                t_log.user_id = t_reg.user_id 
            WHERE 
                t_log.email = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $_SESSION['error'] = "Email does not exist in the database!";
        header("Location: /HomelyBakes/login.php");
        exit();
    } else {
        $row = $result->fetch_assoc();
        
        if (!password_verify($password, $row['password'])) {
            $_SESSION['error'] = "Invalid email or password!";
            header("Location: /HomelyBakes/login.php");
            exit();
        }
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Successful login, store session data
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['firstname'] = $row['first_name'];
        $_SESSION['username'] = $row['first_name'];
        $_SESSION['user_type'] = $row['user_type'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Set cache control headers
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        
        // Redirect based on user type
        if ($row['user_type'] == 0) {
            header("Location: /HomelyBakes/baker/baker_dashboard.php");
        } else if ($row['user_type'] == 1) {
            header("Location: /HomelyBakes/product.php");
        } else {
            $_SESSION['error'] = "Invalid user type!";
            header("Location: /HomelyBakes/login.php");
        }
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Homely Bakes</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <?php include "header.php"; ?>
    <div class="login-card">
        <div class="chef-hat-div">
            <svg class="chef-hat" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V21H6Z"/>
                <line x1="6" y1="17" x2="18" y2="17"/>
            </svg>
        </div>
        <h1>Welcome Back!</h1>
        <p class="subtitle">Sign in to discover delicious homemade treats near you</p>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" novalidate>
            <?php if (!empty($error)): ?>
                <div class="server-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="input-group">
                <label for="email">Email</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="Enter your email" 
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    required
                >
                <div class="error-message" id="email-error"></div>
            </div>
            
            <div class="input-group">
                <label for="password">Password</label>
                <div class="password-input">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password" 
                        required
                    >
                </div>
                <div class="error-message" id="password-error"></div>
            </div>
            
            <button type="submit" id="signin">Sign In</button>
        </form>
        <a href="reading_mail.php" class="forgot-password">Forgot Password?</a>
    </div>
    <script src="login.js"></script>
</body>
</html>