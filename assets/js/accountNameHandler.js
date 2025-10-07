/**
 * Reusable Name Validation Handler for Inline Editing
 * * * Attaches validation listeners to the 'firstname' and (optionally) 'lastname' inputs.
 * Checks for: non-empty, valid characters, and appropriate length.
 * * * Controls the disabled state of their respective Save buttons:
 * - 'save-firstname-button' is disabled if 'firstname' is invalid.
 * - 'save-lastname-button' is disabled if 'lastname' is invalid.
 */
document.addEventListener('DOMContentLoaded', () => {
    // Identify input elements and their feedback messages
    const firstNameInput = document.getElementById('firstname');
    const lastNameInput = document.getElementById('lastname');
    const firstNameFeedback = document.getElementById('firstname-feedback');
    const lastNameFeedback = document.getElementById('lastname-feedback');

    // Identify the specific Save buttons to control
    const saveFirstNameButton = document.getElementById('save-firstname-button');
    const saveLastNameButton = document.getElementById('save-lastname-button');

    // Mandatory elements check: Must have first name input and its specific save button.
    if (!firstNameInput || !saveFirstNameButton) {
        console.error('Name Handler failed to initialize: Missing mandatory elements (ID: "firstname" or "save-firstname-button").');
        return;
    }
    
    // Regex allows letters (A-Z, a-z), spaces, hyphens, and apostrophes.
    const NAME_REGEX = /^[A-Za-z\s'.-]{1,50}$/; 
    const MIN_LENGTH = 1;
    const MAX_LENGTH = 50;

    /**
     * Helper function for individual field validation
     * @param {HTMLElement | null} inputElement - The input field (e.g., firstNameInput).
     * @param {HTMLElement | null} feedbackElement - The paragraph for error messages (or null if not used).
     * @returns {boolean} True if the field's current value is valid and not empty.
     */
    function validateNameField(inputElement, feedbackElement) {
        if (!inputElement) {
            // If the input element doesn't exist, treat it as valid.
            return true;
        }

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

        // Update UI only if the feedback element is provided
        if (feedbackElement) {
            feedbackElement.textContent = message;
            // Show red if invalid AND the user has typed something (name.length > 0)
            feedbackElement.classList.toggle('text-red-400', !isValid && name.length > 0);
            // Show green if valid AND the user has typed something
            feedbackElement.classList.toggle('text-green-400', isValid && name.length > 0);
        }
        
        // Return true only if valid AND not empty
        return isValid && name.length > 0;
    }

    /**
     * Main validation function, checks fields and updates their respective button states.
     */
    function validateForm() {
        // --- First Name Validation ---
        const isFirstNameValid = validateNameField(firstNameInput, firstNameFeedback);
        if (saveFirstNameButton) {
            saveFirstNameButton.disabled = !isFirstNameValid;
            saveFirstNameButton.classList.toggle('opacity-50', !isFirstNameValid);
            saveFirstNameButton.classList.toggle('cursor-not-allowed', !isFirstNameValid);
        }

        // --- Last Name Validation (Only if inputs and button exist) ---
        if (lastNameInput && saveLastNameButton) {
            const isLastNameValid = validateNameField(lastNameInput, lastNameFeedback);
            saveLastNameButton.disabled = !isLastNameValid;
            saveLastNameButton.classList.toggle('opacity-50', !isLastNameValid);
            saveLastNameButton.classList.toggle('cursor-not-allowed', !isLastNameValid);
        }
    }

    // --- Event Listeners ---
    firstNameInput.addEventListener('input', validateForm);
    
    // Only attach listener for last name if the input element exists
    if (lastNameInput) {
        lastNameInput.addEventListener('input', validateForm);
    }
    
    // Initial validation check on load (important for autofill scenarios)
    validateForm();
});
