<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$current_page = 'users';
$page_title = 'Users';
?>

<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$current_page = 'users';
$page_title = 'Users';
?>

<!-- Users Management Page Content -->
<div class="p-6">
    <!-- Breadcrumb -->
    <div class="mb-6">
        <nav class="text-gray-400 text-sm">
            /Users
        </nav>
    </div>

    <div class="mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-semibold text-white">Users</h1>
                <p class="text-gray-400 mt-2">Showing all users</p>
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
                        id="addUserBtn"
                        class="inline-flex items-center px-4 py-2.5 border text-sm font-medium rounded-lg shadow-sm border-gray-600 text-white bg-[#1D1D1D] hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500  focus:ring-offset-2 focus:ring-offset-gray-900">
                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add
                </button>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-[#1D1D1D] backdrop-blur-sm rounded-2xl overflow-hidden shadow-sm">

        <div class="flex-1 overflow-y-auto bg-transparent">
            <!-- Table Container -->
            <div class="min-w-full">

                <div class="px-8 pt-5 pb-3 flex items-center gap-5">
                    <h3 class="text-base font-medium text-gray-300">
                        List of all users
                    </h3>
                    <span class="flex items-center justify-center w-9 h-9 text-lg font-bold text-gray-400 bg-gray-800 rounded-md border border-gray-700">
                        0
                    </span>
                </div>

                <!-- Table Content -->
                <div class="divide-y divide-[#2a2a2a]">
                    <!-- Table Header Row -->
                    <div class="px-8 py-4">
                        <div class="grid grid-cols-12 gap-4 text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <div class="col-span-3">Email</div>
                            <div class="col-span-2 text-center">First name</div>
                            <div class="col-span-2 text-center">Last name</div>
                            <div class="col-span-2 text-center">Team</div>
                            <div class="col-span-1 text-center">Role</div>
                            <div class="col-span-1 text-center">Last updated</div>
                            <div class="col-span-1 text-center">Actions</div>
                        </div>
                    </div>

                    <!-- Table Body -->
                    <div class="divide-y divide-[#2a2a2a] users-list overflow-y-auto custom-scroll" style="max-height:60vh;">
                        <!-- JavaScript will fill this with user rows -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State (optional) -->
        <div id="emptyState" class="hidden px-8 py-20 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-400">No users found</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by adding your first user.</p>
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
                    <h3 class="text-lg leading-6 font-medium text-white mb-4">Filter Users</h3>
                    
                    <div class="space-y-6">
                        <!-- Role Filter -->
                        <div>
                            <label for="roleFilter" class="block text-sm font-medium text-gray-300 mb-2">Role</label>
                            <select id="roleFilter" class="mt-1 block w-full px-4 py-3 border-gray-600 bg-gray-700 text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-base">
                                <option value="">All Roles</option>
                                <option value="Coach">Coach</option>
                                <option value="Player">Player</option>
                            </select>
                        </div>
                        
                        <!-- Team Filter -->
                        <div>
                            <label for="teamFilter" class="block text-sm font-medium text-gray-300 mb-2">Team</label>
                            <select id="teamFilter" class="mt-1 block w-full px-4 py-3 border-gray-600 bg-gray-700 text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-base">
                                <option value="">All Teams</option>
                                <option value="San Beda">San Beda</option>
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

<!-- Add User Modal -->
<div id="addUserModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 bg-[#131313b0] bg-opacity-40 backdrop-blur-sm" aria-hidden="true"></div>
        
        <div class="relative inline-block align-middle bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full sm:p-6 border border-gray-600 z-10">
            <div class="sm:flex sm:items-start">
                <div class="w-full">
                    <h3 class="text-lg leading-6 font-medium text-white mb-4">Add New User</h3>
                    
                    <form id="addUserForm" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="firstName" class="block text-sm font-medium text-gray-300 mb-2">First Name</label>
                                <input type="text" id="firstName" required class="mt-1 block w-full px-4 py-3 border-gray-600 bg-gray-700 text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-base">
                            </div>
                            <div>
                                <label for="lastName" class="block text-sm font-medium text-gray-300 mb-2">Last Name</label>
                                <input type="text" id="lastName" required class="mt-1 block w-full px-4 py-3 border-gray-600 bg-gray-700 text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-base">
                            </div>
                        </div>
                        
                        <div>
                            <label for="userTeam" class="block text-sm font-medium text-gray-300 mb-2">Team</label>
                            <select id="userTeam" class="mt-1 block w-full px-4 py-3 border-gray-600 bg-gray-700 text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-base">
                                <option value="">Select a team (optional)</option>
                                <!-- Teams will be loaded dynamically by JavaScript -->
                            </select>
                        </div>

                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-300 mb-2">Role</label>
                            <select id="role" required class="mt-1 block w-full px-4 py-3 border-gray-600 bg-gray-700 text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-base">
                                <option value="">Select a role</option>
                                <option value="Coach">Coach</option>
                                <option value="Player">Player</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-300 mb-2">
                                Initial Email
                                <span id="emailHelp" class="text-gray-500 text-xs">(user will be prompted to change on first login)</span>
                            </label>
                            <div class="flex gap-2">
                                <div class="flex-1 relative">
                                    <input type="email" id="email" required class="w-full px-4 py-3 pr-10 border-gray-600 bg-gray-700 text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-base" placeholder="Enter email address">
                                </div>
                                <button type="button" id="generateEmail" class="px-4 py-3 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 text-sm font-medium">
                                    Generate
                                </button>
                            </div>
                        </div>
                        
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-300 mb-2">
                                Initial Password 
                                <span id="passwordHelp" class="text-gray-500 text-xs">(user will be prompted to change on first login)</span>
                            </label>
                            <div class="flex gap-2">
                                <div class="flex-1 relative">
                                    <input type="password" id="password" required class="w-full px-4 py-3 pr-10 border-gray-600 bg-gray-700 text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-base" placeholder="Enter temporary password">
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
                                <button type="button" id="generatePassword" class="px-4 py-3 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 text-sm font-medium">
                                    Generate
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="mt-10 sm:mt-8 flex justify-end space-x-4 gap-4">
                <button type="button" id="cancelAddUser" class="inline-flex justify-center rounded-md border border-gray-500 shadow-sm px-6 py-3 bg-gray-700 text-base font-medium text-gray-300 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    Cancel
                </button>
                <button type="button" id="saveUser" class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-6 py-3 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Add User
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Processing Modal -->
<div id="processingModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 bg-[#131313b0] bg-opacity-40 backdrop-blur-sm" aria-hidden="true"></div>
        
        <div class="relative inline-block align-middle bg-gray-800 rounded-lg px-6 py-8 text-center overflow-hidden shadow-xl transform transition-all sm:max-w-sm sm:w-full border border-gray-600 z-10">
            <div class="flex flex-col items-center">
                <!-- Spinning loader -->
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500 mb-4"></div>
                <h3 class="text-lg font-medium text-white mb-2">Creating User...</h3>
                <p class="text-sm text-gray-400">Please wait while we process your request.</p>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal for User Credentials -->
<div id="successModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 bg-[#131313b0] bg-opacity-40 backdrop-blur-sm" aria-hidden="true"></div>
        
        <div class="relative inline-block align-middle bg-gray-800 rounded-lg px-4 py-5 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-sm sm:w-full border border-gray-600 z-10">
            <div class="text-center space-y-4">
                <div class="mx-auto flex items-center justify-center h-10 w-10 rounded-full bg-green-100">
                    <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-base font-medium text-white">User Created Successfully!</h3>

                <div class="bg-gray-700 rounded-lg p-3 space-y-3 text-left">
                    <div class="flex items-center gap-3 py-1 px-1">
                        <div class="flex-1 relative bg-gray-900 rounded px-3 py-2 gap-2">
                            <code id="displayEmail" class="text-green-400 font-mono text-xs break-all">email</code>
                        </div>
                        <div class="w-16 text-right text-xs text-gray-300">Email</div>
                    </div>

                    <div class="flex items-center gap-3 py-1 px-1">
                        <div class="flex-1 relative bg-gray-900 rounded px-3 py-2 gap-2">
                            <code id="displayPassword" class="text-green-400 font-mono text-xs">password</code>
                            <button type="button" id="toggleSuccessPassword" class="absolute inset-y-0 right-0 pr-3 px-1 flex items-center text-gray-400 hover:text-gray-300">
                                <!-- Eye Slash Icon (Password Hidden - Default State) -->
                                    <svg id="success-eye-closed" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                                    </svg>
                                <!-- Eye Icon (Password Visible) -->
                                    <svg id="success-eye-open" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                            </button>
                        </div>
                        <div class="w-16 text-right text-xs text-gray-300">Password</div>
                    </div>

                    <div class="flex items-center gap-3 py-1 px-1">
                        <button id="copyCredentials" class="w-full inline-flex items-center justify-center gap-2 rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            Copy Email & Password
                        </button>
                    </div>
                </div>

                <p class="text-xs text-gray-400">Please save these credentials. User will change password on first login.</p>

                <button type="button" id="closeSuccessModal" class="w-full justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-generate username and email functionality
document.addEventListener('DOMContentLoaded', function() {
    const firstNameInput = document.getElementById('firstName');
    const lastNameInput = document.getElementById('lastName');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const generateUsernameBtn = document.getElementById('generateUsername');
    const generateEmailBtn = document.getElementById('generateEmail');

    // Generate username from first and last name
    function generateUsername() {
        const firstName = firstNameInput.value.trim().toLowerCase().replace(/\s+/g, '');
        const lastName = lastNameInput.value.trim().toLowerCase().replace(/\s+/g, '');
        
        if (firstName && lastName) {
            // Generate username: firstname.lastname + random 3-digit number
            const randomNum = Math.floor(100 + Math.random() * 900);
            const username = `${firstName}.${lastName}${randomNum}`;
            usernameInput.value = username;
        }
    }

    // Generate email from first and last name
    function generateEmail() {
        const firstName = firstNameInput.value.trim().toLowerCase().replace(/\s+/g, '');
        const lastName = lastNameInput.value.trim().toLowerCase().replace(/\s+/g, '');
        
        if (firstName && lastName) {
            // Generate email: firstname.lastname + random number @insytes.com
            const randomNum = Math.floor(100 + Math.random() * 900);
            const email = `${firstName}.${lastName}${randomNum}@insytes.com`;
            emailInput.value = email;
        }
    }

    // Auto-generate on name input change
    function autoGenerate() {
        const firstName = firstNameInput.value.trim().replace(/\s+/g, '');
        const lastName = lastNameInput.value.trim().replace(/\s+/g, '');
        
        if (firstName && lastName) {
            // Only auto-generate if fields are empty or contain placeholder-like text
            if (!usernameInput.value || usernameInput.value.includes('generated')) {
                generateUsername();
            }
            if (!emailInput.value || emailInput.value.includes('generated') || emailInput.value.includes('@insytes.com')) {
                generateEmail();
            }
        }
    }

    // Event listeners
    firstNameInput.addEventListener('blur', autoGenerate);
    lastNameInput.addEventListener('blur', autoGenerate);
    generateUsernameBtn.addEventListener('click', generateUsername);
    generateEmailBtn.addEventListener('click', generateEmail);

    // Clear generated values when modal opens for new user
    const addUserBtn = document.getElementById('addUserBtn');
    if (addUserBtn) {
        addUserBtn.addEventListener('click', function() {
            usernameInput.placeholder = 'Will be generated automatically';
            emailInput.placeholder = 'Will be generated automatically';
        });
    }
});
</script>

<script src="<?php echo base_url('assets/js/adminUserListHandler.js'); ?>"></script>
