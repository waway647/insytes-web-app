<!-- views/account/account_main -->
<h1 class="text-white font-bold text-2xl mb-10">Account</h1>

<div x-data="{ activeTab: 'personal' }" class="w-full h-full flex">
    
    <div class="w-48 h-full flex flex-col pt-20 border-r border-[#2A2A2A]">
        
        <div 
            @click="activeTab = 'personal'" 
            class="text-center border-t border-[#2A2A2A] py-4 px-10 cursor-pointer transition-colors duration-150"
            
            :class="{ 
                // Set active classes if activeTab is 'personal' (Requirement #2)
                'bg-[#2A2A2A] text-white': activeTab === 'personal', 
                // Set inactive classes if activeTab is NOT 'personal'
                'text-[#B6BABD]': activeTab !== 'personal'
            }">
            
            <h3 :class="{'group-hover:text-white': activeTab !== 'personal'}">Personal</h3>
        </div>
        
        <div 
            @click="activeTab = 'security'" 
            class="text-center border-t border-[#2A2A2A] py-4 px-10 cursor-pointer transition-colors duration-150"
            
            :class="{ 
                // Set active classes if activeTab is 'security' (Requirement #2)
                'bg-[#2A2A2A] text-white': activeTab === 'security', 
                // Set inactive classes if activeTab is NOT 'security'
                'text-[#B6BABD]': activeTab !== 'security'
            }">
            
            <h3 :class="{'group-hover:text-white': activeTab !== 'security'}">Security</h3>
        </div>
    </div>

    <div class="w-full h-full p-10">
        
        <div x-show="activeTab === 'personal'">
            <?php $this->load->view('account/personal_data'); ?>
        </div>
        
        <div x-show="activeTab === 'security'">
            <?php $this->load->view('account/security'); ?>
        </div>
    </div>
</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>