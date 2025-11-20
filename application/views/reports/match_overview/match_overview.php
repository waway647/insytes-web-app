<?php
// reports/match_overview/match_overview.php
$team_metrics = $team_metrics ?? null;
$metrics_file_path = $metrics_file_path ?? '';
$metrics_file_url  = $metrics_file_url  ?? null;
$mc = $match_config ?? [];

// helper to find a team's metrics (case-insensitive / partial)
function find_team_metrics($metrics, $team_name) {
    if (empty($metrics) || empty($team_name)) return null;
    foreach ($metrics as $k => $v) {
        if (strcasecmp(trim($k), trim($team_name)) === 0) return $v;
    }
    foreach ($metrics as $k => $v) {
        if (stripos($k, $team_name) !== false || stripos($team_name, $k) !== false) return $v;
    }
    return null;
}
$home_name = $mc['home']['name'] ?? null;
$away_name = $mc['away']['name'] ?? null;
$home_metrics = find_team_metrics($team_metrics, $home_name);
$away_metrics = find_team_metrics($team_metrics, $away_name);

// Partial data passed into each stats partial
$partialData = [
  'home_metrics' => $home_metrics,
  'away_metrics' => $away_metrics,
  'mc' => $mc,
  'metrics_file_path' => $metrics_file_path,
  'metrics_file_url' => $metrics_file_url,
];
?>

<!-- Debug info (optional) -->
<div class="text-xs text-gray-400 mb-2">
  <div>Metrics file (server): <code><?= htmlspecialchars($metrics_file_path) ?></code></div>
  <?php if (!empty($metrics_file_url)): ?>
    <div>Metrics file (public): <a href="<?= htmlspecialchars($metrics_file_url) ?>" target="_blank"><?= htmlspecialchars($metrics_file_url) ?></a></div>
  <?php endif; ?>
  <div>Home team: <?= htmlspecialchars($home_name ?? '-') ?> — metrics <?= $home_metrics ? 'FOUND' : 'MISSING' ?></div>
  <div>Away team: <?= htmlspecialchars($away_name ?? '-') ?> — metrics <?= $away_metrics ? 'FOUND' : 'MISSING' ?></div>
</div>

<!-- Tab buttons -->
<div class="w-full flex flex-col items-center">
  <div class="w-full flex flex-col items-center">
    <div id="stats-tabbar" class="w-full flex py-2 justify-center" role="tablist" aria-label="Match stats tabs">
      <button data-type="general" class="stat-tab px-10 py-2 bg-[#2a2a2a] hover:bg-[#1d1d1d] rounded-l-3xl text-[#B6BABD] cursor-pointer" role="tab" aria-selected="false">General</button>
      <button data-type="distribution" class="stat-tab px-10 py-2 bg-[#2a2a2a] hover:bg-[#1d1d1d] text-[#B6BABD] cursor-pointer" role="tab" aria-selected="false">Distribution</button>
      <button data-type="attacking" class="stat-tab px-10 py-2 bg-[#2a2a2a] hover:bg-[#1d1d1d] text-[#B6BABD] cursor-pointer" role="tab" aria-selected="false">Attacking</button>
      <button data-type="defense" class="stat-tab px-10 py-2 bg-[#2a2a2a] hover:bg-[#1d1d1d] text-[#B6BABD] cursor-pointer" role="tab" aria-selected="false">Defense</button>
      <button data-type="discipline" class="stat-tab px-10 py-2 bg-[#2a2a2a] hover:bg-[#1d1d1d] rounded-r-3xl text-[#B6BABD] cursor-pointer" role="tab" aria-selected="false">Discipline</button>
    </div>
  </div>

  <!-- Container where the active stat template will be injected -->
  <div id="dynamic-stats-container" class="mt-4">
    <div class="text-sm text-[#B6BABD] p-6">Loading stats…</div>
  </div>

  <div class="w-170 flex flex-col py-20 gap-20">
    <?php if (!empty($team_heatmap_url)): ?>
        <img src="<?php echo $team_heatmap_url ?>" alt="Team heatmap" class="max-w-full rounded" />
    <?php else: ?>
        <p class="text-sm text-gray-400">Heatmap not available.</p>
    <?php endif; ?>

    <?php if (!empty($pass_network_url)): ?>
        <img src="<?php echo $pass_network_url ?>" alt="Pass network" class="max-w-full rounded" />
    <?php else: ?>
        <p class="text-sm text-gray-400">Pass network not available.</p>
    <?php endif; ?>
  </div>
</div>

<!-- Hidden server-rendered templates (load partial files here) -->
<div id="stat-templates" style="display:none;">
  <div data-type="general">
    <?php $this->load->view('reports/match_overview/stats/general', $partialData); ?>
  </div>

  <div data-type="distribution">
    <?php $this->load->view('reports/match_overview/stats/distribution', $partialData); ?>
  </div>

  <div data-type="attacking">
    <?php $this->load->view('reports/match_overview/stats/attacking', $partialData); ?>
  </div>

  <div data-type="defense">
    <?php $this->load->view('reports/match_overview/stats/defense', $partialData); ?>
  </div>

  <div data-type="discipline">
    <?php $this->load->view('reports/match_overview/stats/discipline', $partialData); ?>
  </div>
</div>

<!-- JS: tab switching logic (same as before) -->
<script>
(function(){
  const tabButtons = Array.from(document.querySelectorAll('#stats-tabbar .stat-tab'));
  const container = document.getElementById('dynamic-stats-container');
  const templatesRoot = document.getElementById('stat-templates');
  const TABS = tabButtons.map(btn => btn.dataset.type);
  const STORAGE_KEY = 'match_stats_last_tab';

  function setActiveTab(type) {
    tabButtons.forEach(btn => {
      const isActive = btn.dataset.type === type;
      btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
      if (isActive) {
        btn.classList.add('bg-[#151515]');
      } else {
        btn.classList.remove('bg-[#151515]');
      }
    });

    const tpl = templatesRoot.querySelector(`[data-type="${type}"]`);
    if (!tpl) {
      container.innerHTML = `<div class="p-6 text-sm text-red-400">No stats available for "${type}".</div>`;
      return;
    }
    container.innerHTML = '';
    const clone = tpl.cloneNode(true);
    clone.style.display = 'block';
    while (clone.firstChild) {
      container.appendChild(clone.firstChild);
    }
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
