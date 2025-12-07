<div class="flex flex-col w-full h-full">
	<div class="flex gap-10 mb-5 items-center">
		<h1 class="text-white font-bold text-2xl">Match Reports</h1>
	</div>

	<div id="match-library-main-content" class="">
		<div class="flex flex-col gap-5 py-5">
			<!-- Search -->
			<div class="flex w-full gap-4">
				<input type="text" placeholder="Search"
				class="w-full border border-white/6 text-white text-sm px-4 py-2 rounded-lg bg-[#0f0f0f] focus:outline-none focus:ring-1 focus:ring-white/10" />

				<button id="match-filter-btn" class="flex gap-2 items-center px-4 py-2 bg-[#141414] text-white text-sm font-medium rounded-lg hover:bg-[#191919] transition-shadow border border-white/6 shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-white/8">
					<img src="<?php echo base_url('assets/images/icons/filter.svg'); ?>" class="w-4 h-4">
					<span>Filter</span>
				</button>
			</div>

			<div class="flex">
				<h1 id="season-header" class="text-[#B6BABD] font-semibold text-xl">2025/2026 Season</h1>
			</div>
		</div>

		<!-- month cards container -->
		<div id="month-cards-container" class="flex flex-col py-5 gap-10 w-full h-full">
			<!-- append all month cards here-->
			<div id="month-card" class="flex flex-col w-full p-6 gap-5 bg-gradient-to-b from-[#111111] to-[#111111] rounded-2xl border border-white/6 shadow-sm">
				<div class="flex w-full justify-between items-center">
					<h2 id="month-name" class="text-white font-semibold text-xl">
						July
						<span id="year" class="text-[#B6BABD] text-sm ml-2">2026</span>
					</h2>
				</div>

				<div id="match-cards-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 w-full">
					<!-- append all match cards here -->
					<div id="match-card" class="group flex flex-col w-full bg-gradient-to-b from-[#1b1b1b] to-[#141414] rounded-xl overflow-hidden border border-white/5 shadow-md hover:shadow-2xl transform transition-all duration-200 hover:-translate-y-1 cursor-pointer">
						<div class="flex w-full h-64 justify-center items-center bg-[#2b2b2b]">
                            <h1 id="my-team-result" class="text-4xl font-semibold text-white">Win</h1>
						</div>

						<!-- match info -->
						<div class="flex flex-col w-full h-fit p-5 gap-3">
							<div class="flex w-full h-fit justify-between items-center">
								<p id="match-name" class="text-white font-semibold text-lg">vs. Ateneo</p>
								<span id="match-date" class="text-[#B6BABD] font-medium">Jul 28</span>
							</div>

							<!-- subtle footer row for micro-info (keeps minimal) -->
							<div class="flex justify-end items-center text-xs text-[#9aa0a6] mt-2">
								<span class="opacity-80">See more details</span>
							</div>
						</div>
					</div>
					<!-- end match-card -->
				</div>
			</div>
		</div>
	</div>
</div>

<script>
    window.APP_BASE_URL = '<?php echo base_url(); ?>index.php/';
</script>
<script src="<?php echo base_url('assets/js/matchCardResultsHandler.js') . '?v=' . time(); ?>"></script>