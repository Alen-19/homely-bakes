<?php

session_start();
include 'connect.php';
// Retrieve user type from URL or session
if (isset($_GET['type']) && !empty($_GET['type'])) {
    $userType = $_GET['type'];
    $_SESSION['user_type'] = $userType; // Store in session
} elseif (isset($_SESSION['user_type']) && !empty($_SESSION['user_type'])) {
    $userType = $_SESSION['user_type'];
} else {
    // Redirect to signup.php if user_type is not set
    header("Location: signup.php");
    exit(); // Make sure no further code is executed after redirection
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $firstName = mysqli_real_escape_string($conn, $_POST['firstName']);
    $lastName = mysqli_real_escape_string($conn, $_POST['lastName']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $mobileNumber = mysqli_real_escape_string($conn, $_POST['mobileNumber']);
    $streetAddress = mysqli_real_escape_string($conn, $_POST['streetAddress']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $district = mysqli_real_escape_string($conn, $_POST['district']);
    $state = mysqli_real_escape_string($conn, $_POST['state']);
    $country = mysqli_real_escape_string($conn, $_POST['country']);
    $pincode = mysqli_real_escape_string($conn, $_POST['pincode']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Insert data into table_registration
    $sql_registration = "INSERT INTO table_registration ( first_name, last_name, mobile_number, 
            street_address, city, district, state, country, pincode) 
            VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt_registration = $conn->prepare($sql_registration);
    $stmt_registration->bind_param("sssssssss", $firstName, $lastName, $mobileNumber, 
                                    $streetAddress, $city, $district, $state, $country, $pincode);

    if ($stmt_registration->execute()) {
        // Get the last inserted user_id
        $user_id = $conn->insert_id;

        // Insert data into table_login
        $sql_login = "INSERT INTO table_login (user_type,user_id, email, password) VALUES (?, ?, ?, ?)";
        $stmt_login = $conn->prepare($sql_login);
        $stmt_login->bind_param("siss",$userType, $user_id, $email, $password);

        if ($stmt_login->execute()) {
            $_SESSION['success'] = "Registration successful! Please login.";
            header("Location: login.php");
            exit();
        } else {
            $_SESSION['error'] = "Error: " . $stmt_login->error;
        }

        $stmt_login->close();
    } else {
        $_SESSION['error'] = "Error: " . $stmt_registration->error;
    }

    $stmt_registration->close();
}
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
                    <label for="streetAddress">Street Address</label>
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