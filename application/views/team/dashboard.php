<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Defensive locals (safe defaults)
$aggregates = isset($season_aggregates) ? $season_aggregates : (object) ['avg_possession_pct' => 0, 'total_goals' => 0, 'total_shots' => 0, 'total_passes' => 0];
$match_metrics = isset($match_metrics) && is_array($match_metrics) ? $match_metrics : [];
$tagged_matches = isset($tagged_matches) && is_array($tagged_matches) ? $tagged_matches : [];
$latest_match = isset($latest_match) ? $latest_match : null;
$team_name = isset($team_name) ? $team_name : null;
$opponent_name = isset($opponent_name) ? $opponent_name : null;
$last_5_matches = isset($last_5_matches) && is_array($last_5_matches) ? $last_5_matches : (array_slice($match_metrics, 0, 5) ?: []);
$last_5_pass_accuracy = isset($last_5_pass_accuracy) ? $last_5_pass_accuracy : 0;
$last_5_shots_on_target_pct = isset($last_5_shots_on_target_pct) ? $last_5_shots_on_target_pct : 0;
$last_5_record = isset($last_5_record) ? $last_5_record : ['Win' => 0, 'Draw' => 0, 'Lose' => 0];
$top_players = isset($top_players) && is_array($top_players) ? $top_players : [];
$position_breakdown = isset($position_breakdown) ? $position_breakdown : [];
$dashboard_summary = isset($dashboard_summary) ? $dashboard_summary : null;
$role = $this->session->userdata('role');
?>

<!-- Palette CSS (drop into this view) -->
<style>
  :root{
    --color-bg-surface:     #0f1113;
    --color-bg-card:        rgba(20,20,20,0.6);
    --color-bg-thumb:       #1f1f1f;

    --color-border:         rgba(255,255,255,0.04);
    --color-border-weak:    rgba(255,255,255,0.03);

    --color-muted:          #A8ACB0;
    --color-white:          #FFFFFF;
    --color-primary:        #6366F1;
    --color-primary-hover:  #4F46E5;
    --color-primary-soft:   #818CF8;
    --color-accent-blue:    #3B82F6;
    --color-icon-muted:     #9CA3AF;
  }

  /* small utility classes used in this view */
  .bg-card { background-color: var(--color-bg-card) !important; }
  .bg-surface { background-color: var(--color-bg-surface) !important; }
  .bg-thumb { background-color: var(--color-bg-thumb) !important; }

  .border-default { border-color: var(--color-border) !important; }
  .border-weak { border-color: var(--color-border-weak) !important; }

  .text-muted { color: var(--color-muted) !important; }
  .text-primary { color: var(--color-primary) !important; }
  .text-primary-soft { color: var(--color-primary-soft) !important; }

  .bg-primary { background-color: var(--color-primary) !important; color: var(--color-white) !important; }
  .hover\:bg-primary-hover:hover { background-color: var(--color-primary-hover) !important; }

  /* pill helpers */
  .pill { border-radius: 9999px; }
  .rounded-2xl { border-radius: 1rem; }
  .rounded-4xl { border-radius: 2rem; }

  /* subtle backgrounds */
  .bg-card-weak { background-color: rgba(255,255,255,0.02); }
  .text-icon-muted { color: var(--color-icon-muted) !important; }

  svg[class*="text-gray-400"], .icon-muted { color: var(--color-icon-muted) !important; fill: currentColor; }


  /* ==== Modern floating card system (minimal + elevated) ==== */
  .card-floating {
    background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
    border: 1px solid var(--color-border);
    box-shadow: 0 8px 30px rgba(2,6,23,0.6), 0 1px 0 rgba(255,255,255,0.02) inset;
    backdrop-filter: blur(6px) saturate(110%);
    -webkit-backdrop-filter: blur(6px) saturate(110%);
    transition: transform 220ms cubic-bezier(.2,.8,.2,1), box-shadow 220ms ease, border-color 220ms ease;
  }
  .card-floating:hover, .card-floating:focus-within {
    transform: translateY(-6px);
    box-shadow: 0 18px 50px rgba(2,6,23,0.7);
    border-color: rgba(255,255,255,0.06);
  }

  /* lighter card variant for small tiles */
  .card-tile {
    background: rgba(255,255,255,0.012);
    border-radius: 0.6rem;
    padding: 0.6rem;
    border: 1px solid rgba(255,255,255,0.02);
    box-shadow: 0 6px 18px rgba(2,6,23,0.45);
    transition: transform 180ms ease, box-shadow 180ms ease;
  }
  .card-tile:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(2,6,23,0.5); }

  /* pill-like small boxes inside cards */
  .card-pill {
    background: linear-gradient(180deg, rgba(255,255,255,0.01), rgba(255,255,255,0.008));
    border-radius: 0.6rem;
    padding: 0.75rem 0.6rem;
    border: 1px solid rgba(255,255,255,0.02);
    box-shadow: 0 2px 6px rgba(2,6,23,0.35) inset;
  }

  /* buttons */
  .btn-primary {
    background: linear-gradient(180deg, var(--color-primary), var(--color-primary-hover));
    border-radius: 0.8rem;
    padding: 0.5rem 0.9rem;
    box-shadow: 0 6px 18px rgba(79,70,229,0.14);
    border: none;
    transition: transform 160ms ease, box-shadow 160ms ease, opacity 160ms ease;
  }
  .btn-primary:hover { transform: translateY(-2px); }

  .btn-ghost {
    background: transparent;
    border: 1px solid var(--color-border);
    padding: 0.45rem 0.85rem;
    border-radius: 0.6rem;
    color: var(--color-muted);
  }

  /* typography tweaks */
  h1 { letter-spacing: -0.02em; }
  h3 { margin-bottom: 0.5rem; }

  /* subtle utilities */
  .muted-small { font-size: 0.82rem; color: var(--color-muted); }
  .muted-xs { font-size: 0.72rem; color: var(--color-muted); }

  /* ensure placeholder chart area fits the new look */
  .chart-placeholder { border-radius: 0.7rem; overflow: hidden; }

  /* tabs: modern underline indicator */
  .tab-btn { background: transparent; border: none; padding: 0.5rem 0.9rem; cursor: pointer; }
  .tab-btn[aria-selected="true"] { border-bottom: 2px solid var(--color-primary); color: var(--color-primary-soft); font-weight:600; }
  .tab-btn[aria-selected="false"] { color: var(--color-muted); }

