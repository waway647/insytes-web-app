<div class="flex flex-col w-full h-full">
	<div class="flex gap-10 mb-5">
		<h1 class="text-white font-bold text-2xl">Match Reports</h1>
	</div>

	<div id="match-library-main-content" class="">
		<div class="flex flex-col gap-5 py-5">
			<!-- Search -->
			<div class="flex w-full gap-10">
				<input type="text" placeholder="Search"
				class="w-full border-1 border-[#2A2A2A] text-white text-sm px-4 py-2 rounded-lg focus:outline-none focus:ring-1 focus:ring-white" />

				<button id="match-filter-btn" class="flex gap-2 w-26 justify-between items-center h-relative bg-[#1d1d1d] cursor-pointer px-4 text-white font-medium text-sm rounded-lg hover:bg-[#1a1a1a] transition border-1 border-[#2A2A2A]">
					<img src="<?php echo base_url('assets/images/icons/filter.svg'); ?>">
					Filter
				</button>
			</div>

			<div class="flex">
				<h1 id="season-header" class="text-[#B6BABD] font-bold text-xl">2025/2026 Season</h1>
			</div>
		</div>

		<!-- month cards container -->
		<div id="month-cards-container" class="flex flex-col py-5 gap-10 w-full h-full">
			<!-- append all month cards here-->
			<div id="month-card" class="flex flex-col w-full p-6 gap-5 bg-[#1D1D1D] rounded-lg">
				<div class="flex w-full justify-between items-center">
					<h2 id="month-name" class="text-white font-bold text-xl">
						July
						<span id="year" class="text-[#B6BABD]">2026</span>
					</h2>
				</div>
				<div id="match-cards-container" class="grid grid-cols-4 w-full gap-6">
					<!-- append all match cards here -->
					<div id="match-card" class="flex flex-col w-full bg-[#2A2A2A] rounded-lg cursor-pointer"> 
						<div class="flex w-full h-64 justify-center items-center bg-[#363636] rounded-lg">
                            <h1 id="my-team-result" class="text-4xl font-semibold">Win</h1>
						</div>
						<!-- match info -->
						<div class="flex flex-col w-full h-fit p-5 gap-3">
							<div class="flex w-full h-fit justify-between items-center">
								<p id="match-name" class="text-white font-bold text-lg">vs. Ateneo</p>
								<span id="match-date" class="text-[#B6BABD] font-medium">Jul 28</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
    window.APP_BASE_URL = '<?php echo base_url(); ?>index.php/';
</script>
<script src="<?php echo base_url('assets/js/matchCardResultsHandler.js') . '?v=' . time(); ?>"></script>