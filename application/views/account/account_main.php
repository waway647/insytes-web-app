<?php 
    // Get the user role once
    $role = $this->session->userdata('role'); 
    
    // Determine the default active tab based on role (personal for all)
    $default_tab = 'personal';
?>

<h1 class="text-white font-bold text-2xl mb-10">Account</h1>

<div x-data="{ activeTab: '<?php echo $default_tab; ?>' }" class="w-full h-full flex">
    
    <div class="w-48 h-full flex flex-col pt-20 border-r border-[#2A2A2A]">
        
        <div 
            @click="activeTab = 'personal'" 
            class="text-center border-t border-[#2A2A2A] py-4 px-10 cursor-pointer transition-colors duration-150"
            :class="{ 
                'bg-[#2A2A2A] text-white': activeTab === 'personal', 
                'text-[#B6BABD] hover:text-white': activeTab !== 'personal'
            }">
            <h3>Personal</h3>
        </div>
        
        <div 
            @click="activeTab = 'security'" 
            class="text-center border-t border-[#2A2A2A] py-4 px-10 cursor-pointer transition-colors duration-150"
            :class="{ 
                'bg-[#2A2A2A] text-white': activeTab === 'security', 
                'text-[#B6BABD] hover:text-white': activeTab !== 'security'
            }">
            <h3>Security</h3>
        </div>

        <?php if ($role == 'Coach'): ?>
            <div 
                @click="activeTab = 'team'" 
                class="text-center border-t border-[#2A2A2A] py-4 px-10 cursor-pointer transition-colors duration-150"
                :class="{ 
                    'bg-[#2A2A2A] text-white': activeTab === 'team', 
                    'text-[#B6BABD] hover:text-white': activeTab !== 'team'
                }">
                <h3>Team</h3>
            </div>
        <?php endif; ?>
    </div>

    <div class="w-full h-full p-10">
        
        <div x-show="activeTab === 'personal'">
            <?php $this->load->view('account/personal_data'); ?>
        </div>
        
        <div x-show="activeTab === 'security'">
            <?php $this->load->view('account/security'); ?>
        </div>

        <?php if ($role == 'Coach'): ?>
            <div x-show="activeTab === 'team'">
                <?php $this->load->view('account/team'); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($role == 'Player'): ?>
            <?php endif; ?>
        
    </div>
</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>