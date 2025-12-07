<?php
// scoreboard.php
$team_metrics = $team_metrics ?? null;
$metrics_file_path = $metrics_file_path ?? '';
$metrics_file_url  = $metrics_file_url  ?? null;
$mc = $match_config ?? [];

// Prefer controller-provided values, then GET, then session
$page_requested_match_id   = $requested_match_id ?? (isset($_GET['match_id']) ? $_GET['match_id'] : null);
$page_requested_match_name = $requested_match_name ?? (isset($_GET['match_name']) ? $_GET['match_name'] : null);

$page_match_id   = $match_id ?? $page_requested_match_id ?? ($this->session->userdata('current_match_id') ?? null);
$page_match_name = $match_name ?? $page_requested_match_name ?? ($this->session->userdata('current_match_name') ?? null);

$home_name = $mc['home']['name'] ?? null;
$away_name = $mc['away']['name'] ?? null;
$home_metrics = function_exists('find_team_metrics') ? find_team_metrics($team_metrics, $home_name) : null;
$away_metrics = function_exists('find_team_metrics') ? find_team_metrics($team_metrics, $away_name) : null;

// Partial data passed into each stats partial
$partialData = [
  'home_metrics' => $home_metrics,
  'away_metrics' => $away_metrics,
  'mc' => $mc,
  'metrics_file_path' => $metrics_file_path,
  'metrics_file_url' => $metrics_file_url,
];

$home_goals = $home_metrics['attack']['goals'] ?? $this->session->userdata('home_goals') ?? null;
$away_goals = $away_metrics['attack']['goals'] ?? $this->session->userdata('away_goals') ?? null;
?>

<!-- Hidden canonical inputs for JS -->
<input type="hidden" id="REPORT_MATCH_ID"   value="<?= htmlspecialchars($page_match_id ?? '') ?>">
<input type="hidden" id="REPORT_MATCH_NAME" value="<?= htmlspecialchars($page_match_name ?? '') ?>">
<input type="hidden" id="REPORT_REQUESTED_MATCH_ID"   value="<?= htmlspecialchars($page_requested_match_id ?? '') ?>">
<input type="hidden" id="REPORT_REQUESTED_MATCH_NAME" value="<?= htmlspecialchars($page_requested_match_name ?? '') ?>">

<div id="scoreboard-summary"
     class="w-full flex flex-col p-6 items-center gap-4 bg-[#1d1d1d] rounded-2xl shadow-lg hover:shadow-xl transition-all"
     data-match-id="<?= htmlspecialchars($page_match_id ?? '') ?>"
     data-match-name="<?= htmlspecialchars($page_match_name ?? '') ?>"
>
    <!-- Full Time Label -->
    <div class="w-full flex justify-center">
        <h1 class="text-md text-white font-bold tracking-wide">Full Time</h1>
    </div>

    <!-- Score Row -->
    <div class="w-full flex justify-between gap-20 items-center">
        <!-- Home Team -->
        <div class="w-full flex flex-col items-end gap-2">
            <div class="flex items-center gap-3">
                <h2 class="text-lg text-white font-semibold tracking-wide"><?= $home_name ?></h2>
                <div class="text-4xl text-indigo-500 font-extrabold shadow-md"><?= $home_goals ?></div>
            </div>
            <div class="flex flex-col gap-1">
                <!-- Example home goal scorer -->
                <!--
                <div class="flex gap-1 items-center">
                    <span class="text-sm text-gray-400 font-normal">L. Yamal</span>
                    <img src="<?= base_url('assets/images/icons/goal.svg') ?>" alt="goal icon" class="w-4 h-4">
                </div>
                -->
            </div>
        </div>

        <!-- Away Team -->
        <div class="w-full flex flex-col items-start gap-2">
            <div class="flex items-center gap-3">
                <div class="text-4xl text-indigo-500 font-extrabold shadow-md"><?= $away_goals ?></div>
                <h2 class="text-lg text-white font-semibold tracking-wide"><?= $away_name ?></h2>
            </div>
            <div class="flex flex-col gap-1">
                <!-- Example away goal scorer -->
                <!--
                <div class="flex gap-1 items-center">
                    <img src="<?= base_url('assets/images/icons/goal.svg') ?>" alt="goal icon" class="w-4 h-4">
                    <span class="text-sm text-gray-400 font-normal">L. Yamal</span>
                </div>
                -->
            </div>
        </div>
    </div>
</div>


<!-- Optional JS: augment any internal links inside the scoreboard so they keep match params -->
<script>
(function(){
  const matchId = document.getElementById('REPORT_MATCH_ID')?.value || '';
  const matchName = document.getElementById('REPORT_MATCH_NAME')?.value || '';
  if (!matchId && !matchName) return;

  function ensureParams(url) {
    try {
      const u = new URL(url, window.location.origin);
      if (matchId && !u.searchParams.has('match_id')) u.searchParams.set('match_id', matchId);
      if (matchName && !u.searchParams.has('match_name')) u.searchParams.set('match_name', matchName);
      return u.pathname + u.search + u.hash;
    } catch (e) {
      // fallback naive approach for relative URLs
      if (url.indexOf('match_id=') !== -1 || url.indexOf('match_name=') !== -1) return url;
      const sep = url.indexOf('?') === -1 ? '?' : '&';
      const parts = [];
      if (matchId) parts.push('match_id=' + encodeURIComponent(matchId));
      if (matchName) parts.push('match_name=' + encodeURIComponent(matchName));
      return url + sep + parts.join('&');
    }
  }

  // augment links only within scoreboard-summary to be conservative
  document.querySelectorAll('#scoreboard-summary a[href]').forEach(a => {
    const href = a.getAttribute('href') || '';
    // skip external links
    if (href.startsWith('http') && !href.startsWith(window.location.origin)) return;
    const newHref = ensureParams(href);
    if (newHref !== href) a.setAttribute('href', newHref);
  });
})();
</script>