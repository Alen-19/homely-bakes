<?php
session_start();
include 'connect.php';

// Retrieve user type from URL or session
if (isset($_GET['type']) && $_GET['type'] !== '') {
    $numericType = $_GET['type'];
    // Convert numeric type to string representation
    $userType = ($numericType == 0) ? 'Baker' : 'Customer';
    $_SESSION['user_type'] = $userType; // Store string type in session
    $_SESSION['numeric_type'] = $numericType; // Store numeric type for database
} elseif (isset($_SESSION['user_type']) && !empty($_SESSION['user_type'])) {
    $userType = $_SESSION['user_type'];
    $numericType = ($userType == 'Baker') ? 0 : 1;
} else {
    // Redirect to signup.php if user_type is not set
    header("Location: signup.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $firstName = mysqli_real_escape_string($conn, trim($_POST['firstName']));
    $lastName = mysqli_real_escape_string($conn, trim($_POST['lastName']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $mobileNumber = mysqli_real_escape_string($conn, trim($_POST['mobileNumber']));
    $streetAddress = mysqli_real_escape_string($conn, trim($_POST['streetAddress']));
    $city = mysqli_real_escape_string($conn, trim($_POST['city']));
    $district = mysqli_real_escape_string($conn, trim($_POST['district']));
    $state = mysqli_real_escape_string($conn, trim($_POST['state']));
    $country = mysqli_real_escape_string($conn, trim($_POST['country']));
    $pincode = mysqli_real_escape_string($conn, trim($_POST['pincode']));
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);

    // First, check if email already exists
    $check_email = "SELECT email FROM table_login WHERE email = ?";
    $stmt_check = $conn->prepare($check_email);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Email already exists!";
    } else {
        // Start transaction
        mysqli_autocommit($conn, FALSE);
        $success = true;

        // Insert into table_registration first
        $sql_registration = "INSERT INTO table_registration (first_name, last_name, mobile_number, 
                street_address, city, district, state, country, pincode) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt_registration = $conn->prepare($sql_registration)) {
            $stmt_registration->bind_param("sssssssss", 
                $firstName, $lastName, $mobileNumber, $streetAddress, 
                $city, $district, $state, $country, $pincode
            );

            if (!$stmt_registration->execute()) {
                $success = false;
                $_SESSION['error'] = "Registration Error: " . $stmt_registration->error;
            }
        } else {
            $success = false;
            $_SESSION['error'] = "Preparation Error: " . $conn->error;
        }

        // If registration successful, proceed with login table insertion
        if ($success) {
            $user_id = $conn->insert_id;
            
            $sql_login = "INSERT INTO table_login (user_type, user_id, email, password) 
                         VALUES (?, ?, ?, ?)";
            
            if ($stmt_login = $conn->prepare($sql_login)) {
                $stmt_login->bind_param("iiss", $numericType, $user_id, $email, $password);
                
                if (!$stmt_login->execute()) {
                    $success = false;
                    $_SESSION['error'] = "Login Error: " . $stmt_login->error;
                }
            } else {
                $success = false;
                $_SESSION['error'] = "Login Preparation Error: " . $conn->error;
            }
        }

        // Commit or rollback based on success
        if ($success) {
            mysqli_commit($conn);
            $_SESSION['success'] = "Registration successful! Please login.";
            header("Location: login.php");
            exit();
        } else {
            mysqli_rollback($conn);
        }

        // Reset autocommit to true
        mysqli_autocommit($conn, TRUE);

        // Close statements
        if (isset($stmt_registration)) $stmt_registration->close();
        if (isset($stmt_login)) $stmt_login->close();
        if (isset($stmt_check)) $stmt_check->close();
    }
}

// Rest of the HTML code remains exactly the same...
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sign Up</title>
    <link rel="stylesheet" href="signup.css">
</head>
<body>
<?php include "header.php"?>
    <div class="form-container">
        <?php 
        $userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : ''; 
        ?>
        <h2>Sign Up <?php echo htmlspecialchars($userType); ?></h2>
        <form id="signupForm" method="POST" action="signup.php" >
            <!-- Personal Information -->
            <div class="form-section">
                <h3>Personal Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstName">First Name</label>
                        <input type="text" id="firstName" name="firstName" required>
                        <span class="error-message"></span>
                    </div>
                    <div class="form-group">
                        <label for="lastName">Last Name</label>
                        <input type="text" id="lastName" name="lastName" required>
                        <span class="error-message"></span>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                        <span class="error-message"></span>
                    </div>
                    <div class="form-group">
                        <label for="mobileNumber">Mobile Number</label>
                        <input type="tel" id="mobileNumber" name="mobileNumber" required>
                        <span class="error-message"></span>
                    </div>
                </div>
            </div>

            <!-- Address Information -->
            <div class="form-section">
                <h3>Address Information</h3>
                <div class="form-group">
                    <label for="streetAddress">Street Address/House Name</label>
                    <input type="text" id="streetAddress" name="streetAddress" required>
                    <span class="error-message"></span>
                </div>
                <h2 style="text-align: center;">Select Country, State And District</h2>
                <div class="form-row">
                    <!-- Country Dropdown -->
                    <div class="form-group">
                    <label for="country">Country</label>
                    <select id="country" name="country" required>
                        <option value="">Select Country</option>
                        <option value="India">India</option>
                    </select>
                    <span class="error-message" id="country-error"></span>
                    </div>

                    <!-- State Dropdown -->
                    <div class="form-group">
                        <label for="state">State</label>
                        <select id="state" name="state" required>
                            <option value="">Select State</option>
                        </select>
                        <span class="error-message" id="state-error"></span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="district">District</label>
                        <select id="district" name="district" required>
                            <option value="">Select District</option>
                        </select>
                        <span class="error-message"></span>
                    </div>
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" required>
                        <span class="error-message"></span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="pincode">Pincode</label>
                        <input type="number" id="pincode" name="pincode" required>
                        <span class="error-message"></span>
                    </div>
                </div>
            </div>

            <!-- Password Section -->
            <div class="form-section">
                <h3>Security</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                        <span class="error-message"></span>
                        <div class="password-strength"></div>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" required>
                        <span class="error-message"></span>
                    </div>
                </div>
            </div>

            <button type="submit">Sign Up</button>
        </form>
    </div>
    <script src="signup.js"></script>
</body>
</html>