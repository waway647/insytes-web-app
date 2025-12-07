<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$current_page = 'logs';
$title = 'System Logs';
?>

<!-- System Logs Page Content -->
<div class="p-6 min-h-full overflow-hidden flex flex-col">
    <!-- Breadcrumb -->
    <div class="mb-6">
        <nav class="text-gray-400 text-sm">
            /System Logs
        </nav>
    </div>

    <div class="mb-8 flex-1 min-h-0 flex flex-col">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-semibold text-white">System Logs</h1>
                <p class="text-gray-400 mt-2">Monitor system activities and audit trail</p>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex items-center space-x-3">
                <!-- Export -->
                <button type="button" 
                        id="exportBtn"
                        class="inline-flex items-center px-4 py-2.5 border border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-300 bg-[#1D1D1D] hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export CSV
                </button>
                
                <!-- Filters -->
                <button type="button" 
                        id="filtersBtn"
                        class="inline-flex items-center px-4 py-2.5 border border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-300 bg-[#1D1D1D] hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"/>
                    </svg>
                    Filters
                </button>

                <!-- Search -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" 
                           placeholder="Search logs..." 
                           id="searchInput"
                           class="block w-64 pl-10 pr-3 py-2.5 border border-gray-600 rounded-lg leading-5 bg-[#1D1D1D] text-white placeholder-gray-400 hover:bg-gray-700 focus:outline-none focus:placeholder-gray-500 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
            </div>
        </div>

        <!-- Professional Quick Filter Chips -->
        <div class="mb-6">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center text-sm text-gray-400 mr-2">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"/>
                    </svg>
                    Filter by category:
                </div>
                
                <!-- Authentication Filter -->
                <button class="category-chip group inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg border transition-all duration-200 border-gray-600 bg-gray-900/20 text-gray-300 hover:bg-gray-800 hover:text-gray-200" data-category="authentication">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Authentication
                </button>
                
                <!-- User Management Filter -->
                <button class="category-chip group inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg border transition-all duration-200 border-gray-600 bg-gray-900/20 text-gray-300 hover:bg-gray-800 hover:text-gray-200" data-category="user_management">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    User Management
                </button>
                
                <!-- Team Operations Filter -->
                <button class="category-chip group inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg border transition-all duration-200 border-gray-600 bg-gray-900/20 text-gray-300 hover:bg-gray-800 hover:text-gray-200" data-category="team_operations">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Team Operations
                </button>
                
                <!-- Match Activities Filter -->
                <button class="category-chip group inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg border transition-all duration-200 border-gray-600 bg-gray-900/20 text-gray-300 hover:bg-gray-800 hover:text-gray-200" data-category="match_activities">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    Match Activities
                </button>
                
                <!-- System Events Filter -->
                <button class="category-chip group inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg border transition-all duration-200 border-gray-600 bg-gray-900/20 text-gray-300 hover:bg-gray-800 hover:text-gray-200" data-category="system_events">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    System Events
                </button>
                
                <!-- Security Events Filter -->
                <button class="category-chip group inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg border transition-all duration-200 border-gray-600 bg-gray-900/20 text-gray-300 hover:bg-gray-800 hover:text-gray-200" data-category="security_events">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Security Events
                </button>
                
                <!-- Data Operations Filter -->
                <button class="category-chip group inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg border transition-all duration-200 border-gray-600 bg-gray-900/20 text-gray-300 hover:bg-gray-800 hover:text-gray-200" data-category="data_operations">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                    </svg>
                    Data Operations
                </button>
            </div>

        </div>

        <!-- Logs Table -->
        <div class="bg-[#1D1D1D] rounded-lg border border-[#2a2a2a] overflow-hidden flex flex-col" style="height: 600px;">
            <!-- Loading State -->
            <div id="loadingState" class="p-8 text-center">
                <div class="inline-flex items-center">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-500 mr-3"></div>
                    <span class="text-gray-400">Loading logs...</span>
                </div>
            </div>

            <!-- Logs Table -->
            <div id="logsTable" class="hidden" style="display: none; flex-direction: column; flex: 1; min-height: 0;">
                <!-- Table Header -->
                <div class="bg-[#2a2a2a] px-6 py-3 border-b border-[#3a3a3a]">
                    <div class="grid grid-cols-12 gap-4 text-xs font-medium text-gray-300 uppercase tracking-wider">
                        <div class="col-span-2">Timestamp</div>
                        <div class="col-span-2">Category</div>
                        <div class="col-span-2">Action</div>
                        <div class="col-span-3">Description</div>
                        <div class="col-span-3">User</div>
                    </div>
                </div>

                <!-- Table Body -->
                <div class="divide-y divide-[#2a2a2a] logs-list overflow-y-auto overflow-x-auto custom-scroll flex-1">
                    <!-- JavaScript will fill this with log entries -->
                </div>
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="hidden px-8 py-20 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-400">No logs found</h3>
                <p class="mt-1 text-sm text-gray-500">Try adjusting your filters or search terms.</p>
            </div>
        </div>

        <!-- Pagination -->
        <div id="pagination" class="hidden mt-6">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-400">
                    Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalLogs">0</span> logs
                </div>
                <div class="flex items-center space-x-2">
                    <button id="prevPage" class="px-3 py-2 text-sm bg-gray-700 text-gray-300 rounded-md hover:bg-gray-600 disabled:opacity-50" disabled>Previous</button>
                    <div id="pageNumbers" class="flex space-x-1"></div>
                    <button id="nextPage" class="px-3 py-2 text-sm bg-gray-700 text-gray-300 rounded-md hover:bg-gray-600 disabled:opacity-50" disabled>Next</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Advanced Filters Modal -->
