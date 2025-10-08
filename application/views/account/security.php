<div class="flex flex-col gap-6">
	<div class="flex flex-col gap-2">
		<h3 class="text-[#B6BABD] text-xl font-bold">Login</h3>
		<p class="text-[#B6BABD]">Manage your passwords</p>
	</div>

	<div>
		<a href="<?php echo site_url('auth/passwordresetcontroller/show_password_reset_step1'); ?>" @click="redirectPasswordChange()" type="button" class="group w-full flex justify-between text-[#B6BABD] px-4 py-3 border-1 rounded-lg hover:text-white cursor-pointer">
        	Change password
			<!-- arrow right icon -->
			<svg class="h-5 w-5 transition-transform duration-300 text-[#B6BABD] group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
			</svg>
        </a>
	</div>
</div>