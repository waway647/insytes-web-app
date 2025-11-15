<!-- Edit Player Modal -->
<div 
    id="edit-player-modal"
    data-modal 
    role="document" 
    class="flex flex-col w-120 py-5 bg-[#131313] rounded-lg"
    x-data="{
        isSaving: false,
        successMessage: '',
        errorMessage: '',
        teamId: '',
        teamName: '',
        middleInitial: '',

        // copy dataset into Alpine after the element is initialized
        init() {
          // $el is available inside x-init
          this.teamId = $el?.dataset?.teamId || '';
          this.teamName = $el?.dataset?.teamName || '';
          console.log('🟣 [Alpine:init] Edit Player modal initialized — teamId=', this.teamId, 'teamName=', this.teamName);
        },

        formatMiddleInitial(event) {
            let value = event.target.value.toUpperCase();        // uppercase
            value = value.replace(/[^A-Z]/g, '');                // allow only A–Z
            value = value.slice(0, 1);                           // limit to 1 char
            this.middleInitial = value;
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
    x-init="init()"
>
    <form @submit.prevent="submitForm" action="<?php echo site_url('match/modalInsertsController/update_player'); ?>" method="POST">
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
            Edit Player
        </h3>

        <!-- Dates -->
        <div class="flex flex-col w-full justify-center items-center gap-10 py-5 border-b border-b-[#2A2A2A]">
            <input type="hidden" name="id">

            <div class="flex flex-col pb-5 w-full gap-1 items-center border-b border-b-[#2A2A2A]">
                <p class="text-xs text-[#B6BABD]">Team</p>
                <input type="hidden" name="team_id" x-model="teamId">
                <input 
                    type="text" 
                    x-model="teamName"
                    class="date-input w-40 h-9 px-3 py-1.5 rounded-md border-1 border-[#2A2A2A] text-white focus:border-white bg-[#1d1d1d] cursor-not-allowed"
                    disabled
                >
            </div>

            <div class="flex gap-6">
                <div class="flex flex-col w-full gap-1 items-center">
                    <p class="text-xs text-[#B6BABD]">First Name</p>
                    <input 
                        type="text" 
                        name="first_name" 
                        class="date-input w-40 h-9 px-3 py-1.5 rounded-md border-1 border-[#2A2A2A] text-white focus:border-white"
                    >
                </div>
                <div class="flex flex-col w-full gap-1 items-center">
                    <p class="text-xs text-[#B6BABD]">M.I.</p>
                    <input 
                        type="text" 
                        name="middle_initial" 
                        x-model="middleInitial"
                        @input="formatMiddleInitial($event)"
                        maxlength="1"
                        class="date-input w-16 h-9 px-3 py-1.5 rounded-md border-1 border-[#2A2A2A] text-white focus:border-white"
                    >
                </div>
                <div class="flex flex-col w-full gap-1 items-center">
                    <p class="text-xs text-[#B6BABD]">Last Name</p>
                    <input 
                        type="text" 
                        name="last_name" 
                        class="date-input w-40 h-9 px-3 py-1.5 rounded-md border-1 border-[#2A2A2A] text-white focus:border-white"
                    >
                </div>
            </div>
            <div class="flex gap-12">
                <div class="flex flex-col w-full gap-1 items-center">
                    <p class="text-xs text-[#B6BABD]">Position</p>
                    <select 
                        name="position"
                        class="w-26 h-9 px-3 py-1.5 rounded-md border border-[#2A2A2A] text-white bg-[#131313] focus:border-white focus:outline-none cursor-pointer"
                    >
                        <option value="" disabled selected></option>
                        <option value="GK">GK</option>
                        <option value="CB">CB</option>
                        <option value="RWB">RWB</option>
                        <option value="LWB">LWB</option>
                        <option value="CDM">CDM</option>
                        <option value="CAM">CAM</option>
                        <option value="RW">RW</option>
                        <option value="ST">ST</option>
                        <option value="LW">LW</option>
                    </select>
                </div>
                <div class="flex flex-col w-full gap-1 items-center">
                    <p class="text-xs text-[#B6BABD]">Jersey Number</p>
                    <input 
                        type="number" 
                        name="jersey" 
                        class="w-26 h-9 px-3 py-1.5 rounded-md border-1 border-[#2A2A2A] text-white focus:border-white"
                    >
                </div>
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
                id="save-edot-player-btn" 
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