<div id="filtersModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 bg-[#131313b0] bg-opacity-40 backdrop-blur-sm" aria-hidden="true"></div>
        
        <div class="relative inline-block align-middle bg-gray-800 rounded-lg px-6 pt-6 pb-6 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full border border-gray-600 z-10">
            <div class="sm:flex sm:items-start">
                <div class="w-full">
                    <h3 class="text-lg leading-6 font-medium text-white mb-4">Advanced Filters</h3>
                    
                    <div class="space-y-6">
                        <!-- Role Filter -->
                        <div>
                            <label for="roleFilter" class="block text-sm font-medium text-gray-300 mb-2">User Role</label>
                            <select id="roleFilter" class="mt-1 block w-full px-4 py-3 border-gray-600 bg-gray-700 text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-base">
                                <option value="">All Roles</option>
                                <option value="admin">Admin</option>
                                <option value="coach">Coach</option>
                                <option value="player">Player</option>
                            </select>
                        </div>
                        
                        <!-- Date Range -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="dateFrom" class="block text-sm font-medium text-gray-300 mb-2">From Date</label>
                                <input type="date" id="dateFrom" class="mt-1 block w-full px-4 py-3 border-gray-600 bg-gray-700 text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-base">
                            </div>
                            <div>
                                <label for="dateTo" class="block text-sm font-medium text-gray-300 mb-2">To Date</label>
                                <input type="date" id="dateTo" class="mt-1 block w-full px-4 py-3 border-gray-600 bg-gray-700 text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-base">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" id="clearFilters" class="px-4 py-2 text-sm font-medium text-gray-300 bg-gray-600 rounded-md hover:bg-gray-500">
                    Clear All
                </button>
                <button type="button" id="cancelFilters" class="px-4 py-2 text-sm font-medium text-gray-300 bg-gray-600 rounded-md hover:bg-gray-500">
                    Cancel
                </button>
                <button type="button" id="applyFilters" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                    Apply Filters
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Log Details Modal -->
<div id="logDetailsModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 bg-[#131313b0] bg-opacity-40 backdrop-blur-sm" aria-hidden="true"></div>
        
        <div class="relative inline-block align-middle bg-gray-800 rounded-lg px-6 pt-6 pb-6 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-2xl sm:w-full border border-gray-600 z-10">
            <div id="logDetailsContent">
                <!-- Log details will be populated here -->
            </div>
            
            <div class="mt-6 flex justify-end">
                <button type="button" id="closeLogDetails" class="px-4 py-2 text-sm font-medium text-gray-300 bg-gray-600 rounded-md hover:bg-gray-500">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo base_url('assets/js/adminLogsHandler.js'); ?>"></script>
