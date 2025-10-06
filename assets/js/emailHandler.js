/**
 * Reusable Email Requirements Handler
 * * Attaches validation listeners to the email input (ID: 'email') to check:
 * 1. Proper email format (client-side regex).
 * 2. Allowed TLD/suffix (STRICT check for common providers, flexible for ccTLDs).
 * 3. Uniqueness (simulated asynchronous server check).
 * * It updates the feedback UI and controls the submission button state.
 */

// Simple regex for general format validation
// Now allows 2-6 character TLDs (e.g., .com, .ph, .co.uk, .global)
const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,6}$/; 

// 1. Define the allowed suffixes (add more as needed)
const ALLOWED_SUFFIXES = [
    '@gmail.com',
    '@yahoo.com',
    '@outlook.com',
    '@hotmail.com',
    '@aol.com',
    '@icloud.com',
    '@live.com',
    '@mail.com',
    '@protonmail.com',
    '@zoho.com',
    '@gmx.com',
    '@yandex.com',
    // Add more common providers as needed
];

// Generic TLDs that are typically associated with large mail providers
const GENERIC_TLDS = ['.com', '.net', '.org']; 

// TLDs we allow outside of the specific providers (e.g., ccTLDs, edu/gov)
const ALLOWED_TLDS_FOR_GENERIC_DOMAIN = [
    '.edu', 
    '.gov',
    '.ph',  // Philippines country code TLD
    '.ca',  // Canada
    '.uk',  // United Kingdom
    '.de',  // Germany
    // All other ccTLDs you listed...
    '.au', '.jp', '.fr', '.in', '.br', '.za', '.ru', '.cn', '.mx', '.es', 
    '.it', '.nl', '.se', '.no', '.fi', '.dk', '.ch', '.be', '.at', '.ie', 
    '.nz', '.sg', '.hk', '.kr', '.tw', '.tr', '.sa', '.ae', '.us', 
];


const DEBOUNCE_DELAY = 500; // Wait 500ms after user stops typing
let debounceTimer;


// 2. New function to check for allowed suffix (Updated Logic)
const isValidSuffix = (email) => {
    const lowerEmail = email.toLowerCase();
    
    // --- STEP 1: Check for explicit, full provider match (e.g., @gmail.com) ---
    const isProviderMatch = ALLOWED_SUFFIXES.some(suffix => lowerEmail.endsWith(suffix));
    if (isProviderMatch) {
        return true;
    }
    
    // --- STEP 2: Handle non-whitelisted domains (like local/government/ccTLD) ---
    // Extract the TLD (e.g., '.com', '.ph')
    const TLD_REGEX = /\.([a-zA-Z]{2,6})$/; 
    const TLD_MATCH = lowerEmail.match(TLD_REGEX);

    if (TLD_MATCH) {
        const tld = TLD_MATCH[0]; 
        
        // **CRITICAL FIX:** If the TLD is generic (.com, .net, .org), 
        // and it didn't pass STEP 1, it must be rejected (e.g., gmil.com)
        if (GENERIC_TLDS.includes(tld)) {
            return false; // Rejects domains like @gmil.com, @yaho.com, etc.
        }

        // If the TLD is not generic (e.g., .edu, .gov, or a ccTLD like .ph), 
        // allow it, as we assume it's a valid non-major-provider email.
        if (ALLOWED_TLDS_FOR_GENERIC_DOMAIN.includes(tld)) {
            return true;
        }
    }

    return false; // Fails if not a whitelisted provider and not a whitelisted non-generic TLD.
};

// --- REST OF THE CODE REMAINS THE SAME ---

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
            case 'invalid_suffix':
                // UPDATED MESSAGE to reflect the stricter check
                emailFeedback.textContent = 'Please use a common email provider (e.g., @gmail.com) or a government/country-specific domain (e.g., ending in .ph or .edu).';
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
            // NOTE: 'checkEmailUrl' must be a defined variable in your global scope or elsewhere.
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
        
        // 2. Check Allowed Suffix (NEW VALIDATION STEP)
        if (!isValidSuffix(email)) {
            updateUI('invalid_suffix');
            return;
        }
        
        // 3. If format is valid, start debounce for server check
        updateUI('loading'); 
        
        debounceTimer = setTimeout(() => {
            checkEmailUniqueness(email);
        }, DEBOUNCE_DELAY);
    });

    // Initial check for browser autofill scenarios
    if (emailInput.value.trim()) {
        // Only run uniqueness check if format and suffix are valid on load
        if (EMAIL_REGEX.test(emailInput.value.trim()) && isValidSuffix(emailInput.value.trim())) {
             checkEmailUniqueness(emailInput.value.trim());
        } else {
             updateUI('initial');
        }
       
    } else {
        updateUI('initial');
    }
});