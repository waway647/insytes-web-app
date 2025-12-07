<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insytes | Update Profile</title>
    <link href="<?php echo base_url('assets/css/tailwind_output.css'); ?>" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= base_url('assets/images/logo/logo-indigo.png'); ?>">
</head>
<body class="min-h-screen bg-gray-900 flex items-center justify-center">
    <div class="max-w-lg w-full mx-4">
        <div class="bg-[#1D1D1D] rounded-xl shadow-2xl p-8 border border-gray-600">
            <!-- Logo -->
            <div class="text-center mb-8">
                <img src="<?= base_url('assets/images/logo/logo-indigo.png'); ?>" alt="INSYTES Logo" class="mx-auto h-12 w-auto mb-4">
                <h2 class="text-2xl font-bold text-white">Update Your Profile</h2>
                <p class="text-sm text-gray-400 mt-2">Please update your temporary credentials to continue</p>
            </div>

            <!-- Update Profile Form -->
            <form id="password-form" method="POST" action="<?= site_url('auth/update-profile') ?>">
                <div class="space-y-6">
                    <!-- Email Section -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300 mb-2">
                            Email Address
                        </label>
                        <div class="relative">
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                required 
                                class="w-full px-4 py-3 border-gray-600 bg-gray-700 text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                placeholder="Enter your new email address"
                            >
                            <div id="loading-spinner" class="absolute inset-y-0 right-0 pr-3 items-center hidden">
                                <svg class="animate-spin h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                        <div id="email-feedback" class="text-xs mt-1"></div>
                    </div>

                    <!-- Password Section -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-300 mb-2">
                            New Password
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required 
                                minlength="8"
                                class="w-full px-4 py-3 pr-10 border-gray-600 bg-gray-700 text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                placeholder="Enter new password"
                            >
                            <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 pr-3 px-1 flex items-center text-gray-400 hover:text-gray-300">
                                <!-- Eye Slash Icon (Password Hidden - Default State) -->
                                <svg id="eye-closed" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                                </svg>
                                <!-- Eye Icon (Password Visible) -->
                                <svg id="eye-open" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                               </svg>
                            </button>
                        </div>
                        
                        <!-- Password Requirements -->
                        <div id="password-requirements" class="hidden mt-3 p-4 bg-gray-800 rounded-lg border border-gray-600">
                            <p class="text-sm text-gray-300 mb-2">Password must contain:</p>
                            <ul class="space-y-2">
                                <li id="req-length" class="flex items-center text-sm text-red-400">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    At least 8 characters
                                </li>
                                <li id="req-uppercase" class="flex items-center text-sm text-red-400">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    One uppercase letter
                                </li>
                                <li id="req-lowercase" class="flex items-center text-sm text-red-400">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    One lowercase letter
                                </li>
                                <li id="req-number" class="flex items-center text-sm text-red-400">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    One number
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div>
                        <label for="retype_password" class="block text-sm font-medium text-gray-300 mb-2">
                            Confirm New Password
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="retype_password" 
                                name="retype_password" 
                                required 
                                minlength="8"
                                class="w-full px-4 py-3 pr-10 border-gray-600 bg-gray-700 text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                placeholder="Confirm your new password"
                            >
                            <button type="button" id="toggle-retype-password" class="absolute inset-y-0 right-0 pr-3 px-1 flex items-center text-gray-400 hover:text-gray-300">
                                <!-- Eye Slash Icon (Password Hidden - Default State) -->
                                <svg id="eye-closed-retype" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                                </svg>
                                <!-- Eye Icon (Password Visible) -->
                                <svg id="eye-open-retype" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div id="match-error" class="text-red-400 text-sm hidden">
                        Passwords do not match
                    </div>

                    <button 
                        type="submit" 
                        id="continue-button"
                        disabled
                        class="w-full bg-indigo-600 text-white py-3 px-4 rounded-lg hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition duration-200 font-medium opacity-50 cursor-not-allowed"
                    >
                        Update Profile
                    </button>
                </div>
            </form>

            <!-- Alert Messages -->
            <div id="alertMessage" class="hidden mt-4 p-4 rounded-lg"></div>
        </div>
    </div>

    <!-- Processing Modal -->
    <div id="processingModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
            <div class="fixed inset-0 bg-[#131313b0] bg-opacity-40 backdrop-blur-sm" aria-hidden="true"></div>
            
            <div class="relative inline-block align-middle bg-gray-800 rounded-lg px-6 py-8 text-center overflow-hidden shadow-xl transform transition-all sm:max-w-sm sm:w-full border border-gray-600 z-10">
                <div class="flex flex-col items-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500 mb-4"></div>
                    <h3 class="text-lg font-medium text-white mb-2">Updating Profile...</h3>
                    <p class="text-sm text-gray-400">Please wait while we update your credentials.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
            <div class="fixed inset-0 bg-[#131313b0] bg-opacity-40 backdrop-blur-sm" aria-hidden="true"></div>
            
            <div class="relative inline-block align-middle bg-gray-800 rounded-lg px-4 py-5 text-center overflow-hidden shadow-xl transform transition-all sm:max-w-sm sm:w-full border border-gray-600 z-10">
                <div class="flex flex-col items-center">
                    <div class="mx-auto flex items-center justify-center h-10 w-10 rounded-full bg-green-100 mb-3">
                        <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-base font-medium text-white mb-2">Profile Updated Successfully!</h3>
                    <p class="text-sm text-gray-400 mb-4">Please login with your new credentials.</p>
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-500"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Email Check URL Global Variable -->

    <!-- Configuration Variables -->
    <script>
        const checkEmailUrl = '<?= site_url('auth/check-email-availability') ?>';
        const updateProfileUrl = '<?= site_url('auth/update-profile') ?>';
    </script>

    <!-- Form Submission Handler -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('password-form');
            const continueButton = document.getElementById('continue-button');
            
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault(); // Prevent normal form submission
                    
                    // Final validation
                    if (continueButton && continueButton.disabled) {
                        showAlert('Please complete all required fields correctly.', 'error');
                        return;
                    }
                    
                    const password = document.getElementById('password')?.value || '';
                    const retypePassword = document.getElementById('retype_password')?.value || '';
                    
                    if (password !== retypePassword) {
                        showAlert('Passwords do not match.', 'error');
                        return;
                    }
                    
                    // Show processing modal
                    const processingModal = document.getElementById('processingModal');
                    if (processingModal) {
                        processingModal.classList.remove('hidden');
                    }
                    
                    // Submit form via AJAX
                    const formData = new FormData(form);
                    
                    fetch(form.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Hide processing modal
                        if (processingModal) {
                            processingModal.classList.add('hidden');
                        }
                        
                        if (data.success) {
                            // Show success modal briefly, then redirect to login
                            const successModal = document.getElementById('successModal');
                            if (successModal) {
                                successModal.classList.remove('hidden');
                                
                                // Redirect to login page after 2 seconds to re-authenticate
                                setTimeout(() => {
                                    window.location.href = '<?php echo site_url("auth/login"); ?>?message=credentials_updated';
                                }, 2000);
                            } else {
                                // Fallback: immediate redirect
                                window.location.href = '<?php echo site_url("auth/login"); ?>?message=credentials_updated';
                            }
                        } else {
                            showAlert(data.message || 'An error occurred. Please try again.', 'error');
                        }
                    })
                    .catch(error => {
                        // Hide processing modal
                        if (processingModal) {
                            processingModal.classList.add('hidden');
                        }
                        console.error('Error:', error);
                        showAlert('A network error occurred. Please try again.', 'error');
                    });
                });
            }
            
            function showAlert(message, type) {
                const alertDiv = document.getElementById('alertMessage');
                if (alertDiv) {
                    alertDiv.className = `mt-4 p-4 rounded-lg ${type === 'success' ? 'bg-green-900/50 text-green-400 border border-green-500' : 'bg-red-900/50 text-red-400 border border-red-500'}`;
                    alertDiv.textContent = message;
                    alertDiv.classList.remove('hidden');
                }
            }
        });
    </script>

    <!-- External JavaScript Handlers -->
    <script src="<?php echo base_url('assets/js/emailHandler.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/passwordHandler.js'); ?>"></script>
</body>
</html>
