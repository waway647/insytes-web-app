
  <div class="flex flex-col lg:flex-row w-full h-full gap-6">
    <!-- LEFT SIDE -->
    <div class="flex-1 rounded-2xl flex flex-col">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <h1 class="text-2xl text-white font-semibold">People</h1>
        <button id="openModal" class="bg-indigo-500 hover:bg-indigo-400 transition px-4 py-2 rounded-lg flex items-center gap-2 text-sm font-medium">
			<img src="<?php echo base_url('assets/images/icons/invite.png'); ?>" alt="Plus Icon" class="h-4 w-4">
          <span class="text-white">Invite people</span>
        </button>
      </div>

	 	 <!-- MODAL BACKDROP -->
			<div id="inviteModal" class="fixed inset-0 bg-black bg-opacity-40 backdrop-blur-sm flex items-center justify-center z-50" hidden>
			<!-- MODAL CONTENT -->
			<div class="bg-[#1D1D1D] rounded-2xl w-[90%] max-w-md shadow-lg p-6 relative">
				<!-- Close Button -->
				<button id="closeModal" class="absolute top-3 right-3 text-gray-400 hover:text-white">
				✕
				</button>

				<!-- Header -->
				<div class="flex items-center gap-3 mb-4">
					<div class="p-2 bg-indigo-500 rounded-full">	
						<img src="<?php echo base_url('assets/images/icons/invite.png'); ?>" alt="Invite Icon" class="w-6 h-6">
					</div>
				<h2 class="text-white font-semibold text-lg">Invite people to San Beda</h2>
				</div>

				<!-- Body -->
				<p class="text-gray-400 text-sm mb-3">Send a server invite link to your team</p>

				<div class="flex items-center bg-[#111111] rounded-lg overflow-hidden border border-[#2A2A2A]">
				<input type="text" readonly value="https://insytes.com/aosf3fs4"
					class="flex-1 bg-transparent text-gray-300 text-sm px-3 py-2 outline-none">
				<button onclick="copyInviteLink()" class="bg-indigo-500 hover:bg-indigo-400 text-white text-sm px-4 py-2 font-medium">
					Copy
				</button>
				</div>

				<p class="text-gray-500 text-xs mt-3">Your invite link will expire in 1 day.</p>
			</div>
		</div>

      <!-- Search -->
      <div class="my-5">
        <input type="text" placeholder="Search"
          class="w-full bg-[#1D1D1D] text-white text-sm px-4 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" />
      </div>

      <!-- People list -->
      <p class="text-gray-400 text-sm mb-5">All people in this team (5)</p>

      <div class="custom-scroll flex-1 overflow-y-auto bg-transparent">
        <div class="divide-y divide-[#2A2A2A]">

          <!-- Item -->
          <div class="flex items-center justify-between bg-transparent hover:bg-[#1E1E1E]  px-4 py-3 transition">
            <div>
              <p class="font-medium text-white">Mapula, Paul Joshua</p>
              <p class="text-sm text-gray-400">Player</p>
            </div>
            <button class="p-1 hover:bg-[#2A2A2A] rounded-full">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
              </svg>
            </button>
          </div>
		  

          <!-- Active Row Example -->
          <div class="flex items-center justify-between hover:bg-[#1E1E1E] px-4 py-3">
            <div>
              <p class="font-medium text-white">Mapula, Paul Joshua</p>
              <p class="text-sm text-gray-400">Coach</p>
            </div>
            <button class="p-1 hover:bg-[#2A2A2A] rounded-full">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
              </svg>
            </button>
          </div>

          <!-- More rows -->
          <div class="flex items-center justify-between bg-transparent hover:bg-[#1E1E1E]  px-4 py-3 transition">
            <div>
              <p class="font-medium text-white">Mapula, Paul Joshua</p>
              <p class="text-sm text-gray-400">Player</p>
            </div>
            <button class="p-1 hover:bg-[#2A2A2A] rounded-full">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
              </svg>
            </button>
          </div>

		  <!-- Active Row Example -->
          <div class="flex items-center justify-between hover:bg-[#1E1E1E] px-4 py-3">
            <div>
              <p class="font-medium text-white">Mapula, Paul Joshua</p>
              <p class="text-sm text-gray-400">Coach</p>
            </div>
            <button class="p-1 hover:bg-[#2A2A2A] rounded-full">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
              </svg>
            </button>
          </div>

          <!-- More rows -->
          <div class="flex items-center justify-between bg-transparent hover:bg-[#1E1E1E]  px-4 py-3 transition">
            <div>
              <p class="font-medium text-white">Mapula, Paul Joshua</p>
              <p class="text-sm text-gray-400">Player</p>
            </div>
            <button class="p-1 hover:bg-[#2A2A2A] rounded-full">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
              </svg>
            </button>
          </div>

		  <!-- Active Row Example -->
          <div class="flex items-center justify-between hover:bg-[#1E1E1E] px-4 py-3">
            <div>
              <p class="font-medium text-white">Mapula, Paul Joshua</p>
              <p class="text-sm text-gray-400">Coach</p>
            </div>
            <button class="p-1 hover:bg-[#2A2A2A] rounded-full">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
              </svg>
            </button>
          </div>

          <!-- More rows -->
          <div class="flex items-center justify-between bg-transparent hover:bg-[#1E1E1E]  px-4 py-3 transition">
            <div>
              <p class="font-medium text-white">Mapula, Paul Joshua</p>
              <p class="text-sm text-gray-400">Player</p>
            </div>
            <button class="p-1 hover:bg-[#2A2A2A] rounded-full">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
              </svg>
            </button>
          </div>

		  <!-- Active Row Example -->
          <div class="flex items-center justify-between hover:bg-[#1E1E1E] px-4 py-3">
            <div>
              <p class="font-medium text-white">Mapula, Paul Joshua</p>
              <p class="text-sm text-gray-400">Coach</p>
            </div>
            <button class="p-1 hover:bg-[#2A2A2A] rounded-full">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
              </svg>
            </button>
          </div>

          <!-- More rows -->
          <div class="flex items-center justify-between bg-transparent hover:bg-[#1E1E1E]  px-4 py-3 transition">
            <div>
              <p class="font-medium text-white">Mapula, Paul Joshua</p>
              <p class="text-sm text-gray-400">Player</p>
            </div>
            <button class="p-1 hover:bg-[#2A2A2A] rounded-full">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
              </svg>
            </button>
          </div>

        </div>
      </div>
    </div>

    <!-- RIGHT SIDE -->
    <div class="w-full lg:w-1/3 bg-[#1D1D1D] rounded-2xl p-6">
      <h2 class="text-xl text-white font-semibold mb-2">Active Now</h2>
      <div class="text-gray-400 text-sm bg-[#181818] rounded-xl p-8 mt-4 text-center">
        <p class="text-white font-medium mb-2">It’s quiet for now...</p>
        <p>When one of your team starts an activity—like reviewing past matches and clips—we’ll show it here!</p>
      </div>
    </div>
  </div>
  <script src="<?php echo base_url('assets/js/modalHandler.js'); ?>"></script>

