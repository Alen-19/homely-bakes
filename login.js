document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const emailError = document.getElementById('email-error');
    const passwordError = document.getElementById('password-error');
    const form = document.querySelector('form');

    // Regular expressions for validation
    const emailRegex = /^(?=[^@]*[a-zA-Z]{3,})[a-zA-Z0-9._%+-]+@[a-zA-Z0-9-]+(\.[a-zA-Z]{2,})+$/;
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>])[A-Za-z\d!@#$%^&*(),.?":{}|<>]{8,}$/;

    function showError(element, errorElement, message) {
        element.classList.add('error');
        errorElement.textContent = message;
        errorElement.classList.add('visible');
    }

    function hideError(element, errorElement) {
        element.classList.remove('error');
        errorElement.textContent = '';
        errorElement.classList.remove('visible');
    }

    async function validateEmail() {
        const email = emailInput.value.trim();

        if (!email) {
            showError(emailInput, emailError, 'Email is required');
            return false;
        }

        if (!emailRegex.test(email)) {
            showError(emailInput, emailError, 'Please enter a valid email address');
            return false;
        }

        try {
            const response = await fetch(`?check_email=${encodeURIComponent(email)}`);
            const data = await response.json();
            
            if (!data.exists) {
                showError(emailInput, emailError, 'Email not registered');
                return false;
            }
            
            hideError(emailInput, emailError);
            return true;
        } catch (error) {
            console.error('Error checking email:', error);
            showError(emailInput, emailError, 'Error checking email');
            return false;
        }
    }

    async function validatePassword() {
        const password = passwordInput.value.trim();
        const email = emailInput.value.trim();

        if (!password) {
            showError(passwordInput, passwordError, 'Password is required');
            return false;
        }

        if (!passwordRegex.test(password)) {
            showError(passwordInput, passwordError, 'Password must contain at least 8 characters, including uppercase, lowercase, number, and special character');
            return false;
        }

        // Only check password with server if email exists and password format is valid
        if (await validateEmail()) {
            try {
                const formData = new FormData();
                formData.append('check_password', password);
                formData.append('email', email);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (!data.valid) {
                    showError(passwordInput, passwordError, 'Wrong password');
                    return false;
                }
                
                hideError(passwordInput, passwordError);
                return true;
            } catch (error) {
                console.error('Error validating password:', error);
                return false;
            }
        }
        
        return false;
    }

    // Real-time validation
    let emailTimeout;
    emailInput.addEventListener('input', () => {
        clearTimeout(emailTimeout);
        if (emailInput.value.trim()) {
            emailTimeout = setTimeout(() => {
                validateEmail();
            }, 500);
        } else {
            hideError(emailInput, emailError);
        }
    });

    let passwordTimeout;
    passwordInput.addEventListener('input', () => {
        clearTimeout(passwordTimeout);
        if (passwordInput.value.trim()) {
            passwordTimeout = setTimeout(() => {
                validatePassword();
            }, 500);
        } else {
            hideError(passwordInput, passwordError);
        }
    });

    // Validate on blur
    emailInput.addEventListener('blur', validateEmail);
    passwordInput.addEventListener('blur', validatePassword);

    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const isEmailValid = await validateEmail();
        const isPasswordValid = await validatePassword();

        if (isEmailValid && isPasswordValid) {
            this.submit();
        }
    });
});