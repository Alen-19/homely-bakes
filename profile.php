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
    
    // Handle profile photo upload
    $profile_photo = null;
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] != 4) {
        if ($_FILES['profile_photo']['error'] != 0) {
            $upload_errors = array(
                1 => "The uploaded file exceeds the upload_max_filesize directive",
                2 => "The uploaded file exceeds the MAX_FILE_SIZE directive",
                3 => "The uploaded file was only partially uploaded",
                6 => "Missing a temporary folder",
                7 => "Failed to write file to disk",
                8 => "A PHP extension stopped the file upload"
            );
            $error_message = "Error uploading file: " . 
                ($upload_errors[$_FILES['profile_photo']['error']] ?? "Unknown error");
        } else {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_photo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $filesize = $_FILES['profile_photo']['size'];
            
            if (!in_array($ext, $allowed)) {
                $error_message = "Invalid file type. Please upload a JPG, JPEG, PNG, or GIF file.";
            } elseif ($filesize > MAX_FILE_SIZE) {
                $error_message = "File size too large. Please upload an image under 5MB.";
            } else {
                $newname = "profile_" . $user_id . "_" . time() . "." . $ext;
                $tmpFile = $_FILES['profile_photo']['tmp_name'];
                $destinationPath = UPLOAD_DIR . $newname;
                
                // Check if image is valid
                if (!getimagesize($tmpFile)) {
                    $error_message = "Invalid image file. Please upload a valid image.";
                } else if (!move_uploaded_file($tmpFile, $destinationPath)) {
                    $error_message = "Failed to upload profile photo. Please try again.";
                    error_log("Profile photo upload failed for user $user_id: " . error_get_last()['message']);
                } else {
                    $profile_photo = $newname; // Set the profile photo name for database update
                    error_log("Debug - Profile photo name: " . $profile_photo); // Debug line
                    
                    // Delete old profile photo if exists
                    $sql_old_photo = "SELECT profile_photo FROM table_customer WHERE user_id = ?";
                    $stmt_old_photo = $conn->prepare($sql_old_photo);
                    $stmt_old_photo->bind_param("i", $user_id);
                    $stmt_old_photo->execute();
                    $result_old_photo = $stmt_old_photo->get_result();
                    $old_photo = $result_old_photo->fetch_assoc();
                    
                    if ($old_photo && !empty($old_photo['profile_photo'])) {
                        $old_photo_path = UPLOAD_DIR . $old_photo['profile_photo'];
                        if (file_exists($old_photo_path)) {
                            unlink($old_photo_path);
                        }
                    }
                }
            }
        }
    }
    
    if (!isset($error_message)) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            error_log("Debug - Starting profile update for user_id: " . $user_id);
            error_log("Debug - Profile photo value: " . ($profile_photo ?? 'NULL'));

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
                if ($profile_photo !== null) {
                    error_log("Debug - Updating customer with profile photo");
                    $sql2 = "UPDATE table_customer SET alt_phone = ?, profile_photo = ? WHERE user_id = ?";
                    $stmt2 = $conn->prepare($sql2);
                    $stmt2->bind_param("ssi", $alt_phone, $profile_photo, $user_id);
                } else {
                    error_log("Debug - Updating customer without profile photo");
                    $sql2 = "UPDATE table_customer SET alt_phone = ? WHERE user_id = ?";
                    $stmt2 = $conn->prepare($sql2);
                    $stmt2->bind_param("si", $alt_phone, $user_id);
                }
            } else {
                // Insert new customer record
                error_log("Debug - Inserting new customer record");
                $sql2 = "INSERT INTO table_customer (user_id, alt_phone, profile_photo) VALUES (?, ?, ?)";
                $stmt2 = $conn->prepare($sql2);
                $stmt2->bind_param("iss", $user_id, $alt_phone, $profile_photo);
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
            
            // Delete uploaded file if exists and there was an error
            if ($profile_photo !== null && file_exists(UPLOAD_DIR . $profile_photo)) {
                unlink(UPLOAD_DIR . $profile_photo);
            }
        }
    }
}

