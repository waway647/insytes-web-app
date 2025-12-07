<div class="flex flex-col w-full h-full">
	<div class="flex gap-10 mb-5">
		<h1 class="text-white font-bold text-2xl">Match Library</h1>
		<button id="new-match-btn" class="h-fit bg-indigo-500 cursor-pointer hover:bg-indigo-400 transition-shadow transition-colors px-4 py-2 rounded-lg flex items-center gap-2 text-sm font-medium shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-400/30">
			<img src="<?php echo base_url('assets/images/icons/plus.svg'); ?>">
			<span class="text-white">New</span>
		</button>
	</div>

	<!-- new match panel -->
	<div id="new-match-panel" class="flex flex-col w-full h-fit p-5 rounded-t-lg bg-gradient-to-b from-[#151515] to-[#0f0f0f] backdrop-blur-sm border border-white/6 hidden">
		<form
			id="new-match-panel-form"
			x-data="matchMetadataForm()"
			x-init="init()"
			@submit.prevent="submitForm"
			action="<?php echo site_url('match/metadataController/create_match'); ?>"
			method="POST"
			class=""
			novalidate
		>
			<!-- Hidden inputs mirrored by the Alpine component -->
			<input type="hidden" name="season_id" value="" />
			<input type="hidden" name="season_name" value="" />

			<input type="hidden" name="competition_id" value="" />
			<input type="hidden" name="competition_name" value="" />

			<input type="hidden" name="venue_id" value="" />
			<input type="hidden" name="venue_name" value="" />

			<input type="hidden" name="my_team_id" value="<?php echo $this->session->userdata('team_id'); ?>" />
			<input type="hidden" name="my_team_name" value="<?php echo $this->session->userdata('team_abbreviation'); ?>" />

			<input type="hidden" name="opponent_team_id" value="" />
			<input type="hidden" name="opponent_team_name" value="" />

			<img id="close-new-match-panel" src="<?php echo base_url('assets/images/icons/close.svg'); ?>" class="w-3 h-auto cursor-pointer" alt="">
			<div class="flex flex-col items-center px-80 py-4 gap-4 border-b border-[#2A2A2A]">
				<h2 class="text-[#B6BABD] text-xl font-bold">New Match</h2>
				<div class="flex w-full justify-between gap-10 py-4">
					<div class="flex flex-col w-full gap-1 items-center">
						<p class="text-xs text-[#B6BABD]">Season</p>
						<div id="season-btn" class="text-center w-full h-9 px-3 py-1.5 rounded-md bg-[#111111] border border-white/6 cursor-pointer hover:bg-[#1a1a1a] transition">
							<span id="season" class="text-white font-medium"></span>
						</div>
					</div>
					<div class="flex flex-col w-full gap-1 items-center">
						<p class="text-xs text-[#B6BABD]">Competition</p>
						<div id="competition-btn" class="text-center w-full h-9 px-3 py-1.5 rounded-md bg-[#111111] border border-white/6 cursor-pointer hover:bg-[#1a1a1a] transition">
							<span id="competition" class="text-white font-medium"></span>
						</div>
					</div>
					<div class="flex flex-col w-full gap-1 items-center">
						<p class="text-xs text-[#B6BABD]">Date of Match</p>
						<input 
							type="date" 
							name="match_date" 
							class="date-input text-center w-full h-9 px-3 py-1.5 rounded-md bg-[#111111] border border-white/6 text-white focus:outline-none focus:ring-1 focus:ring-indigo-500/30 appearance-none [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none [-moz-appearance:textfield]"
						>
					</div>
					<div class="flex flex-col w-full gap-1 items-center">
						<p class="text-xs text-[#B6BABD]">Venue</p>
						<div id="venue-btn" class="text-center w-full h-9 px-3 py-1.5 rounded-md bg-[#111111] border border-white/6 cursor-pointer hover:bg-[#1a1a1a] transition">
							<span id="venue" class="text-white font-medium"></span>
						</div>
					</div>
				</div>
			</div>
			<div class="flex flex-col w-full py-4 items-center border-b border-[#2A2A2A]">
				<h3 class="text-[#B6BABD] text-md font-bold py-4">Team Roster</h3>
				<div class="flex w-full mt-4">
					<!-- your team -->
					<div class="flex flex-col w-full h-full py-8 px-14 border-r border-white/6">
						<div class="flex w-full justify-end gap-10">
							<div class="flex flex-col gap-1 items-center">
								<p class="text-xs text-[#B6BABD]">My Team</p>
								<div id="my-team-btn" class="text-center w-50 h-9 px-3 py-1.5 rounded-md bg-[#111111] border border-white/6 cursor-pointer hover:bg-[#1a1a1a] transition">
									<span 
									id="my-team" 
									class="text-white font-medium"
									data-team-id="<?php echo $this->session->userdata('team_id'); ?>"
									>
									<?php echo $this->session->userdata('team_abbreviation'); ?>
									</span>
								</div>
							</div>

							<div class="flex flex-col gap-1 items-center">
								<p class="text-xs text-[#B6BABD]">Result</p>
								<select 
									name="my_team_result"
									class="text-center w-26 h-9 px-3 py-1.5 rounded-md border border-white/6 text-white bg-[#111111] hover:bg-[#1a1a1a] focus:outline-none cursor-pointer [-moz-appearance:none] [-webkit-appearance:none] [background-image:none]"
								>
									<option value="" disabled selected></option>
									<option value="Win">Win</option>
									<option value="Lose">Lose</option>
									<option value="Draw">Draw</option>
								</select>
							</div>
							<div class="flex flex-col gap-1 items-center">
								<p class="text-xs text-[#B6BABD]">Goals</p>
								<input 
									type="number" 
									name="my_team_goals" 
									class="text-center w-20 h-9 px-3 py-1.5 rounded-md bg-[#111111] border border-white/6 text-white focus:outline-none appearance-none [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none [-moz-appearance:textfield]"
								>
							</div>
						</div>
						<div class="w-full px-4 py-6">
							<table class="w-full table-fixed border-collapse">
								<thead class="text-[#B6BABD] text-xs border-b border-neutral-700">
									<tr>
										<th class="py-2 px-6 font-normal text-center w-16">#</th>
										<th class="py-2 px-6 font-normal text-left">Player Name</th>
										<th class="py-2 px-6 font-normal text-center w-28">Jersey No.</th>
										<th class="py-2 px-6 font-normal text-center w-28">Position</th>
										<th class="py-2 px-6 font-normal text-center w-28">XI</th>
									</tr>
								</thead>
								<tbody id="my-players-tbody" class="text-white text-sm bg-[#111111]">
								<!-- rows will be created dynamically -->
								</tbody>
							</table>
							<div class="bg-[#111111] border-b border-neutral-800 hover:bg-neutral-800 transition-colors">
								<button id="add-player-btn" type="button" class="flex justify-center w-full cursor-pointer py-2.5">
									<img src="<?php echo base_url('assets/images/icons/plus.svg'); ?>" class="w-3">
								</button>
							</div>
						</div>
					</div>

					<!-- opponent team -->
					<div class="flex flex-col w-full h-full py-8 px-14 border-r border-white/6">
						<div class="flex w-full justify-baseline gap-10">
							<div class="flex flex-col gap-1 items-center">
								<p class="text-xs text-[#B6BABD]">Goals</p>
								<input 
									type="number" 
									name="opponent_team_goals" 
									class="text-center w-20 h-9 px-3 py-1.5 rounded-md bg-[#111111] border border-white/6 text-white focus:outline-none appearance-none [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none [-moz-appearance:textfield]"
								>
							</div>
							<div class="flex flex-col gap-1 items-center">
								<p class="text-xs text-[#B6BABD]">Your Opponent's Team</p>
								<div id="opponent-team-btn" class="text-center w-50 h-9 px-3 py-1.5 rounded-md bg-[#111111] border border-white/6 cursor-pointer hover:bg-[#1a1a1a] transition">
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
										<th class="py-2 px-6 font-normal text-center w-28">Position</th>
										<th class="py-2 px-6 font-normal text-center w-28">XI</th>
									</tr>
								</thead>
								<tbody id="opponent-players-tbody" class="text-white text-sm bg-[#111111]">
								<!-- rows will be created dynamically -->
								</tbody>
							</table>
							<div class="bg-[#111111] border-b border-neutral-800 hover:bg-neutral-800 transition-colors">
								<button id="add-opponent-player-btn" type="button" class="flex justify-center w-full cursor-pointer py-2.5">
									<img src="<?php echo base_url('assets/images/icons/plus.svg'); ?>" class="w-3">
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="w-full px-4 py-6">
				<button id="create-match-btn" type="submit" class="flex justify-center items-center w-full text-white bg-[#6366F1] rounded-lg cursor-pointer hover:bg-indigo-400 transition-shadow px-4 py-2 shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-400/25">Save & Create Match Metadata</button>
			</div>
		</form>
	</div>

	<div id="match-library-main-content" class="">
		<div class="flex flex-col gap-5 py-5">
			<!-- Search -->
			<div class="flex w-full gap-10">
				<input type="text" placeholder="Search"
				class="w-full border border-white/6 text-white text-sm px-4 py-2 rounded-lg focus:outline-none focus:ring-1 focus:ring-white/10 bg-[#0f0f0f]" />

				<button id="match-filter-btn" class="flex gap-2 w-26 justify-between items-center h-relative bg-[#1d1d1d] cursor-pointer px-4 text-white font-medium text-sm rounded-lg hover:bg-[#1a1a1a] transition border border-white/6 shadow-sm focus:outline-none focus:ring-2 focus:ring-white/10">
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
			<div id="month-card" class="flex flex-col w-full p-6 gap-5 bg-[#111111] rounded-lg border border-white/6 shadow-sm">
				<div class="flex w-full justify-between items-center">
					<h2 id="month-name" class="text-white font-bold text-xl">
						July
						<span id="year" class="text-[#B6BABD]">2026</span>
					</h2>
				</div>
				<div id="match-cards-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 w-full gap-6">
					<!-- append all match cards here -->
					<div id="match-card" class="group flex flex-col w-full bg-gradient-to-b from-[#1b1b1b] to-[#141414] rounded-xl border border-white/6 shadow-lg hover:shadow-2xl transform transition-all duration-200 hover:-translate-y-1">
						<div id="thumbnail" class="relative flex w-full h-64 bg-[#363636] rounded-t-xl overflow-hidden">
							<div class="absolute inset-0 bg-cover bg-center" style="background-image: url('');"></div>
							<div class="flex w-full h-fit p-2 justify-end z-10">
								<button id="card-options-btn" type="button" class="p-1 hover:bg-white/6 rounded-full cursor-pointer transition focus:outline-none focus:ring-2 focus:ring-white/20">
									<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-300" viewBox="0 0 20 20" fill="currentColor">
										<path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
									</svg>
								</button>
								<div id="match-options" class="absolute right-3 top-12 hidden group-hover:flex flex-col bg-[#0f0f0f]/95 border border-white/6 text-white rounded-md shadow-md overflow-hidden">
									<span id="start-tagging-btn" class="px-6 py-2 cursor-pointer hover:bg-white/6 transition-colors">Open in Tagging Studio</span>
									<span id="remove-match-card-btn" class="px-6 py-2 cursor-pointer hover:bg-red-600/60 transition-colors">Remove</span>
								</div>
							</div>
						</div>
						<!-- match info -->
						<div class="flex flex-col w-full h-fit p-5 gap-3 rounded-b-xl">
							<div class="flex w-fit items-center gap-2 px-3 py-1 rounded-full bg-transparent border border-blue-500">
								<div id="status-color" class="w-2 h-2 rounded-full bg-blue-500"></div>
								<span id="status" class="text-[#B6BABD] text-xs">Ready</span>
							</div>
							<div class="flex w-full h-fit justify-between items-center">
								<p id="match-name" class="text-white font-bold text-lg">vs. Ateneo</p>
								<span id="match-date" class="text-[#B6BABD] font-medium">Jul 28</span>
							</div>
						</div>
					</div> <!-- match-card -->
				</div>
			</div>
		</div>
	</div>
