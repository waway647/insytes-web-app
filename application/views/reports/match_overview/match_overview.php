<input type="hidden" id="REPORT_MATCH_ID" value="<?= htmlspecialchars($match_id ?? '') ?>">
<input type="hidden" id="REPORT_MATCH_NAME" value="<?= htmlspecialchars($match_name ?? '') ?>">

<style>
    /* Custom Keyframe for smooth content entry */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-up {
        animation: fadeInUp 0.4s ease-out forwards;
    }
    
    /* Smooth transition for tab background */
    .stat-tab {
        transition: all 0.3s ease;
    }

    /* Lightbox specific transitions */
    #lightbox-modal {
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }
    #lightbox-modal.open {
        visibility: visible;
        opacity: 1;
    }
    #lightbox-modal.closed {
        visibility: hidden;
        opacity: 0;
    }
    #lightbox-image {
        transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    #lightbox-modal.open #lightbox-image {
        transform: scale(1);
    }
    #lightbox-modal.closed #lightbox-image {
        transform: scale(0.95);
    }
</style>

<?php
// ... [Keep your existing PHP variable initialization logic here] ...
$team_metrics = $team_metrics ?? null;
$metrics_file_path = $metrics_file_path ?? '';
$metrics_file_url  = $metrics_file_url  ?? null;
$mc = $match_config ?? [];

$home_name = $mc['home']['name'] ?? null;
$away_name = $mc['away']['name'] ?? null;
$home_metrics = function_exists('find_team_metrics') ? find_team_metrics($team_metrics, $home_name) : null;
$away_metrics = function_exists('find_team_metrics') ? find_team_metrics($team_metrics, $away_name) : null;

$partialData = [
  'home_metrics' => $home_metrics,
  'away_metrics' => $away_metrics,
  'mc' => $mc,
  'metrics_file_path' => $metrics_file_path,
  'metrics_file_url' => $metrics_file_url,
];

$home_goals = $home_metrics['attack']['goals'] ?? null;
$away_goals = $away_metrics['attack']['goals'] ?? null;
$this->session->set_userdata('home_goals', $home_goals);
$this->session->set_userdata('away_goals', $away_goals);
?>

