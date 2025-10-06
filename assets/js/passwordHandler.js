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

    // Ensure all required elements exist before attaching listeners
    if (!passwordInput || !confirmInput || !requirementsBox || !continueButton) {
        console.error('Password Handler failed to initialize: Missing required HTML elements.');
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

    // --- Show/Hide Toggle ---
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
