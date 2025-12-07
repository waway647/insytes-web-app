<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$current_page = 'teams';
$page_title = 'Teams';
?>

<style>
/* Custom styles for dropdown in modal */
#teamManager {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=utf-8,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 12px center;
    background-repeat: no-repeat;
    background-size: 16px 12px;
    padding-right: 40px;
}

/* Ensure modal content doesn't overflow */
.modal-content {
    max-height: 85vh;
    overflow-y: auto;
}

/* Fix dropdown positioning in modal */
.dropdown-container {
    position: relative;
    z-index: 1000;
}

#addTeamModal {
    z-index: 9999;
}

#addTeamModal .modal-content {
    position: relative;
    z-index: 10000;
}
</style>

<!-- Teams Management Page Content -->
<div class="p-6">
    <!-- Breadcrumb -->
    <div class="mb-6">
        <nav class="text-gray-400 text-sm">
            /Teams
        </nav>
    </div>

    <div class="mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-semibold text-white">Teams</h1>
                <p class="text-gray-400 mt-2">Showing all teams</p>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex items-center space-x-3">
                <!-- Search -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" 
                           placeholder="Search" 
                           id="searchInput"
                           class="block w-64 pl-10 pr-3 py-2.5 border border-gray-600 rounded-lg leading-5 bg-[#1D1D1D] text-white placeholder-gray-400 hover:bg-gray-700 focus:outline-none focus:placeholder-gray-500 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                
                <!-- Filters -->
                <button type="button" 
                        id="filtersBtn"
                        class="inline-flex items-center px-4 py-2.5 border border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-300 bg-[#1D1D1D] hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"/>
                    </svg>
                    Filters
                </button>
                
                <!-- Add Button -->
                <button type="button" 
                        id="addTeamBtn"
                        class="inline-flex items-center px-4 py-2.5 border text-sm font-medium rounded-lg shadow-sm border-gray-600 text-white bg-[#1D1D1D] hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500  focus:ring-offset-2 focus:ring-offset-gray-900">
                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add
                </button>
            </div>
        </div>
    </div>

    <!-- Teams Table -->
    <div class="bg-[#1D1D1D] backdrop-blur-sm rounded-2xl overflow-hidden shadow-sm">

        <div class="flex-1 overflow-y-auto bg-transparent">
            <!-- Table Container -->
            <div class="min-w-full">

                <div class="px-8 pt-5 pb-3 flex items-center gap-5">
                    <h3 class="text-base font-medium text-gray-300">
                        List of all teams
                    </h3>
                    <span class="flex items-center justify-center w-9 h-9 text-lg font-bold text-gray-400 bg-gray-800 rounded-md border border-gray-700" id="teamCount">
                        0
                    </span>
                </div>

                <!-- Table Content -->
                <div class="divide-y divide-[#2a2a2a]">
                    <!-- Table Header Row -->
                    <div class="px-8 py-4">
                        <div class="grid grid-cols-12 gap-4 text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <div class="col-span-3">Team Name</div>
                            <div class="col-span-2 text-center">Location</div>
                            <div class="col-span-2 text-center">Manager</div>
                            <div class="col-span-2 text-center">Total Members</div>
                            <div class="col-span-1 text-center">Colors</div>
                            <div class="col-span-1 text-center">Last updated</div>
                            <div class="col-span-1 text-center">Actions</div>
                        </div>
                    </div>

                    <!-- Table Body -->
                    <div class="divide-y divide-[#2a2a2a] teams-list overflow-y-auto custom-scroll" style="max-height:60vh;">
                        <!-- JavaScript will fill this with team rows -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State (optional) -->
        <div id="emptyState" class="hidden px-8 py-20 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-400">No teams found</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by adding your first team.</p>
        </div>
    </div>
</div>

