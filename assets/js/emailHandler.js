/**
 * Reusable Email Requirements Handler
 * * Attaches validation listeners to the email input (ID: 'email') to check:
 * 1. Proper email format (client-side regex).
 * 2. Uniqueness (simulated asynchronous server check).
 * * It updates the feedback UI and controls the submission button state.
 */

// Simple regex for format validation
const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const DEBOUNCE_DELAY = 500; // Wait 500ms after user stops typing
let debounceTimer;

document.addEventListener('DOMContentLoaded', () => {
    const emailInput = document.getElementById('email');
    const emailFeedback = document.getElementById('email-feedback');
    const continueButton = document.getElementById('continue-button');
    const loadingSpinner = document.getElementById('loading-spinner');

    if (!emailInput || !continueButton || !emailFeedback || !loadingSpinner) {
        console.error('Email Handler failed to initialize: Missing required HTML elements.');
        return;
    }

    // --- UI State Management ---
    const updateUI = (state, message = '') => {
        // Reset state
        emailFeedback.textContent = '';
        emailFeedback.className = 'text-xs mt-1';
        loadingSpinner.classList.add('hidden');
        continueButton.disabled = true;
        continueButton.classList.add('opacity-50', 'cursor-not-allowed');
        
        // Apply new state
        switch (state) {
            case 'loading':
                loadingSpinner.classList.remove('hidden');
                break;
            case 'invalid_format':
                emailFeedback.textContent = 'Please enter a valid email address.';
                emailFeedback.classList.add('text-red-400');
                break;
            case 'used':
                emailFeedback.textContent = 'This email is already registered. Try logging in.';
                emailFeedback.classList.add('text-red-400');
                break;
            case 'available':
                emailFeedback.textContent = 'Email is valid and available!';
                emailFeedback.classList.add('text-green-400');
                continueButton.disabled = false;
                continueButton.classList.remove('opacity-50', 'cursor-not-allowed');
                break;
            case 'initial':
            default:
                // No feedback, button remains disabled
                break;
        }
    };

    // --- Server Check Simulation ---
    const checkEmailUniqueness = async (email) => {
        // IMPORTANT: You must create an endpoint in your CodeIgniter controller 
        // (e.g., auth/check_email_unique) that handles this POST request and 
        // queries your database for the email's existence.
        
        updateUI('loading');

        try {
            // Placeholder: Assume your CI endpoint is at 'auth/check_email_unique'
            const response = await fetch('auth/check_email_unique', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email }) 
            });
            
            // Assume the response body is JSON like: { unique: true/false }
            const data = await response.json(); 
            
            if (data.unique === true) {
                updateUI('available');
            } else {
                updateUI('used');
            }
        } catch (error) {
            console.error("Uniqueness check failed (API/Network Error):", error);
            // In a production environment, you might fail open (allow signup) or fail closed (error message) 
            // depending on security policy. Failing open for demonstration:
            updateUI('available'); 
        }
    };

    // --- Input Handling (Debounced) ---
    emailInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const email = emailInput.value.trim();
        
        if (!email) {
            updateUI('initial');
            return;
        }
        
        // 1. Check Format immediately
        if (!EMAIL_REGEX.test(email)) {
            updateUI('invalid_format');
            return;
        }
        
        // 2. If format is valid, start debounce for server check
        updateUI('loading'); 
        
        debounceTimer = setTimeout(() => {
            checkEmailUniqueness(email);
        }, DEBOUNCE_DELAY);
    });

    // Initial check for browser autofill scenarios
    if (emailInput.value.trim()) {
        checkEmailUniqueness(emailInput.value.trim());
    } else {
        updateUI('initial');
    }
});