// Fetch current user details
$sql = "SELECT r.first_name, r.last_name, r.mobile_number, r.street_address, r.city, r.district, 
        r.state, r.country, r.pincode,
        c.alt_phone, c.profile_photo,
        l.email
        FROM table_registration r 
        LEFT JOIN table_customer c ON r.user_id = c.user_id
        LEFT JOIN table_login l ON r.user_id = l.user_id 
        WHERE r.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// If no customer record exists, create one
if (!isset($user['alt_phone'])) {
    $insert_sql = "INSERT INTO table_customer (user_id, alt_phone, profile_photo) VALUES (?, '', NULL)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("i", $user_id);
    $insert_stmt->execute();
    $user['alt_phone'] = '';
    $user['profile_photo'] = NULL;
}

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
                            <?php
                            $profile_photo_path = '';
                            if (!empty($user['profile_photo'])) {
                                $photo_path = UPLOAD_DIR . $user['profile_photo'];
                                if (file_exists($photo_path)) {
                                    $profile_photo_path = 'uploads/profile_photos/' . htmlspecialchars($user['profile_photo']);
                                }
                            }
                            
                            // Set default image path
                            $display_image = !empty($profile_photo_path) ? $profile_photo_path : 'assets/images/default-profile.svg';
                            $photo_exists = !empty($profile_photo_path);
                            ?>
                            <div class="profile-photo-wrapper">
                                <img src="<?php echo htmlspecialchars($display_image); ?>" 
                                    alt="Profile Photo" id="profile-photo-preview"
                                    class="<?php echo $photo_exists ? 'has-photo' : ''; ?>">
                            </div>
                            <div class="profile-photo-overlay">
                                <label for="profile-photo-input" class="profile-photo-upload">
                                    <i class="fas fa-camera"></i>
                                    <span><?php echo $photo_exists ? 'Change Photo' : 'Add Photo'; ?></span>
                                </label>
                            </div>
                            <input type="file" id="profile-photo-input" name="profile_photo" 
                                accept="image/jpeg,image/png,image/gif" style="display: none;">
                        </div>
                    </div>
                    
                    <div class="profile-section">
                        <h2 class="section-title">Personal Information</h2>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" 
                                    value="<?php echo htmlspecialchars($user['first_name']); ?>" 
                                    required minlength="2" pattern="[A-Za-z ]+" 
                                    title="Please enter at least 2 characters, letters only"
                                    class="form-control">
                                <div class="invalid-feedback">Please enter a valid first name (minimum 2 characters, letters only)</div>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" 
                                    value="<?php echo htmlspecialchars($user['last_name']); ?>" 
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
                                    value="<?php echo htmlspecialchars($user['email']); ?>" 
                                    readonly class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="phone">Primary Phone Number</label>
                                <input type="tel" id="phone" name="phone" 
                                    value="<?php echo htmlspecialchars($user['mobile_number']); ?>" 
                                    required pattern="[0-9]{10}" maxlength="10"
                                    title="Please enter exactly 10 digits"
                                    class="form-control">
                                <div class="invalid-feedback">Please enter a valid 10-digit phone number</div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="alt_phone">Alternate Phone Number (Optional)</label>
                            <input type="tel" id="alt_phone" name="alt_phone" 
                                value="<?php echo !empty($user['alt_phone']) ? htmlspecialchars($user['alt_phone']) : ''; ?>"
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
                                value="<?php echo htmlspecialchars($user['street_address']); ?>" 
                                required class="form-control">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" id="city" name="city" 
                                    value="<?php echo htmlspecialchars($user['city']); ?>" 
                                    required class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="district">District</label>
                                <input type="text" id="district" name="district" 
                                    value="<?php echo htmlspecialchars($user['district']); ?>" 
                                    required class="form-control">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="state">State</label>
                                <input type="text" id="state" name="state" 
                                    value="<?php echo htmlspecialchars($user['state']); ?>" 
                                    required class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="country">Country</label>
                                <input type="text" id="country" name="country" 
                                    value="<?php echo htmlspecialchars($user['country'] ?? 'India'); ?>" 
                                    required class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="pincode">PIN Code</label>
                            <input type="text" id="pincode" name="pincode" 
                                value="<?php echo htmlspecialchars($user['pincode']); ?>" 
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
