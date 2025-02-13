document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');

    const emailPattern = /^(?=[^@]*[a-zA-Z]{3,})[a-zA-Z0-9._%+-]+@[a-zA-Z0-9-]+(\.[a-zA-Z]{2,})+$/;

    form.addEventListener('submit', function(e) {
        let isValid = true;

        // Reset previous error states
        emailInput.classList.remove('invalid');
        passwordInput.classList.remove('invalid');

        // Email validation
        if (!emailPattern.test(emailInput.value)) {
            emailInput.classList.add('invalid');
            isValid = false;
        }

        // Password validation
        if (!passwordInput.value || passwordInput.value.length < 8) {
            passwordInput.classList.add('invalid');
            isValid = false;
        }

        // Prevent form submission if invalid
        if (!isValid) {
            e.preventDefault();
        }
    });

    // Optional: Instant feedback when typing
    emailInput.addEventListener('input', function() {
        if (emailPattern.test(emailInput.value)) {
            emailInput.classList.remove('invalid');
            emailInput.style.border = "2px solid green";
        } else {
            emailInput.style.border = "2px solid red";
        }
    });
});
