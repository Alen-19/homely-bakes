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

// Check if user is logged in and is a customer
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] != 1) {
    header("Location: login.php");
    exit();
}

$success_message = "";
$error_message = "";

// Fetch current user details before form submission
$fetch_sql = "SELECT r.*, c.alt_phone, c.profile_photo, l.email 
              FROM table_registration r 
              LEFT JOIN table_customer c ON r.user_id = c.user_id 
              LEFT JOIN table_login l ON r.user_id = l.user_id 
              WHERE r.user_id = ?";
$fetch_stmt = $conn->prepare($fetch_sql);
$fetch_stmt->bind_param("i", $user_id);
$fetch_stmt->execute();
$user_details = $fetch_stmt->get_result()->fetch_assoc();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate inputs
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

    if (strlen($first_name) < 2 || strlen($last_name) < 2) {
        $error_message = "First name and last name must be at least 2 characters long.";
    } elseif (!preg_match("/^[0-9]{10}$/", $mobile_number)) {
        $error_message = "Primary phone number must be exactly 10 digits.";
    } elseif (!empty($alt_phone) && !preg_match("/^[0-9]{10}$/", $alt_phone)) {
        $error_message = "Alternate phone number must be exactly 10 digits.";
    } elseif (!preg_match("/^[0-9]{6}$/", $pincode)) {
        $error_message = "PIN code must be exactly 6 digits.";
    }

    // Handle profile photo upload
    $profile_photo_url = $user_details['profile_photo']; // Keep existing photo by default
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_photo'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($file_extension, $allowed_types)) {
            $error_message = "Only JPG, PNG, and GIF files are allowed.";
        } elseif ($file['size'] > MAX_FILE_SIZE) {
            $error_message = "File size must not exceed 5MB.";
        } else {
            $new_filename = uniqid('profile_') . '.' . $file_extension;
            $upload_path = UPLOAD_DIR . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Delete old profile photo if exists
                if (!empty($profile_photo_url) && file_exists($profile_photo_url)) {
                    unlink($profile_photo_url);
                }
                $profile_photo_url = 'uploads/profile_photos/' . $new_filename;
            } else {
                $error_message = "Failed to upload profile photo.";
            }
        }
    }

    // If no errors, update the database
    if (empty($error_message)) {
        try {
            $conn->begin_transaction();

            // Update table_registration
            $update_reg_sql = "UPDATE table_registration SET 
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
            
            $update_reg_stmt = $conn->prepare($update_reg_sql);
            $update_reg_stmt->bind_param("sssssssssi", 
                $first_name, $last_name, $mobile_number, 
                $street_address, $city, $district, 
                $state, $country, $pincode, $user_id
            );
            $update_reg_stmt->execute();

            // Check if customer record exists
            $check_sql = "SELECT COUNT(*) as count FROM table_customer WHERE user_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $user_id);
            $check_stmt->execute();
            $exists = $check_stmt->get_result()->fetch_assoc()['count'] > 0;

            if ($exists) {
                // Update existing customer record
                $update_cust_sql = "UPDATE table_customer SET 
                    alt_phone = ?, 
                    profile_photo = ? 
                    WHERE user_id = ?";
                $update_cust_stmt = $conn->prepare($update_cust_sql);
                $update_cust_stmt->bind_param("ssi", 
                    $alt_phone, $profile_photo_url, $user_id
                );
            } else {
                // Insert new customer record
                $insert_cust_sql = "INSERT INTO table_customer 
                    (user_id, alt_phone, profile_photo) 
                    VALUES (?, ?, ?)";
                $update_cust_stmt = $conn->prepare($insert_cust_sql);
                $update_cust_stmt->bind_param("iss", 
                    $user_id, $alt_phone, $profile_photo_url
                );
            }
            $update_cust_stmt->execute();

            $conn->commit();
            
            // Update all necessary session data
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_type'] = 1; // 1 for customer
            $_SESSION['firstname'] = $first_name;
            $_SESSION['username'] = $first_name;
            $_SESSION['email'] = $user_details['email'];
            $_SESSION['mobile_number'] = $mobile_number;
            $_SESSION['profile_photo'] = $profile_photo_url;
            
            $success_message = "Profile updated successfully!";
            
            // Redirect to products page after successful update
            header("Location: product.php");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Set default profile photo if none exists
$profile_photo_path = !empty($user_details['profile_photo']) 
    ? $user_details['profile_photo'] 
    : 'assets/images/default-profile.svg';

$email = $user_details['email'] ?? 'Not Available';

// Close all prepared statements
if (isset($fetch_stmt)) $fetch_stmt->close();
if (isset($update_reg_stmt)) $update_reg_stmt->close();
if (isset($check_stmt)) $check_stmt->close();
if (isset($update_cust_stmt)) $update_cust_stmt->close();

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
                <?php if (!isset($user_details['alt_phone'])): ?>
                    <div class="welcome-message">
                        <p>Welcome to Homely Bakes! Please complete your profile to start shopping.</p>
                        <p>All fields marked with * are required.</p>
                    </div>
                <?php else: ?>
                    <p>View and update your personal information</p>
                <?php endif; ?>
            </div>
            
            <div class="profile-content">
                <?php if (!empty($success_message)): ?>
                    <div class="success-message">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" id="profile-form">
                    <div class="profile-photo-section">
                        <div class="profile-photo-container" id="profile-photo-container">
                            <img src="<?php echo htmlspecialchars($profile_photo_path); ?>" 
                                 alt="Profile Photo" 
                                 id="profile-photo-preview" 
                                 class="profile-photo">
                            <div class="profile-photo-upload">
                                <input type="file" 
                                       id="profile-photo-input" 
                                       name="profile_photo" 
                                       accept="image/jpeg,image/png,image/gif" 
                                       class="profile-photo-input">
                                <label for="profile-photo-input">
                                    <i class="fas fa-camera"></i>
                                    <span>Change Photo</span>
                                </label>
                            </div>
                        </div>
                        <p class="profile-photo-help">Allowed formats: JPG, PNG, GIF. Max size: 5MB</p>
                    </div>

                    <div class="profile-section">
                        <h2>Personal Information</h2>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" 
                                       id="first_name" 
                                       name="first_name" 
                                       value="<?php echo htmlspecialchars($user_details['first_name']); ?>" 
                                       required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" 
                                       id="last_name" 
                                       name="last_name" 
                                       value="<?php echo htmlspecialchars($user_details['last_name']); ?>" 
                                       required>
                            </div>
                        </div>
                    </div>

                    <div class="profile-section">
                        <h2>Contact Information</h2>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" 
                                       id="email" 
                                       value="<?php echo htmlspecialchars($email); ?>" 
                                       readonly>
                            </div>
                            <div class="form-group">
                                <label for="phone">Primary Phone Number</label>
                                <input type="tel" 
                                       id="phone" 
                                       name="phone" 
                                       value="<?php echo htmlspecialchars($user_details['mobile_number']); ?>" 
                                       required 
                                       pattern="[0-9]{10}" 
                                       maxlength="10">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="alt_phone">Alternate Phone Number (Optional)</label>
                            <input type="tel" 
                                   id="alt_phone" 
                                   name="alt_phone" 
                                   value="<?php echo htmlspecialchars($user_details['alt_phone'] ?? ''); ?>" 
                                   pattern="[0-9]{10}" 
                                   maxlength="10">
                        </div>
                    </div>

                    <div class="profile-section">
                        <h2>Address Information</h2>
                        <div class="form-group">
                            <label for="street_address">House Name / Street Address</label>
                            <input type="text" 
                                   id="street_address" 
                                   name="street_address" 
                                   value="<?php echo htmlspecialchars($user_details['street_address']); ?>" 
                                   required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" 
                                       id="city" 
                                       name="city" 
                                       value="<?php echo htmlspecialchars($user_details['city']); ?>" 
                                       required>
                            </div>
                            <div class="form-group">
                                <label for="district">District</label>
                                <input type="text" 
                                       id="district" 
                                       name="district" 
                                       value="<?php echo htmlspecialchars($user_details['district']); ?>" 
                                       required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="state">State</label>
                                <input type="text" 
                                       id="state" 
                                       name="state" 
                                       value="<?php echo htmlspecialchars($user_details['state']); ?>" 
                                       required>
                            </div>
                            <div class="form-group">
                                <label for="country">Country</label>
                                <input type="text" 
                                       id="country" 
                                       name="country" 
                                       value="<?php echo htmlspecialchars($user_details['country']); ?>" 
                                       required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="pincode">PIN Code</label>
                            <input type="text" 
                                   id="pincode" 
                                   name="pincode" 
                                   value="<?php echo htmlspecialchars($user_details['pincode']); ?>" 
                                   required 
                                   pattern="[0-9]{6}" 
                                   maxlength="6">
                        </div>
                    </div>

                    <div class="profile-actions">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='products.php'">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('profile-form');
        const photoInput = document.getElementById('profile-photo-input');
        const photoPreview = document.getElementById('profile-photo-preview');
        
        // Profile photo preview
        photoInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const file = e.target.files[0];
                
                // Validate file size
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must not exceed 5MB');
                    this.value = '';
                    return;
                }
                
                // Validate file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    alert('Only JPG, PNG and GIF files are allowed');
                    this.value = '';
                    return;
                }
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    photoPreview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
        
        // Form validation
        form.addEventListener('submit', function(e) {
            const phone = document.getElementById('phone').value;
            const altPhone = document.getElementById('alt_phone').value;
            const pincode = document.getElementById('pincode').value;
            
            if (!/^[0-9]{10}$/.test(phone)) {
                e.preventDefault();
                alert('Please enter a valid 10-digit phone number');
                return;
            }
            
            if (altPhone && !/^[0-9]{10}$/.test(altPhone)) {
                e.preventDefault();
                alert('Please enter a valid 10-digit alternate phone number');
                return;
            }
            
            if (!/^[0-9]{6}$/.test(pincode)) {
                e.preventDefault();
                alert('Please enter a valid 6-digit PIN code');
                return;
            }
        });
        
        // Auto-hide messages after 5 seconds
        const messages = document.querySelectorAll('.success-message, .error-message');
        messages.forEach(msg => {
            setTimeout(() => {
                msg.style.opacity = '0';
                setTimeout(() => msg.style.display = 'none', 300);
            }, 5000);
        });
    });
    </script>
</body>
</html>