<!-- Filter Modal -->
<div id="filtersModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 bg-[#131313b0] bg-opacity-40 backdrop-blur-sm" aria-hidden="true"></div>
        
        <div class="relative inline-block align-middle bg-gray-800 rounded-lg px-6 pt-6 pb-6 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full border border-gray-600 z-10">
            <div class="sm:flex sm:items-start">
                <div class="w-full">
                    <h3 class="text-lg leading-6 font-medium text-white mb-4">Filter Teams</h3>
                    
                    <div class="space-y-6">
                        <!-- Location Filter -->
                        <div>
                            <label for="locationFilter" class="block text-sm font-medium text-gray-300 mb-2">Location</label>
                            <select id="locationFilter" class="mt-1 block w-full px-4 py-3 border-gray-600 bg-gray-700 text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-base">
                                <option value="">All Locations</option>
                                <option value="Manila">Manila</option>
                                <option value="Barcelona">Barcelona</option>
                            </select>
                        </div>
                        
                        <!-- Manager Filter -->
                        <div>
                            <label for="managerFilter" class="block text-sm font-medium text-gray-300 mb-2">Manager</label>
                            <select id="managerFilter" class="mt-1 block w-full px-4 py-3 border-gray-600 bg-gray-700 text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-base">
                                <option value="">All Managers</option>
                                <!-- Teams will be loaded dynamically -->
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-10 sm:mt-8 flex justify-end space-x-4 gap-4">
                <button type="button" id="cancelFilters" class="inline-flex justify-center rounded-md border border-gray-500 shadow-sm px-6 py-3 bg-gray-700 text-base font-medium text-gray-300 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    Cancel
                </button>
                <button type="button" id="applyFilters" class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-6 py-3 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Apply Filters
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Team Modal -->
<div id="addTeamModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 bg-[#131313b0] bg-opacity-40 backdrop-blur-sm" aria-hidden="true"></div>
        
        <div class="relative inline-block align-middle bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full sm:p-6 border border-gray-600 z-10 modal-content">
            <div class="sm:flex sm:items-start">
                <div class="w-full">
                    <h3 class="text-lg leading-6 font-medium text-white mb-4">Add New Team</h3>
                    
                    <form id="addTeamForm" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="teamName" class="block text-sm font-medium text-gray-300 mb-2">Team Name</label>
                                <input type="text" id="teamName" required class="mt-1 block w-full px-4 py-3 border-gray-600 bg-gray-700 text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-base">
                            </div>
                            <div>
                                <label for="teamLocation" class="block text-sm font-medium text-gray-300 mb-2">Location</label>
                                <input type="text" id="teamLocation" required class="mt-1 block w-full px-4 py-3 border-gray-600 bg-gray-700 text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-base">
                            </div>
                        </div>
                        
                        <div class="dropdown-container">
                            <label for="teamManager" class="block text-sm font-medium text-gray-300 mb-2">Team Manager</label>
                            <select id="teamManager" required class="mt-1 block w-full px-4 py-3 border-gray-600 bg-gray-700 text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-base">
                                <option value="">Select a manager...</option>
                                <!-- Managers will be loaded dynamically -->
                            </select>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="primaryColor" class="block text-sm font-medium text-gray-300 mb-2">Primary Color</label>
                                <input type="color" id="primaryColor" value="#dc2626" class="mt-1 block w-full h-12 px-2 border-gray-600 bg-gray-700 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="secondaryColor" class="block text-sm font-medium text-gray-300 mb-2">Secondary Color</label>
                                <input type="color" id="secondaryColor" value="#ffffff" class="mt-1 block w-full h-12 px-2 border-gray-600 bg-gray-700 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        
                        <div>
                            <label for="teamLogo" class="block text-sm font-medium text-gray-300 mb-2">Team Logo</label>
                            <input type="file" id="teamLogo" accept="image/*" class="mt-1 block w-full px-4 py-3 border-gray-600 bg-gray-700 text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-base file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="mt-10 sm:mt-8 flex justify-end space-x-4 gap-4">
                <button type="button" id="cancelAddTeam" class="inline-flex justify-center rounded-md border border-gray-500 shadow-sm px-6 py-3 bg-gray-700 text-base font-medium text-gray-300 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    Cancel
                </button>
                <button type="button" id="saveTeam" class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-6 py-3 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Add Team
                </button>
            </div>
        </div>
    </div>
</div>
<script src="<?php echo base_url('assets/js/adminTeamListHandler.js'); ?>"></script>
