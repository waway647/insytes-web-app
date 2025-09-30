/**
 * Reusable Password Requirements Handler
 * * Attaches validation listeners to password and confirm password fields (ID: 'password', ID: 'retype_password') 
 * and provides real-time feedback to the UI elements defined in the view.
 * * Dependencies: 
 * - HTML elements with IDs: 'password', 'retype_password', 'password-requirements', 'match-error', 'continue-button', 'password-form'.
 * - List items with IDs: 'req-length', 'req-uppercase', 'req-lowercase', 'req-number'.
 */
document.addEventListener('DOMContentLoaded', () => {
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('retype_password');
    const requirementsBox = document.getElementById('password-requirements');
    const matchError = document.getElementById('match-error');
    const continueButton = document.getElementById('continue-button');
    const form = document.getElementById('password-form');

    // Ensure all required elements exist before attaching listeners
    if (!passwordInput || !confirmInput || !requirementsBox || !continueButton) {
        console.error('Password Handler failed to initialize: Missing required HTML elements.');
        return;
    }

    // Define the validation rules and their corresponding UI elements
    const rules = [
        { id: 'req-length', regex: /.{8,50}/ },
        { id: 'req-uppercase', regex: /[A-Z]/ },
        { id: 'req-lowercase', regex: /[a-z]/ },
        { id: 'req-number', regex: /[0-9]/ }
    ];

    // Helper function to update the button state
    function updateButtonState(isAllValid, isMatch) {
        const isValid = isAllValid && isMatch;
        continueButton.disabled = !isValid;
        continueButton.classList.toggle('opacity-50', !isValid);
        continueButton.classList.toggle('cursor-not-allowed', !isValid);
    }

    // --- Core Validation Logic ---
    function validatePassword() {
        const password = passwordInput.value;
        let allRulesValid = true;

        // 1. Check Requirements and update UI icons/colors
        rules.forEach(rule => {
            const isValid = rule.regex.test(password);
            const listItem = document.getElementById(rule.id);
            
            if (!listItem) return; // Skip if a requirement item is missing
            
            if (!isValid) {
                allRulesValid = false;
                listItem.classList.replace('text-green-400', 'text-red-400');
                // Change icon to X (for failure)
                listItem.querySelector('svg path').setAttribute('d', 'M6 18L18 6M6 6l12 12');
            } else {
                listItem.classList.replace('text-red-400', 'text-green-400');
                // Change icon to Checkmark (for success)
                listItem.querySelector('svg path').setAttribute('d', 'M5 13l4 4L19 7');
            }
        });

        // 2. Check Password Match
        const isMatch = password === confirmInput.value && (password.length > 0 || confirmInput.value.length === 0);
        // Only show error if confirmation field has input OR if password has input but is empty/mismatched
        const shouldShowMatchError = confirmInput.value.length > 0 && !isMatch;

        if (matchError) {
             matchError.classList.toggle('hidden', !shouldShowMatchError);
        }

        // 3. Update Continue Button
        // Button is enabled ONLY if all rules are valid AND passwords match AND fields are not empty
        const isReadyForSubmission = allRulesValid && isMatch && password.length > 0;
        updateButtonState(isReadyForSubmission, isMatch);
    }

    // --- Event Listeners ---

    // Show requirements on focus
    passwordInput.addEventListener('focus', () => {
        requirementsBox.classList.remove('hidden');
        validatePassword(); // Run validation immediately on focus
    });

    // Hide requirements on blur, but only if input is empty
    passwordInput.addEventListener('blur', () => {
        if (passwordInput.value.length === 0) {
             requirementsBox.classList.add('hidden');
        }
    });

    // Validate on password input change
    passwordInput.addEventListener('input', validatePassword);

    // Validate match on confirm input change
    confirmInput.addEventListener('input', validatePassword);

    // Prevent form submission if validation fails (final safety check)
    form.addEventListener('submit', (e) => {
        // Since we disable the button, this should rarely be hit, but acts as a safeguard.
        if (continueButton.disabled) {
            e.preventDefault();
            console.warn("Attempted submission with invalid or incomplete password.");
        }
    });

    // Run validation on load to correctly initialize the button state (e.g., if using browser autofill)
    validatePassword();
});