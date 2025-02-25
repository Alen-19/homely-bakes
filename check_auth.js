// Check authentication status periodically
function checkAuthStatus() {
    fetch('check_auth.php')
        .then(response => response.json())
        .then(data => {
            if (!data.authenticated) {
                window.location.href = 'login.php?session_expired=1';
            }
        })
        .catch(error => console.error('Error:', error));
}

// Check auth status every 5 minutes
if (document.querySelector('.profile-container')) {
    setInterval(checkAuthStatus, 300000); // 5 minutes
    
    // Also check when user becomes active after being idle
    let timeoutId;
    document.addEventListener('mousemove', function() {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(checkAuthStatus, 1000);
    });
}
