<?php
session_start();
include_once __DIR__ . '/../connect.php';


// Ensure the user is a baker
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== '0') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = "";

// Fetch existing baker profile
$baker_query = $conn->prepare("SELECT * FROM table_baker WHERE user_id = ?");
$baker_query->bind_param("i", $user_id);
$baker_query->execute();
$baker_result = $baker_query->get_result();
$baker = $baker_result->fetch_assoc();

// Fetch user registration details
$registration_query = $conn->prepare("
    SELECT r.* 
    FROM table_registration r 
    JOIN table_login l ON r.user_id = l.user_id 
    WHERE l.user_id = ?
");
$registration_query->bind_param("i", $user_id);
$registration_query->execute();
$registration_result = $registration_query->get_result();
$registration = $registration_result->fetch_assoc();

// Check if this is a new baker
$is_new_baker = isset($_GET['new']) && $_GET['new'] === '1';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Update registration details
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $mobile_number = $_POST['mobile_number'];
    $street_address = $_POST['street_address'];
    $city = $_POST['city'];
    $district = $_POST['district'];
    $state = $_POST['state'];
    $country = $_POST['country'];
    $pincode = $_POST['pincode'];

    $update_registration_sql = "UPDATE table_registration SET first_name=?, last_name=?, mobile_number=?, street_address=?, city=?, district=?, state=?, country=?, pincode=? WHERE user_id=?";
    $update_registration_stmt = $conn->prepare($update_registration_sql);
    $update_registration_stmt->bind_param("sssssssssi", $firstname, $lastname, $mobile_number, $street_address, $city, $district, $state, $country, $pincode, $registration['user_id']);
    $update_registration_stmt->execute();

    // Update or insert baker profile
    $bakery_name = $_POST['bakery_name'];
    $description = $_POST['description'];
    $business_license = $_POST['business_license'];
    $availability_status = $_POST['availability_status'];

    // Handle file upload and remove old image
    $profile_image = $baker['profile_image'] ?? '';  // Default to existing image
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = mime_content_type($_FILES['profile_image']['tmp_name']);

        if (in_array($file_type, $allowed_types)) {
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0755, true);
            }

            // Remove old image if exists
            if (!empty($profile_image) && file_exists($profile_image)) {
                unlink($profile_image);
            }

            $file_name = uniqid() . '_' . basename($_FILES["profile_image"]["name"]);
            $profile_image = $target_dir . $file_name;

            if (!move_uploaded_file($_FILES["profile_image"]["tmp_name"], $profile_image)) {
                $error = "Failed to upload the image. Please try again.";
            }
        } else {
            $error = "Invalid file type. Only JPG, PNG, GIF, and WEBP formats are allowed.";
        }
    }

    // Insert or Update logic
    if (empty($error)) {
        if ($baker) {
            // Update existing baker profile
            $update_baker_sql = "UPDATE table_baker SET bakery_name=?, description=?, business_license=?, profile_image=?, availability_status=? WHERE user_id=?";
            $update_baker_stmt = $conn->prepare($update_baker_sql);
            $update_baker_stmt->bind_param("sssssi", $bakery_name, $description, $business_license, $profile_image, $availability_status, $user_id);
        } else {
            // Insert new baker profile
            $insert_baker_sql = "INSERT INTO table_baker (user_id, bakery_name, description, business_license, profile_image, availability_status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $update_baker_stmt = $conn->prepare($insert_baker_sql);
            $update_baker_stmt->bind_param("isssss", $user_id, $bakery_name, $description, $business_license, $profile_image, $availability_status);
        }

        if ($update_baker_stmt->execute()) {
            header("Location: baker_dashboard.php");
            exit();
        } else {
            $error = "Error saving baker information. Please try again.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Baker Profile Setup - Homely Bakes</title>
    <link rel="stylesheet" href="baker_profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fileInput = document.getElementById('profile_image');
    const previewImage = document.getElementById('profile-preview');
    const uploadIcon = document.getElementById('upload-icon');

    // Hide the image preview if no existing image
    if (previewImage.src === window.location.href || previewImage.src === '') {
        previewImage.style.display = 'none';
        uploadIcon.style.display = 'block';
    } else {
        uploadIcon.style.display = 'none';
    }

    fileInput.addEventListener('change', function () {
        const file = fileInput.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                previewImage.src = e.target.result;
                previewImage.style.display = 'block';
                uploadIcon.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    });
});
</script>

