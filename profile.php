<?php
session_start();
include 'connect.php';

// Check if user is logged in and get user_id
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
error_log("Debug - Session user_id: " . $user_id);

// Define constants for upload
define('UPLOAD_DIR', __DIR__ . '/uploads/profile_photos/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Create upload directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
    chmod(UPLOAD_DIR, 0777);
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] != 1) {
    header("Location: login.php");
    exit();
}

$success_message = "";
$error_message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $mobile_number = trim($_POST['phone']); 
    $alt_phone = trim($_POST['alt_phone']);
    $street_address = trim($_POST['street_address']);
    $city = trim($_POST['city']);
    $district = trim($_POST['district']);
    $state = trim($_POST['state']);
    $country = trim($_POST['country']);
    $pincode = trim($_POST['pincode']);

    // Validate inputs
    if (strlen($first_name) < 2 || strlen($last_name) < 2) {
        $error_message = "First name and last name must be at least 2 characters long.";
    } elseif (!preg_match("/^[0-9]{10}$/", $mobile_number)) {
        $error_message = "Primary phone number must be exactly 10 digits.";
    } elseif (!empty($alt_phone) && !preg_match("/^[0-9]{10}$/", $alt_phone)) {
        $error_message = "Alternate phone number must be exactly 10 digits.";
    } elseif (!preg_match("/^[0-9]{6}$/", $pincode)) {
        $error_message = "PIN code must be exactly 6 digits.";
    }
    
    // Handle file upload
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_photo']['tmp_name'];
        $fileName = $_FILES['profile_photo']['name'];
        $fileSize = $_FILES['profile_photo']['size'];
        $fileType = $_FILES['profile_photo']['type'];
        $fileNameCmps = explode('.', $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Sanitize file name
        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;

        // Check file size
        if ($fileSize > MAX_FILE_SIZE) {
            $error_message = 'File size exceeds the maximum limit of 5MB.';
        } else {
            $dest_path = UPLOAD_DIR . $newFileName;
            
            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                // Update database with new profile photo URL
                $profile_photo_url = 'uploads/profile_photos/' . $newFileName;
                $sql = "UPDATE table_customer SET profile_photo = ? WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('si', $profile_photo_url, $user_id);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $success_message = 'Profile photo updated successfully.';
                } else {
                    $error_message = 'Failed to update profile photo in the database.';
                }

                $stmt->close();
            } else {
                $error_message = 'There was an error moving the uploaded file.';
            }
        }
    }
    
    if (!isset($error_message)) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            error_log("Debug - Starting profile update for user_id: " . $user_id);

            // Update registration table
            $sql1 = "UPDATE table_registration SET 
                    first_name = ?,
                    last_name = ?,
                    mobile_number = ?,
                    street_address = ?,
                    city = ?,
                    district = ?,
                    state = ?,
                    country = ?,
                    pincode = ?
                    WHERE user_id = ?";
                    
            $stmt1 = $conn->prepare($sql1);
            $stmt1->bind_param("sssssssssi", $first_name, $last_name, $mobile_number, $street_address, $city, $district, $state, $country, $pincode, $user_id);
            
            if (!$stmt1->execute()) {
                throw new Exception("Failed to update registration: " . $stmt1->error);
            }
            
            // Check if customer record exists
            $check_sql = "SELECT COUNT(*) as count FROM table_customer WHERE user_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $exists = $check_result->fetch_assoc()['count'] > 0;
            
            error_log("Debug - Customer record exists: " . ($exists ? 'Yes' : 'No'));
            
            if ($exists) {
                // Update existing customer record
                $sql2 = "UPDATE table_customer SET alt_phone = ? WHERE user_id = ?";
                $stmt2 = $conn->prepare($sql2);
                $stmt2->bind_param("si", $alt_phone, $user_id);
            } else {
                // Insert new customer record
                $sql2 = "INSERT INTO table_customer (user_id, alt_phone) VALUES (?, ?)";
                $stmt2 = $conn->prepare($sql2);
                $stmt2->bind_param("is", $user_id, $alt_phone);
            }
            
            if (!$stmt2->execute()) {
                throw new Exception("Failed to update customer record: " . $stmt2->error);
            }
            
            // Verify the update
            $verify_sql = "SELECT profile_photo FROM table_customer WHERE user_id = ?";
            $verify_stmt = $conn->prepare($verify_sql);
            $verify_stmt->bind_param("i", $user_id);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();
            $verify_data = $verify_result->fetch_assoc();
            error_log("Debug - After update, profile_photo in DB: " . ($verify_data['profile_photo'] ?? 'NULL'));

            // Commit transaction
            $conn->commit();
            error_log("Debug - Transaction committed successfully");
            
            $_SESSION['firstname'] = $first_name;
            $_SESSION['username'] = $first_name;
            $success_message = "Profile updated successfully!";
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            error_log("Debug - Error in transaction: " . $e->getMessage());
            $error_message = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Fetch current user details including profile photo and email
