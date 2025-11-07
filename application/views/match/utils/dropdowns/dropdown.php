<!-- match/utils/dropdowns/dropdown.php -->
<!-- DROPDOWN -->
<div id="dynamic-dropdown" class="bg-[#131313]">
  <div class="flex flex-col gap-5 pb-4 bg-[#131313] w-fit min-w-100 items-center">
    <div id="dropdown-item-container" class="flex flex-col w-fit">
      <!-- NOTE: changed id -> class and added data attributes -->
      <div class="dropdown-item flex justify-between gap-4 py-2 px-2 border-b-1 border-b-[#2A2A2A] bg-[#131313] hover:bg-[#2A2A2A]" 
           data-item-id="static-2025" data-item-name="2025/2026">
        <span class="dropdown-item-value pl-2 text-[#B6BABD] font-medium text-sm">2025/2026</span>

        <!-- NOTE: changed to class action-utility-btn so JS handlers catch it -->
        <button class="action-utility-btn p-1 hover:bg-[#2A2A2A] rounded-full cursor-pointer" type="button">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
            <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
          </svg>
        </button>
      </div>
    </div>
    <div id="add-item-container" class="w-full justify-center items-center py-4">
      <p id="add-item-btn" class="text-[#B6BABD] font-normal text-xs">Add</p>
    </div>
  </div>
</div>


<!-- ACTION UTILITIES -->
<div id="dynamic-action-utility" class="hidden">
  <div class="flex pb-2 bg-[#1D1D1D] w-fit min-w-100">
    <div id="action-utility-container" class="flex flex-col w-fit">
      <div id="edit-utility-item" class="flex justify-between gap-4 py-2 px-2 border-b-1 border-b-[#2A2A2A]">
        <img src="<?php echo base_url('assets/images/icons/edit.svg'); ?>" class="pl-2" alt="">
        <!-- use class, not id -->
        <span class="action-utility-item-value pl-2 text-[#B6BABD] font-medium text-sm">Edit</span>
      </div>
      <div id="remove-utility-item" class="flex gap-3 py-2 px-2 border-b-1 border-b-[#2A2A2A]">
        <img src="<?php echo base_url('assets/images/icons/trash.svg'); ?>" class="pl-2" alt="">
        <span class="action-utility-item-value text-[#B6BABD] font-medium text-sm">Remove</span>
      </div>
    </div>
  </div>
</div>