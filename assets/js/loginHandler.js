/**
 * Login Validation Handler (CodeIgniter Integrated)
 * - Attaches an event listener to the login form (ID: 'login-form').
 * - Prevents default submission and sends credentials to CI backend.
 * - Displays success/error messages from server response.
 */

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const feedbackArea = document.getElementById('login-feedback');
    const signInButton = document.getElementById('login-button');

    if (!loginForm || !emailInput || !passwordInput || !feedbackArea || !signInButton) {
        console.error('Login Handler failed to initialize: Missing required HTML elements.');
        return;
    }

    // --- Helpers ---
    const displayFeedback = (message, isError = true) => {
        feedbackArea.textContent = message;
        feedbackArea.classList.remove('hidden');
        feedbackArea.classList.toggle('text-red-400', isError);
        feedbackArea.classList.toggle('text-green-400', !isError);
    };

    const setButtonState = (isLoading) => {
        signInButton.disabled = isLoading;
        signInButton.classList.toggle('opacity-50', isLoading);
        signInButton.textContent = isLoading ? 'Signing In...' : 'Sign in';
    };

    // --- Core Login Function ---
    const handleLogin = (e) => {
        e.preventDefault();
        setButtonState(true);
        displayFeedback("", false); // Clear previous feedback

        const email = emailInput.value.trim();
        const password = passwordInput.value;

        // 🔹 CodeIgniter Endpoint
        const LOGIN_ENDPOINT = validateLoginURL; 
        // If you use base_url in views, you can echo it:
        // const LOGIN_ENDPOINT = "<?= base_url('LoginController/process_login') ?>";

        fetch(LOGIN_ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ email, password })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayFeedback("Login successful! Redirecting...", false);
                // Redirect after short delay
                setTimeout(() => {
                    window.location.href = data.redirect_url; // adjust your redirect page
                }, 1000);
            } else {
                displayFeedback(data.message || "Invalid email or password. Please try again.");
            }
        })
        .catch(error => {
            console.error('Login error:', error);
            displayFeedback("An unexpected error occurred. Please try again later.");
        })
        .finally(() => {
            setButtonState(false);
        });
    };

    loginForm.addEventListener('submit', handleLogin);
});