</style>

<div class="min-h-screen font-sans text-white">

  <div class="flex items-center justify-between mb-8">
    <div>
      <h1 class="text-3xl font-extrabold text-white">Team Dashboard</h1>
      <div class="text-sm muted-small mt-1">
        <?php if ($team_name): ?>
          <span class="font-medium text-white"><?php echo htmlspecialchars($team_name); ?></span>
          <?php if ($opponent_name && $latest_match): ?>
            <span class="mx-2 text-muted">•</span>
            <span class="muted-small">Latest vs <?php echo htmlspecialchars($opponent_name); ?> (<?php echo $latest_match->match_date ? date('M d, Y', strtotime($latest_match->match_date)) : 'date N/A'; ?>)</span>
          <?php endif; ?>
        <?php else: ?>
          <span class="muted-small">Team not selected</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="flex items-center gap-4">
      <div class="text-right">
        <div class="muted-small">Avg Rating</div>
        <div class="text-xl font-bold text-white"><?php echo number_format($avg_overall_rating ?? ($latest_match->my_overall_rating ?? 0), 1); ?></div>
      </div>
      <a href="<?php echo site_url('reports/all'); ?>" class="btn-primary text-white">All Reports</a>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Core Performance -->
    <div class="card-floating p-6 rounded-2xl">
      <h3 class="text-xl font-semibold mb-4 text-primary-soft">📈 Core Performance</h3>
      <?php if ($role == 'Coach'): ?>
      <div class="grid grid-cols-3 gap-4">
        <div class="card-pill text-center">
          <div class="text-2xl font-bold text-white"><?php echo count($tagged_matches); ?></div>
          <div class="muted-xs uppercase tracking-wider">Matches Tagged</div>
        </div>
        <div class="card-pill text-center">
          <div class="text-2xl font-bold text-white"><?php echo number_format($latest_match->my_overall_rating ?? 0, 1); ?></div>
          <div class="muted-xs uppercase tracking-wider">Recent Rating</div>
        </div>
        <div class="card-pill text-center">
          <div class="text-2xl font-bold text-white"><?php echo $aggregates->avg_possession_pct ?? 0; ?><span class="text-lg">%</span></div>
          <div class="muted-xs uppercase tracking-wider">Avg Possession</div>
        </div>
      </div>
      <?php elseif ($role == 'Player'): ?>
      <div class="grid grid-cols-2 gap-4">
        <div class="card-pill text-center">
          <div class="text-2xl font-bold text-white"><?php echo number_format($latest_match->my_overall_rating ?? 0, 1); ?></div>
          <div class="muted-xs uppercase tracking-wider">Recent Rating</div>
        </div>
        <div class="card-pill text-center">
          <div class="text-2xl font-bold text-white"><?php echo $aggregates->avg_possession_pct ?? 0; ?><span class="text-lg">%</span></div>
          <div class="muted-xs uppercase tracking-wider">Avg Possession</div>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Goal Metrics -->
    <div class="card-floating p-6 rounded-2xl">
      <h3 class="text-xl font-semibold mb-4 text-primary-soft">⚽ Goals This Season</h3>
      <div class="grid grid-cols-2 gap-4">
        <div class="card-tile text-center">
          <div class="text-3xl font-extrabold text-green-400"><?php echo $aggregates->total_goals ?? 0; ?></div>
          <div class="muted-xs">Scored</div>
        </div>
        <div class="card-tile text-center">
          <div class="text-3xl font-extrabold text-red-400"><?php echo $goals_against ?? 'N/A'; ?></div>
          <div class="muted-xs">Conceded</div>
        </div>
      </div>
    </div>

    <!-- Recent Technical Stats -->
    <div class="card-floating p-6 rounded-2xl">
      <h3 class="text-xl font-semibold mb-4 text-primary-soft">🎯 Recent Technical Stats (Last 5)</h3>
      <div class="grid grid-cols-3 gap-2">
        <div class="card-tile text-center border-r border-default">
          <div class="text-2xl font-bold text-yellow-400"><?php echo $last_5_pass_accuracy; ?><span class="text-lg">%</span></div>
          <div class="muted-xs">Pass Acc.</div>
        </div>
        <div class="card-tile text-center border-r border-default">
          <div class="text-2xl font-bold text-cyan-400"><?php echo $last_5_shots_on_target_pct; ?><span class="text-lg">%</span></div>
          <div class="muted-xs">Shots on Target</div>
        </div>
        <div class="card-tile text-center">
          <div class="text-lg font-bold">
            <span class="text-green-500"><?php echo $last_5_record['Win']; ?>W</span> - 
            <span class="muted-small"><?php echo $last_5_record['Draw']; ?>D</span> - 
            <span class="text-red-500"><?php echo $last_5_record['Lose']; ?>L</span>
          </div>
          <div class="muted-xs mt-1">Record (L5)</div>
        </div>
      </div>
    </div>
  </div>

  <hr class="border-default mb-8">

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Latest Match Summary (left) -->
    <div class="card-floating p-6 rounded-2xl">
      <h3 class="text-xl font-semibold mb-4 text-white">📅 Latest Match Summary</h3>
      <?php if ($latest_match): ?>
        <div class="flex justify-between items-center border-b pb-3 mb-4 border-default">
          <div class="flex items-center space-x-4">
            <span class="mb-2 text-4xl font-extrabold <?php echo (($latest_match->my_team_result ?? '') === 'Win') ? 'text-green-400' : ((($latest_match->my_team_result ?? '') === 'Draw') ? 'text-yellow-400' : 'text-red-400'); ?>">
              <?php echo ($latest_match->my_team_goals ?? 0) . ' - ' . ($latest_match->opponent_team_goals ?? 0); ?>
            </span>
            <div class="flex flex-col text-lg muted-small">
              <span class="h-6 font-semibold text-white">
                vs
                <?php
                  echo htmlspecialchars($opponent_name ?? ($latest_match->opponent_team_name ?? ('ID ' . ($latest_match->opponent_team_id ?? '-'))));
                ?>
              </span>
              <span class="muted-xs"> (<?php echo $latest_match->match_date ? date('M d, Y', strtotime($latest_match->match_date)) : 'date N/A'; ?>)</span>
            </div>
          </div>

          <?php
          // id fallback (existing)
          $report_id = $latest_match->match_id ?? ($latest_match->id ?? null);

          // build a sensible match name: prefer explicit match_name/name, else try team_vs_opponent
          $report_name = null;
          if (!empty($latest_match->match_name)) {
            $report_name = $latest_match->match_name;
          } elseif (!empty($latest_match->name)) {
            $report_name = $latest_match->name;
          } elseif (!empty($team_name) && !empty($opponent_name)) {
            // create a compact slug-like name (you can adjust the separator)
            $report_name = $team_name . '_vs_' . $opponent_name;
          }

          // build the controller URL with query params (URL-encoded)
          $report_url = '#';
          if ($report_id) {
            $base = site_url('reports/overviewcontroller/index');
            $query = 'match_id=' . rawurlencode($report_id);
            if (!empty($report_name)) $query .= '&match_name=' . rawurlencode($report_name);
            $report_url = $base . '?' . $query;
          }
        ?>
        <a href="<?php echo htmlspecialchars($report_url, ENT_QUOTES, 'UTF-8'); ?>"
          class="btn-primary text-white"
          rel="noopener noreferrer">
          View Full Report
        </a>
        </div>

        <div class="grid grid-cols-4 gap-4 text-center">
          <div class="card-pill">
            <div class="text-xl font-bold text-white"><?php echo $latest_match->my_overall_rating ?? 0; ?></div>
            <div class="muted-xs">Overall Rating</div>
          </div>
          <div class="card-pill">
            <div class="text-xl font-bold text-white"><?php echo (int) round((float)($latest_match->my_possession_pct ?? 0)); ?>%</div>
            <div class="muted-xs">Possession</div>
          </div>
          <div class="card-pill">
            <div class="text-xl font-bold text-white"><?php echo ($latest_match->my_shots_on_target ?? 0); ?> / <?php echo ($latest_match->my_shots ?? 0); ?></div>
            <div class="muted-xs">Shots (On Target)</div>
          </div>
          <div class="card-pill">
            <div class="text-xl font-bold text-white"><?php echo (int) round((float)($latest_match->my_passing_accuracy_pct ?? 0)); ?>%</div>
            <div class="muted-xs">Pass Accuracy</div>
          </div>
        </div>
      <?php else: ?>
        <p class="muted-small">No recent match data available.</p>
      <?php endif; ?>
    </div>

    <!-- Performance Trend (right, larger) -->
    <div class="lg:col-span-2 card-floating p-6 rounded-2xl">
      <h3 class="text-xl font-semibold mb-4 text-white">📈 Performance Trend</h3>
      <div class="flex justify-end space-x-2 mb-3">
        <button data-mode="last5" aria-pressed="true" type="button"
                class="text-xs px-3 py-1 bg-primary rounded-full text-white pill">Last 5 Matches</button>

        <button data-mode="season" aria-pressed="false" type="button"
                class="text-xs px-3 py-1 bg-surface rounded-full text-muted pill">Season</button>
      </div>
      <div class="h-40 flex items-center justify-center chart-placeholder rounded-lg">
        <!-- Chart.js will replace this placeholder -->
        <div class="w-full h-full p-2">
          <div class="h-40 w-full"></div>
        </div>
      </div>
    </div>
  </div>

  <hr class="border-default mb-8">

  <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Event Distribution Snapshots -->
    <div id="event-distribution" class="lg:col-span-2 card-floating p-6 rounded-2xl">
      <h3 class="text-xl font-semibold mb-4 text-white">🔬 Event Distribution Snapshots</h3>

      <!-- Tabs -->
      <div class="flex border-b border-default mb-4" role="tablist" aria-label="Event distribution tabs">
        <button data-tab="attack" role="tab" aria-selected="true"
                class="tab-btn">Attack</button>

        <button data-tab="possession" role="tab" aria-selected="false"
                class="tab-btn">Possession</button>

        <button data-tab="defense" role="tab" aria-selected="false"
                class="tab-btn">Defense</button>
      </div>

      <!-- Tab panels (one visible at a time) -->
      <div class="h-48 flex items-center justify-center bg-surface rounded-lg text-muted relative overflow-hidden">
        <div class="w-full h-full p-2 relative">

          <!-- ATTACK panel (default visible) -->
          <div data-panel="attack" class="tab-panel absolute inset-0 flex items-center justify-center transition-opacity duration-200">
            <div class="muted-small">Attack charts / small multiples</div>
          </div>

          <!-- POSSESSION panel (hidden initially) -->
          <div data-panel="possession" class="tab-panel absolute inset-0 flex items-center justify-center transition-opacity duration-200 hidden opacity-0 pointer-events-none">
            <div class="muted-small">Possession charts / heatmaps</div>
          </div>

          <!-- DEFENSE panel (hidden initially) -->
          <div data-panel="defense" class="tab-panel absolute inset-0 flex items-center justify-center transition-opacity duration-200 hidden opacity-0 pointer-events-none">
            <div class="muted-small">Defense charts / interceptions</div>
          </div>

        </div>
      </div>
    </div>

    <!-- Season Overview (now its own column) -->
    <div class="card-floating p-6 rounded-2xl">
      <h3 class="text-xl font-semibold mb-4 text-white">📝 Season Overview</h3>

      <ul class="space-y-2 text-sm">
        <li class="flex justify-between border-b border-default pb-1">
          <span class="text-muted">Total Shots:</span>
          <span class="font-bold text-white"><?php echo $aggregates->total_shots ?? 0; ?></span>
        </li>
        <li class="flex justify-between border-b border-default pb-1">
          <span class="text-muted">Total Passes:</span>
          <span class="font-bold text-white"><?php echo $aggregates->total_passes ?? 0; ?></span>
        </li>
        <li class="flex justify-between border-b border-default pb-1">
          <span class="text-muted">Avg Possession:</span>
          <span class="font-bold text-white"><?php echo $aggregates->avg_possession_pct ?? 0; ?>%</span>
        </li>
      </ul>

      <?php if($role == 'Coach'): ?>
      <h3 class="text-lg font-semibold mt-6 mb-3 text-white border-t pt-3 border-default">Recent Activity</h3>
      <ul class="text-xs space-y-2 max-h-28 overflow-y-auto">
        <?php if (!empty($tagged_matches)): ?>
          <?php foreach (array_slice($tagged_matches, 0, 4) as $match): ?>
            <li class="flex justify-between items-center text-muted">
              <span>Tagging complete for Match <?php echo htmlspecialchars($match->id ?? ($match->match_id ?? 'N/A')); ?></span>
              <span class="text-primary"><?php echo isset($match->updated_at) ? date('M d', strtotime($match->updated_at)) : (isset($match->match_date) ? date('M d', strtotime($match->match_date)) : 'N/A'); ?></span>
            </li>
          <?php endforeach; ?>
        <?php else: ?>
          <li class="muted-small">No recent activity.</li>
        <?php endif; ?>
      </ul>
      <?php endif; ?>
    </div>

    <!-- Top Players -->
    <div class="card-floating p-6 rounded-2xl">
      <h3 class="text-xl font-semibold mb-4 text-white">🏅 Top Players (Season)</h3>

      <?php if (!empty($top_players)): ?>
        <ul class="space-y-3">
          <?php foreach ($top_players as $p): ?>
            <li class="flex items-center justify-between">
              <div>
                <div class="text-sm muted-small font-medium"><?php echo htmlspecialchars(($p->first_name ?? '') . ' ' . ($p->last_name ?? '')); ?></div>
                <div class="text-xs text-muted"><?php echo number_format($p->performance_score ?? 0, 1); ?> score · <?php echo intval($p->minutes_played ?? 0); ?> mins</div>
              </div>
              <div class="text-right">
                <div class="text-sm font-bold text-white"><?php echo intval($p->goals ?? 0); ?>G</div>
                <div class="text-xs text-muted"><?php echo intval($p->assists ?? 0); ?>A</div>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <div class="muted-small">No top players found for this season.</div>
      <?php endif; ?>
    </div>
  </div>

  <hr class="border-default mt-8 mb-6">

  <?php if ($role == 'Coach'): ?>
  <div class="flex justify-end gap-3">
    <a href="<?php echo site_url('match/librarycontroller/index'); ?>" class="btn-primary text-white">▶️ Start Tagging</a>
    <a href="<?php echo site_url('reports/generate/last5'); ?>" class="btn-ghost muted-small">Generate L5 Report</a>
  </div>
  <?php endif; ?>

