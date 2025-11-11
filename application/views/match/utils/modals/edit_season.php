<script src="<?php echo base_url('assets/js/modalManager.js') . '?v=' . time(); ?>"></script>

<!-- Edit New Season Modal -->
<div 
    id="edit-season-modal" 
    data-modal 
    role="document" 
    class="flex flex-col w-100 py-5 bg-[#131313] rounded-lg"
    x-data="{
        startYear: '',
        startDate: '',
        endDate: '',
        showEndDate: false,
        isActive: false,
        isSaving: false,
        successMessage: '',
        errorMessage: '',

        get seasonPreview() {
            const year = parseInt(this.startYear);
            if (!isNaN(year) && year >= 2000 && year <= 2050) {
                return `${year}/${year + 1}`;
            }
            return '';
        },

        get minStartDate() { return `${this.startYear}-01-01`; },
        get maxStartDate() { return `${this.startYear}-12-31`; },
        get minEndDate() { return `${parseInt(this.startYear) + 1}-01-01`; },
        get maxEndDate() { return `${parseInt(this.startYear) + 1}-12-31`; },

        async submitForm(event) {
            this.isSaving = true;
            this.successMessage = '';
            this.errorMessage = '';

            try {
                const formData = new FormData(event.target);
                const res = await fetch(event.target.action, { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    this.successMessage = data.message;
                    setTimeout(() => this.closeModal(), 500);
                } else {
                    this.errorMessage = data.message || 'Unknown error';
                }
            } catch (e) {
                this.errorMessage = 'Network error';
            } finally {
                this.isSaving = false;
            }
        }
    }"
>
    <form @submit.prevent="submitForm" action="<?php echo site_url('match/modalInsertsController/update_season'); ?>" method="POST">
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
            Edit Season
        </h3>

        <!-- Start Year + Preview -->
        <div class="flex w-full justify-between py-5 px-5 border-b border-b-[#2A2A2A]">
            <input type="hidden" name="id">    

            <div class="flex flex-col w-full gap-1 items-center">
                <p class="text-xs text-[#B6BABD]">Start Year</p>
                <input 
                    x-model="startYear" 
                    x-on:input="if ($el.value.length > 4) $el.value = $el.value.slice(0, 4)"
                    type="number" 
                    min="2000" 
                    max="2050" 
                    placeholder="YYYY" 
                    name="start_year" 
                    class="w-30 h-9 px-3 py-1.5 rounded-md border-1 border-[#2A2A2A] text-white focus:border-white"
                >
            </div>

            <div class="flex flex-col w-full gap-1 items-center">
                <p class="text-xs text-[#B6BABD]">Name Preview</p>
                <span 
                    class="py-1.5 transition-colors duration-300" 
                    :class="seasonPreview ? 'text-white' : 'text-[#B6BABD]'"
                    x-text="seasonPreview"
                ></span>
            </div>
        </div>

        <!-- Dates -->
        <div class="flex flex-col w-full justify-center items-center gap-10 py-5 border-b border-b-[#2A2A2A]">
            <div class="flex flex-col w-full gap-1 items-center">
                <p class="text-xs text-[#B6BABD]">Official Start Date</p>
                <input 
                    type="date" 
                    name="start_date" 
                    :min="minStartDate"
                    :max="maxStartDate"
                    class="date-input w-40 h-9 px-3 py-1.5 rounded-md border-1 border-[#2A2A2A] text-white focus:border-white"
                >
            </div>

            <div class="flex gap-2">
                <input 
                    id="is-end-date-true-checkbox" 
                    type="checkbox"
                    x-model="showEndDate"
                    name="end_date_known"
                    value="true"
                >
                <span class="text-[#B6BABD]">End Date is known</span>
            </div>

            <div 
                id="end-date-input-container" 
                class="flex flex-col w-full gap-1 items-center"
                x-show="showEndDate"
                x-transition
            >
                <p class="text-xs text-[#B6BABD]">End Date</p>
                <input 
                    type="date" 
                    name="end_date" 
                    :min="minEndDate"
                    :max="maxEndDate"
                    class="date-input w-40 h-9 px-3 py-1.5 rounded-md border-1 border-[#2A2A2A] text-white focus:border-white"
                >
            </div>
        </div>

        <!-- Active Season -->
        <div class="flex flex-col w-full justify-center items-center py-5 border-b border-b-[#2A2A2A]">
            <div class="flex gap-2">
                <input 
                    id="is-active-season-checkbox" 
                    type="checkbox" 
                    name="is_active" 
                    value="true"
                    x-model="isActive"
                    >
                <span class="text-[#B6BABD]">Set as Active Season</span>
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
                id="save-edit-season-btn" 
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
