/**
 * Reusable Name Validation Handler
 * * Attaches validation listeners to the firstname (ID: 'firstname') and 
 * lastname (ID: 'lastname') inputs.
 * * Checks for: non-empty, valid characters (letters, spaces, hyphens, apostrophes), 
 * and appropriate length (2-50 chars).
 * * Controls the final submission button state (ID: 'finish-button').
 */
document.addEventListener('DOMContentLoaded', () => {
    const firstNameInput = document.getElementById('firstname');
    const lastNameInput = document.getElementById('lastname');
    const firstNameFeedback = document.getElementById('firstname-feedback');
    const lastNameFeedback = document.getElementById('lastname-feedback');
    const finishButton = document.getElementById('finish-button');
    const form = document.getElementById('name-form'); 

    if (!firstNameInput || !lastNameInput || !finishButton) {
        console.error('Name Handler failed to initialize: Missing required HTML elements.');
        return;
    }
    
    // Regex allows letters (A-Z, a-z), spaces, hyphens, and apostrophes.
    const NAME_REGEX = /^[A-Za-z\s'.-]{1,50}$/; 
    const MIN_LENGTH = 1;
    const MAX_LENGTH = 50;

    // Helper function for individual field validation
    function validateNameField(inputElement, feedbackElement) {
        const name = inputElement.value.trim();
        let isValid = true;
        let message = '';

        if (name.length === 0) {
            isValid = false;
        } else if (name.length < MIN_LENGTH || name.length > MAX_LENGTH) {
            isValid = false;
            message = `Must be between ${MIN_LENGTH} and ${MAX_LENGTH} characters.`;
        } else if (!NAME_REGEX.test(name)) {
            isValid = false;
            message = 'Only letters, spaces, hyphens, and apostrophes are allowed.';
        }

        // Update UI
        if (feedbackElement) {
            feedbackElement.textContent = message;
            feedbackElement.classList.toggle('text-red-400', !isValid && name.length > 0);
            feedbackElement.classList.toggle('text-green-400', isValid && name.length > 0);
        }

        return isValid;
    }

    // Main validation function
    function validateForm() {
        const isFirstNameValid = validateNameField(firstNameInput, firstNameFeedback);
        const isLastNameValid = validateNameField(lastNameInput, lastNameFeedback);

        // Form is valid only if both names are valid AND not empty
        const isFormValid = isFirstNameValid && isLastNameValid && firstNameInput.value.trim() !== '' && lastNameInput.value.trim() !== '';

        // Update Button State
        finishButton.disabled = !isFormValid;
        finishButton.classList.toggle('opacity-50', !isFormValid);
        finishButton.classList.toggle('cursor-not-allowed', !isFormValid);
    }

    // --- Event Listeners ---
    firstNameInput.addEventListener('input', validateForm);
    lastNameInput.addEventListener('input', validateForm);
    
    // Initial validation check on load (important for autofill scenarios)
    validateForm();
});