<div class="profile-card">
    <h1><?php echo $is_new_baker ? "Welcome! Complete Your Baker Profile" : "Baker Profile"; ?></h1>
    
    <?php if ($is_new_baker): ?>
    <div class="welcome-message">
        <p>Welcome to Homely Bakes! To start selling your delicious treats, please complete your baker profile.</p>
        <p>All fields marked with * are required.</p>
    </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <!-- Profile Image Section -->
        <div class="profile-image-section">
            <div class="profile-image-container">
                <input type="file" id="profile_image" name="profile_image" accept="image/jpeg, image/png, image/gif, image/webp">
                <label for="profile_image" class="profile-image-label">
                    <img id="profile-preview" src="<?php echo !empty($baker['profile_image']) ? htmlspecialchars($baker['profile_image']) : ''; ?>" 
                         alt="Profile Image Preview">
                    <div id="upload-icon" class="upload-icon">
                        <i class="fas fa-camera"></i>
                        <span>Upload Photo</span>
                    </div>
                </label>
            </div>
        </div>

        <h2>Personal Details</h2>
        <div class="input-group">
            <label for="firstname">First Name</label>
            <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($registration['first_name']); ?>" required>
        </div>

        <div class="input-group">
            <label for="lastname">Last Name</label>
            <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($registration['last_name']); ?>" required>
        </div>

        <div class="input-group">
            <label for="mobile_number">Mobile Number</label>
            <input type="text" id="mobile_number" name="mobile_number" value="<?php echo htmlspecialchars($registration['mobile_number']); ?>" required>
        </div>

        <div class="input-group">
            <label for="street_address">House Name/Street Address</label>
            <input type="text" id="street_address" name="street_address" value="<?php echo htmlspecialchars($registration['street_address']); ?>" required>
        </div>

        <div class="input-group">
            <label for="city">City</label>
            <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($registration['city']); ?>" required>
        </div>

        <div class="input-group">
            <label for="district">District</label>
            <input type="text" id="district" name="district" value="<?php echo htmlspecialchars($registration['district']); ?>" required>
        </div>

        <div class="input-group">
            <label for="state">State</label>
            <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($registration['state']); ?>" required>
        </div>

        <div class="input-group">
            <label for="country">Country</label>
            <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($registration['country']); ?>" required>
        </div>

        <div class="input-group">
            <label for="pincode">Pincode</label>
            <input type="text" id="pincode" name="pincode" value="<?php echo htmlspecialchars($registration['pincode']); ?>" required>
        </div>

        <h2>Bakery Details</h2>
        <div class="input-group">
            <label for="bakery_name">Bakery Name</label>
            <input type="text" id="bakery_name" name="bakery_name" value="<?php echo htmlspecialchars($baker['bakery_name'] ?? ''); ?>" required>
        </div>

        <div class="input-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" required><?php echo htmlspecialchars($baker['description'] ?? ''); ?></textarea>
        </div>

        <div class="input-group">
            <label for="business_license">Business License Number</label>
            <input type="text" id="business_license" name="business_license" value="<?php echo htmlspecialchars($baker['business_license'] ?? ''); ?>">
        </div>

        <div class="input-group">
            <label for="availability_status">Availability Status</label>
            <select id="availability_status" name="availability_status" required>
                <option value="available" <?php echo (isset($baker['availability_status']) && $baker['availability_status'] == 'available') ? 'selected' : ''; ?>>Available</option>
                <option value="busy" <?php echo (isset($baker['availability_status']) && $baker['availability_status'] == 'busy') ? 'selected' : ''; ?>>Busy</option>
                <option value="closed" <?php echo (isset($baker['availability_status']) && $baker['availability_status'] == 'closed') ? 'selected' : ''; ?>>Closed</option>
            </select>
        </div>

        <button type="submit">Save Profile</button>
    </form>
</div>

</body>
</html>