<div class="w-full flex flex-col items-center animate-fade-in-up">
    
    <div class="w-full flex justify-center mb-8">
        <div id="stats-tabbar" class="inline-flex bg-[#2a2a2a] p-1 rounded-full shadow-lg" role="tablist" aria-label="Match stats tabs">
            <button data-type="general" class="stat-tab px-6 py-2 rounded-full text-sm font-medium text-[#B6BABD] hover:text-white" role="tab" aria-selected="false">General</button>
            <button data-type="distribution" class="stat-tab px-6 py-2 rounded-full text-sm font-medium text-[#B6BABD] hover:text-white" role="tab" aria-selected="false">Distribution</button>
            <button data-type="attacking" class="stat-tab px-6 py-2 rounded-full text-sm font-medium text-[#B6BABD] hover:text-white" role="tab" aria-selected="false">Attacking</button>
            <button data-type="defense" class="stat-tab px-6 py-2 rounded-full text-sm font-medium text-[#B6BABD] hover:text-white" role="tab" aria-selected="false">Defense</button>
            <button data-type="discipline" class="stat-tab px-6 py-2 rounded-full text-sm font-medium text-[#B6BABD] hover:text-white" role="tab" aria-selected="false">Discipline</button>
        </div>
    </div>

    <div id="dynamic-stats-container" class="w-full min-h-[300px] transition-all duration-300">
        <div class="text-sm text-[#B6BABD] p-6 text-center animate-pulse">Loading stats...</div>
    </div>

    <hr class="w-full border-[#2a2a2a] my-12">

    <div class="w-full max-w-7xl px-4">
        <h3 class="text-xl font-semibold text-white mb-6 text-center md:text-left pl-2">Match Visualization</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pb-20">
            
            <div class="bg-[#1e1e1e] rounded-xl border border-[#2a2a2a] overflow-hidden shadow-lg group hover:border-[#444] transition-colors duration-300">
                <div class="px-4 py-3 border-b border-[#2a2a2a] bg-[#252525] flex justify-between items-center">
                    <span class="text-sm font-medium text-[#B6BABD]">Heatmap</span>
                    <span class="text-xs text-gray-500">Click to expand</span>
                </div>
                <div class="p-6 flex justify-center items-center bg-[#151515] min-h-[300px] relative overflow-hidden cursor-zoom-in"
                     onclick="openLightbox('<?php echo $team_heatmap_url ?? '' ?>')">
                    <?php if (!empty($team_heatmap_url)): ?>
                        <div class="absolute inset-0 bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-300 z-10 flex items-center justify-center">
                             <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-300 transform scale-75 group-hover:scale-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path></svg>
                        </div>
                        <img src="<?php echo $team_heatmap_url ?>" 
                             alt="Team heatmap" 
                             class="max-w-full max-h-[400px] object-contain rounded transform group-hover:scale-105 transition-transform duration-500 ease-out" 
                             loading="lazy" />
                    <?php else: ?>
                        <div class="flex flex-col items-center justify-center text-gray-500 h-40 pointer-events-none">
                            <svg class="w-10 h-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            <span class="text-sm">No Heatmap Data</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-[#1e1e1e] rounded-xl border border-[#2a2a2a] overflow-hidden shadow-lg group hover:border-[#444] transition-colors duration-300">
                <div class="px-4 py-3 border-b border-[#2a2a2a] bg-[#252525] flex justify-between items-center">
                    <span class="text-sm font-medium text-[#B6BABD]">Pass Network</span>
                    <span class="text-xs text-gray-500">Click to expand</span>
                </div>
                <div class="p-6 flex justify-center items-center bg-[#151515] min-h-[300px] relative overflow-hidden cursor-zoom-in"
                     onclick="openLightbox('<?php echo $pass_network_url ?? '' ?>')">
                    <?php if (!empty($pass_network_url)): ?>
                        <div class="absolute inset-0 bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-300 z-10 flex items-center justify-center">
                             <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-300 transform scale-75 group-hover:scale-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path></svg>
                        </div>
                        <img src="<?php echo $pass_network_url ?>" 
                             alt="Pass network" 
                             class="max-w-full max-h-[400px] object-contain rounded transform group-hover:scale-105 transition-transform duration-500 ease-out" 
                             loading="lazy" />
                    <?php else: ?>
                        <div class="flex flex-col items-center justify-center text-gray-500 h-40 pointer-events-none">
                             <svg class="w-10 h-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                            <span class="text-sm">No Network Data</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<div id="lightbox-modal" class="closed fixed inset-0 z-[9999] bg-black bg-opacity-100 backdrop-blur-sm flex items-center justify-center p-4" aria-hidden="true">
    <button type="button" onclick="closeLightbox()" class="absolute top-6 right-6 text-white text-opacity-70 hover:text-opacity-100 focus:outline-none transition-colors z-50">
        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
    </button>
    
    <div class="relative w-full h-full flex items-center justify-center pointer-events-none">
        <img id="lightbox-image" src="" alt="Zoomed view" class="max-w-full max-h-full object-contain rounded-lg shadow-2xl pointer-events-auto" onclick="event.stopPropagation()">
    </div>
    
    <div class="absolute inset-0 -z-10" onclick="closeLightbox()"></div>
</div>

<div id="stat-templates" style="display:none;">
    <div data-type="general" class="w-full">
        <?php $this->load->view('reports/match_overview/stats/general', $partialData); ?>
    </div>
    <div data-type="distribution" class="w-full">
        <?php $this->load->view('reports/match_overview/stats/distribution', $partialData); ?>
    </div>
    <div data-type="attacking" class="w-full">
        <?php $this->load->view('reports/match_overview/stats/attacking', $partialData); ?>
    </div>
    <div data-type="defense" class="w-full">
        <?php $this->load->view('reports/match_overview/stats/defense', $partialData); ?>
    </div>
    <div data-type="discipline" class="w-full">
        <?php $this->load->view('reports/match_overview/stats/discipline', $partialData); ?>
    </div>