</div>


<script>
	window.APP_BASE_URL = '<?php echo base_url(); ?>index.php/';

	window.SESSION_TEAM = {
		id: "<?php echo $this->session->userdata('team_id'); ?>",
		abbr: "<?php echo $this->session->userdata('team_abbreviation'); ?>"
	};
</script>

<script src="<?php echo base_url('assets/js/modalManager.js') . '?v=' . time(); ?>"></script>
<script src="<?php echo base_url('assets/js/dropdownHandler.js') . '?v=' . time(); ?>"></script>
<script src="<?php echo base_url('assets/js/matchMetadataFormHandler.js') . '?v=' . time(); ?>"></script>

<script src="<?php echo base_url('assets/js/matchCardHandler.js') . '?v=' . time(); ?>"></script>
<script src="<?php echo base_url('assets/js/newMatchPanelHandler.js') . '?v=' . time(); ?>"></script>

<script>
(function () {
  // Helpers to find the tbody for each panel.
  function findMyTeamTbody() {
    // Prefer explicit id if present
    const explicit = document.getElementById('my-players-tbody');
    if (explicit) return explicit;
    // Fallback: find container that holds #my-team-btn and get the first tbody inside it
    const container = document.querySelector('#my-team-btn')?.closest('div.flex');
    if (!container) {
      // last fallback: first table in document (risky)
      return document.querySelector('table tbody');
    }
    return container.querySelector('table tbody');
  }

  function findOpponentTbody() {
    const explicit = document.getElementById('opponent-players-tbody');
    if (explicit) return explicit;
    const container = document.querySelector('#opponent-team-btn')?.closest('div.flex');
    if (!container) {
      // fallback: second table tbody on the page
      return document.querySelectorAll('table tbody')[1] || document.querySelector('table tbody');
    }
    return container.querySelector('table tbody');
  }

  // Generic row creation for both tables
  function createRowHTML(index, name = '', jersey = '', position = '') {
    return `
      <td class="py-2 px-6 text-center text-[#B6BABD]">${index}</td>
      <td class="player-name py-2 px-6">${name}</td>
	  <td class="jersey-num py-2 px-6 text-center">${jersey}</td>
      <td class="player-position py-2 px-6 text-center">${position}</td>
      <td class="py-2 px-6 flex justify-center items-center">
        <input type="checkbox" class="w-5 h-5 rounded bg-neutral-700 border-neutral-600 text-indigo-500 cursor-pointer focus:ring-indigo-600 focus:ring-offset-neutral-800">
      </td>
    `;
  }

  // Attach listeners and MutationObserver to a row element
  function attachRow(row) {
    if (!row || row._dropdownAttached) return;
    row._dropdownAttached = true;

    // Click opens dropdown for 'player' with the row as trigger
    row.addEventListener('click', function (e) {
      // if checkbox clicked, ignore
      if (e.target.closest('input[type="checkbox"]')) return;
      if (window.dropdownHandler && typeof window.dropdownHandler.openDropdown === 'function') {
        // Pass the actual row element as trigger (this is what dropdownHandler expects)
        window.dropdownHandler.openDropdown('player', row);
      } else {
        console.warn('dropdownHandler.openDropdown not available yet');
      }
    });

    // Observe dataset changes set by dropdownHandler.selectItem (data-selected-name/data-selected-id/data-selected-jersey)
    const mo = new MutationObserver((mutations) => {
      for (const m of mutations) {
        if (m.type === 'attributes' && ['data-selected-name', 'data-selected-id', 'data-selected-jersey', 'data-selected-position'].includes(m.attributeName)) {
          const selectedName = row.dataset.selectedName || '';
          const selectedJersey = row.dataset.selectedJersey || '';
		  const selectedPosition = row.dataset.selectedPosition || '';

          const nameCell = row.querySelector('.player-name');
          const jerseyCell = row.querySelector('.jersey-num');
		  const positionCell = row.querySelector('.player-position');

          if (nameCell) nameCell.textContent = selectedName;
          if (jerseyCell) jerseyCell.textContent = selectedJersey;
		  if (positionCell) positionCell.textContent = selectedPosition;
        }
      }
    });

    mo.observe(row, { attributes: true, attributeFilter: ['data-selected-name', 'data-selected-id', 'data-selected-jersey', 'data-selected-position'] });
    row._mo = mo;
  }

  // Render initial rows if tbody is empty; otherwise attach to existing rows
  function initTable(tbody, addBtnId, initialPlayers = [], rowClass = 'fill-player-row') {
    if (!tbody) {
      console.warn('tbody not found for', addBtnId);
      return { addRow: () => {} };
    }

    // Count existing rows
    let existingRows = Array.from(tbody.querySelectorAll('tr'));
    let rowCount = existingRows.length;

    // If no rows or all rows are placeholders (empty cells), create 11 initial rows
    if (rowCount === 0) {
      for (let i = 0; i < 11; i++) {
        const idx = i + 1;
        const data = initialPlayers[i] || { name: '', jersey: '', position: '' };
        const tr = document.createElement('tr');
        tr.className = `${rowClass} border-b border-neutral-800 cursor-pointer hover:bg-neutral-800 transition-colors`;
        tr.dataset.rowIndex = String(idx);
        tr.innerHTML = createRowHTML(idx, data.name || '', data.jersey || '', data.jersey || '');
        tbody.appendChild(tr);
        attachRow(tr);
      }
      rowCount = 11;
    } else {
      // attach to existing rows found in the DOM
      existingRows.forEach((r, idx) => {
        // ensure there's a name/jersy cell classes for our script to update
        if (!r.querySelector('.player-name')) {
          // try to map known ids (legacy): replace cells if they exist
          const playerNameCell = r.querySelector('[id^="player-name"], [id^="opponent-player-name"]');
          const jerseyCell = r.querySelector('[id^="jersey-num"], [id^="opponent-jersey-num"]');
		  const positionCell = r.querySelector('[id^="player-position"], [id^="opponent-player-position"]');
          if (playerNameCell) playerNameCell.classList.add('player-name');
          if (jerseyCell) jerseyCell.classList.add('jersey-num');
		  if (positionCell) positionCell.classList.add('player-position');
        }
        attachRow(r);
      });
      rowCount = tbody.querySelectorAll('tr').length;
    }

    // Add button behavior
    const addBtn = document.getElementById(addBtnId);
    if (addBtn) {
      // If this is the "player" add buttons, open the Add Player modal with team info
      if (addBtnId === 'add-player-btn' || addBtnId === 'add-opponent-player-btn') {
    
        // legacy behavior: create a blank row
        addBtn.addEventListener('click', (e) => {
          e.preventDefault();
          rowCount++;
          const tr = document.createElement('tr');
          tr.className = `${rowClass} border-b border-neutral-800 cursor-pointer hover:bg-neutral-800 transition-colors`;
          tr.dataset.rowIndex = String(rowCount);
          tr.innerHTML = createRowHTML(rowCount, '', '');
          tbody.appendChild(tr);
          // attach listeners after appended
          attachRow(tr);
          // ensure visible
          tr.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }, { passive: true });
      }
    } else {
      console.warn('Add button not found:', addBtnId);
    }

    // Expose programmatic add
    return {
      addRow(player = {}) {
        rowCount++;
        const tr = document.createElement('tr');
        tr.className = `${rowClass} border-b border-neutral-800 cursor-pointer hover:bg-neutral-800 transition-colors`;
        tr.dataset.rowIndex = String(rowCount);
        tr.innerHTML = createRowHTML(rowCount, player.name || '', player.jersey || '', player.position || '');
        tbody.appendChild(tr);
        attachRow(tr);
        return tr;
      }
    };
  }

  // Initialize on DOMContentLoaded
  document.addEventListener('DOMContentLoaded', () => {
    const myTbody = findMyTeamTbody();
    const oppTbody = findOpponentTbody();

    const myPlayers = [
      // first row example with known player — keeps the one you already had
      // { name: 'Mapula, Paul Jsoshua', jersey: '20' }
      // others left blank
    ];

    const myTable = initTable(myTbody, 'add-player-btn', myPlayers, 'fill-player-row');
    const oppTable = initTable(oppTbody, 'add-opponent-player-btn', [], 'fill-opponent-player-row');

    // Expose helpers for other scripts
    window.addPlayerRow = (player = {}) => myTable.addRow(player);
    window.addOpponentPlayerRow = (player = {}) => oppTable.addRow(player);

    console.log('Player row initializer ready. MyTeam tbody:', !!myTbody, 'Opponent tbody:', !!oppTbody);
  });
})();
</script>