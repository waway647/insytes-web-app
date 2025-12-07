<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="text-white">
    <h2 class="text-xl font-semibold mb-6">Admin Settings</h2>
    
    <div class="space-y-6">
        <!-- Admin Role Information -->
        <div class="bg-[#2A2A2A] rounded-lg p-6 border border-[#3A3A3A]">
            <h3 class="text-lg font-medium mb-4">Admin Role</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Role</label>
                    <div class="px-3 py-2 bg-[#1A1A1A] border border-[#3A3A3A] rounded-md text-gray-300">
                        Admin
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Access Level</label>
                    <div class="px-3 py-2 bg-[#1A1A1A] border border-[#3A3A3A] rounded-md text-gray-300">
                        Full System Access
                    </div>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="bg-[#2A2A2A] rounded-lg p-6 border border-[#3A3A3A]">
            <h3 class="text-lg font-medium mb-4">System Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Last Login</label>
                    <div class="px-3 py-2 bg-[#1A1A1A] border border-[#3A3A3A] rounded-md text-gray-300">
                        <?php echo date('F j, Y g:i A'); ?>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Session Status</label>
                    <div class="px-3 py-2 bg-[#1A1A1A] border border-[#3A3A3A] rounded-md text-green-400">
                        Active
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-[#2A2A2A] rounded-lg p-6 border border-[#3A3A3A]">
            <h3 class="text-lg font-medium mb-4">Quick Actions</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="<?php echo site_url('Admin/DashboardController/adminDashboard'); ?>" 
                   class="flex items-center justify-center px-4 py-3 bg-indigo-600 hover:bg-indigo-700 rounded-md text-white text-sm font-medium transition-colors duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Admin Dashboard
                </a>
                
                <a href="<?php echo site_url('Admin/UserController/index'); ?>" 
                   class="flex items-center justify-center px-4 py-3 bg-green-600 hover:bg-green-700 rounded-md text-white text-sm font-medium transition-colors duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                    </svg>
                    Manage Users
                </a>
                
                <a href="<?php echo site_url('Admin/TeamController/index'); ?>" 
                   class="flex items-center justify-center px-4 py-3 bg-purple-600 hover:bg-purple-700 rounded-md text-white text-sm font-medium transition-colors duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Manage Teams
                </a>
            </div>
        </div>

        <!-- Admin Permissions -->
        <div class="bg-[#2A2A2A] rounded-lg p-6 border border-[#3A3A3A]">
            <h3 class="text-lg font-medium mb-4">Admin Permissions</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-sm text-gray-300">User Management</span>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-sm text-gray-300">Team Management</span>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-sm text-gray-300">System Configuration</span>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-sm text-gray-300">Data Access</span>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-sm text-gray-300">Report Generation</span>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-sm text-gray-300">Security Settings</span>
                </div>
            </div>
        </div>
    </div>
</div>