$sql = "SELECT r.first_name, r.last_name, r.mobile_number, r.street_address, r.city, r.district, 
        r.state, r.country, r.pincode, c.alt_phone, c.profile_photo, l.email
        FROM table_registration r
        JOIN table_customer c ON r.user_id = c.user_id
        JOIN table_login l ON r.user_id = l.user_id
        WHERE r.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_details = $result->fetch_assoc();

$profile_photo_path = $user_details['profile_photo'] ?? 'assets/images/default-profile.svg';
$email = $user_details['email'] ?? 'Not Available';

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Homely Bakes</title>
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include "header.php"; ?>
    <div class="outer-profile-container">
        <div class="profile-container">
            <div class="profile-header">
                <h1>My Profile</h1>
                <p>View and update your personal information</p>
            </div>
            
            <div class="profile-content">
                <?php if (!empty($success_message)): ?>
                    <div class="success-message" style="display: block;">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="error-message" style="display: block;">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data" id="profile-form" novalidate>
                    <div class="profile-photo-section">
                        <div class="profile-photo-container" id="profile-photo-container">
                            <img src="<?php echo htmlspecialchars($profile_photo_path); ?>" alt="Profile Photo" id="profile-photo-preview" class="profile-photo">
                            <div class="profile-photo-upload">
                                <input type="file" id="profile-photo-input" name="profile_photo" accept="image/jpeg,image/png,image/gif" class="profile-photo-input">
                                <label for="profile-photo-input">
                                    <i class="fas fa-camera"></i>
                                    <span>Change Photo</span>
                                </label>
                            </div>
                        </div>
                        <p class="profile-photo-help">Allowed formats: JPG, PNG, GIF. Max size: 5MB</p>
                    </div>
                    
                    <div class="profile-section">
                        <h2 class="section-title">Personal Information</h2>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" 
                                    value="<?php echo htmlspecialchars($user_details['first_name']); ?>" 
                                    required minlength="2" pattern="[A-Za-z ]+" 
                                    title="Please enter at least 2 characters, letters only"
                                    class="form-control">
                                <div class="invalid-feedback">Please enter a valid first name (minimum 2 characters, letters only)</div>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" 
                                    value="<?php echo htmlspecialchars($user_details['last_name']); ?>" 
                                    required minlength="2" pattern="[A-Za-z ]+"
                                    title="Please enter at least 2 characters, letters only"
                                    class="form-control">
                                <div class="invalid-feedback">Please enter a valid last name (minimum 2 characters, letters only)</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-section">
                        <h2 class="section-title">Contact Information</h2>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" 
                                    value="<?php echo htmlspecialchars($email); ?>" 
                                    readonly class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="phone">Primary Phone Number</label>
                                <input type="tel" id="phone" name="phone" 
                                    value="<?php echo htmlspecialchars($user_details['mobile_number']); ?>" 
                                    required pattern="[0-9]{10}" maxlength="10"
                                    title="Please enter exactly 10 digits"
                                    class="form-control">
                                <div class="invalid-feedback">Please enter a valid 10-digit phone number</div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="alt_phone">Alternate Phone Number (Optional)</label>
                            <input type="tel" id="alt_phone" name="alt_phone" 
                                value="<?php echo !empty($user_details['alt_phone']) ? htmlspecialchars($user_details['alt_phone']) : ''; ?>"
                                pattern="[0-9]{10}" maxlength="10"
                                title="Please enter exactly 10 digits"
                                class="form-control">
                            <div class="invalid-feedback">Please enter a valid 10-digit phone number</div>
                        </div>
                    </div>
                    <div class="profile-section">
                        <h2 class="section-title">Address Information</h2>
                        <div class="form-group">
                            <label for="street_address">House Name / Street Address</label>
                            <input type="text" id="street_address" name="street_address" 
                                value="<?php echo htmlspecialchars($user_details['street_address']); ?>" 
                                required class="form-control">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" id="city" name="city" 
                                    value="<?php echo htmlspecialchars($user_details['city']); ?>" 
                                    required class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="district">District</label>
                                <input type="text" id="district" name="district" 
                                    value="<?php echo htmlspecialchars($user_details['district']); ?>" 
                                    required class="form-control">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="state">State</label>
                                <input type="text" id="state" name="state" 
                                    value="<?php echo htmlspecialchars($user_details['state']); ?>" 
                                    required class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="country">Country</label>
                                <input type="text" id="country" name="country" 
                                    value="<?php echo htmlspecialchars($user_details['country'] ?? 'India'); ?>" 
                                    required class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="pincode">PIN Code</label>
                            <input type="text" id="pincode" name="pincode" 
                                value="<?php echo htmlspecialchars($user_details['pincode']); ?>" 
                                required pattern="[0-9]{6}" maxlength="6"
                                title="Please enter exactly 6 digits"
                                class="form-control">
                            <div class="invalid-feedback">Please enter a valid 6-digit PIN code</div>
                        </div>
                    </div>
                    
                    <div class="profile-actions">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='product.php'">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
        
    </div>

    <script>
        // Form validation
        const form = document.getElementById('profile-form');
        
        // Only show validation on form submission
        form.addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            this.classList.add('was-validated');
        });

        // Preview profile photo before upload
        document.getElementById('profile-photo-input').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const file = e.target.files[0];
                const reader = new FileReader();
                const preview = document.getElementById('profile-photo-preview');
                const uploadLabel = document.querySelector('.profile-photo-upload span');
                const container = document.getElementById('profile-photo-container');
                
                // Check file size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size too large. Please select an image under 5MB.');
                    e.target.value = ''; // Clear the input
                    return;
                }
                
                // Check file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Invalid file type. Please select a JPG, PNG, or GIF file.');
                    e.target.value = ''; // Clear the input
                    return;
                }
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    container.classList.add('has-image');
                    uploadLabel.textContent = 'Change Photo';
                }
                reader.readAsDataURL(file);
            }
        });

        // Auto-hide messages
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('profile-photo-container');
            const preview = document.getElementById('profile-photo-preview');
            
            // Add has-image class if there's an existing image
            if (preview.src && !preview.src.includes('assets/images/default-profile.svg')) {
                container.classList.add('has-image');
            }
            
            // Auto-hide messages after 5 seconds
            const messages = document.querySelectorAll('.success-message, .error-message');
            messages.forEach(msg => {
                if (msg) {
                    setTimeout(() => {
                        msg.style.opacity = '0';
                        setTimeout(() => msg.style.display = 'none', 300);
                    }, 5000);
                }
            });

            // Add input event listeners for real-time validation only after user interaction
            const inputs = document.querySelectorAll('input[pattern]');
            inputs.forEach(input => {
                // Only show validation after first interaction
                input.addEventListener('blur', function() {
                    this.dataset.touched = 'true';
                });

                input.addEventListener('input', function() {
                    // Only validate if the field has been touched
                    if (this.dataset.touched === 'true') {
                        if (this.checkValidity()) {
                            this.classList.remove('is-invalid');
                            this.classList.add('is-valid');
                        } else {
                            this.classList.remove('is-valid');
                            this.classList.add('is-invalid');
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
