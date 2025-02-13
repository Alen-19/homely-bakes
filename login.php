<?php
session_start(); 
include 'connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

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

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            // Store session variables
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['firstname'] = $row['first_name'];
            $_SESSION['username'] = $row['first_name']; 
            $_SESSION['user_type'] = $row['user_type'];
            $_SESSION['logged_in'] = true;

            // Redirect based on user type


           // if ($row['user_type'] === '0') {
                $check_baker_sql = "SELECT * FROM table_baker WHERE user_id = ?";
                $check_baker_stmt = $conn->prepare($check_baker_sql);
                $check_baker_stmt->bind_param("i", $row['user_id']);
                $check_baker_stmt->execute();
                $baker_result = $check_baker_stmt->get_result();
            
                if ($baker_result->num_rows > 0) {
                    header("Location: baker/baker_dashboard.php");
                } else {
                    header("Location: baker_profile.php"); // Redirect to profile completion if not done
                }
              //  exit();


            } elseif ($row['user_type'] === '1') {
                header("Location: product.php");
                exit();
            }
        } else {
            $error = "Invalid email or password!";
        }
    } else {
        $error = "Invalid email or password!";
        
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Homely Bakes</title>
    <link rel="stylesheet" href="login.css">
    <script src="login.js"></script>
</head>
<body>
    <?php include "header.php" ?>
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
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
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
            </div>
            
            <button type="submit" id="signin">Sign In</button>
        </form>
        <a href="reading_mail.php" style="text-align: center;">Forgot Password?</a>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Error message fade out
        const errorMessage = document.querySelector('.error-message');
        if (errorMessage) {
            setTimeout(() => {
                errorMessage.style.opacity = '0';
                setTimeout(() => {
                    errorMessage.style.display = 'none';
                }, 300);
            }, 3000);
        }

        // Form validation
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            let isValid = true;

            // Reset previous error states
            email.classList.remove('invalid');
            password.classList.remove('invalid');

            // Email validation
            if (!email.value || !email.value.includes('@')) {
                email.classList.add('invalid');
                isValid = false;
            }

            // Password validation
            if (!password.value || password.value.length < 8) {
                password.classList.add('invalid');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>