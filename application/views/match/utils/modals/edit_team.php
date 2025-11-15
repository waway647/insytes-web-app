<!-- Edit Team Modal -->
<div 
    id="edit-team-modal"
    data-modal 
    role="document" 
    class="flex flex-col w-100 py-5 bg-[#131313] rounded-lg"
    x-data="{
        isSaving: false,
        successMessage: '',
        errorMessage: '',
        tempTeamLogoFile: null,
        tempTeamLogoPreview: null,
        team: { team_logo: null },

        handleLogoChange(event) {
            const file = event.target.files[0];
            this.tempTeamLogoFile = file;

            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.tempTeamLogoPreview = e.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                // If no file, revert to existing logo (if any)
                this.tempTeamLogoPreview = this.team.team_logo;
                this.tempTeamLogoFile = null;
            }
        },

        async submitForm(event) {
            this.isSaving = true;
            this.successMessage = '';
            this.errorMessage = '';

            try {
                const form = event.target;
                const formData = new FormData(form);
                
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.successMessage = data.message;
                    // Optional: Reset the form fields and close the modal after a short delay
                    setTimeout(() => {
                        // This assumes you have a way to close the modal (e.g., a global Alpine property or function)
                        ModalManager.closeModal();
                    }, 500);

                } else {
                    // Handle server-side validation/error messages from the JSON
                    this.errorMessage = data.message || 'An unknown error occurred during saving.';
                }
            } catch (e) {
                // Handle network errors (e.g., server unreachable)
                this.errorMessage = 'Could not connect to the server. Please check your connection.';
                console.error('Fetch Error:', e);
            } finally {
                this.isSaving = false;
            }
        }
    }"
>
    <form @submit.prevent="submitForm" action="<?php echo site_url('match/modalInsertsController/update_team'); ?>" method="POST">
        <div class="px-5 py-3">
            <template x-if="successMessage">
                <p x-text="successMessage" class="text-sm text-green-500"></p>
            </template>
            <template x-if="errorMessage">
                <p x-text="errorMessage" class="text-sm text-red-500"></p>
            </template>
        </div>
        
        <!-- Header -->
        <h3 class="flex w-full justify-center items-center py-5 border-b border-b-[#2A2A2A] text-white text-lg font-medium">
            Edit Team
        </h3>

        <!-- Dates -->
        <div class="flex flex-col w-full justify-center items-center gap-10 py-5 border-b border-b-[#2A2A2A]">
            <input type="hidden" name="id">
        
            <div class="flex flex-col w-full gap-1 items-center">
                <p class="text-xs text-[#B6BABD]">Full Team Name</p>
                <input 
                    type="text" 
                    name="team_name" 
                    class="date-input w-64 h-9 px-3 py-1.5 rounded-md border-1 border-[#2A2A2A] text-white focus:border-white"
                >
            </div>

            <div class="flex flex-col w-full gap-1 items-center">
                <p class="text-xs text-[#B6BABD]">Abbreviation (e.g., FCB, SBU, UP)</p>
                <input 
                    type="text"
                    name="abbreviation" 
                    class="date-input w-64 h-9 px-3 py-1.5 rounded-md border-1 border-[#2A2A2A] text-white focus:border-white"
                >
            </div>
            
            <div class="flex flex-col w-full gap-1 items-center">
                <p class="text-xs text-[#B6BABD]">Country</p>
                <input 
                    type="text" 
                    name="country" 
                    class="date-input w-64 h-9 px-3 py-1.5 rounded-md border-1 border-[#2A2A2A] text-white focus:border-white"
                >
            </div>

            <div class="flex flex-col w-full gap-1 items-center">
                <p class="text-xs text-[#B6BABD]">City</p>
                <input 
                    type="text" 
                    name="city" 
                    class="date-input w-64 h-9 px-3 py-1.5 rounded-md border-1 border-[#2A2A2A] text-white focus:border-white"
                >
            </div>

            <div class="flex flex-col w-full gap-2 items-center">
                <label class="text-xs text-[#B6BABD]">Team Logo</label>                
                <input 
                    id="team_logo_upload" 
                    type="file" 
                    accept="image/*" 
                    @change="handleLogoChange"
                    class="hidden" 
                    name="team_logo"
                >

                <!-- Upload Button -->
                <label 
                    for="team_logo_upload"
                    class="flex items-center justify-center w-64 px-3 py-1.5 cursor-pointer rounded-md bg-white/5 hover:bg-white/10 border border-white/10 text-gray-300 text-sm font-medium gap-2 transition ring-1 ring-inset ring-white/10"
                >
                    <img src="<?php echo base_url('assets/images/icons/attach-file.png'); ?>" alt="Upload Icon" class="h-5 w-5">
                    <span x-text="tempTeamLogoFile ? tempTeamLogoFile.name : 'Attach Team Logo'"></span>
                </label>

                <!-- Live Preview -->
                <template x-if="tempTeamLogoPreview">
                    <img 
                        :src="tempTeamLogoPreview" 
                        alt="Team Logo Preview"
                        class="w-20 h-20 object-cover rounded-md mt-2 border border-[#2A2A2A]"
                    >
                </template>
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex py-10 px-5 gap-5 w-full">
            <button 
                id="cancel-btn" 
                type="button"
                class="flex justify-center items-center w-full text-white rounded-lg cursor-pointer border-1 border-[#2A2A2A] hover:bg-[#1d1d1d] transition px-4 py-2">
                Cancel
            </button>

            <button 
                id="save-edit-competition-btn" 
                type="submit"
                :disabled="isSaving"
                class="flex justify-center items-center w-full text-white bg-[#6366F1] rounded-lg cursor-pointer hover:bg-indigo-400 transition px-4 py-2">
                <span x-show="!isSaving">Save Changes</span>
                <span x-show="isSaving">Saving...</span>
            </button>
        </div>
    </form>
</div>

<!-- AlpineJS -->
<script src="//unpkg.com/alpinejs" defer></script>
