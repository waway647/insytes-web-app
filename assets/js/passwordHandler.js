/**
 * Reusable Password Requirements + Show/Hide Handler
 * * Validates password strength and confirm password field in real time.
 * * Provides UI feedback + enables/disables submit button.
 * * Adds show/hide toggle functionality for password inputs.
 * 
 * Dependencies: 
 * - HTML elements with IDs: 'password', 'retype_password', 
 *   'password-requirements', 'match-error', 'continue-button', 'password-form'.
 * - List items with IDs: 'req-length', 'req-uppercase', 'req-lowercase', 'req-number'.
 * - Optional: Buttons with class 'toggle-password' and data-target="inputId"
 */

document.addEventListener('DOMContentLoaded', () => {
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('retype_password');
    const requirementsBox = document.getElementById('password-requirements');
    const matchError = document.getElementById('match-error');
    const continueButton = document.getElementById('continue-button');
    const form = document.getElementById('password-form');
    const loginTogglePassword = document.getElementById('toggle-password');
    const retypeTogglePassword = document.getElementById('toggle-retype-password');

    // Initialize login toggle functionality regardless of other elements
    if (loginTogglePassword) {
        console.log('Found login toggle button');
        const loginPasswordInput = document.getElementById('password');
        const eyeClosed = document.getElementById('eye-closed');
        const eyeOpen = document.getElementById('eye-open');
        
        console.log('Elements found:', {
            loginPasswordInput: !!loginPasswordInput,
            eyeClosed: !!eyeClosed,
            eyeOpen: !!eyeOpen
        });
        
        if (loginPasswordInput && eyeClosed && eyeOpen) {
            console.log('Adding click listener to main password toggle');
            loginTogglePassword.addEventListener('click', function() {
                console.log('Main password toggle clicked');
                // Toggle password input type
                if (loginPasswordInput.type === 'password') {
                    loginPasswordInput.type = 'text';
                    eyeClosed.classList.add('hidden');
                    eyeOpen.classList.remove('hidden');
                    console.log('Password shown');
                } else {
                    loginPasswordInput.type = 'password';
                    eyeClosed.classList.remove('hidden');
                    eyeOpen.classList.add('hidden');
                    console.log('Password hidden');
                }
            });
        }
    }

    // Initialize retype password toggle functionality
    if (retypeTogglePassword) {
        const retypePasswordInput = document.getElementById('retype_password');
        const eyeClosedRetype = document.getElementById('eye-closed-retype');
        const eyeOpenRetype = document.getElementById('eye-open-retype');

         console.log('Elements found:', {
            retypePasswordInput: !!retypePasswordInput,
            eyeClosed: !!eyeClosedRetype,
            eyeOpen: !!eyeOpenRetype
        });
        
        if (retypePasswordInput && eyeClosedRetype && eyeOpenRetype) {
            retypeTogglePassword.addEventListener('click', function() {
                // Toggle password input type
                if (retypePasswordInput.type === 'password') {
                    retypePasswordInput.type = 'text';
                    eyeClosedRetype.classList.add('hidden');
                    eyeOpenRetype.classList.remove('hidden');
                } else {
                    retypePasswordInput.type = 'password';
                    eyeClosedRetype.classList.remove('hidden');
                    eyeOpenRetype.classList.add('hidden');
                }
            });
        }
    }

    // Only continue with password requirements if all required elements exist
    if (!passwordInput || !confirmInput || !requirementsBox || !continueButton) {
        // If this is just the login page, that's fine - the toggle still works
        if (!loginTogglePassword) {
            console.error('Password Handler failed to initialize: Missing required HTML elements.');
        }
        return;
    }

    // --- Rules ---
    const rules = [
        { id: 'req-length', regex: /.{8,50}/ },
        { id: 'req-uppercase', regex: /[A-Z]/ },
        { id: 'req-lowercase', regex: /[a-z]/ },
        { id: 'req-number', regex: /[0-9]/ }
    ];

    // --- Helpers ---
    function updateButtonState(isAllValid, isMatch) {
        const isValid = isAllValid && isMatch;
        continueButton.disabled = !isValid;
        continueButton.classList.toggle('opacity-50', !isValid);
        continueButton.classList.toggle('cursor-not-allowed', !isValid);
    }

    function validatePassword() {
        const password = passwordInput.value;
        let allRulesValid = true;

        // 1. Check Requirements
        rules.forEach(rule => {
            const isValid = rule.regex.test(password);
            const listItem = document.getElementById(rule.id);
            if (!listItem) return;

            if (!isValid) {
                allRulesValid = false;
                listItem.classList.replace('text-green-400', 'text-red-400');
                listItem.querySelector('svg path')
                    .setAttribute('d', 'M6 18L18 6M6 6l12 12'); // X
            } else {
                listItem.classList.replace('text-red-400', 'text-green-400');
                listItem.querySelector('svg path')
                    .setAttribute('d', 'M5 13l4 4L19 7'); // Check
            }
        });

        // 2. Match check
        const isMatch = password === confirmInput.value && (password.length > 0 || confirmInput.value.length === 0);
        const shouldShowMatchError = confirmInput.value.length > 0 && !isMatch;

        if (matchError) {
            matchError.classList.toggle('hidden', !shouldShowMatchError);
        }

        // 3. Update button state
        const isReadyForSubmission = allRulesValid && isMatch && password.length > 0;
        updateButtonState(isReadyForSubmission, isMatch);
    }

    // --- Show/Hide Toggle (Generic) ---
    const toggleButtons = document.querySelectorAll('.toggle-password');
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);

            if (!targetInput) return;

            const eyeOpen = btn.querySelector('.eye-open');
            const eyeClosed = btn.querySelector('.eye-closed');

            if (targetInput.type === 'password') {
                targetInput.type = 'text';
                if (eyeOpen && eyeClosed) {
                    eyeOpen.classList.add('hidden');
                    eyeClosed.classList.remove('hidden');
                }
            } else {
                targetInput.type = 'password';
                if (eyeOpen && eyeClosed) {
                    eyeOpen.classList.remove('hidden');
                    eyeClosed.classList.add('hidden');
                }
            }
        });
    });

    // --- Events ---
    passwordInput.addEventListener('focus', () => {
        requirementsBox.classList.remove('hidden');
        validatePassword();
    });

    passwordInput.addEventListener('blur', () => {
        if (passwordInput.value.length === 0) {
            requirementsBox.classList.add('hidden');
        }
    });

    passwordInput.addEventListener('input', validatePassword);
    confirmInput.addEventListener('input', validatePassword);

    if (form) {
        form.addEventListener('submit', (e) => {
            if (continueButton.disabled) {
                e.preventDefault();
                console.warn("Attempted submission with invalid or incomplete password.");
            }
        });
    }

    // Initial validation (for autofill)
    validatePassword();
});
