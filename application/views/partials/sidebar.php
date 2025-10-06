<?php
    /* $role = $this->session->userdata('role'); */
    $role = 'coach';
?>

<div id="sidebar-nav" class="sidebar h-full w-64 text-[#2A2A2A] flex flex-col justify-between p-4 border-r bg-[gray-900]">
    <div class="upper-menu flex flex-col gap-10">
        <div class="logo-container px-5">
            <a href="<?php echo site_url('team/dashboardcontroller/index'); ?>" class="logo">
                <img src="<?php echo base_url('assets/images/logo/logo-text.svg'); ?>" alt="">
            </a>
        </div>

        <div class="navigation-links flex flex-col gap-2">
            <!-- COACH nav-items -->
            <?php if ($role == 'coach'): ?>
            <a href="<?php echo site_url('team/dashboardcontroller/index'); ?>" class="group py-2.5 px-5 rounded-md hover:bg-gray-800">
                <div class="nav-item flex items-center gap-3">
                    <img class="group-hover:hidden" src="<?php echo base_url('assets/images/icons/dashboard.svg'); ?>" alt="">
                    <img class="hidden group-hover:block" src="<?php echo base_url('assets/images/icons/dashboard-active.svg'); ?>" alt="">
                    <span class="nav-item-text text-[#B6BABD] text-base font-semibold group-hover:text-white">Dashboard</span>
                </div>
            </a>

            <a href="<?php echo site_url('clips/resultscontroller/index'); ?>" class="group py-2.5 px-5 rounded-md hover:bg-gray-800">
                <div class="nav-item flex items-center gap-3">
                    <img class="group-hover:hidden" src="<?php echo base_url('assets/images/icons/results.svg'); ?>" alt="">
                    <img class="hidden group-hover:block" src="<?php echo base_url('assets/images/icons/results-active.svg'); ?>" alt="">
                    <span class="nav-item-text text-[#B6BABD] text-base font-semibold group-hover:text-white">Results</span>
                </div>
            </a>

            <a href="<?php echo site_url('match/librarycontroller/index'); ?>" class="group py-2.5 px-5 rounded-md hover:bg-gray-800">
                <div class="nav-item flex items-center gap-3">
                    <img class="group-hover:hidden" src="<?php echo base_url('assets/images/icons/match-library.svg'); ?>" alt="">
                    <img class="hidden group-hover:block" src="<?php echo base_url('assets/images/icons/match-library-active.svg'); ?>" alt="">
                    <span class="nav-item-text text-[#B6BABD] text-base font-semibold group-hover:text-white">Match Library</span>
                </div>
            </a>
            
            <a href="<?php echo site_url('team/peoplecontroller/index'); ?>" class="group py-2.5 px-5 rounded-md hover:bg-gray-800">
                <div class="nav-item flex items-center gap-3">
                    <img class="group-hover:hidden" src="<?php echo base_url('assets/images/icons/people.svg'); ?>" alt="">
                    <img class="hidden group-hover:block" src="<?php echo base_url('assets/images/icons/people-active.svg'); ?>" alt="">
                    <span class="nav-item-text text-[#B6BABD] text-base font-semibold group-hover:text-white">People</span>
                </div>
            </a>

            <a href="<?php echo site_url('clips/reviewcontroller/index'); ?>" class="group py-2.5 px-5 rounded-md hover:bg-gray-800">
                <div class="nav-item flex items-center gap-3">
                    <img class="group-hover:hidden" src="<?php echo base_url('assets/images/icons/review.svg'); ?>" alt="">
                    <img class="hidden group-hover:block" src="<?php echo base_url('assets/images/icons/review-active.svg'); ?>" alt="">
                    <span class="nav-item-text text-[#B6BABD] text-base font-semibold group-hover:text-white">Review</span>
                </div>
            </a>

            <a href="<?php echo site_url('reports/reportscontroller/index'); ?>" class="group py-2.5 px-5 rounded-md hover:bg-gray-800">
                <div class="nav-item flex items-center gap-3">
                    <img class="group-hover:hidden" src="<?php echo base_url('assets/images/icons/reports.svg'); ?>" alt="">
                    <img class="hidden group-hover:block" src="<?php echo base_url('assets/images/icons/reports-active.svg'); ?>" alt="">
                    <span class="nav-item-text text-[#B6BABD] text-base font-semibold group-hover:text-white">Reports</span>
                </div>
            </a>
            <?php endif; ?>

            <!-- PLAYER nav-items -->
            <?php if ($role == 'player'): ?>
            <a href="<?php echo site_url('team/dashboardcontroller/index'); ?>" class="group py-2.5 px-5 rounded-md hover:bg-gray-800">
                <div class="nav-item flex items-center gap-3">
                    <img class="group-hover:hidden" src="<?php echo base_url('assets/images/icons/dashboard.svg'); ?>" alt="">
                    <img class="hidden group-hover:block" src="<?php echo base_url('assets/images/icons/dashboard-active.svg'); ?>" alt="">
                    <span class="nav-item-text text-[#B6BABD] text-base font-semibold group-hover:text-white">Dashboard</span>
                </div>
            </a>

            <a href="<?php echo site_url('clips/resultscontroller/index'); ?>" class="group py-2.5 px-5 rounded-md hover:bg-gray-800">
                <div class="nav-item flex items-center gap-3">
                    <img class="group-hover:hidden" src="<?php echo base_url('assets/images/icons/results.svg'); ?>" alt="">
                    <img class="hidden group-hover:block" src="<?php echo base_url('assets/images/icons/results-active.svg'); ?>" alt="">
                    <span class="nav-item-text text-[#B6BABD] text-base font-semibold group-hover:text-white">Results</span>
                </div>
            </a>
            
            <a href="<?php echo site_url('team/peoplecontroller/index'); ?>" class="group py-2.5 px-5 rounded-md hover:bg-gray-800">
                <div class="nav-item flex items-center gap-3">
                    <img class="group-hover:hidden" src="<?php echo base_url('assets/images/icons/people.svg'); ?>" alt="">
                    <img class="hidden group-hover:block" src="<?php echo base_url('assets/images/icons/people-active.svg'); ?>" alt="">
                    <span class="nav-item-text text-[#B6BABD] text-base font-semibold group-hover:text-white">People</span>
                </div>
            </a>

            <a href="<?php echo site_url('clips/reviewcontroller/index'); ?>" class="group py-2.5 px-5 rounded-md hover:bg-gray-800">
                <div class="nav-item flex items-center gap-3">
                    <img class="group-hover:hidden" src="<?php echo base_url('assets/images/icons/review.svg'); ?>" alt="">
                    <img class="hidden group-hover:block" src="<?php echo base_url('assets/images/icons/review-active.svg'); ?>" alt="">
                    <span class="nav-item-text text-[#B6BABD] text-base font-semibold group-hover:text-white">Review</span>
                </div>
            </a>
            <?php endif; ?>

            <?php if ($role == 'admin'): ?>
            <a href="<?php echo site_url('team/dashboardcontroller/index'); ?>" class="group py-2.5 px-5 rounded-md hover:bg-gray-800">
                <div class="nav-item flex items-center gap-3">
                    <img class="group-hover:hidden" src="<?php echo base_url('assets/images/icons/dashboard.svg'); ?>" alt="">
                    <img class="hidden group-hover:block" src="<?php echo base_url('assets/images/icons/dashboard-active.svg'); ?>" alt="">
                    <span class="nav-item-text text-[#B6BABD] text-base font-semibold group-hover:text-white">Dashboard</span>
                </div>
            </a>

            <a href="<?php echo site_url('clips/resultscontroller/index'); ?>" class="group py-2.5 px-5 rounded-md hover:bg-gray-800">
                <div class="nav-item flex items-center gap-3">
                    <img class="group-hover:hidden" src="<?php echo base_url('assets/images/icons/people.svg'); ?>" alt="">
                    <img class="hidden group-hover:block" src="<?php echo base_url('assets/images/icons/people-active.svg'); ?>" alt="">
                    <span class="nav-item-text text-[#B6BABD] text-base font-semibold group-hover:text-white">Users</span>
                </div>
            </a>
            
            <a href="<?php echo site_url('team/peoplecontroller/index'); ?>" class="group py-2.5 px-5 rounded-md hover:bg-gray-800">
                <div class="nav-item flex items-center gap-3">
                    <img class="group-hover:hidden" src="<?php echo base_url('assets/images/icons/teams.svg'); ?>" alt="">
                    <img class="hidden group-hover:block" src="<?php echo base_url('assets/images/icons/teams-active.svg'); ?>" alt="">
                    <span class="nav-item-text text-[#B6BABD] text-base font-semibold group-hover:text-white">Teams</span>
                </div>
            </a>

            <a href="<?php echo site_url('clips/reviewcontroller/index'); ?>" class="group py-2.5 px-5 rounded-md hover:bg-gray-800">
                <div class="nav-item flex items-center gap-3">
                    <img class="group-hover:hidden" src="<?php echo base_url('assets/images/icons/logs.svg'); ?>" alt="">
                    <img class="hidden group-hover:block" src="<?php echo base_url('assets/images/icons/logs-active.svg'); ?>" alt="">
                    <span class="nav-item-text text-[#B6BABD] text-base font-semibold group-hover:text-white">Logs</span>
                </div>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="lower-content">
        <div class="utility-links flex flex-col gap-2">
            <a href="<?php echo site_url('utilities/documentationcontroller/index'); ?>" class="group py-2.5 px-5 rounded-md hover:bg-gray-800">
                <div class="nav-item flex items-center justify-between">
                    <div class="flex items-center justify-between gap-3">
                        <img class="group-hover:hidden" src="<?php echo base_url('assets/images/icons/documentation.svg'); ?>" alt="">
                        <img class="hidden group-hover:block" src="<?php echo base_url('assets/images/icons/documentation-active.svg'); ?>" alt="">
                        <span class="nav-item-text text-[#B6BABD] text-base font-semibold group-hover:text-white">Documentation</span>
                    </div>
                    <img class="group-hover:hidden" src="<?php echo base_url('assets/images/icons/external-link.svg'); ?>" alt="">
                    <img class="hidden group-hover:block" src="<?php echo base_url('assets/images/icons/external-link-active.svg'); ?>" alt="">
                </div>
            </a>

            <a href="<?php echo site_url('utilities/notificationscontroller/index'); ?>" class="group py-2.5 px-5 rounded-md hover:bg-gray-800">
                <div class="nav-item flex items-center gap-3">
                    <img class="group-hover:hidden" src="<?php echo base_url('assets/images/icons/notifications.svg'); ?>" alt="">
                    <img class="hidden group-hover:block" src="<?php echo base_url('assets/images/icons/notifications-active.svg'); ?>" alt="">
                    <span class="nav-item-text text-[#B6BABD] text-base font-semibold group-hover:text-white">Notifications</span>
                </div>
            </a>

            <div x-data="{ open: false, isHovering: false }" class="flex flex-col">
                <a @click="open = !open" 
                @mouseenter="isHovering = true" 
                @mouseleave="isHovering = false"
                class="group py-2.5 px-5 rounded-md hover:bg-gray-800 cursor-pointer" 
                :class="{'bg-gray-800' : open}">
                    
                    <div class="nav-item flex justify-between items-center w-full">
                        <div class="flex items-center justify-between gap-3">
                            
                            <img x-cloak x-show="!open && !isHovering" 
                                src="<?php echo base_url('assets/images/icons/settings.svg'); ?>" alt="">
                            
                            <img x-cloak x-show="open || isHovering" 
                                src="<?php echo base_url('assets/images/icons/settings-active.svg'); ?>" alt="">
                            
                            <span class="nav-item-text text-[#B6BABD] text-base font-semibold group-hover:text-white" :class="{'text-white' : open}">Settings</span>
                        </div>
                        
                        <svg class="h-5 w-5 transition-transform duration-300 text-[#B6BABD] group-hover:text-white" :class="{'rotate-180 text-white': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </a>

                <div x-show="open" x-collapse.duration.300ms class="flex flex-col">
                    <a href="<?php echo site_url('account/personaldatacontroller/index'); ?>" class="group py-2.5 px-11 rounded-md hover:bg-gray-800">
                        <div class="nav-item flex items-center gap-3">
                            <img src="" alt="">
                            <span class="nav-item-text text-[#B6BABD] text-base font-semibold group-hover:text-white">Account</span>
                        </div>
                    </a>

                    <a href="<?php echo site_url('auth/logincontroller/logout'); ?>" class="group py-2.5 px-11 rounded-md hover:bg-gray-800">
                        <div class="nav-item flex items-center gap-3">
                            <img src="" alt="">
                            <span class="nav-item-text text-[#B6BABD] text-base font-semibold group-hover:text-white">Logout</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>