</div>

<!-- Chart JS script section unchanged (keep functionality) -->
<script type="text/javascript">
/* ==== Inject server-side data into JS (adds opponent field when available) ==== */
window.__DASHBOARD_CHART_DATA__ = <?php
  // prefer controller-provided last_5_matches; fallback to first 5 of match_metrics
  $source = isset($last_5_matches) && is_array($last_5_matches) ? $last_5_matches : (is_array($match_metrics) ? array_slice($match_metrics, 0, 5) : []);
  $season_source = is_array($match_metrics) ? array_slice($match_metrics, 0, 12) : $source;

    $norm = function($arr) use ($opponent_name) {
      $out = [];
      foreach ($arr as $m) {
          $id = isset($m->match_id) ? $m->match_id : (isset($m->id) ? $m->id : null);
          $date = isset($m->match_date) ? $m->match_date : (isset($m->date) ? $m->date : null);
          $rating = 0;
          if (is_object($m) && isset($m->my_overall_rating)) $rating = $m->my_overall_rating;
          elseif (is_array($m) && isset($m['my_overall_rating'])) $rating = $m['my_overall_rating'];

          // opponent detection (per-match) with fallback to controller-level $opponent_name
          $opp = null;
          if (is_object($m)) {
              if (!empty($m->opponent_team_name)) $opp = $m->opponent_team_name;
              elseif (!empty($m->opponent_name)) $opp = $m->opponent_name;
              elseif (!empty($m->opponent_team_id)) $opp = 'ID ' . $m->opponent_team_id;
          } elseif (is_array($m)) {
              if (!empty($m['opponent_team_name'])) $opp = $m['opponent_team_name'];
              elseif (!empty($m['opponent_name'])) $opp = $m['opponent_name'];
              elseif (!empty($m['opponent_team_id'])) $opp = 'ID ' . $m['opponent_team_id'];
          }
          if (empty($opp) && !empty($opponent_name)) $opp = $opponent_name;

          // collect common metrics (defensive: interceptions/tackles/clearances etc.)
          $shots = null; $shots_on_target = null; $pos = null;
          $interceptions = null; $tackles = null; $clearances = null; $passes = null;
          if (is_object($m)) {
              $shots = isset($m->my_shots) ? (int)$m->my_shots : null;
              $shots_on_target = isset($m->my_shots_on_target) ? (int)$m->my_shots_on_target : null;
              $pos = isset($m->my_possession_pct) ? (float)$m->my_possession_pct : null;
              $interceptions = isset($m->my_interceptions) ? (int)$m->my_interceptions : null;
              $tackles = isset($m->my_tackles) ? (int)$m->my_tackles : null;
              $clearances = isset($m->my_clearances) ? (int)$m->my_clearances : null;
              $passes = isset($m->my_passes) ? (int)$m->my_passes : null;
          } elseif (is_array($m)) {
              $shots = isset($m['my_shots']) ? (int)$m['my_shots'] : null;
              $shots_on_target = isset($m['my_shots_on_target']) ? (int)$m['my_shots_on_target'] : null;
              $pos = isset($m['my_possession_pct']) ? (float)$m['my_possession_pct'] : null;
              $interceptions = isset($m['my_interceptions']) ? (int)$m['my_interceptions'] : null;
              $tackles = isset($m['my_tackles']) ? (int)$m['my_tackles'] : null;
              $clearances = isset($m['my_clearances']) ? (int)$m['my_clearances'] : null;
              $passes = isset($m['my_passes']) ? (int)$m['my_passes'] : null;
          }

          // ensure numeric fallbacks (so JS doesn't have to guard everywhere)
          $metrics = [
              'shots' => $shots !== null ? $shots : 0,
              'shots_on_target' => $shots_on_target !== null ? $shots_on_target : 0,
              'possession' => $pos !== null ? $pos : 0,
              'interceptions' => $interceptions !== null ? $interceptions : 0,
              'tackles' => $tackles !== null ? $tackles : 0,
              'clearances' => $clearances !== null ? $clearances : 0,
              'passes' => $passes !== null ? $passes : 0
          ];

          $out[] = [
              'match_id' => $id,
              'match_date' => $date,
              'rating' => (float) $rating,
              'opponent' => $opp,
              'metrics' => $metrics
          ];
      }
      return $out;
  };

  $payload = ['last5' => $norm($source), 'season' => $norm($season_source)];
  echo json_encode($payload, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
?>;

/* ==== Dynamic script loader ==== */
function loadScript(url) {
  return new Promise(function(resolve, reject) {
    if (document.querySelector('script[src="' + url + '"]')) return resolve();
    var s = document.createElement('script');
    s.src = url;
    s.async = true;
    s.onload = function(){ resolve(); };
    s.onerror = function(){ reject(new Error('Failed to load ' + url)); };
    document.head.appendChild(s);
  });
}

/* ==== Helpers (date formatting) ==== */
function fmtShortDate(d) {
  if (!d) return 'N/A';
  var dt = new Date(d);
  if (isNaN(dt.getTime())) {
    dt = new Date((d + '').replace(/-/g,'/'));
    if (isNaN(dt.getTime())) return d;
  }
  var mo = dt.toLocaleString(undefined, { month: 'short' });
  return mo + ' ' + ('0' + dt.getDate()).slice(-2);
}
function fmtFullDate(d) {
  if (!d) return 'N/A';
  var dt = new Date(d);
  if (isNaN(dt.getTime())) dt = new Date((d + '').replace(/-/g,'/'));
  if (isNaN(dt.getTime())) return d;
  return dt.toLocaleString();
}

/* ==== Find placeholder for Performance Trend (by heading) ==== */
function findPerformanceTrendPlaceholder() {
  var headings = document.querySelectorAll('h3');
  for (var i = 0; i < headings.length; i++) {
    var txt = (headings[i].innerText || headings[i].textContent || '').toLowerCase();
    if (txt.indexOf('performance trend') !== -1) {
      var sec = headings[i].closest('div') || headings[i].parentElement;
      if (!sec) return null;
      // find inner content placeholder (the element with class h-40 or the first inner div)
      var placeholder = sec.querySelector('.h-40') || sec.querySelector('div.h-40') || sec.querySelector('div');
      return { section: sec, placeholder: placeholder };
    }
  }
  return null;
}

/* ==== Build dataset while sorting by date ascending (oldest first) ==== */
function makeChartDataset(matches) {
  var mapped = matches.map(function(m, idx) {
    var ts = NaN;
    if (m && m.match_date) {
      var d = new Date(m.match_date);
      if (!isNaN(d.getTime())) ts = d.getTime();
      else {
        var d2 = new Date((m.match_date + '').replace(/-/g, '/'));
        if (!isNaN(d2.getTime())) ts = d2.getTime();
      }
    }
    return { original: m, idx: idx, ts: ts };
  });

  mapped.sort(function(a,b){
    var aNa = isNaN(a.ts), bNa = isNaN(b.ts);
    if (aNa && bNa) return a.idx - b.idx;
    if (aNa) return 1;
    if (bNa) return -1;
    return a.ts - b.ts;
  });

  var labels = [], data = [], rawMatches = [];
  mapped.forEach(function(item){
    var m = item.original || {};
    labels.push(m.match_date ? fmtShortDate(m.match_date) : (m.match_id ? ('Match ' + m.match_id) : 'Match'));
    data.push(typeof m.rating !== 'undefined' ? Number(m.rating) : 0);
    rawMatches.push(m);
  });

  // ensure at least two points
  if (labels.length === 1) {
    labels.push(labels[0] + ' (prev)');
    data.push(data[0]);
    rawMatches.push(rawMatches[0]);
  }
  if (labels.length === 0) {
    labels = ['No data','No data']; data = [0,0];
    rawMatches = [{match_id:null,match_date:null,rating:0,opponent:null},{match_id:null,match_date:null,rating:0,opponent:null}];
  }

  return { labels: labels, data: data, rawMatches: rawMatches };
}

/* ==== Render Chart and attach customData for tooltip ==== */
function renderOverallRatingLineChart(datasetObj) {
  var target = findPerformanceTrendPlaceholder();
  if (!target || !target.placeholder) {
    console.warn('Performance Trend placeholder not found.');
    return null;
  }

  var placeholder = target.placeholder;
  placeholder.innerHTML = '';
  var canvas = document.createElement('canvas');
  canvas.id = 'overall-rating-chart';
  canvas.style.width = '100%';
  canvas.style.height = '100%';
  placeholder.appendChild(canvas);

  var ctx = canvas.getContext('2d');

  var cfg = {
    type: 'line',
    data: {
      labels: datasetObj.labels,
      datasets: [{
        label: 'My Overall Rating',
        data: datasetObj.data,
        tension: 0.35,
        fill: false,
        borderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6,
      }]
    },
    options: {
      maintainAspectRatio: false,
      responsive: true,
      scales: {
        x: { display: true, grid: { display: false } },
        y: {
          display: true,
          suggestedMin: 0,
          suggestedMax: 10,
          ticks: { stepSize: 1 }
        }
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            title: function(context) {
              return context[0] && context[0].label ? context[0].label : '';
            },
            label: function(ctx) {
              var chart = ctx.chart;
              var idx = ctx.dataIndex;
              var match = (chart && chart.customData && chart.customData[idx]) ? chart.customData[idx] : null;
              var lines = [];
              if (match && match.opponent) lines.push('vs ' + match.opponent);
              var val = ctx.formattedValue ?? ctx.raw;
              lines.push('Rating: ' + val);
              return lines;
            }
          }
        }
      },
      interaction: { mode: 'index', intersect: false }
    }
  };

  var chartInstance = new Chart(ctx, cfg);
  chartInstance.customData = datasetObj.rawMatches || [];
  return chartInstance;
}

