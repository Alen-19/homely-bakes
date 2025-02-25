document.addEventListener('DOMContentLoaded', function() {
    // Prevent back button from showing cached pages
    window.onpageshow = function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    };

    // Get all necessary elements
    const signupButton = document.getElementById('sign-button');
    const dropdownContent = document.querySelector('.dropdown-content');
    const profileButton = document.getElementById('profile-button');
    const profileDropdown = document.getElementById('profileDropdown');
    const header = document.querySelector(".header");
    const authButtons = document.querySelector(".auth-buttons");
    const profileContainer = document.querySelector(".profile-container");

    // Function to close all dropdowns
    function closeAllDropdowns() {
        if (dropdownContent) dropdownContent.classList.remove('show');
        if (profileDropdown) profileDropdown.classList.remove('show');
    }

    // Signup dropdown logic
    if (signupButton && dropdownContent) {
        signupButton.addEventListener('click', function(e) {
            e.stopPropagation();
            closeAllDropdowns();
            dropdownContent.classList.toggle('show');
        });

        // Add click handlers for dropdown buttons
        const bakerBtn = document.querySelector('.dropdown-btn[data-type="0"]');
        const customerBtn = document.querySelector('.dropdown-btn[data-type="1"]');

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
    if (profileButton && profileDropdown) {
        profileButton.addEventListener('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            closeAllDropdowns();
            profileDropdown.classList.toggle('show');
        });

        // Handle clicks inside the profile dropdown
        profileDropdown.addEventListener('click', function(e) {
            // Only stop propagation if not clicking a link
            if (!e.target.closest('a')) {
                e.stopPropagation();
            }
        });
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.profile-container') && !e.target.closest('.signup-container')) {
            closeAllDropdowns();
        }
    });

    // Close dropdowns when scrolling
    window.addEventListener('scroll', function() {
        closeAllDropdowns();
    });

    // Header scroll effect
    if (header) {
        window.addEventListener("scroll", () => {
            if (window.scrollY > 50) {
                header.classList.add("scrolled");
            } else {
                header.classList.remove("scrolled");
            }
        });
    }

    // Check authentication status and update UI
    checkAuthStatus().then(isLoggedIn => {
        if (authButtons && profileContainer) {
            if (isLoggedIn) {
                authButtons.classList.add("hidden");
                profileContainer.classList.remove("hidden");
            } else {
                authButtons.classList.remove("hidden"); 
                profileContainer.classList.add("hidden");
            }
        }
    });
});

// Function to check authentication status
async function checkAuthStatus() {
    try {
        const response = await fetch('check_auth.php');
        const data = await response.json();
        return data.isLoggedIn;
    } catch (error) {
        console.error('Error checking authentication status:', error);
        return false;
    }
}

function redirectToLogin() {
    window.location.href = 'login.php';
}
