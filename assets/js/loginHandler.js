/**
 * Login Validation Handler
 * * Attaches an event listener to the login form (ID: 'login-form').
 * * Prevents default submission and simulates an asynchronous credential check.
 * * Updates the UI with error feedback if credentials are invalid.
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

    // Hardcoded User Data (Simulates a database check)
    // In a real application, the password would be a hashed value (e.g., bcrypt hash).
    // For this demonstration, we use plaintext '123456' for the demo user.
    const HARDCODED_USERS = [
        { email: 'user@example.com', password: '123456', id: 101, redirect: '/dashboard' },
        // Add more hardcoded users here for testing different scenarios
    ];

    // Helper to display feedback
    const displayFeedback = (message, isError = true) => {
        feedbackArea.textContent = message;
        feedbackArea.classList.remove('hidden');
        feedbackArea.classList.toggle('text-red-400', isError);
        feedbackArea.classList.toggle('text-green-400', !isError);
    };

    // Helper to control button state during async operation
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

        // --- START SIMULATED ASYNCHRONOUS CHECK (Replace with real fetch) ---
        setTimeout(() => { // Simulates network latency

            // Check hardcoded credentials
            const user = HARDCODED_USERS.find(u => u.email === email && u.password === password);

            if (user) {
                // SUCCESS: Credentials Matched
                displayFeedback("Login successful! Redirecting...", false);
                // In a real application, the server would return a session token/cookie here.
                
                // Example: window.location.href = user.redirect; 
                console.log("Login Success. User:", user);

            } else {
                // FAILURE: Invalid Credentials
                displayFeedback("Invalid email or password. Please try again.");
                console.warn("Login Failed for:", email);
            }

            setButtonState(false);
        }, 1000); // Wait 1 second before showing result
        // --- END SIMULATED CHECK ---


        /*
        // --- START REAL CODEIGNITER API CALL (Uncomment and implement server endpoint) ---
        // const LOGIN_ENDPOINT = 'auth/login_attempt'; // Replace with your actual CI controller endpoint

        // fetch(LOGIN_ENDPOINT, {
        //     method: 'POST',
        //     headers: { 'Content-Type': 'application/json' },
        //     body: JSON.stringify({ email: email, password: password })
        // })
        // .then(response => response.json())
        // .then(data => {
        //     if (data.success) {
        //         displayFeedback("Login successful! Redirecting...", false);
        //         // Handle success, e.g., save token and redirect
        //         // window.location.href = data.redirect_url;
        //     } else {
        //         displayFeedback(data.message || "Invalid email or password. Please try again.");
        //     }
        // })
        // .catch(error => {
        //     console.error('Login error:', error);
        //     displayFeedback("An unexpected error occurred. Please try again later.");
        // })
        // .finally(() => {
        //     setButtonState(false);
        // });
        // --- END REAL API CALL ---
        */
    };

    loginForm.addEventListener('submit', handleLogin);
});