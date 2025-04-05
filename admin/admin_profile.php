<?php
// Move session check to the calling page
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_image = $_SESSION['admin_image'] ?? 'img/default-admin.png'; // Default image path
?>

<div class="profile-container">
    <div class="admin-profile">
        <img src="img/admin-profile.jpeg" alt="Admin Profile" class="admin-profile-img">
        <span class="admin-name">Admin</span>
    </div>
</div>

<style>
.profile-container {
    position: fixed;
    top: 0;
    right: 0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding: 10px 20px;
    z-index: 1000;
    width: calc(100% - 250px); /* Adjust based on your sidebar width */
}

.admin-profile {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-left: auto; /* Push to the right */
}

.admin-profile-img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.admin-name {
    font-weight: 500;
    color: #333;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .profile-container {
        width: 100%;
        position: static;
    }
}
</style> 