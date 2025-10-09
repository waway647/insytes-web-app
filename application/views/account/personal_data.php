<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<script>
    // These PHP variables should be defined before the other scripts run
    const checkEmailUrl = "<?php echo site_url('Auth/SignupController/check_email_unique'); ?>";
</script>
    
<script src="<?php echo base_url(); ?>assets/js/emailHandler.js?<?php echo time(); ?>"></script>
<script src="<?php echo base_url(); ?>assets/js/accountNameHandler.js?<?php echo time(); ?>"></script>

    <!-- Basic Info Section -->
    <div 
        class="flex flex-col gap-6 pb-6"
        x-data="{
            user: { 
                    email: 'N/A', 
                    first_name: 'N/A',
                    last_name: 'N/A',
                    role: 'N/A', // Initialize
                    team_name: 'N/A'
            },
            isEditingEmail: false,
            tempEmail: '',

            isEditingFirstName: false, 
            tempFirstName: '',

            isEditingLastName: false, 
            tempLastName: '',

            isLoading: true,
            errorMessage: '',

			// --- DELETION STATE ---
            isDeletingAccount: false, 
            deleteConfirmationText: '', 
            deleteError: '',
            
            // --- API CALL FUNCTION ---
            async fetchUserData() {
                this.isLoading = true;
                this.errorMessage = '';
                
                // Use the correct API URL from PHP
                const apiUrl = '<?php echo site_url('account/personaldatacontroller/get_user'); ?>';
                
                try {
                    const response = await fetch(apiUrl);

                    if (!response.ok) {
                        throw new Error(`HTTP Error ${response.status}: Could not load user data.`);
                    }
                    
                    const responseClone = response.clone(); 
                    
                    try {
                        // Response is a single object, not an array!
                        const data = await response.json(); 
                        
                        // Check if the data is a valid object and not an error structure
                        if (data && typeof data === 'object' && (data.email || data.error === undefined)) {
                            // Assign the entire received object to the reactive user state
                            this.user = {
                                email: data.email || 'N/A',
                                first_name: data.first_name || 'N/A',
                                last_name: data.last_name || 'N/A',
                                team_name: data.team_name || 'N/A',
                                // FIX: Check for data.role (state name) AND data.user_role (potential backend field name)
                                role: data.role || data.user_role || 'N/A', 
                            };
                            this.tempEmail = this.user.email;
                            this.tempFirstName = this.user.first_name;
                            this.tempLastName = this.user.last_name;
                            console.log('Data fetch successful. Loaded:', this.user);
                        } else {
                            this.errorMessage = data.error || 'API returned unexpected data structure or error.';
                            console.error('API Error:', this.errorMessage, data);
                        }

                    } catch (jsonError) {
                        const text = await responseClone.text();
                        this.errorMessage = 'Server Response Error: Expected JSON but received text/HTML. See console for raw output.';
                        console.error('Raw Server Response:', text);
                        console.error('Original JSON error:', jsonError);
                    }
                    
                } catch (error) {
                    console.error('General Fetch Operation Error:', error);
                    this.errorMessage = `Connection Failed: ${error.message}`;
                    this.user.email = 'Fetch Error.';
                } finally {
                    this.isLoading = false;
                }
            },
            
            // --- GENERIC API CALL FUNCTION: SAVE FIELD (REPLACES saveChanges) ---
            /**
             * Saves a single field value to the server after performing validation checks.
             * @param {string} field - The key of the field to save ('email', 'first_name', 'last_name').
             * @param {string} tempValue - The current value from the temporary input (x-model).
             * @param {string} editingStateName - The Alpine state variable to reset on success ('isEditingEmail', etc.).
             */
            async saveField(field, tempValue, editingStateName) {
                // Map the field name to its corresponding button ID for validation check
                let buttonId;
                if (field === 'email') {
                    buttonId = 'continue-button';
                } else if (field === 'first_name') {
                    buttonId = 'save-firstname-button';
                } else if (field === 'last_name') {
                    buttonId = 'save-lastname-button';
                } else {
                    this.errorMessage = 'Invalid field specified for saving.';
                    return;
                }

                const saveButton = document.getElementById(buttonId);

                // 1. Check if the value is different
                if (this.user[field] === tempValue) {
                    this[editingStateName] = false;
                    this.errorMessage = 'Value unchanged. Nothing to save.';
                    return;
                }

                // 2. Check if the button is disabled by the external validation scripts
                if (saveButton && saveButton.disabled) {
                    this.errorMessage = `Please fix validation errors for the ${field.replace('_', ' ')} field before saving.`;
                    return;
                }
                
                this.isLoading = true; 
                this.errorMessage = '';
                let result = {};
                
                try {
                    const response = await fetch('<?php echo site_url('account/personaldatacontroller/update_user'); ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        // Use generic payload structure { field: '...', value: '...' }
                        body: JSON.stringify({ field: field, value: tempValue })
                    });

                    if (!response.ok) {
                        throw new Error(`Save failed! Status: ${response.status}`);
                    }
                    
                    result = await response.json();
                    
                    if (result.success) { 
                        // Update the official user state and close edit mode
                        this.user[field] = tempValue;
                        this[editingStateName] = false;
                        this.errorMessage = result.message || `${field.replace('_', ' ')} updated successfully.`; 
                    } else {
                        this.errorMessage = result.message || `Unknown save error for ${field}`;
                    }
                    
                } catch (error) {
                    this.errorMessage = `Error saving ${field}: ${error.message}`;
                } finally {
                    this.isLoading = false;
                    // Synchronize data immediately after save
                    if (result?.success) {
                        // Re-fetch to ensure all fields are fresh, though generally not necessary for single field updates
                        // await this.fetchUserData(); 
                    }
                }
            },

			// --- DELETE ACCOUNT FUNCTION ---
            async deleteAccount() {
                this.deleteError = '';

                this.isLoading = true;
                
                try {
                    const response = await fetch('<?php echo site_url('account/personaldatacontroller/delete_user'); ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        // Send confirmation text to the server for final server-side check
                        body: JSON.stringify({ confirmation: this.deleteConfirmationText }) 
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Redirect to a logout page or homepage after successful deletion
                        window.location.href = result.redirect_url || '<?php echo site_url('logout'); ?>';
                    } else {
                        this.deleteError = result.message || 'Failed to delete account due to an unknown error.';
                        this.isLoading = false;
                    }

                } catch (error) {
                    this.deleteError = `Connection failed: ${error.message}.`;
                    this.isLoading = false;
                }
            },
            
            // --- CANCEL FUNCTION ---
            cancelEmailEdit() {
                this.tempEmail = this.user.email; // Revert temp value to original state
                this.isEditingEmail = false;
                document.getElementById('email')?.dispatchEvent(new Event('input'));
            },

            cancelFirstNameEdit() {
                this.tempFirstName = this.user.first_name; // Revert temp value to original state
                this.isEditingFirstName = false;
                document.getElementById('firstname')?.dispatchEvent(new Event('input'));
            },

            cancelLastNameEdit() {
                this.tempLastName = this.user.last_name; // Revert temp value to original state
                this.isEditingLastName = false;
                document.getElementById('lastname')?.dispatchEvent(new Event('input'));
            },

			openDeleteModal() {
                this.isDeletingAccount = true;
                this.deleteConfirmationText = '';
                this.deleteError = '';
            },

            cancelDeletion() {
                this.isDeletingAccount = false;
                this.deleteConfirmationText = '';
                this.deleteError = '';
            }

        }" 
        x-init="fetchUserData()"
    >

    <!-- Loading Indicator -->
    <div x-show="isLoading" class="p-4 text-center text-gray-400 animate-pulse">
        Loading user data...
    </div>

    <!-- Email address Field Container -->
    <div x-show="!isLoading" class="flex flex-col gap-3">
        <!-- read-only Email address -->
        <div x-show="!isEditingEmail" id="email-display" class="transition duration-300 ease-in-out">
            <label for="email" class="block text-xs font-medium text-[#B6BABD]">Email address</label>
            <div class="mt-2 flex gap-4">
                <p class="text-white font-medium" x-text="user.email"></p>
                <button @click="isEditingEmail = true; tempEmail = user.email" class="p-1 rounded-full hover:bg-white/10 transition cursor-pointer">
                    <!-- Edit Icon (assuming base_url is set up) -->
                    <img class="w-4 h-4" src="<?php echo base_url(); ?>assets/images/icons/edit.svg?<?php echo time(); ?>" alt="Edit">
                </button>
            </div>
        </div>
        <!-- email edit mode -->
        <div x-show="isEditingEmail" id="email-edit" class="transition duration-300 ease-in-out">
            <label for="email" class="block text-xs font-medium text-[#B6BABD]">Email address</label>
            <div class="mt-2 flex flex-col gap-3">
                <input id="email" type="email" name="email" required autocomplete="email" x-model="tempEmail" class="block w-80 rounded-md bg-white/5 px-3 py-1.5 text-base text-white outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
                <!-- Feedback Message -->
                <p id="email-feedback" class="text-xs"></p>

                <div>
                    <!-- Email Validation Feedback UI -->
                    <div class="flex items-center mt-2">
                        <!-- Loading Spinner (Hidden by default) -->
                        <svg id="loading-spinner" class="animate-spin -ml-1 mr-3 h-5 w-5 text-indigo-400 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <div class="w-80 flex justify-end gap-3">
                        <button @click="cancelEmailEdit()" class="px-4 py-1 flex items-center text-sm font-medium border-1 border-[#2A2A2A] text-[#B6BABD] rounded-md hover:bg-[#2A2A2A] cursor-pointer">
                            Cancel
                        </button>
                        <button id="continue-button" @click="saveField('email', tempEmail, 'isEditingEmail')" class="px-7 py-1 flex items-center text-sm font-medium bg-[#6366F1] text-white rounded-md hover:bg-indigo-600 transition cursor-pointer">
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
        
        <!-- read-only First name -->
        <div x-show="!isEditingFirstName" id="firstname-display" class="transition duration-300 ease-in-out">
            <label for="first_name" class="block text-xs font-medium text-[#B6BABD]">First name</label>
            <div class="mt-2 flex gap-4">
                <p class="text-white font-medium" x-text="user.first_name"></p>
                <button @click="isEditingFirstName = true; tempFirstName = user.first_name; document.getElementById('firstname')?.dispatchEvent(new Event('input'))" class="p-1 rounded-full hover:bg-white/10 transition cursor-pointer">
                    <!-- Edit Icon (assuming base_url is set up) -->
                    <img class="w-4 h-4" src="<?php echo base_url(); ?>assets/images/icons/edit.svg?<?php echo time(); ?>" alt="Edit">
                </button>
            </div>
        </div>
        <!-- first name edit mode -->
        <div x-show="isEditingFirstName" id="firstname-edit" class="transition duration-300 ease-in-out">
            <label for="first_name" class="block text-xs font-medium text-[#B6BABD]">First name</label>
            <div class="mt-2 flex flex-col gap-3">
                <input id="firstname" type="text" name="first_name" required autocomplete="first_name" x-model="tempFirstName" class="block w-80 rounded-md bg-white/5 px-3 py-1.5 text-base text-white outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
                <!-- Feedback Message -->
                <p id="firstname-feedback" class="text-xs"></p>

                <div>
                    <div class="w-80 flex justify-end gap-3">
                        <button @click="cancelFirstNameEdit()" class="px-4 py-1 flex items-center text-sm font-medium border-1 border-[#2A2A2A] text-[#B6BABD] rounded-md hover:bg-[#2A2A2A] cursor-pointer">
                            Cancel
                        </button>
                        <button id="save-firstname-button" @click="saveField('first_name', tempFirstName, 'isEditingFirstName')" class="px-7 py-1 flex items-center text-sm font-medium bg-[#6366F1] text-white rounded-md hover:bg-indigo-600 transition cursor-pointer">
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- read-only Last name -->
        <div x-show="!isEditingLastName" id="lastname-display" class="transition duration-300 ease-in-out">
            <label for="last_name" class="block text-xs font-medium text-[#B6BABD]">Last name</label>
            <div class="mt-2 flex gap-4">
                <p class="text-white font-medium" x-text="user.last_name"></p>
                <button @click="isEditingLastName = true; tempLastName = user.last_name; document.getElementById('lastname')?.dispatchEvent(new Event('input'))" class="p-1 rounded-full hover:bg-white/10 transition cursor-pointer">
                    <!-- Edit Icon (assuming base_url is set up) -->
                    <img class="w-4 h-4" src="<?php echo base_url(); ?>assets/images/icons/edit.svg?<?php echo time(); ?>" alt="Edit">
                </button>
            </div>
        </div>
        <!-- last name edit mode -->
        <div x-show="isEditingLastName" id="lastname-edit" class="transition duration-300 ease-in-out">
            <label for="last_name" class="block text-xs font-medium text-[#B6BABD]">Last name</label>
            <div class="mt-2 flex flex-col gap-3">
                <input id="lastname" type="text" name="last_name" required autocomplete="last_name" x-model="tempLastName" class="block w-80 rounded-md bg-white/5 px-3 py-1.5 text-base text-white outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
                <!-- Feedback Message -->
                <p id="lastname-feedback" class="text-xs"></p>

                <div>
                    <div class="w-80 flex justify-end gap-3">
                        <button @click="cancelLastNameEdit()" class="px-4 py-1 flex items-center text-sm font-medium border-1 border-[#2A2A2A] text-[#B6BABD] rounded-md hover:bg-[#2A2A2A] cursor-pointer">
                            Cancel
                        </button>
                        <button id="save-lastname-button" @click="saveField('last_name', tempLastName, 'isEditingLastName')" class="px-7 py-1 flex items-center text-sm font-medium bg-[#6366F1] text-white rounded-md hover:bg-indigo-600 transition cursor-pointer">
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </div>

    <!-- Affiliated Team & Role Section -->
    <div class="flex flex-col gap-6 border-t border-[#2A2A2A] pt-10 pb-6">
        <div class="flex items-center gap-2">
            <label for="user_role" class="block text-xs font-medium text-[#B6BABD]">Role:</label>
            <!-- REMOVED hardcoded default value 'Coach' to avoid confusion -->
            <p class="text-white font-medium" x-text="user.role"></p> 
        </div>
    </div>

    <!-- Delete Zone Section -->
    <div class="flex flex-col gap-6 border-t border-[#2A2A2A] pt-10 pb-6">
        <!-- Delete Button (outside of confirmation view) -->
        <button @click="openDeleteModal()" x-show="!isDeletingAccount" type="button" class="w-40 text-[#8E2C2C] px-4 py-2 border-1 rounded-lg hover:cursor-pointer">
        	Delete Account
        </button>

        <!-- Confirmation Modal/Prompt -->
        <div 
            x-show="isDeletingAccount" 
            x-transition:enter="transition ease-out duration-300" 
            x-transition:enter-start="opacity-0 scale-90" 
            x-transition:enter-end="opacity-100 scale-100" 
            x-transition:leave="transition ease-in duration-300" 
            x-transition:leave-start="opacity-100 scale-100" 
            x-transition:leave-end="opacity-0 scale-90" 
            class="mt-2 p-6 border border-[#8E2C2C] rounded-xl bg-red-900/10 shadow-xl flex flex-col gap-4 max-w-lg"
        >
            <h4 class="text-xl font-bold text-[#a04a4a] leading-tight">Are you absolutely sure you want to proceed?</h4>
            <p class="text-sm text-[#FFB6B6]">
                This will permanently delete all your data and cannot be recovered. To confirm, please type 
                <strong class="font-mono text-white bg-red-800/50 px-1 py-0.5 rounded">DELETE</strong> in the box below.
            </p>
            
            <!-- Confirmation Input -->
            <input 
                type="text" 
                x-model="deleteConfirmationText"
                placeholder="Type DELETE to confirm"
                class="block w-full rounded-md border-0 bg-white/5 px-3 py-2 text-base text-white placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-red-500 sm:text-sm/6"
            />

            <!-- Deletion Error Message -->
            <p x-show="deleteError" x-text="deleteError" class="text-sm font-medium text-red-400"></p>

            <!-- Action Buttons -->
            <div class="w-full flex justify-end gap-3 pt-2">
                <button @click="cancelDeletion()" type="button" class="px-4 py-1 flex items-center text-sm font-medium border border-[#2A2A2A] text-[#B6BABD] rounded-md hover:bg-[#2A2A2A] cursor-pointer" :disabled="isLoading">
                    Cancel
                </button>
                <button @click="deleteAccount()" :disabled="deleteConfirmationText !== 'DELETE' || isLoading" type="button" class="px-7 py-1 flex items-center text-sm font-medium bg-[#8E2C2C] text-white rounded-md transition hover:bg-[#A33C3C] cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!isLoading">Confirm Deletion</span>
                    <span x-show="isLoading" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Deleting...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
