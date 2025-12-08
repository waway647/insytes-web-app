<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * application/views/admin/dashboard.php
 *
 * Balanced layout, consistent card sizing, and scrollable recent lists.
 *
 * Expects same variables as before:
 *  - $kpis, $user_signups, $logs_daily, $recent_users, $recent_logs,
 *    $role_distribution, $active_teams
 */

/* --- safe defaults --- */
$kpis = isset($kpis) && is_array($kpis) ? $kpis : [];
$kpis = array_merge([
    'total_users' => 0,
    'active_users' => 0,
    'new_users_30d' => 0,
    'total_teams' => 0,
    'new_teams_30d' => 0,
    'events_24h' => 0,
    'errors_warnings_24h' => 0,
    'last_refreshed' => date('Y-m-d H:i:s')
], $kpis);

/* --- helpers (unchanged) --- */
function human_time_ago($ts) {
    if (!$ts) return '';
    $t = is_numeric($ts) ? $ts : strtotime($ts);
    $diff = time() - $t;
    if ($diff < 60) return $diff . 's ago';
    if ($diff < 3600) return round($diff/60) . 'm ago';
    if ($diff < 86400) return round($diff/3600) . 'h ago';
    return round($diff/86400) . 'd ago';
}

function build_sparkline_svg($rows, $valueKey, $width = 560, $height = 96, $stroke = '#9CA3AF') {
    if (empty($rows)) {
        return '<div class="text-sm text-gray-500">No data</div>';
    }
    $values = array_map(function($r) use ($valueKey) {
        return (int)$r[$valueKey];
    }, $rows);

    $max = max($values);
    $min = min($values);
    $count = count($values);
    if ($max === $min) $max = $min + 1;

    $padX = 6; $padY = 6;
    $usableW = $width - ($padX * 2);
    $usableH = $height - ($padY * 2);

    $points = [];
    foreach ($values as $i => $v) {
        $x = $padX + ($i / max(1, $count - 1)) * $usableW;
        $y = $padY + ($max - $v) / ($max - $min) * $usableH;
        $points[] = round($x,2) . ',' . round($y,2);
    }
    $poly = implode(' ', $points);
    $last = end($points);
    list($lx, $ly) = explode(',', $last);

    $svg = '<svg viewBox="0 0 ' . $width . ' ' . $height . '" class="w-full h-auto">';
    $svg .= '<defs>
        <linearGradient id="g" x1="0" x2="0" y1="0" y2="1">
          <stop offset="0%" stop-opacity="0.18" stop-color="' . htmlspecialchars($stroke) . '"/>
          <stop offset="100%" stop-opacity="0.02" stop-color="' . htmlspecialchars($stroke) . '"/>
        </linearGradient>
      </defs>';
    $areaPoints = $poly . ' ' . ($padX + $usableW) . ',' . ($padY + $usableH) . ' ' . $padX . ',' . ($padY + $usableH);
    $svg .= '<polygon points="' . $areaPoints . '" fill="url(#g)"/>';
    $svg .= '<polyline fill="none" stroke="' . htmlspecialchars($stroke) . '" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" points="' . $poly . '" />';
    $svg .= '<circle cx="' . $lx . '" cy="' . $ly . '" r="3" fill="' . htmlspecialchars($stroke) . '" />';
    $svg .= '</svg>';
    return $svg;
}
?>

