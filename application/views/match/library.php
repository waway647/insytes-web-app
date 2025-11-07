<div class="flex flex-col w-full h-full">
	<div class="flex gap-10 mb-5">
		<h1 class="text-white font-bold text-2xl">Match Library</h1>
		<button id="new-match-btn" class="h-fit bg-indigo-500 cursor-pointer hover:bg-indigo-400 transition px-4 py-2 rounded-lg flex items-center gap-2 text-sm font-medium">
			<img src="<?php echo base_url('assets/images/icons/plus.svg'); ?>">
			<span class="text-white">New</span>
		</button>
	</div>

	<!-- new match panel -->
	<div id="new-match-panel" class="flex flex-col w-full h-fit p-5 rounded-lg bg-[#1D1D1D] hidden">
		<img id="close-new-match-panel" src="<?php echo base_url('assets/images/icons/close.svg'); ?>" class="w-3 h-auto cursor-pointer" alt="">
		<div class="flex flex-col items-center px-80 py-4 gap-4 border-b-1 border-b-[#2A2A2A]">
			<h2 class="text-[#B6BABD] text-xl font-bold">New Match</h2>
			<div class="flex w-full justify-between gap-10 py-4">
				<div class="flex flex-col w-full gap-1 items-center">
					<p class="text-xs text-[#B6BABD]">Season</p>
					<div id="season-btn" class="w-full h-9 px-3 py-1.5 rounded-md border-1 border-[#2A2A2A] cursor-pointer">
						<span id="season" class="text-white font-medium"></span>
					</div>
				</div>
				<div class="flex flex-col w-full gap-1 items-center">
					<p class="text-xs text-[#B6BABD]">Competition</p>
					<div id="competition-btn" class="w-full h-9 px-3 py-1.5 rounded-md border-1 border-[#2A2A2A] cursor-pointer">
						<span id="competition" class="text-white font-medium"></span>
					</div>
				</div>
				<div class="flex flex-col w-full gap-1 items-center">
					<p class="text-xs text-[#B6BABD]">Date of Match</p>
					<div id="match-date-btn" class="w-full h-9 px-3 py-1.5 rounded-md border-1 border-[#2A2A2A] cursor-pointer">
						<span id="match-date" class="text-white font-medium"></span>
					</div>
				</div>
				<div class="flex flex-col w-full gap-1 items-center">
					<p class="text-xs text-[#B6BABD]">Venue</p>
					<div id="venue-btn" class="w-full h-9 px-3 py-1.5 rounded-md border-1 border-[#2A2A2A] cursor-pointer">
						<span id="venue" class="text-white font-medium"></span>
					</div>
				</div>
			</div>
		</div>
		<div class="flex flex-col w-full h-full py-4 items-center border-b-1 border-b-[#2A2A2A]">
			<h3 class="text-[#B6BABD] text-md font-bold py-4">Team Roster</h3>
			<div class="flex w-full mt-4">
				<!-- your team -->
				<div class="flex flex-col w-full h-full py-8 px-14 border-r-1 border-r-[#2A2A2A]">
					<div class="flex w-full justify-end gap-10">
						<div class="flex flex-col gap-1 items-center">
							<p class="text-xs text-[#B6BABD]">My Team</p>
							<div id="my-team-btn" class="w-50 h-9 px-3 py-1.5 rounded-md border-1 border-[#2A2A2A] cursor-pointer">
								<span id="my-team" class="text-white font-medium"></span>
							</div>
						</div>
						<div class="flex flex-col gap-1 items-center">
							<p class="text-xs text-[#B6BABD]">Result</p>
							<div id="my-team-result-btn" class="w-20 h-9 px-3 py-1.5 rounded-md border-1 border-[#2A2A2A] cursor-pointer">
								<span id="my-team-result" class="text-white font-medium"></span>
							</div>
						</div>
						<div class="flex flex-col gap-1 items-center">
							<p class="text-xs text-[#B6BABD]">Goals</p>
							<div id="my-team-goals-btn" class="w-20 h-9 px-3 py-1.5 rounded-md border-1 border-[#2A2A2A] cursor-pointer">
								<span id="my-team-goals" class="text-white font-medium"></span>
							</div>
						</div>
					</div>
					<div class="w-full px-4 py-6">
						<table class="w-full table-fixed border-collapse">
							<thead class="text-[#B6BABD] text-xs border-b border-neutral-700">
								<tr>
									<th class="py-2 px-6 font-normal text-center w-16">#</th>
									
									<th class="py-2 px-6 font-normal text-left">Player Name</th>
									
									<th class="py-2 px-6 font-normal text-center w-28">Jersey No.</th>
									<th class="py-2 px-6 font-normal text-center w-28">XI</th>
								</tr>
							</thead>
							<tbody class="text-white text-sm">
								<tr id="fill-player-btn" class="border-b border-neutral-800 cursor-pointer hover:bg-neutral-800 transition-colors">
									<td id="index-of-player" class="py-2 px-6 text-center text-[#B6BABD]">1</td>
									<td id="player-name" class="py-2 px-6">Mapula, Paul Jsoshua</td>
									<td id="jersey-num" class="py-2 px-6 text-center">20</td>
									<td class="py-2 px-6 flex justify-center items-center">
										<input type="checkbox" class="w-5 h-5 rounded bg-neutral-700 border-neutral-600 text-indigo-500 cursor-pointer focus:ring-indigo-600 focus:ring-offset-neutral-800">
									</td>
								</tr>
								<tr id="fill-player-btn" class="border-b border-neutral-800 cursor-pointer hover:bg-neutral-800 transition-colors">
									<td id="index-of-player" class="py-2 px-6 text-center text-[#B6BABD]">2</td>
									<td id="player-name" class="py-2 px-6"></td>
									<td id="jersey-num" class="py-2 px-6 text-center"></td>
									<td class="py-2 px-6 flex justify-center items-center">
										<!-- <input type="checkbox" class="w-5 h-5 rounded bg-neutral-700 border-neutral-600 text-indigo-500 cursor-pointer focus:ring-indigo-600 focus:ring-offset-neutral-800"> -->
									</td>
								</tr>
								<tr id="fill-player-btn" class="border-b border-neutral-800 cursor-pointer hover:bg-neutral-800 transition-colors">
									<td id="index-of-player" class="py-2 px-6 text-center text-[#B6BABD]">3</td>
									<td id="player-name" class="py-2 px-6"></td>
									<td id="jersey-num" class="py-2 px-6 text-center"></td>
									<td class="py-2 px-6 flex justify-center items-center">
										<!-- <input type="checkbox" class="w-5 h-5 rounded bg-neutral-700 border-neutral-600 text-indigo-500 cursor-pointer focus:ring-indigo-600 focus:ring-offset-neutral-800"> -->
									</td>
								</tr>
								<tr id="fill-player-btn" class="border-b border-neutral-800 cursor-pointer hover:bg-neutral-800 transition-colors">
									<td id="index-of-player" class="py-2 px-6 text-center text-[#B6BABD]">4</td>
									<td id="player-name" class="py-2 px-6"></td>
									<td id="jersey-num" class="py-2 px-6 text-center"></td>
									<td class="py-2 px-6 flex justify-center items-center">
										<!-- <input type="checkbox" class="w-5 h-5 rounded bg-neutral-700 border-neutral-600 text-indigo-500 cursor-pointer focus:ring-indigo-600 focus:ring-offset-neutral-800"> -->
									</td>
								</tr>
								<tr id="fill-player-btn" class="border-b border-neutral-800 cursor-pointer hover:bg-neutral-800 transition-colors">
									<td id="index-of-player" class="py-2 px-6 text-center text-[#B6BABD]">5</td>
									<td id="player-name" class="py-2 px-6"></td>
									<td id="jersey-num" class="py-2 px-6 text-center"></td>
									<td class="py-2 px-6 flex justify-center items-center">
										<!-- <input type="checkbox" class="w-5 h-5 rounded bg-neutral-700 border-neutral-600 text-indigo-500 cursor-pointer focus:ring-indigo-600 focus:ring-offset-neutral-800"> -->
									</td>
								</tr>
								<tr id="fill-player-btn" class="border-b border-neutral-800 cursor-pointer hover:bg-neutral-800 transition-colors">
									<td id="index-of-player" class="py-2 px-6 text-center text-[#B6BABD]">6</td>
									<td id="player-name" class="py-2 px-6"></td>
									<td id="jersey-num" class="py-2 px-6 text-center"></td>
									<td class="py-2 px-6 flex justify-center items-center">
										<!-- <input type="checkbox" class="w-5 h-5 rounded bg-neutral-700 border-neutral-600 text-indigo-500 cursor-pointer focus:ring-indigo-600 focus:ring-offset-neutral-800"> -->
									</td>
								</tr>
								<tr id="fill-player-btn" class="border-b border-neutral-800 cursor-pointer hover:bg-neutral-800 transition-colors">
									<td id="index-of-player" class="py-2 px-6 text-center text-[#B6BABD]">7</td>
									<td id="player-name" class="py-2 px-6"></td>
									<td id="jersey-num" class="py-2 px-6 text-center"></td>
									<td class="py-2 px-6 flex justify-center items-center">
										<!-- <input type="checkbox" class="w-5 h-5 rounded bg-neutral-700 border-neutral-600 text-indigo-500 cursor-pointer focus:ring-indigo-600 focus:ring-offset-neutral-800"> -->
									</td>
								</tr>
								<tr id="fill-player-btn" class="border-b border-neutral-800 cursor-pointer hover:bg-neutral-800 transition-colors">
									<td id="index-of-player" class="py-2 px-6 text-center text-[#B6BABD]">8</td>
									<td id="player-name" class="py-2 px-6"></td>
									<td id="jersey-num" class="py-2 px-6 text-center"></td>
									<td class="py-2 px-6 flex justify-center items-center">
										<!-- <input type="checkbox" class="w-5 h-5 rounded bg-neutral-700 border-neutral-600 text-indigo-500 cursor-pointer focus:ring-indigo-600 focus:ring-offset-neutral-800"> -->
									</td>
								</tr>
								<tr id="fill-player-btn" class="border-b border-neutral-800 cursor-pointer hover:bg-neutral-800 transition-colors">
									<td id="index-of-player" class="py-2 px-6 text-center text-[#B6BABD]">9</td>
									<td id="player-name" class="py-2 px-6"></td>
									<td id="jersey-num" class="py-2 px-6 text-center"></td>
									<td class="py-2 px-6 flex justify-center items-center">
										<!-- <input type="checkbox" class="w-5 h-5 rounded bg-neutral-700 border-neutral-600 text-indigo-500 cursor-pointer focus:ring-indigo-600 focus:ring-offset-neutral-800"> -->
									</td>
								</tr>
								<tr id="fill-player-btn" class="border-b border-neutral-800 cursor-pointer hover:bg-neutral-800 transition-colors">
									<td id="index-of-player" class="py-2 px-6 text-center text-[#B6BABD]">10</td>
									<td id="player-name" class="py-2 px-6"></td>
									<td id="jersey-num" class="py-2 px-6 text-center"></td>
									<td class="py-2 px-6 flex justify-center items-center">
										<!-- <input type="checkbox" class="w-5 h-5 rounded bg-neutral-700 border-neutral-600 text-indigo-500 cursor-pointer focus:ring-indigo-600 focus:ring-offset-neutral-800"> -->
									</td>
								</tr>
								<tr id="fill-player-btn" class="border-b border-neutral-800 cursor-pointer hover:bg-neutral-800 transition-colors">
									<td id="index-of-player" class="py-2 px-6 text-center text-[#B6BABD]">11</td>
									<td id="player-name" class="py-2 px-6"></td>
									<td id="jersey-num" class="py-2 px-6 text-center"></td>
									<td class="py-2 px-6 flex justify-center items-center">
										<!-- <input type="checkbox" class="w-5 h-5 rounded bg-neutral-700 border-neutral-600 text-indigo-500 cursor-pointer focus:ring-indigo-600 focus:ring-offset-neutral-800"> -->
									</td>
								</tr>
							</tbody>
						</table>
						<div class="border-b border-neutral-800 hover:bg-neutral-800 transition-colors">
							<button id="add-player-btn" class="flex justify-center w-full cursor-pointer py-2.5">
								<img src="<?php echo base_url('assets/images/icons/plus.svg'); ?>" class="w-3">
							</button>
						</div>
					</div>
				</div>

				<!-- opponent team -->
				<div class="flex flex-col w-full h-full py-8 px-14 border-r-1 border-r-[#2A2A2A]">
					<div class="flex w-full justify-baseline gap-10">
						<div class="flex flex-col gap-1 items-center">
							<p class="text-xs text-[#B6BABD]">Goals</p>
							<div id="opponent-goals-btn" class="w-20 h-9 px-3 py-1.5 rounded-md border-1 border-[#2A2A2A] cursor-pointer">
								<span id="opponent-goals" class="text-white font-medium"></span>
							</div>
						</div>
						<div class="flex flex-col gap-1 items-center">
							<p class="text-xs text-[#B6BABD]">Result</p>
							<div id="opponent-result-btn" class="w-20 h-9 px-3 py-1.5 rounded-md border-1 border-[#2A2A2A] cursor-pointer">
								<span id="opponent-result" class="text-white font-medium"></span>
							</div>
						</div>
						<div class="flex flex-col gap-1 items-center">
							<p class="text-xs text-[#B6BABD]">Your Opponent's Team</p>
							<div id="opponent-team-btn" class="w-50 h-9 px-3 py-1.5 rounded-md border-1 border-[#2A2A2A] cursor-pointer">
								<span id="opponent-team" class="text-white font-medium"></span>
							</div>
						</div>
					</div>
					<div class="w-full px-4 py-6">
						<table class="w-full table-fixed border-collapse">
							<thead class="text-[#B6BABD] text-xs border-b border-neutral-700">
								<tr>
									<th class="py-2 px-6 font-normal text-center w-16">#</th>
									
									<th class="py-2 px-6 font-normal text-left">Player Name</th>
									
									<th class="py-2 px-6 font-normal text-center w-28">Jersey No.</th>
									<th class="py-2 px-6 font-normal text-center w-28">XI</th>
								</tr>
							</thead>
							<tbody class="text-white text-sm">
								<tr id="fill-opponent-player-btn" class="border-b border-neutral-800 cursor-pointer hover:bg-neutral-800 transition-colors">
									<td id="opponent-index-of-player" class="py-2 px-6 text-center text-[#B6BABD]">1</td>
									<td id="opponent-player-name" class="py-2 px-6">Mapula, Paul Jsoshua</td>
									<td id="opponent-jersey-num" class="py-2 px-6 text-center">20</td>
									<td class="py-2 px-6 flex justify-center items-center">
										<input type="checkbox" class="w-5 h-5 rounded bg-neutral-700 border-neutral-600 text-indigo-500 cursor-pointer focus:ring-indigo-600 focus:ring-offset-neutral-800">
									</td>
								</tr>
								<tr id="fill-opponent-player-btn" class="border-b border-neutral-800 cursor-pointer hover:bg-neutral-800 transition-colors">
									<td id="opponent-index-of-player" class="py-2 px-6 text-center text-[#B6BABD]">2</td>
									<td id="opponent-player-name" class="py-2 px-6"></td>
									<td id="opponent-jersey-num" class="py-2 px-6 text-center"></td>
									<td class="py-2 px-6 flex justify-center items-center">
										<!-- <input type="checkbox" class="w-5 h-5 rounded bg-neutral-700 border-neutral-600 text-indigo-500 cursor-pointer focus:ring-indigo-600 focus:ring-offset-neutral-800"> -->
									</td>
								</tr>
								<tr id="fill-opponent-player-btn" class="border-b border-neutral-800 cursor-pointer hover:bg-neutral-800 transition-colors">
									<td id="opponent-index-of-player" class="py-2 px-6 text-center text-[#B6BABD]">3</td>
									<td id="opponent-player-name" class="py-2 px-6"></td>
									<td id="opponent-jersey-num" class="py-2 px-6 text-center"></td>
									<td class="py-2 px-6 flex justify-center items-center">
										<!-- <input type="checkbox" class="w-5 h-5 rounded bg-neutral-700 border-neutral-600 text-indigo-500 cursor-pointer focus:ring-indigo-600 focus:ring-offset-neutral-800"> -->
									</td>
								</tr>
								<tr id="fill-opponent-player-btn" class="border-b border-neutral-800 cursor-pointer hover:bg-neutral-800 transition-colors">
									<td id="opponent-index-of-player" class="py-2 px-6 text-center text-[#B6BABD]">4</td>
									<td id="opponent-player-name" class="py-2 px-6"></td>
									<td id="opponent-jersey-num" class="py-2 px-6 text-center"></td>
									<td class="py-2 px-6 flex justify-center items-center">
										<!-- <input type="checkbox" class="w-5 h-5 rounded bg-neutral-700 border-neutral-600 text-indigo-500 cursor-pointer focus:ring-indigo-600 focus:ring-offset-neutral-800"> -->
									</td>
								</tr>
								<tr id="fill-opponent--player-btn" class="border-b border-neutral-800 cursor-pointer hover:bg-neutral-800 transition-colors">
									<td id="opponent-index-of-player" class="py-2 px-6 text-center text-[#B6BABD]">5</td>
									<td id="opponent-player-name" class="py-2 px-6"></td>
									<td id="opponent-jersey-num" class="py-2 px-6 text-center"></td>
									<td class="py-2 px-6 flex justify-center items-center">
										<!-- <input type="checkbox" class="w-5 h-5 rounded bg-neutral-700 border-neutral-600 text-indigo-500 cursor-pointer focus:ring-indigo-600 focus:ring-offset-neutral-800"> -->
									</td>
								</tr>
								<tr id="fill-opponent--player-btn" class="border-b border-neutral-800 cursor-pointer hover:bg-neutral-800 transition-colors">
									<td id="opponent-index-of-player" class="py-2 px-6 text-center text-[#B6BABD]">6</td>
									<td id="opponent-player-name" class="py-2 px-6"></td>
									<td id="opponent-jersey-num" class="py-2 px-6 text-center"></td>
									<td class="py-2 px-6 flex justify-center items-center">
										<!-- <input type="checkbox" class="w-5 h-5 rounded bg-neutral-700 border-neutral-600 text-indigo-500 cursor-pointer focus:ring-indigo-600 focus:ring-offset-neutral-800"> -->
									</td>
								</tr>
								<tr id="fill-opponent--player-btn" class="border-b border-neutral-800 cursor-pointer hover:bg-neutral-800 transition-colors">
									<td id="opponent-index-of-player" class="py-2 px-6 text-center text-[#B6BABD]">7</td>
									<td id="opponent-player-name" class="py-2 px-6"></td>
									<td id="opponent-jersey-num" class="py-2 px-6 text-center"></td>
									<td class="py-2 px-6 flex justify-center items-center">
										<!-- <input type="checkbox" class="w-5 h-5 rounded bg-neutral-700 border-neutral-600 text-indigo-500 cursor-pointer focus:ring-indigo-600 focus:ring-offset-neutral-800"> -->
									</td>
								</tr>
								<tr id="fill-opponent--player-btn" class="border-b border-neutral-800 cursor-pointer hover:bg-neutral-800 transition-colors">
									<td id="opponent-index-of-player" class="py-2 px-6 text-center text-[#B6BABD]">8</td>
									<td id="opponent-player-name" class="py-2 px-6"></td>
									<td id="opponent-jersey-num" class="py-2 px-6 text-center"></td>
									<td class="py-2 px-6 flex justify-center items-center">
										<!-- <input type="checkbox" class="w-5 h-5 rounded bg-neutral-700 border-neutral-600 text-indigo-500 cursor-pointer focus:ring-indigo-600 focus:ring-offset-neutral-800"> -->
									</td>
								</tr>
								<tr id="fill-opponent--player-btn" class="border-b border-neutral-800 cursor-pointer hover:bg-neutral-800 transition-colors">
									<td id="opponent-index-of-player" class="py-2 px-6 text-center text-[#B6BABD]">9</td>
									<td id="opponent-player-name" class="py-2 px-6"></td>
									<td id="opponent-jersey-num" class="py-2 px-6 text-center"></td>
									<td class="py-2 px-6 flex justify-center items-center">
										<!-- <input type="checkbox" class="w-5 h-5 rounded bg-neutral-700 border-neutral-600 text-indigo-500 cursor-pointer focus:ring-indigo-600 focus:ring-offset-neutral-800"> -->
									</td>
								</tr>
								<tr id="fill-opponent--player-btn" class="border-b border-neutral-800 cursor-pointer hover:bg-neutral-800 transition-colors">
									<td id="opponent-index-of-player" class="py-2 px-6 text-center text-[#B6BABD]">10</td>
									<td id="opponent-player-name" class="py-2 px-6"></td>
									<td id="opponent-jersey-num" class="py-2 px-6 text-center"></td>
									<td class="py-2 px-6 flex justify-center items-center">
										<!-- <input type="checkbox" class="w-5 h-5 rounded bg-neutral-700 border-neutral-600 text-indigo-500 cursor-pointer focus:ring-indigo-600 focus:ring-offset-neutral-800"> -->
									</td>
								</tr>
								<tr id="fill-opponent--player-btn" class="border-b border-neutral-800 cursor-pointer hover:bg-neutral-800 transition-colors">
									<td id="opponent-index-of-player" class="py-2 px-6 text-center text-[#B6BABD]">11</td>
									<td id="opponent-player-name" class="py-2 px-6"></td>
									<td id="opponent-jersey-num" class="py-2 px-6 text-center"></td>
									<td class="py-2 px-6 flex justify-center items-center">
										<!-- <input type="checkbox" class="w-5 h-5 rounded bg-neutral-700 border-neutral-600 text-indigo-500 cursor-pointer focus:ring-indigo-600 focus:ring-offset-neutral-800"> -->
									</td>
								</tr>
							</tbody>
						</table>
						<div class="border-b border-neutral-800 hover:bg-neutral-800 transition-colors">
							<button id="add-opponent-player-btn" class="flex justify-center w-full cursor-pointer py-2.5">
								<img src="<?php echo base_url('assets/images/icons/plus.svg'); ?>" class="w-3">
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="w-full px-4 py-6">
			<button id="create-match-btn" class="flex justify-center items-center w-full text-white bg-[#6366F1] rounded-lg cursor-pointer hover:bg-indigo-400 transition px-4 py-2">Save & Create Match Metadata</button>
		</div>
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
					<button id="view-full-replays-btn" class="px-4 py-2 border-1 border-[#2A2A2A] rounded-4xl text-[#B6BABD] text-sm cursor-pointer hover:border-[#414141] transition">
						View all <span id="month-name-in-btn">July</span> Full Replays
					</button>
				</div>
				<div id="match-cards-container" class="grid grid-cols-4 w-full gap-6">
					<!-- append all match cards here -->
					<div id="match-card" class="flex flex-col w-full bg-[#2A2A2A] rounded-lg"> 
						<div id="thumbnail" class="flex w-full h-64 bg-[#363636] rounded-lg">
							<div class="flex w-full h-fit p-2 justify-end">
								<button class="p-1 hover:bg-[#2A2A2A] rounded-full cursor-pointer">
									<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
										<path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
									</svg>
								</button>
							</div>
						</div>
						<!-- match info -->
						<div class="flex flex-col w-full h-fit p-5 gap-3">
							<div class="flex w-fit items-center gap-2 px-3 py-1 border-1 border-blue-500 rounded-2xl">
								<div id="status-color" class="w-2 h-2 rounded-2xl bg-blue-500"></div>
								<span id="status" class="text-[#B6BABD] text-xs">Ready</span>
							</div>
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

<script>window.APP_BASE_URL = '<?php echo base_url(); ?>index.php/';</script>

<script src="<?php echo base_url('assets/js/modalManager.js') . '?v=' . time(); ?>"></script>
<script src="<?php echo base_url('assets/js/dropdownHandler.js') . '?v=' . time(); ?>"></script>

<script src="<?php echo base_url('assets/js/matchCardHandler.js') . '?v=' . time(); ?>"></script>
<script src="<?php echo base_url('assets/js/newMatchPanelHandler.js') . '?v=' . time(); ?>"></script>