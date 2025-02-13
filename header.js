document.addEventListener('DOMContentLoaded', function() {
    // Signup dropdown functionality
    const signupButton = document.getElementById('sign-button');
    const dropdownContent = document.querySelector('.dropdown-content');

    if (signupButton && dropdownContent) {
        signupButton.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownContent.classList.toggle('show');
        });

        // Add click handlers for dropdown buttons
        const bakerBtn = document.querySelector('.dropdown-btn:first-child');
        const customerBtn = document.querySelector('.dropdown-btn:last-child');

        if (bakerBtn) {
            bakerBtn.addEventListener('click', function() {
                window.location.href = 'signup.php?type=0';
            });
        }

        if (customerBtn) {
            customerBtn.addEventListener('click', function() {
                window.location.href = 'signup.php?type=1';
            });
        }
    }

    // Profile dropdown functionality
    const profileButton = document.getElementById('profile-button');
    const profileDropdown = document.querySelector('.profile-dropdown');

    if (profileButton && profileDropdown) {
        profileButton.addEventListener('click', function(e) {
            e.stopPropagation();
            profileDropdown.classList.toggle('show');
        });
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (dropdownContent && !dropdownContent.contains(e.target) && e.target !== signupButton) {
            dropdownContent.classList.remove('show');
        }
        if (profileDropdown && !profileDropdown.contains(e.target) && e.target !== profileButton) {
            profileDropdown.classList.remove('show');
        }
    });

    // Header scroll effect
    document.addEventListener("scroll", () => {
        const header = document.querySelector(".header");
        if (window.scrollY > 50) {
            header.classList.add("scrolled");
        } else {
            header.classList.remove("scrolled");
        }
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const authButtons = document.querySelector(".auth-buttons");
    const profileContainer = document.querySelector(".profile-container");

    // Simulated user login status (replace with actual logic)
    let isLoggedIn = false; // Change to true if the user is logged in

    if (isLoggedIn) {
        authButtons.classList.add("hidden");
        profileContainer.classList.remove("hidden");
    } else {
        authButtons.classList.remove("hidden");
        profileContainer.classList.add("hidden");
    }
});

function redirectToLogin() {
    window.location.href = 'login.php';
}

