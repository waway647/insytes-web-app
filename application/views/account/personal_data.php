<div class="flex flex-col gap-6 pb-6">
	<!-- read-only Email address -->
	<div id="email-read">
		<label for="email" class="block text-xs font-medium text-[#B6BABD]">Email address</label>
		<div class="mt-2 flex gap-4">
			<p class="text-white font-medium">mapula.pauljoshua@gmail.com</p>
			<img class="hover:cursor-pointer" src="<?php echo base_url(); ?>assets/images/icons/edit.svg?<?php echo time(); ?>" alt="">
		</div>
	</div>
	<!-- email edit mode -->
	<div id="email-edit" class="hidden">
		<label for="email" class="block text-xs font-medium text-[#B6BABD]">Email address</label>
		<div class="mt-2 flex flex-col gap-3">
			<input id="email" type="email" name="email" required autocomplete="email" class="block w-80 rounded-md bg-white/5 px-3 py-1.5 text-base text-white outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
			<div class="w-80 flex justify-end gap-3">
				<div class="px-4 py-1 flex items-center text-sm font-medium border-1 border-[#2A2A2A] text-[#B6BABD] rounded-md">Cancel</div>
				<div class="px-7 py-1 flex items-center text-sm font-medium bg-[#6366F1] text-white rounded-md">Save</div>
			</div>
		</div>
	</div>
		
	<!-- read-only First name -->
	<div id="firstname-read">
		<label for="first_name" class="block text-xs font-medium text-[#B6BABD]">First name</label>
		<div class="mt-2 flex gap-4">
			<p class="text-white font-medium">Paul Joshua</p>
			<img class="hover:cursor-pointer" src="<?php echo base_url(); ?>assets/images/icons/edit.svg?<?php echo time(); ?>" alt="">
		</div>
	</div>
	<!-- first name edit mode -->
	<div id="firstname-edit" class="hidden">
		<label for="first_name" class="block text-xs font-medium text-[#B6BABD]">First name</label>
		<div class="mt-2 flex flex-col gap-3">
			<input id="first_name" type="text" name="first_name" required autocomplete="first_name" class="block w-80 rounded-md bg-white/5 px-3 py-1.5 text-base text-white outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
			<div class="w-80 flex justify-end gap-3">
				<div class="px-4 py-1 flex items-center text-sm font-medium border-1 border-[#2A2A2A] text-[#B6BABD] rounded-md">Cancel</div>
				<div class="px-7 py-1 flex items-center text-sm font-medium bg-[#6366F1] text-white rounded-md">Save</div>
			</div>
		</div>
	</div>

	<!-- read-only Last name -->
	<div id="lastname-read">
		<label for="last_name" class="block text-xs font-medium text-[#B6BABD]">Last name</label>
		<div class="mt-2 flex gap-4">
			<p class="text-white font-medium">Mapula</p>
			<img class="hover:cursor-pointer" src="<?php echo base_url(); ?>assets/images/icons/edit.svg?<?php echo time(); ?>" alt="">
		</div>
	</div>
	<!-- last name edit mode -->
	<div id="lastname-edit" class="hidden">
		<label for="last_name" class="block text-xs font-medium text-[#B6BABD]">Last name</label>
		<div class="mt-2 flex flex-col gap-3">
			<input id="last_name" type="text" name="last_name" required autocomplete="last_name" class="block w-80 rounded-md bg-white/5 px-3 py-1.5 text-base text-white outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
			<div class="w-80 flex justify-end gap-3">
				<div class="px-4 py-1 flex items-center text-sm font-medium border-1 border-[#2A2A2A] text-[#B6BABD] rounded-md">Cancel</div>
				<div class="px-7 py-1 flex items-center text-sm font-medium bg-[#6366F1] text-white rounded-md">Save</div>
			</div>
		</div>
	</div>
</div>

<div class="flex flex-col gap-6 border-t border-[#2A2A2A] pt-10 pb-6">
	<div class="flex items-end gap-2">
		<label for="email" class="block text-xs font-medium text-[#B6BABD]">Affiliated Team:</label>
		<p class="text-white font-medium">San Beda</p>
	</div>

	<div class="flex items-end gap-2">
		<label for="email" class="block text-xs font-medium text-[#B6BABD]">Role:</label>
		<p class="text-white font-medium">Coach</p>
	</div>
</div>

<div class="flex flex-col gap-6 border-t border-[#2A2A2A] pt-10 pb-6">
	<button type="button" class="w-30 text-[#8E2C2C] p-1 border-1 rounded-lg hover:cursor-pointer">Delete</button>
</div>

