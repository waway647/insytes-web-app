<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<div 
    class="flex flex-col gap-6 pb-6"
    x-data="{
        BASE_LOGO_PATH: '<?php echo base_url(""); ?>',
        
        // --- TEAM DATA (Initial placeholder) ---
        team: { 
            team_name: 'Loading Team...',
            country: '...',
            city: '...',
            team_logo: '<?php 
                // Assuming $teamData is available in your view for the initial load
                echo isset($teamData['team_logo']) ? base_url('assets/team_logos/' . $teamData['team_logo']) : 'logo.png'; 
            ?>',
            primary_color: '#4f46e5',
            secondary_color: '#6366f1',
            team_link: '#',
            created_by: '...',
            role: '...' 
        },

        // --- GLOBAL STATE ---
        isLoading: false,
        errorMessage: '',
        successMessage: '',

        // --- EDITING STATE ---
        isEditingTeam: false, 
        
        // --- TEMPORARY EDITING VALUES (Bound to panel inputs) ---
        tempTeamName: '',
        tempCountry: '',
        tempCity: '',
        tempTeamLogo: '', // For the *original* URL, used if no new file is uploaded
        tempPrimaryColor: '',
        tempSecondaryColor: '',
        tempTeamLink: '',
        tempCreatedBy: '',
        
        // --- NEW FILE/PREVIEW VARIABLES ---
        tempTeamLogoFile: null,        // Stores the actual File object selected by the user
        tempTeamLogoPreview: '',     // Stores the Base64 URL for live preview
        
        // --- DELETION STATE ---
        isDeletingTeam: false, 
        deleteConfirmationText: '', 
        deleteError: '',
        
        // Placeholder for PHP URLs (Mocked for runnable example):
        API_URL_GET: '<?php echo site_url('account/teamcontroller/get_team'); ?>',
        API_URL_UPDATE: '<?php echo site_url('account/teamcontroller/update_team'); ?>',
        API_URL_DELETE: '<?php echo site_url('account/teamcontroller/delete_team'); ?>',

        // --- SIDE PANEL FUNCTIONS ---
        openEditPanel() {
            // Load current team data into temporary variables when opening the panel
            this.tempTeamName = this.team.team_name;
            this.tempCountry = this.team.country;
            this.tempCity = this.team.city;
            this.tempTeamLogo = this.team.team_logo;
            this.tempPrimaryColor = this.team.primary_color;
            this.tempSecondaryColor = this.team.secondary_color;
            this.tempTeamLink = this.team.team_link;
            this.tempCreatedBy = this.team.created_by;

            let currentLogo = this.team.team_logo;

            if (currentLogo && !currentLogo.startsWith('http') && !currentLogo.startsWith(this.BASE_LOGO_PATH)) {
                // If it looks like a filename, prepend the base path
                currentLogo = this.BASE_LOGO_PATH + currentLogo;
            }
            
            this.tempTeamLogoPreview = currentLogo; 
            this.tempTeamLogoFile = null;
            
            this.isEditingTeam = true;
            this.errorMessage = '';
            this.successMessage = '';
        },

        closeEditPanel() {
            this.isEditingTeam = false;
            this.errorMessage = '';
            this.successMessage = '';
            // Clear file-related temp data when closing
            this.tempTeamLogoFile = null;
            this.tempTeamLogoPreview = null;
        },
        
        handleLogoChange(event) {
            const file = event.target.files[0];
            this.tempTeamLogoFile = file;

            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.tempTeamLogoPreview = e.target.result;
                };
                reader.readAsDataURL(file);
                
                // Update the button text to show the filename
                document.getElementById('teamLogoText').textContent = file.name;
            } else {
                this.tempTeamLogoPreview = this.team.team_logo; // Revert to current saved logo
                document.getElementById('teamLogoText').textContent = 'Attach Logo';
            }
        },

        // --- FETCH TEAM DATA FUNCTION (IMPLEMENTED) ---
        async fetchTeamData() { 
            this.isLoading = true;
            this.errorMessage = '';
            try {
                const response = await fetch(this.API_URL_GET, {
                    method: 'GET',
                    headers: { 'Content-Type': 'application/json' },
                });

                if (!response.ok) {
                    throw new Error(`Failed to load team data. Status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result && result.team_name) { 
                    if (result.team_logo && !result.team_logo.startsWith('http') && !result.team_logo.startsWith(this.BASE_LOGO_PATH)) {
                        result.team_logo = this.BASE_LOGO_PATH + result.team_logo;
                    }
                    
                    this.team = result;
                } else {
                    this.team.team_name = 'No Team Found';
                    this.errorMessage = 'Could not parse team data.';
                }
                
            } catch (error) {
                this.errorMessage = `Error loading team details: ${error.message}`;
            } finally {
                this.isLoading = false;
            }
        },
        
        // --- SAVE ALL FIELDS FUNCTION (MODIFIED TO USE FormData FOR FILE UPLOAD) ---
        async saveAllFields() {
            this.isLoading = true; 
            this.errorMessage = '';
            this.successMessage = '';
            
            // Use FormData for file upload
            const formData = new FormData();
            
            // Append regular fields
            formData.append('team_name', this.tempTeamName);
            formData.append('country', this.tempCountry);
            formData.append('city', this.tempCity);
            formData.append('primary_color', this.tempPrimaryColor);
            formData.append('secondary_color', this.tempSecondaryColor);
            formData.append('team_link', this.tempTeamLink);
            
            // Check if a new file was selected
            if (this.tempTeamLogoFile) {
                // If a new file is present, append the file object
                formData.append('team_logo', this.tempTeamLogoFile);
            } else {
                // If no new file, but the existing logo is a URL, send the URL for existing logo
                formData.append('team_logo', this.tempTeamLogo);
            }

            try {
                // NOTE: When using FormData, you MUST NOT manually set the 'Content-Type': 'application/json' header. 
                // The browser will set it correctly (multipart/form-data) along with the boundary.
                const response = await fetch(this.API_URL_UPDATE, {
                    method: 'POST',
                    enctype: 'multipart/form-data',
                    body: formData // Send FormData directly
                });

                if (!response.ok) {
                    throw new Error(`Save failed! Status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.success) { 
                    // Assuming the API returns the updated team data, including the new logo URL
                    if (result.new_team_data) {
                        this.team = result.new_team_data;
                    } else {
                        // Fallback if API doesn't return new data (less ideal, may need a new fetchTeamData())
                        this.team.team_name = this.tempTeamName;
                        // ... update other fields
                        this.team.team_logo = this.tempTeamLogoFile ? 'NEW_LOGO_UPLOADED_URL' : this.tempTeamLogo; 
                        this.fetchTeamData(); // Best to re-fetch if you don't get the new data back
                    }
                    
                    this.successMessage = result.message || 'Team details updated successfully.'; 
                    this.closeEditPanel();
                } else {
                    this.errorMessage = result.message || 'Unknown save error. Please check inputs.';
                }
                
            } catch (error) {
                this.errorMessage = `Error saving team details: ${error.message}`;
            } finally {
                this.isLoading = false;
            }
        },
        
        // --- DELETE TEAM FUNCTION (Unchanged) ---
        async deleteTeam() {
            if (this.deleteConfirmationText !== 'DELETE') {
                this.deleteError = 'Please type DELETE exactly to confirm.';
                return;
            }

            this.isLoading = true;
            this.deleteError = '';
            this.successMessage = '';

            const payload = { 
                confirmation: this.deleteConfirmationText
            };

            try {
                const response = await fetch(this.API_URL_DELETE, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                if (!response.ok) {
                    throw new Error(`Deletion failed! Status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.success) { 
                    this.successMessage = result.message || 'Team successfully deleted. Redirecting...'; 
                    this.isDeletingTeam = false;
                    this.team = { team_name: 'DELETED', country: '', city: '', team_logo: '', primary_color: '', secondary_color: '', team_link: '', created_by: '', role: '' };
                } else {
                    this.deleteError = result.message || 'Unknown deletion error.';
                }
                
            } catch (error) {
                this.deleteError = `Error deleting team: ${error.message}`;
            } finally {
                this.isLoading = false;
                this.deleteConfirmationText = '';
            }
        },
        
        // --- MODAL/PANEL FUNCTIONS (Unchanged) ---
        openDeleteModal() {
            this.isDeletingTeam = true;
            this.deleteConfirmationText = '';
            this.deleteError = '';
        },
        
        cancelDeletion() {
            this.isDeletingTeam = false;
            this.deleteConfirmationText = '';
            this.deleteError = '';
        }

    }" 
    x-init="fetchTeamData();
    $watch('team.team_logo', (newLogo) => { 
        if (newLogo) {
            // Prepend BASE_LOGO_PATH if it's just a filename
            if (!newLogo.startsWith('http') && !newLogo.startsWith(BASE_LOGO_PATH)) {
                newLogo = BASE_LOGO_PATH + newLogo;
            }
            tempTeamLogoPreview = newLogo; 
        } 
    })" 
>
    <div x-show="isLoading" class="text-indigo-400">
        <p>Processing...</p>
    </div>
    
    <div x-show="errorMessage" x-text="errorMessage" class="p-3 mb-4 bg-red-800/20 text-red-300 border border-red-700 rounded-md transition-all duration-300"></div>
    <div x-show="successMessage" x-text="successMessage" class="p-3 mb-4 bg-green-800/20 text-green-300 border border-green-700 rounded-md transition-all duration-300"></div>


    <div class="flex flex-col gap-6">
        <div class="flex items-center gap-2 relative">
            <label for="affiliated_team" class="block text-xs font-medium text-[#B6BABD]">Affiliated Team:</label>
            <p class="text-white font-medium" x-text="team.team_name"></p>
            
            <div 
                x-data="{ isMenuOpen: false }" 
                @click.outside="isMenuOpen = false" 
                class="relative inline-block text-left ml-1"
            >
                <button 
                    @click="isMenuOpen = !isMenuOpen" 
                    type="button" 
                    class="inline-flex justify-center rounded-full text-[#B6BABD] hover:text-white transition focus:outline-none p-1" 
                    aria-expanded="true" 
                    aria-haspopup="true"
                >
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
                
                <div 
                    x-show="isMenuOpen" 
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-[#1f1f1f] shadow-lg ring-1 ring-white ring-opacity-5 focus:outline-none" 
                    role="menu" 
                    tabindex="-1"
                >
                    <div class="py-1" role="none">
                        <a 
                            href="#" 
                            @click.prevent="isMenuOpen = false; openEditPanel();" 
                            class="text-white block px-4 py-2 text-sm hover:bg-[#2A2A2A] transition cursor-pointer" 
                            role="menuitem" 
                            tabindex="-1"
                        >
                            Edit Team Details
                        </a>
                        <a 
                            href="#" 
                            @click.prevent="isMenuOpen = false; openDeleteModal();" 
                            class="text-red-400 block px-4 py-2 text-sm hover:bg-[#2A2A2A] transition cursor-pointer" 
                            role="menuitem" 
                            tabindex="-1"
                        >
                            Delete Team
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <template x-if="isDeletingTeam">
        <div class="fixed inset-0 z-40 bg-black bg-opacity-75 flex items-center justify-center p-4">
            <div 
                x-transition:enter="ease-out duration-300" 
                x-transition:enter-start="opacity-0 scale-90" 
                x-transition:enter-end="opacity-100 scale-100" 
                x-transition:leave="ease-in duration-200" 
                x-transition:leave-start="opacity-100 scale-100" 
                x-transition:leave-end="opacity-0 scale-90"
                class="p-6 border border-[#8E2C2C] rounded-xl bg-red-900/10 shadow-xl flex flex-col gap-4 max-w-lg"
            >
                <h4 class="text-xl font-bold text-[#a04a4a] leading-tight">Are you absolutely sure you want to proceed?</h4>
                <p class="text-sm text-[#FFB6B6]">
                    This will permanently delete all your team data and cannot be recovered. To confirm, please type 
                    <strong class="font-mono text-white bg-red-800/50 px-1 py-0.5 rounded">DELETE</strong> in the box below.
                </p>
                <input type="text" x-model="deleteConfirmationText" placeholder="Type DELETE to confirm" class="block w-full rounded-md border-0 bg-white/5 px-3 py-2 text-base text-white placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-red-500 sm:text-sm/6"/>
                <p x-show="deleteError" x-text="deleteError" class="text-sm font-medium text-red-400"></p>
                <div class="w-full flex justify-end gap-3 pt-2">
                    <button @click="cancelDeletion()" type="button" class="px-4 py-1 text-sm font-medium border border-[#2A2A2A] text-[#B6BABD] rounded-md hover:bg-[#2A2A2A]">Cancel</button>
                    <button @click="deleteTeam()" :disabled="deleteConfirmationText !== 'DELETE' || isLoading" type="button" class="px-7 py-1 text-sm font-medium bg-[#8E2C2C] text-white rounded-md transition hover:bg-[#A33C3C] disabled:opacity-50">Confirm Deletion</button>
                </div>
            </div>
        </div>
    </template>
        
    <div 
        x-show="isEditingTeam" 
        class="fixed inset-0 overflow-hidden z-30" 
        aria-labelledby="slide-over-title" 
        role="dialog" 
        aria-modal="true"
    >
        <div class="absolute inset-0 overflow-hidden">
            <div 
                x-show="isEditingTeam" 
                x-transition:enter="ease-in-out duration-500" 
                x-transition:enter-start="opacity-0" 
                x-transition:enter-end="opacity-100" 
                x-transition:leave="ease-in-out duration-500" 
                x-transition:leave-start="opacity-100" 
                x-transition:leave-end="opacity-0" 
                @click="closeEditPanel()" 
                class="absolute inset-0 transition-opacity bg-black/60" 
                aria-hidden="true"
            ></div>

            <div class="fixed inset-y-0 right-0 max-w-full flex">
                <div 
                    x-show="isEditingTeam" 
                    x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700" 
                    x-transition:enter-start="translate-x-full" 
                    x-transition:enter-end="translate-x-0" 
                    x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700" 
                    x-transition:leave-start="translate-x-0" 
                    x-transition:leave-end="translate-x-full"
                    class="w-screen max-w-md w-96"
                >
                    <div class="h-full flex flex-col py-6 bg-[#1f1f1f] shadow-xl overflow-y-scroll">
                        <div class="px-4 sm:px-6">
                            <div class="flex items-start justify-between">
                                <h2 class="text-lg font-bold text-white" id="slide-over-title">
                                    Edit Team Details
                                </h2>
                                <div class="ml-3 h-7 flex items-center">
                                    <button @click="closeEditPanel()" type="button" class="rounded-md text-[#B6BABD] hover:text-white focus:outline-none transition">
                                        <span class="sr-only">Close panel</span>
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 relative flex-1 px-4 sm:px-6">
                            <form @submit.prevent="saveAllFields()" class="space-y-6" >
                                <div>
                                    <label class="block text-sm font-medium text-[#B6BABD]">Team Logo</label>
                                    
                                    <input 
                                        id="team_logo_upload" 
                                        type="file" 
                                        accept="image/*" 
                                        @change="handleLogoChange($event)" 
                                        class="hidden" 
                                        name="team_logo"
                                    >

                                    <label for="team_logo_upload"
                                        class="mt-1 flex items-center justify-center w-full px-3 py-1.5 cursor-pointer rounded-md bg-white/5 hover:bg-white/10 border border-white/10 text-gray-300 text-sm font-medium gap-2 transition ring-1 ring-inset ring-white/10"
                                    >
                                        <img src="<?php echo base_url('assets/images/icons/attach-file.png'); ?>" alt="Upload Icon" class="h-5 w-5">
                                        <span id="teamLogoText" x-text="tempTeamLogoFile ? tempTeamLogoFile.name : 'Change Team Logo'">Change Team Logo</span>
                                    </label>
                                    
                                    <div x-show="tempTeamLogoPreview" class="mt-4">
                                        <p class="block text-sm font-medium text-[#B6BABD] mb-2">Current/New Logo Preview:</p>
                                        <img :src="tempTeamLogoPreview" alt="Team Logo Preview" class="w-24 h-24 object-contain border border-white/10 rounded-lg bg-gray-900 p-1">
                                    </div>
                                    
                                </div>    

                                <div>
                                    <label for="team_name" class="block text-sm font-medium text-[#B6BABD]">Team Name</label>
                                    <input type="text" x-model="tempTeamName" id="team_name" class="mt-1 block w-full rounded-md border-0 bg-white/5 text-white shadow-sm ring-1 ring-inset ring-white/10 focus:ring-2 focus:ring-inset focus:ring-indigo-500 sm:text-sm sm:leading-6 p-2">
                                </div>
                                
                                <div>
                                    <label for="country" class="block text-sm font-medium text-[#B6BABD]">Country</label>
                                    <input type="text" x-model="tempCountry" id="country" class="mt-1 block w-full rounded-md border-0 bg-white/5 text-white shadow-sm ring-1 ring-inset ring-white/10 focus:ring-2 focus:ring-inset focus:ring-indigo-500 sm:text-sm sm:leading-6 p-2">
                                </div>

                                <div>
                                    <label for="city" class="block text-sm font-medium text-[#B6BABD]">City</label>
                                    <input type="text" x-model="tempCity" id="city" class="mt-1 block w-full rounded-md border-0 bg-white/5 text-white shadow-sm ring-1 ring-inset ring-white/10 focus:ring-2 focus:ring-inset focus:ring-indigo-500 sm:text-sm sm:leading-6 p-2">
                                </div>
                                
                                <div>
                                    <label for="primary_color" class="block text-sm font-medium text-[#B6BABD]">Primary Color</label>
                                    <input type="color" x-model="tempPrimaryColor" id="primary_color" class="mt-1 block w-full h-10 rounded-md border-0 bg-white/5 text-white shadow-sm ring-1 ring-inset ring-white/10 focus:ring-2 focus:ring-inset focus:ring-indigo-500 sm:text-sm sm:leading-6 p-1">
                                    <p class="text-xs text-[#B6BABD] mt-1" x-text="tempPrimaryColor"></p>
                                </div>

                                <div>
                                    <label for="secondary_color" class="block text-sm font-medium text-[#B6BABD]">Secondary Color</label>
                                    <input type="color" x-model="tempSecondaryColor" id="secondary_color" class="mt-1 block w-full h-10 rounded-md border-0 bg-white/5 text-white shadow-sm ring-1 ring-inset ring-white/10 focus:ring-2 focus:ring-inset focus:ring-indigo-500 sm:text-sm sm:leading-6 p-1">
                                    <p class="text-xs text-[#B6BABD] mt-1" x-text="tempSecondaryColor"></p>
                                </div>

                                <div>
                                    <label for="team_link" class="block text-sm font-medium text-[#B6BABD]">Public Team Link</label>
                                    <input type="url" x-model="tempTeamLink" id="team_link" class="mt-1 block w-full rounded-md border-0 bg-white/5 text-white shadow-sm ring-1 ring-inset ring-white/10 focus:ring-2 focus:ring-inset focus:ring-indigo-500 sm:text-sm sm:leading-6 p-2">
                                </div>
                                
                            </form>
                        </div>
                        
                        <div class="flex-shrink-0 px-4 py-4 sm:px-6 border-t border-[#2A2A2A] mt-6">
                            <div class="flex justify-end space-x-3">
                                <button 
                                    @click.prevent="closeEditPanel()" 
                                    type="button" 
                                    class="rounded-md border border-[#2A2A2A] bg-transparent py-2 px-4 text-sm font-medium text-[#B6BABD] shadow-sm hover:bg-[#2A2A2A] transition"
                                >
                                    Cancel
                                </button>
                                <button 
                                    @click.prevent="saveAllFields()" 
                                    :disabled="isLoading"
                                    type="submit" 
                                    class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none disabled:opacity-50 transition"
                                >
                                    <span x-show="!isLoading">Save Changes</span>
                                    <span x-show="isLoading" class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Saving...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>