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
    updateUI('loading');

        try {
            const response = await fetch(checkEmailUrl, { 
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email })
            });

            if (!response.ok) {
                throw new Error("Network response was not ok");
            }

            const data = await response.json();
            console.log("Server response:", data); // debug

            if (data.unique === true) {
                updateUI('available');
            } else {
                updateUI('used');
            }
        } catch (error) {
            console.error("Uniqueness check failed:", error);
            // fallback → disable button
            updateUI('initial');
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