/* ==== Wire toggles (Last5 / Season) and update chart.customData (updated) ==== */
function wirePerformanceToggles(chartInstance, rawData) {
  if (!chartInstance || !rawData) return;

  // find only the performance-trend buttons inside the Performance Trend section
  var target = findPerformanceTrendPlaceholder();
  if (!target || !target.section) return;
  var btns = Array.from(target.section.querySelectorAll('button[data-mode]'));
  if (!btns || btns.length === 0) return;

  // helper to apply visual active/inactive state using palette utility classes
  function setActiveButton(mode) {
    btns.forEach(function(b){
      var isActive = (b.dataset.mode === mode);
      if (isActive) {
        b.classList.add('bg-primary');
        b.classList.remove('bg-surface');
        b.classList.remove('text-muted');
        b.classList.add('text-white');
        b.setAttribute('aria-pressed', 'true');
      } else {
        b.classList.remove('bg-primary');
        b.classList.remove('text-white');
        b.classList.add('bg-surface');
        b.classList.add('text-muted');
        b.setAttribute('aria-pressed', 'false');
      }
    });
  }

  // updater that swaps the chart dataset
  function updateMode(mode) {
    try {
      var ds = (mode === 'season') ? makeChartDataset(rawData.season) : makeChartDataset(rawData.last5);
      chartInstance.data.labels = ds.labels;
      chartInstance.data.datasets[0].data = ds.data;
      chartInstance.customData = ds.rawMatches || [];
      chartInstance.update();
      setActiveButton(mode);
    } catch (e) {
      console.error('Failed to update performance mode:', e);
    }
  }

  // attach click handlers
  btns.forEach(function(b){
    b.addEventListener('click', function(ev){
      var mode = (b.dataset.mode || '').toString();
      if (!mode) return;
      updateMode(mode);
    });
    // keyboard activation for accessibility
    b.addEventListener('keydown', function(ev){
      if (ev.key === 'Enter' || ev.key === ' ') {
        ev.preventDefault();
        b.click();
      }
    });
  });

  // initialize: prefer a button that already has bg-primary else default to last5
  var initialBtn = btns.find(b => b.classList.contains('bg-primary')) || btns.find(b => b.getAttribute('aria-pressed') === 'true') || btns[0];
  var initialMode = initialBtn ? initialBtn.dataset.mode : 'last5';
  updateMode(initialMode);
}