<div class="p-6">

  <!-- Header -->
  <header class="flex items-start justify-between mb-6 gap-6">
    <div>
      <h1 class="text-3xl font-semibold text-white">Dashboard</h1>
      <p class="text-sm text-gray-400 mt-1">Overview of users, teams and system activity.</p>
    </div>

    <div class="flex items-center gap-4 text-sm text-gray-300">
      <?php if (!empty($kpis['last_refreshed'])): ?>
        <div title="Last refreshed">Last refresh: <span class="font-medium text-white"><?php echo htmlspecialchars($kpis['last_refreshed'], ENT_QUOTES, 'UTF-8'); ?></span></div>
      <?php endif; ?>
    </div>
  </header>

  <!-- KPI Cards: balanced height & spacing -->
  <section aria-labelledby="kpi-heading" class="mb-8">
    <h2 id="kpi-heading" class="sr-only">Key metrics</h2>

    <div class="grid grid-cols-12 gap-4">
      <?php
      $cards = [
        ['label'=>'Total Users','value'=>$kpis['total_users'],'meta'=>'users','cols'=>'col-span-12 sm:col-span-6 lg:col-span-3','muted'=>"All registered accounts including Admin"],
        ['label'=>'Active Users','value'=>$kpis['active_users'],'meta'=>'active','cols'=>'col-span-12 sm:col-span-6 lg:col-span-3','muted'=>"Users with active status"],
        ['label'=>'New Signups (30d)','value'=>$kpis['new_users_30d'],'meta'=>'30d','cols'=>'col-span-12 sm:col-span-6 lg:col-span-3','muted'=>'Signups in the last 30 days'],
        ['label'=>'Total Teams','value'=>$kpis['total_teams'],'meta'=>'teams','cols'=>'col-span-12 sm:col-span-6 lg:col-span-3','muted'=>'All created teams'],
      ];
      foreach ($cards as $c):
      ?>
        <div class="<?php echo $c['cols']; ?>">
          <!-- force equal card height: h-28 on small, h-32 on md+ -->
          <div class="bg-[#1D1D1D]/95 border border-[#2a2a2a] rounded-2xl p-5 shadow-sm backdrop-blur-sm flex flex-col justify-between h-28 sm:h-32">
            <div>
              <div class="text-xs text-gray-400"><?php echo $c['label']; ?></div>
              <div class="mt-2 text-2xl font-semibold text-white"><?php echo number_format((int)$c['value']); ?></div>
            </div>
            <div class="mt-2 text-xs text-gray-500"><?php echo $c['muted']; ?></div>
          </div>
        </div>
      <?php endforeach; ?>

      <!-- Row 2 special cards (match height with KPI row) -->
      <div class="col-span-12 sm:col-span-6 lg:col-span-4">
        <div class="bg-[#1D1D1D]/95 border border-[#2a2a2a] rounded-2xl p-5 shadow-sm backdrop-blur-sm h-32 flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-xs text-gray-400">New Teams (30d)</div>
              <div class="mt-2 text-xl font-semibold text-white"><?php echo number_format((int)$kpis['new_teams_30d']); ?></div>
            </div>
            <div class="text-sm text-gray-400">30d</div>
          </div>
          <div class="text-xs text-gray-500">Teams created in the last 30 days</div>
        </div>
      </div>

      <div class="col-span-12 sm:col-span-6 lg:col-span-4">
        <div class="bg-[#1D1D1D]/95 border border-[#2a2a2a] rounded-2xl p-5 shadow-sm backdrop-blur-sm h-32 flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-xs text-gray-400">System Events (24h)</div>
              <div class="mt-2 text-xl font-semibold text-white"><?php echo number_format((int)$kpis['events_24h']); ?></div>
            </div>
            <div class="text-sm text-gray-400">24h</div>
          </div>
          <div class="text-xs text-gray-500">Total log entries in the last 24 hours</div>
        </div>
      </div>

      <div class="col-span-12 sm:col-span-12 lg:col-span-4">
        <div class="bg-[#1D1D1D]/95 border border-[#2a2a2a] rounded-2xl p-5 shadow-sm backdrop-blur-sm h-32 flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-xs text-gray-400">Errors / Warnings (24h)</div>
              <div class="mt-2 text-xl font-semibold <?php echo ((int)$kpis['errors_warnings_24h'] > 0) ? 'text-amber-400' : 'text-white'; ?>">
                <?php echo number_format((int)$kpis['errors_warnings_24h']); ?>
              </div>
            </div>
            <div class="text-sm text-gray-400">24h</div>
          </div>
          <div class="text-xs text-gray-500">Detected errors or warnings in logs</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Main content: Analytics and Recent Activity -->
  <section class="mt-6">
    <div class="grid grid-cols-12 gap-4 items-stretch">

      <!-- Analytics / Charts area -->
      <div class="col-span-12 lg:col-span-8">
        <div class="bg-[#1D1D1D]/95 border border-[#2a2a2a] rounded-2xl p-6 shadow-sm backdrop-blur-sm min-h-[360px] h-full flex flex-col">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-white">Main analytics</h3>
            <div class="text-sm text-gray-400">(Last 30 days)</div>
          </div>

          <div class="grid grid-cols-1 gap-4 flex-1">
            <!-- Signups sparkline -->
            <div class="p-4 bg-[#111111] border border-[#2a2a2a] rounded-lg flex flex-col flex-1">
              <div class="flex items-center justify-between mb-2">
                <div>
                  <div class="text-xs text-gray-400">Daily signups</div>
                  <div class="text-sm font-semibold text-white mt-1"><?php echo number_format(array_sum(array_map(function($r){return (int)$r['signups'];}, $user_signups ?? []))); ?> total</div>
                </div>
                <div class="text-xs text-gray-400"><?php echo !empty($user_signups) ? htmlspecialchars(end($user_signups)['day']) : ''; ?></div>
              </div>
              <div class="mt-2 flex-1">
                <?php echo build_sparkline_svg($user_signups ?? [], 'signups', 720, 120); ?>
              </div>
            </div>

            <!-- Logs sparkline -->
            <div class="p-4 bg-[#111111] border border-[#2a2a2a] rounded-lg flex flex-col flex-1">
              <div class="flex items-center justify-between mb-2">
                <div>
                  <div class="text-xs text-gray-400">Daily logs</div>
                  <div class="text-sm font-semibold text-white mt-1"><?php echo number_format(array_sum(array_map(function($r){return (int)$r['logs_count'];}, $logs_daily ?? []))); ?> total</div>
                </div>
                <div class="text-xs text-gray-400"><?php echo !empty($logs_daily) ? htmlspecialchars(end($logs_daily)['day']) : ''; ?></div>
              </div>
              <div class="mt-2 flex-1">
                <?php echo build_sparkline_svg($logs_daily ?? [], 'logs_count', 720, 120); ?>
              </div>
            </div>

            <div class="mt-4 grid grid-cols-3 gap-3">
              <div class="bg-[#111111] border border-[#2a2a2a] rounded-lg p-3 text-center">
                <div class="text-xs text-gray-400">Conversion</div>
                <div class="text-sm font-semibold text-white mt-1">3.4%</div>
              </div>
              <div class="bg-[#111111] border border-[#2a2a2a] rounded-lg p-3 text-center">
                <div class="text-xs text-gray-400">Avg. Session</div>
                <div class="text-sm font-semibold text-white mt-1">4m 12s</div>
              </div>
              <div class="bg-[#111111] border border-[#2a2a2a] rounded-lg p-3 text-center">
                <div class="text-xs text-gray-400">Bounce</div>
                <div class="text-sm font-semibold text-white mt-1">27%</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right column: compressed recent lists + bottom small cards pinned to bottom -->
      <aside class="col-span-12 lg:col-span-4 h-full">
        <!-- Make right column stretch to match analytics and use vertical flex layout -->
        <div class="flex flex-col gap-4 h-full">

          <!-- Top: recent lists (takes available space, each list scrolls) -->
          <div class="flex-1 flex flex-col gap-4 overflow-hidden">
            <!-- Recent users: flexible scroll area -->
            <div class="bg-[#1D1D1D]/95 border border-[#2a2a2a] rounded-2xl p-3 shadow-sm backdrop-blur-sm flex flex-col flex-1 overflow-hidden max-h-80">
              <div class="flex items-center justify-between mb-2">
                <h3 class="text-lg font-medium text-white">Recent users</h3>
                <a href="<?php echo site_url('admin/users'); ?>" class="text-xs px-2 py-1 rounded-lg bg-[#1D1D1D] border border-gray-600 text-gray-300 hover:bg-gray-800">View all</a>
              </div>

              <!-- flexible scroll region -->
              <div class="overflow-y-auto pr-2 custom-scroll flex-1">
                <div class="space-y-2">
                  <?php if (!empty($recent_users)): ?>
                    <?php foreach ($recent_users as $u): ?>
                      <?php
                        $fullname = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
                        if ($fullname === '') $fullname = $u['email'] ?? 'Unknown';
                      ?>
                      <div class="grid grid-cols-12 items-center gap-3 p-2 rounded-md hover:bg-[#111111]">
                        <div class="col-span-2">
                          <div class="h-9 w-9 rounded-full bg-gray-800 flex items-center justify-center text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A9 9 0 1 1 18.879 6.196 9 9 0 0 1 5.121 17.804z" />
                            </svg>
                          </div>
                        </div>
                        <div class="col-span-7">
                          <div class="text-sm text-white font-medium"><?php echo htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8'); ?></div>
                          <div class="text-xs text-gray-400"><?php echo htmlspecialchars($u['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?> · <?php echo date('M j, Y', strtotime($u['created_at'] ?? 'now')); ?></div>
                        </div>
                        <div class="col-span-3 text-right text-xs text-gray-400"><?php echo human_time_ago($u['created_at'] ?? time()); ?></div>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <div class="text-sm text-gray-500 p-2">No recent users</div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Recent logs: flexible scroll area -->
            <div class="bg-[#1D1D1D]/95 border border-[#2a2a2a] rounded-2xl p-3 shadow-sm backdrop-blur-sm flex flex-col flex-1 overflow-hidden max-h-80">
              <div class="flex items-center justify-between mb-2">
                <h3 class="text-lg font-medium text-white">Recent logs</h3>
                <a href="<?php echo site_url('admin/logs'); ?>" class="text-xs px-2 py-1 rounded-lg bg-[#1D1D1D] border border-gray-600 text-gray-300 hover:bg-gray-800">View all</a>
              </div>

              <div class="overflow-y-auto pr-2 custom-scroll flex-1">
                <div class="space-y-2">
                  <?php if (!empty($recent_logs)): ?>
                    <?php foreach ($recent_logs as $l): ?>
                      <div class="grid grid-cols-12 items-start gap-3 p-2 rounded-md hover:bg-[#111111]">
                        <div class="col-span-2">
                          <div class="h-9 w-9 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 text-xs font-semibold">
                            <?php echo htmlspecialchars(strtoupper(substr($l['category'] ?? 'N',0,1)), ENT_QUOTES, 'UTF-8'); ?>
                          </div>
                        </div>
                        <div class="col-span-7">
                          <div class="text-sm text-white font-medium"><?php echo htmlspecialchars($l['action'] ?? ($l['message'] ?? 'Log'), ENT_QUOTES, 'UTF-8'); ?></div>
                          <div class="text-xs text-gray-400"><?php echo htmlspecialchars(substr($l['message'] ?? '',0,100), ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="col-span-3 text-right text-xs text-gray-400"><?php echo human_time_ago($l['created_at'] ?? time()); ?></div>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <div class="text-sm text-gray-500 p-2">No recent logs</div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom row pinned to bottom: active teams + role distribution -->
          <div class="grid grid-cols-2 gap-4 mt-auto">
            <!-- Active teams -->
            <div class="bg-[#1D1D1D]/95 border border-[#2a2a2a] rounded-2xl p-3 shadow-sm">
              <div class="flex items-center justify-between mb-2">
                <h3 class="text-md font-medium text-white">Active teams</h3>
                <div class="text-xs text-gray-400">Top</div>
              </div>

              <div class="overflow-y-auto max-h-40">
                <div class="space-y-2 text-sm">
                  <?php if (!empty($active_teams)): ?>
                    <?php foreach (array_slice($active_teams, 0, 6) as $t): ?>
                      <div class="flex items-center justify-between">
                        <div class="text-sm text-white"><?php echo htmlspecialchars($t['team_name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="text-xs text-gray-400"><?php echo (int)$t['active_members_count']; ?> members</div>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <div class="text-sm text-gray-500">No active teams</div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Role distribution -->
            <div class="bg-[#1D1D1D]/95 border border-[#2a2a2a] rounded-2xl p-3 shadow-sm">
              <div class="flex items-center justify-between mb-2">
                <h3 class="text-md font-medium text-white">Role distribution</h3>
                <div class="text-xs text-gray-400">Counts</div>
              </div>

              <div class="overflow-y-auto max-h-40">
                <div class="space-y-2 text-sm">
                  <?php if (!empty($role_distribution)): ?>
                    <?php foreach ($role_distribution as $r): ?>
                      <div class="flex items-center justify-between">
                        <div class="text-gray-300"><?php echo htmlspecialchars($r['role'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="text-white font-medium"><?php echo (int)$r['cnt']; ?></div>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <div class="text-sm text-gray-500">No roles found</div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>

        </div>
      </aside>

    </div>
  </section>
</div>