</div>

<script>
// --- Lightbox Logic ---
function openLightbox(src) {
    if (!src) return;
    const modal = document.getElementById('lightbox-modal');
    const img = document.getElementById('lightbox-image');
    
    img.src = src;
    modal.classList.remove('closed');
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden'; // Prevent scrolling background
}

function closeLightbox() {
    const modal = document.getElementById('lightbox-modal');
    
    modal.classList.remove('open');
    modal.classList.add('closed');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = ''; // Restore scrolling
    
    // Clear src after transition (300ms) to prevent ghosting on next open
    setTimeout(() => {
        document.getElementById('lightbox-image').src = '';
    }, 300);
}

// Close on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeLightbox();
    }
});

// --- Tabs Logic ---
(function(){
  const tabButtons = Array.from(document.querySelectorAll('#stats-tabbar .stat-tab'));
  const container = document.getElementById('dynamic-stats-container');
  const templatesRoot = document.getElementById('stat-templates');
  const TABS = tabButtons.map(btn => btn.dataset.type);
  const STORAGE_KEY = 'match_stats_last_tab';

  const activeClasses = ['bg-[#4a4a4a]', 'text-white', 'shadow-md'];
  const inactiveClasses = ['text-[#B6BABD]', 'hover:bg-[#3a3a3a]'];

  function setActiveTab(type) {
    tabButtons.forEach(btn => {
      const isActive = btn.dataset.type === type;
      btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
      if (isActive) {
        btn.classList.add(...activeClasses);
        btn.classList.remove(...inactiveClasses);
      } else {
        btn.classList.remove(...activeClasses);
        btn.classList.add(...inactiveClasses);
      }
    });

    const tpl = templatesRoot.querySelector(`[data-type="${type}"]`);
    container.innerHTML = '';

    if (!tpl) {
      container.innerHTML = `<div class="p-6 text-sm text-center text-gray-500 animate-fade-in-up">No stats available for "${type}".</div>`;
      return;
    }

    const clone = tpl.cloneNode(true);
    clone.classList.add('animate-fade-in-up');
    container.appendChild(clone);
    try { sessionStorage.setItem(STORAGE_KEY, type); } catch (e) {}
  }

  tabButtons.forEach(btn => {
    btn.addEventListener('click', () => setActiveTab(btn.dataset.type));
    btn.addEventListener('keydown', (ev) => {
      if (ev.key === 'Enter' || ev.key === ' ') {
        ev.preventDefault(); setActiveTab(btn.dataset.type);
      }
      if (ev.key === 'ArrowLeft' || ev.key === 'ArrowRight') {
        ev.preventDefault();
        const idx = TABS.indexOf(btn.dataset.type);
        let next = idx + (ev.key === 'ArrowRight' ? 1 : -1);
        if (next < 0) next = TABS.length - 1;
        if (next >= TABS.length) next = 0;
        const target = document.querySelector(`#stats-tabbar .stat-tab[data-type="${TABS[next]}"]`);
        if (target) { target.focus(); setActiveTab(TABS[next]); }
      }
    });
  });

  let initial = 'general';
  try { const s = sessionStorage.getItem(STORAGE_KEY); if (s && TABS.includes(s)) initial = s; } catch(e){}
  
  if (!templatesRoot.querySelector(`[data-type="${initial}"]`)) {
    const firstTpl = templatesRoot.querySelector('[data-type]');
    initial = firstTpl ? firstTpl.dataset.type : initial;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => setActiveTab(initial));
  } else {
    setActiveTab(initial);
  }
})();
</script>