/* ==== Init Chart.js (loads CDN if needed) ==== */
(function init() {
  var payload = window.__DASHBOARD_CHART_DATA__ || { last5: [], season: [] };
  var cdn = 'https://cdn.jsdelivr.net/npm/chart.js';

  var ensure = (typeof Chart !== 'undefined') ? Promise.resolve() : loadScript(cdn);
  ensure.then(function(){
    if (typeof Chart === 'undefined') {
      console.error('Chart.js not available.');
      return;
    }
    try {
      var initialSource = (payload.last5 && payload.last5.length) ? payload.last5 : payload.season;
      var ds = makeChartDataset(initialSource);
      var chart = renderOverallRatingLineChart(ds);
      if (chart) wirePerformanceToggles(chart, payload);
    } catch (e) {
      console.error('Error rendering Overall Rating chart:', e);
    }
  }).catch(function(err){
    console.error('Failed to load Chart.js:', err);
  });
})();

/* ==== Event Distribution tabs wiring ==== */
function wireEventDistributionTabs() {
  var root = document.getElementById('event-distribution');
  if (!root) return;

  var buttons = Array.from(root.querySelectorAll('.tab-btn'));
  var panels = Array.from(root.querySelectorAll('.tab-panel'));

  function setActive(tabKey) {
    buttons.forEach(function(btn){
      var isActive = (btn.getAttribute('data-tab') === tabKey);
      btn.setAttribute('aria-selected', isActive ? 'true' : 'false');

      if (isActive) {
        btn.classList.remove('border-transparent', 'text-gray-400');
        btn.classList.add('border-indigo-500', 'text-indigo-400', 'font-medium');
      } else {
        btn.classList.add('border-transparent', 'text-gray-400');
        btn.classList.remove('border-indigo-500', 'text-indigo-400', 'font-medium');
      }
    });

    panels.forEach(function(p){
      if (p.getAttribute('data-panel') === tabKey) {
        // show
        p.classList.remove('hidden', 'opacity-0', 'pointer-events-none');
        // small delay to ensure transition runs
        window.requestAnimationFrame(function(){ p.classList.add('opacity-100'); });
      } else {
        // hide
        p.classList.remove('opacity-100');
        p.classList.add('opacity-0', 'pointer-events-none');
        // after transition, hide to remove from flow
        setTimeout(function(){
          if (p.classList.contains('opacity-0')) p.classList.add('hidden');
        }, 220);
      }
    });
  }

  // click handlers
  buttons.forEach(function(btn){
    btn.addEventListener('click', function(ev){
      var key = btn.getAttribute('data-tab');
      setActive(key);
    });

    // keyboard navigation (Left/Right arrows)
    btn.addEventListener('keydown', function(ev){
      if (ev.key === 'ArrowRight' || ev.key === 'ArrowLeft') {
        ev.preventDefault();
        var idx = buttons.indexOf(btn);
        var next = (ev.key === 'ArrowRight') ? (idx + 1) : (idx - 1);
        if (next < 0) next = buttons.length - 1;
        if (next >= buttons.length) next = 0;
        buttons[next].focus();
        setActive(buttons[next].getAttribute('data-tab'));
      }
    });
  });

  // init: find first aria-selected=true, else default to first button
  var initial = buttons.find(b => b.getAttribute('aria-selected') === 'true') || buttons[0];
  if (initial) setActive(initial.getAttribute('data-tab'));
}

