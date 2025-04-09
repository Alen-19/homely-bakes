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
        
        // After validating credentials and before setting session variables
        $check_baker_status = "SELECT b.admin_override 
                              FROM table_baker b 
                              JOIN table_login l ON b.user_id = l.user_id 
                              WHERE l.user_id = ? AND l.user_type = 0";
        $stmt = $conn->prepare($check_baker_status);
        $stmt->bind_param("i", $row['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $baker_status = $result->fetch_assoc();

        // Check if baker is restricted
        if ($row['user_type'] == 0 && $baker_status && $baker_status['admin_override'] == 'restricted') {
            $_SESSION['error'] = "Your account has been restricted. Please contact support for assistance.";
            header("Location: login.php");
            exit();
        }
        
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
            // Check if customer has completed their profile
            $check_profile_sql = "SELECT COUNT(*) as profile_exists FROM table_customer WHERE user_id = ?";
            $check_profile_stmt = $conn->prepare($check_profile_sql);
            $check_profile_stmt->bind_param("i", $row['user_id']);
            $check_profile_stmt->execute();
            $profile_result = $check_profile_stmt->get_result();
            $profile_exists = $profile_result->fetch_assoc()['profile_exists'] > 0;

            if (!$profile_exists) {
                // New customer, redirect to profile completion
                header("Location: /HomelyBakes/profile.php");
            } else {
                // Existing customer with complete profile
                header("Location: /HomelyBakes/product.php");
            }
        }
        else if($row['user_type'] == 2) {
            header("Location: /HomelyBakes/admin/admin_dashboard.php");
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
    <style>
    .error-popup {
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: #f8d7da;
        color: #721c24;
        padding: 15px 25px;
        border-radius: 5px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        z-index: 1000;
        animation: slideIn 0.5s ease-out forwards;
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

    @keyframes fadeOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }

    .input-group {
        position: relative;
        margin-bottom: 20px;
    }

    .error-message {
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 5px;
        display: none;
    }

    .error-message.visible {
        display: block;
    }

    input.error {
        border-color: #dc3545;
    }
    </style>
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
    <?php if(isset($_SESSION['error'])): ?>
        <div id="error-message" class="error-popup">
            <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const emailError = document.getElementById('email-error');
        const passwordError = document.getElementById('password-error');
        const form = document.querySelector('form');
        const errorMessage = document.getElementById('error-message');

        // Regular expressions for validation
        const emailRegex = /^(?=[^@]*[a-zA-Z]{3,})[a-zA-Z0-9._%+-]+@[a-zA-Z0-9-]+(\.[a-zA-Z]{2,})+$/;
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>])[A-Za-z\d!@#$%^&*(),.?":{}|<>]{8,}$/;

        function showError(element, errorElement, message) {
            element.classList.add('error');
            errorElement.textContent = message;
            errorElement.classList.add('visible');
        }

        function hideError(element, errorElement) {
            element.classList.remove('error');
            errorElement.textContent = '';
            errorElement.classList.remove('visible');
        }

        async function validateEmail() {
            const email = emailInput.value.trim();

            if (!email) {
                showError(emailInput, emailError, 'Email is required');
                return false;
            }

            if (!emailRegex.test(email)) {
                showError(emailInput, emailError, 'Please enter a valid email address');
                return false;
            }

            try {
                const response = await fetch(`?check_email=${encodeURIComponent(email)}`);
                const data = await response.json();
                
                if (!data.exists) {
                    showError(emailInput, emailError, 'Email not registered');
                    return false;
                }
                
                hideError(emailInput, emailError);
                return true;
            } catch (error) {
                console.error('Error checking email:', error);
                showError(emailInput, emailError, 'Error checking email');
                return false;
            }
        }

        async function validatePassword() {
            const password = passwordInput.value.trim();
            const email = emailInput.value.trim();

            if (!password) {
                showError(passwordInput, passwordError, 'Password is required');
                return false;
            }

            if (!passwordRegex.test(password)) {
                showError(passwordInput, passwordError, 'Password must contain at least 8 characters, including uppercase, lowercase, number, and special character');
                return false;
            }

            if (await validateEmail()) {
                try {
                    const formData = new FormData();
                    formData.append('check_password', password);
                    formData.append('email', email);

                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (!data.valid) {
                        showError(passwordInput, passwordError, 'Wrong password');
                        return false;
                    }
                    
                    hideError(passwordInput, passwordError);
                    return true;
                } catch (error) {
                    console.error('Error validating password:', error);
                    return false;
                }
            }
            
            return false;
        }

        // Real-time validation
        let emailTimeout;
        emailInput.addEventListener('input', () => {
            clearTimeout(emailTimeout);
            if (emailInput.value.trim()) {
                emailTimeout = setTimeout(() => {
                    validateEmail();
                }, 500);
            } else {
                hideError(emailInput, emailError);
            }
        });

        let passwordTimeout;
        passwordInput.addEventListener('input', () => {
            clearTimeout(passwordTimeout);
            if (passwordInput.value.trim()) {
                passwordTimeout = setTimeout(() => {
                    validatePassword();
                }, 500);
            } else {
                hideError(passwordInput, passwordError);
            }
        });

        // Validate on blur
        emailInput.addEventListener('blur', validateEmail);
        passwordInput.addEventListener('blur', validatePassword);

        // Form submission
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const isEmailValid = await validateEmail();
            const isPasswordValid = await validatePassword();

            if (isEmailValid && isPasswordValid) {
                this.submit();
            }
        });

        // Handle error message fadeout
        if (errorMessage) {
            setTimeout(() => {
                errorMessage.style.animation = 'fadeOut 0.5s ease-out forwards';
            }, 2500);

            setTimeout(() => {
                errorMessage.remove();
            }, 3000);
        }
    });
    </script>
</body>
</html>