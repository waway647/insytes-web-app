/**
 * otpFormHandler.js
 * * Handles all client-side logic for the OTP verification form:
 * 1. Timer countdown (3 minutes).
 * 2. OTP input field manipulation (auto-tabbing, backspace, paste).
 * 3. Form submission (using fetch) and handling server response for OTP validation.
 * 4. Displaying feedback messages (wrong OTP, timer expired, resend).
 */

document.addEventListener('DOMContentLoaded', () => {
    const otpContainer = document.getElementById('otp-container');
    const inputs = otpContainer ? otpContainer.querySelectorAll('.otp-input') : [];
    const finalOtpInput = document.getElementById('final_otp_code');
    const form = document.getElementById('otp-form');
    const timerDisplay = document.getElementById('otp-timer');
    const feedbackContainer = document.getElementById('otp-feedback-container'); // You need to add this to your HTML

    const INITIAL_TIME_SECONDS = APP_CONFIG.InitialTimeSeconds; // 3 minutes
    let timeRemaining = INITIAL_TIME_SECONDS;
    let timerInterval;

    // --- Utility Functions ---

    /**
     * Clears and displays a feedback message (e.g., wrong OTP).
     * @param {string} message - The message to display.
     * @param {string} type - 'success' or 'error'.
     * @param {boolean} [isHtml=false] - Whether the message contains HTML.
     */
    function showFeedback(message, type, isHtml = false) {
        if (!feedbackContainer) return;

        // Base classes for consistent positioning
        feedbackContainer.className = 'mt-3 p-3 rounded-lg text-sm text-center font-medium transition duration-300';

        if (type === 'error') {
            feedbackContainer.classList.add('bg-red-900/50', 'text-red-400', 'border', 'border-red-700');
        } else if (type === 'success') {
            feedbackContainer.classList.add('bg-green-900/50', 'text-green-400', 'border', 'border-green-700');
        } else { // Default/info
            feedbackContainer.classList.add('bg-gray-700/50', 'text-gray-300');
        }

        if (isHtml) {
            feedbackContainer.innerHTML = message;
        } else {
            feedbackContainer.textContent = message;
        }
    }
    
    /**
     * Clears all feedback messages.
     */
    function clearFeedback() {
        if (feedbackContainer) {
            feedbackContainer.innerHTML = '';
            feedbackContainer.className = '';
        }
    }

    // --- Timer Logic ---

    /**
     * Formats seconds into MM:SS string.
     * @param {number} totalSeconds - The time in seconds.
     * @returns {string} Formatted time string.
     */
    function formatTime(totalSeconds) {
        const minutes = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
        const seconds = String(totalSeconds % 60).padStart(2, '0');
        return `${minutes}:${seconds}`;
    }

    /**
     * Updates the timer display every second.
     */
    function updateTimer() {
        if (!timerDisplay) return;

        if (timeRemaining <= 0) {
            clearInterval(timerInterval);
            timerDisplay.textContent = '00:00';
            timerDisplay.classList.add('text-red-400');
            
            const resendHtml = `OTP Expired. <a href="#" id="resend-otp-link" class="font-bold text-indigo-400 hover:text-indigo-300">Resend Code</a>`;
            showFeedback(resendHtml, 'error', true);
            
            // Disable inputs and button
            inputs.forEach(input => input.disabled = true);
            document.getElementById('verify-button').disabled = true;

            // Add event listener to the resend link
            const resendLink = document.getElementById('resend-otp-link');
            if (resendLink) {
                resendLink.addEventListener('click', handleResendOtp);
            }

        } else {
            timerDisplay.textContent = formatTime(timeRemaining);
            timeRemaining--;
        }
    }

    /**
     * Starts the timer countdown.
     */
    function startTimer() {
        if (!timerDisplay) return;
        
        // Ensure the timer is cleared if it was running
        if (timerInterval) clearInterval(timerInterval);
        
        timeRemaining = INITIAL_TIME_SECONDS;
        timerDisplay.classList.remove('text-red-400');
        inputs.forEach(input => input.disabled = false);
        document.getElementById('verify-button').disabled = false;
        
        updateTimer(); // Initial call
        timerInterval = setInterval(updateTimer, 1000);
    }
    
    // --- OTP Input Logic ---

    /**
     * Updates the hidden input field with the combined OTP and controls the button state.
     */
    function updateFinalOtp() {
        let otpCode = '';
        inputs.forEach(input => {
            otpCode += input.value;
        });
        finalOtpInput.value = otpCode;
        
        const button = document.getElementById('verify-button');
        const isComplete = otpCode.length === inputs.length;

        button.disabled = !isComplete;
        button.classList.toggle('opacity-50', !isComplete);
        button.classList.toggle('cursor-not-allowed', !isComplete);

        // Clear feedback if the user starts typing again
        clearFeedback();
    }

    /**
     * Initializes all event listeners for the OTP inputs.
     */
    function initializeOtpInputs() {
        // Initial state
        updateFinalOtp(); 

        inputs.forEach((input, index) => {
            // 1. Enforce numeric input and auto-tab forward
            input.addEventListener('input', (e) => {
                // Remove non-numeric characters and enforce max length 1
                e.target.value = e.target.value.replace(/[^0-9]/g, '').slice(0, 1);
                
                // Auto-tab to the next field if a value was entered
                if (e.target.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }

                // If it's the last input and it's filled, trigger form submission attempt
                if (index === inputs.length - 1 && e.target.value.length === 1) {
                     // Optionally, automatically submit the form here
                     // form.dispatchEvent(new Event('submit')); 
                }

                updateFinalOtp();
            });

            // 2. Handle backspace/delete to auto-tab backward
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && e.target.value.length === 0 && index > 0) {
                    e.preventDefault(); // Stop default backspace action
                    inputs[index - 1].focus();
                    inputs[index - 1].value = ''; // Clear the previous field
                    updateFinalOtp();
                }
            });

            // 3. Handle paste events
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text');
                const digits = paste.replace(/[^0-9]/g, '').slice(0, inputs.length);

                digits.split('').forEach((digit, i) => {
                    if (inputs[index + i]) {
                        inputs[index + i].value = digit;
                    }
                });
                
                // Move focus to the last filled input or the next empty one
                const lastIndex = index + digits.length;
                if (lastIndex < inputs.length) {
                    inputs[lastIndex].focus();
                } else {
                    inputs[inputs.length - 1].focus();
                }
                updateFinalOtp();
            });
        });
    }

    // --- Form Submission and Server Interaction ---

    /**
     * Handles the form submission via Fetch API.
     * @param {Event} e - The submit event.
     */
    async function handleFormSubmit(e) {
        e.preventDefault();

        // Final check before sending
        if (finalOtpInput.value.length !== inputs.length) {
            showFeedback('Please enter the complete 6-digit code.', 'error');
            return;
        }

        // Disable button and show loading state
        const button = document.getElementById('verify-button');
        const originalButtonText = button.textContent;
        button.disabled = true;
        button.textContent = 'Verifying...';

        clearFeedback();

        try {
            const formData = new FormData(form);
            const actionUrl = form.getAttribute('action');

            const response = await fetch(actionUrl, {
                method: 'POST',
                body: formData,
            });

            // Assuming your server returns a JSON response:
            // e.g., { status: 'success', redirect: '/new-password-page' }
            // or { status: 'error', message: 'Wrong OTP, please try again.' }
            const result = await response.json(); 

            if (response.ok && result.status === 'success') {
                showFeedback('Verification successful!', 'success');
                // Redirect on success
                window.location.href = result.redirect; 
            } else {
                // Handle wrong OTP or other server-side errors
                const errorMessage = result.message || 'Verification failed. Please check your code.';
                showFeedback(errorMessage, 'error');
                // Optionally clear the inputs on error
                inputs.forEach(input => input.value = ''); 
                inputs[0].focus(); // Focus on the first input
                updateFinalOtp();
            }
        } catch (error) {
            console.error('Submission error:', error);
            showFeedback('An unexpected error occurred. Please try again.', 'error');
        } finally {
            // Re-enable button
            button.textContent = originalButtonText;
            updateFinalOtp(); // Re-check state which will re-enable if code is complete
        }
    }

    /**
     * Placeholder function to handle the Resend OTP action.
     * In a real application, this would make an AJAX call to the server.
     * @param {Event} e - The click event.
     */
    async function handleResendOtp(e) {
        e.preventDefault();
        
        const resendLink = document.getElementById('resend-otp-link');
        const originalText = resendLink.textContent;
        resendLink.textContent = 'Sending...';
        resendLink.style.pointerEvents = 'none'; // Disable link

        // --- Simulated Server Call ---
        try {
            const response = await fetch(APP_CONFIG.resendUrl, {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            });

            const result = await response.json();

            if (response.ok && result.status === 'success') {
                showFeedback(result.message, 'success');
                startTimer(); // Restart the timer
                inputs.forEach(input => input.value = '');
                inputs[0].focus(); // Focus on the first input
            } else {
                showFeedback(result.message || 'Failed to resend code. Please try again later.', 'error');
            }
        } catch (error) {
            console.error('Resend error:', error);
            showFeedback('An unexpected error occurred. Please try again.', 'error');
        } finally {
            resendLink.textContent = originalText;
            resendLink.style.pointerEvents = 'auto'; // Re-enable link
        }
    }
    

    // --- Initialization ---

    if (form && inputs.length > 0) {
        initializeOtpInputs();
        form.addEventListener('submit', handleFormSubmit);
    }
    
    // Start the timer when the page loads
    startTimer();
});