// invoke immediately (script is at end of body)
try { wireEventDistributionTabs(); } catch (e) { console.error('Event tabs init failed', e); }

(function() {
  var payload = window.__DASHBOARD_CHART_DATA__ || { last5: [], season: [] };

  // --- NEW helpers: robust date parse + sort oldest-first (ascending) ---
  function parseDateSafe(d) {
    if (!d) return null;
    var dt = new Date(d);
    if (!isNaN(dt.getTime())) return dt;
    // try replacing '-' with '/' for cross-browser parsing
    try {
      var alt = new Date((d + '').replace(/-/g, '/'));
      if (!isNaN(alt.getTime())) return alt;
    } catch (e) {}
    return null;
  }

  function sortMatchesAsc(arr) {
    if (!Array.isArray(arr)) return [];
    // copy so we don't mutate original payload
    return arr.slice().sort(function(a, b) {
      var ad = parseDateSafe(a && a.match_date ? a.match_date : null);
      var bd = parseDateSafe(b && b.match_date ? b.match_date : null);
      var at = ad ? ad.getTime() : (a && a.match_id ? Number(a.match_id) : 0);
      var bt = bd ? bd.getTime() : (b && b.match_id ? Number(b.match_id) : 0);
      return at - bt;
    });
  }
  // ---------------------------------------------------------------------

  // helper: format labels from matches (unchanged)
  function matchLabels(matches) {
    return matches.map(function(m) {
      if (!m) return 'N/A';
      if (m.match_date) {
        var d = parseDateSafe(m.match_date);
        if (!isNaN(d && d.getTime())) return (d.toLocaleString(undefined, { month: 'short' }) + ' ' + ('0' + d.getDate()).slice(-2));
      }
      return m.match_id ? ('M' + m.match_id) : 'Match';
    });
  }

  // create or return canvas element inside panel
  function ensureCanvas(panelEl, canvasId) {
    var existing = panelEl.querySelector('canvas[data-cid="' + canvasId + '"]');
    if (existing) return existing;
    var canvas = document.createElement('canvas');
    canvas.setAttribute('data-cid', canvasId);
    canvas.style.width = '100%';
    canvas.style.height = '100%';
    // empty panel then append canvas
    panelEl.innerHTML = '';
    panelEl.appendChild(canvas);
    return canvas;
  }

  // create Attack (bar) chart - shots vs shots_on_target
  function createAttackChart(panelEl, matches) {
    var canvas = ensureCanvas(panelEl, 'attack-chart');
    var ctx = canvas.getContext('2d');
    var labels = matchLabels(matches);
    var shots = matches.map(function(m){ return (m && m.metrics) ? Number(m.metrics.shots || 0) : 0; });
    var sot = matches.map(function(m){ return (m && m.metrics) ? Number(m.metrics.shots_on_target || 0) : 0; });

    return new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          { label: 'Shots', data: shots, stack: 'a', backgroundColor: undefined },
          { label: 'On Target', data: sot, stack: 'a', backgroundColor: undefined }
        ]
      },
      options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: true, position: 'bottom', labels: { boxWidth: 10 } } },
        scales: { x: { stacked: true }, y: { stacked: true, suggestedMin: 0 } }
      }
    });
  }

  // create Possession (line) chart
  function createPossessionChart(panelEl, matches) {
    var canvas = ensureCanvas(panelEl, 'possession-chart');
    var ctx = canvas.getContext('2d');
    var labels = matchLabels(matches);
    var poss = matches.map(function(m){ return (m && m.metrics) ? Number(m.metrics.possession || 0) : 0; });

    return new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: 'Possession %',
          data: poss,
          fill: true,
          tension: 0.3,
          pointRadius: 3,
          borderWidth: 2,
          backgroundColor: undefined,
          borderColor: undefined
        }]
      },
      options: {
        maintainAspectRatio: false,
        scales: {
          y: { suggestedMin: 0, suggestedMax: 100, ticks: { stepSize: 10 } }
        },
        plugins: { legend: { display: true, position: 'bottom' } }
      }
    });
  }

  // create Defense (stacked) chart: interceptions / tackles / clearances
  function createDefenseChart(panelEl, matches) {
    var canvas = ensureCanvas(panelEl, 'defense-chart');
    var ctx = canvas.getContext('2d');
    var labels = matchLabels(matches);
    var ints = matches.map(function(m){ return (m && m.metrics) ? Number(m.metrics.interceptions || 0) : 0; });
    var tks = matches.map(function(m){ return (m && m.metrics) ? Number(m.metrics.tackles || 0) : 0; });
    var cls = matches.map(function(m){ return (m && m.metrics) ? Number(m.metrics.clearances || 0) : 0; });

    return new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          { label: 'Interceptions', data: ints, stack: 'a', backgroundColor: undefined },
          { label: 'Tackles', data: tks, stack: 'a', backgroundColor: undefined },
          { label: 'Clearances', data: cls, stack: 'a', backgroundColor: undefined }
        ]
      },
      options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: true, position: 'bottom' } },
        scales: { x: { stacked: true }, y: { stacked: true, suggestedMin: 0 } }
      }
    });
  }

  // wire into your existing tab system (assumes the tab panels exist with data-panel attr)
  function initTabCharts() {
    var root = document.getElementById('event-distribution');
    if (!root) return;
    var panels = {
      attack: root.querySelector('[data-panel="attack"]'),
      possession: root.querySelector('[data-panel="possession"]'),
      defense: root.querySelector('[data-panel="defense"]')
    };
    if (!panels.attack || !panels.possession || !panels.defense) return;

    // use last5 by default, but SORT ascending so oldest is first (left)
    var matchesRaw = (payload && payload.last5 && payload.last5.length) ? payload.last5 : payload.season;
    var matches = sortMatchesAsc(matchesRaw);

    // store chart refs so we instantiate once
    var charts = { attack: null, possession: null, defense: null };

    // helper to instantiate based on tab
    function ensureChartsFor(tabKey) {
      if (tabKey === 'attack' && !charts.attack) {
        charts.attack = createAttackChart(panels.attack, matches);
      } else if (tabKey === 'possession' && !charts.possession) {
        charts.possession = createPossessionChart(panels.possession, matches);
      } else if (tabKey === 'defense' && !charts.defense) {
        charts.defense = createDefenseChart(panels.defense, matches);
      }
    }

    // initial active panel (the one not hidden)
    var initial = Object.keys(panels).find(k => !panels[k].classList.contains('hidden')) || 'attack';
    ensureChartsFor(initial);

    // watch for tab shows: MutationObserver to catch class changes (hidden -> visible)
    var obs = new MutationObserver(function(muts){
      muts.forEach(function(m){
        if (m.type === 'attributes' && m.attributeName === 'class') {
          var target = m.target;
          if (!target.classList.contains('hidden')) {
            var key = target.getAttribute('data-panel');
            ensureChartsFor(key);
            // if chart exists, call resize to fit container
            var ch = charts[key];
            if (ch && typeof ch.resize === 'function') ch.resize();
            if (ch) ch.update();
          }
        }
      });
    });

    // observe each panel
    Object.values(panels).forEach(function(p){ obs.observe(p, { attributes: true }); });
  }

  // ensure Chart.js loaded then init
  function waitForChartThen(fn) {
    if (typeof Chart !== 'undefined') return fn();
    // simple poll (Chart.js is loaded by your main init)
    var t = setInterval(function(){
      if (typeof Chart !== 'undefined') {
        clearInterval(t);
        fn();
      }
    }, 80);
    setTimeout(function(){ clearInterval(t); }, 5000);
  }

  waitForChartThen(initTabCharts);
})();
</script>