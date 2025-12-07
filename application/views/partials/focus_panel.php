<!-- partials/focus_panel.php -->
<div id="focus-panel-wrapper" class="h-full w-120 flex-shrink-0 flex flex-col px-4 py-6 bg-[#1a1a1a] rounded-2xl overflow-auto custom-scroll">
  <div id="focus-panel" class="w-full text-sm text-gray-200">

    <!-- compact initial summary (top of focus panel) -->
    <div id="focus-initial-summary" class="w-full text-sm text-gray-200 mb-4">
      <div class="flex items-start justify-between">
        <div>
          <div class="text-lg font-semibold text-white">Match summary</div>
          <div class="text-xs text-gray-400 mt-1">Quick highlights — tap a player card to view details</div>
        </div>
      </div>

      <div class="mt-3 flex flex-col gap-3">
        <!-- Top Scorers (table) -->
        <div id="summary-top-scorers-section" class="bg-[#0f1112] rounded-xl p-3 w-full">
          <div class="flex items-center justify-between">
            <div class="text-xs text-gray-400">Top Scorers</div>
            <div class="text-xs text-gray-400">Goals / Shots / Acc</div>
          </div>

          <div class="mt-3 overflow-x-auto">
            <table class="w-full text-sm table-fixed">
              <thead>
                <tr class="text-xs text-gray-400">
                  <th class="py-2 px-2 text-left w-8">#</th>
                  <th class="py-2 px-2 text-left">Name</th>
                  <th class="py-2 px-2 text-right">G/90</th>
                  <th class="py-2 px-2 text-right">S/90</th>
                  <th class="py-2 px-2 text-right">Acc%</th>
                </tr>
              </thead>
              <tbody id="summary-top-scorers">
                <tr><td class="py-2 text-gray-400 text-center" colspan="5">Loading…</td></tr>
              </tbody>
            </table>
          </div>

          <div class="mt-3 text-xs text-gray-400 flex justify-end">
            <div class="text-gray-400 mr-2">Top</div>
            <div id="summary-top-scorers-count" class="text-sm text-white font-semibold">—</div>
          </div>
        </div>

        <!-- Top Defenders (table) -->
        <div id="summary-top-defenders-section" class="bg-[#0f1112] rounded-xl p-3 w-full">
          <div class="flex items-center justify-between">
            <div class="text-xs text-gray-400">Top Defenders</div>
            <div class="text-xs text-gray-400">Tkl / Clr / Int / DefAct</div>
          </div>

          <div class="mt-3 overflow-x-auto">
            <table class="w-full text-sm table-fixed">
              <thead>
                <tr class="text-xs text-gray-400">
                  <th class="py-2 px-2 text-left w-8">#</th>
                  <th class="py-2 px-2 text-left">Name</th>
                  <th class="py-2 px-2 text-right">Tkl/90</th>
                  <th class="py-2 px-2 text-right">Clr/90</th>
                  <th class="py-2 px-2 text-right">Int/90</th>
                  <th class="py-2 px-2 text-right">DefAct/90</th>
                </tr>
              </thead>
              <tbody id="summary-top-defenders">
                <tr><td class="py-2 text-gray-400 text-center" colspan="6">Loading…</td></tr>
              </tbody>
            </table>
          </div>

          <div class="mt-3 text-xs text-gray-400 flex justify-end">
            <div class="text-gray-400 mr-2">Top</div>
            <div id="summary-top-defenders-count" class="text-sm text-white font-semibold">—</div>
          </div>
        </div>

        <!-- Top Passers (table) -->
        <div id="summary-top-passers-section" class="bg-[#0f1112] rounded-xl p-3 w-full">
          <div class="flex items-center justify-between">
            <div class="text-xs text-gray-400">Top Passers</div>
            <div class="text-xs text-gray-400">Passes / Acc / Key / Prog</div>
          </div>

          <div class="mt-3 overflow-x-auto">
            <table class="w-full text-sm table-fixed">
              <thead>
                <tr class="text-xs text-gray-400">
                  <th class="py-2 px-2 text-left w-8">#</th>
                  <th class="py-2 px-2 text-left">Name</th>
                  <th class="py-2 px-2 text-right">Pass/90</th>
                  <th class="py-2 px-2 text-right">Acc</th>
                  <th class="py-2 px-2 text-right">KP/90</th>
                  <th class="py-2 px-2 text-right">Prog/90</th>
                </tr>
              </thead>
              <tbody id="summary-top-passers">
                <tr><td class="py-2 text-gray-400 text-center" colspan="6">Loading…</td></tr>
              </tbody>
            </table>
          </div>

          <div class="mt-3 text-xs text-gray-400 flex justify-end">
            <div class="text-gray-400 mr-2">Top</div>
            <div id="summary-top-passers-count" class="text-sm text-white font-semibold">—</div>
          </div>
        </div>

        <!-- Match recommendations (full width) -->
        <div class="bg-[#0f1112] rounded-xl p-3 col-span-full">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-xs text-gray-400">Match recommendations</div>
              <div id="summary-match-recs" class="mt-3 text-sm text-white">
                Loading…
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Player detail template (hidden when initial summary exists) -->
    <div id="focus-detail-template" style="display:none;">
      <!-- Header: avatar + name + role -->
      <div class="flex items-center gap-4 pb-4 border-b border-neutral-800">
        <div class="flex-shrink-0">
          <div id="focus-avatar" class="w-20 h-20 rounded-full bg-gradient-to-br from-gray-700 to-gray-900 flex items-center justify-center text-2xl font-bold text-white">
            <span id="focus-avatar-initials">JS</span>
          </div>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between">
            <div>
              <div id="focus-fullname" class="text-xl font-semibold text-white truncate">Jul-Andrei Aningalan</div>
              <div id="focus-subtitle" class="text-xs text-gray-400 mt-1 truncate">CB • San Beda University</div>
            </div>
            <div class="ml-4 text-right">
              <div id="focus-rating" class="text-2xl font-extrabold text-white">8.2</div>
              <div class="text-xs text-gray-400">Match rating</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick stats row -->
      <div class="grid grid-cols-2 gap-3 mt-4">
        <div class="bg-[#151617] rounded-lg p-3">
          <div class="text-xs text-gray-400">Position</div>
          <div id="focus-position" class="text-white font-semibold">CB</div>
        </div>
        <div class="bg-[#151617] rounded-lg p-3">
          <div class="text-xs text-gray-400">Jersey</div>
          <div id="focus-jersey" class="text-white font-semibold">14</div>
        </div>
        <div class="bg-[#151617] rounded-lg p-3">
          <div class="text-xs text-gray-400">Minutes</div>
          <div id="focus-minutes" class="text-white font-semibold">30.0</div>
        </div>
        <div class="bg-[#151617] rounded-lg p-3">
          <div class="text-xs text-gray-400">DPR</div>
          <div id="focus-dpr" class="text-white font-semibold">82.0</div>
        </div>
      </div>

      <!-- DPR + DPR breakdown -->
      <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- DPR Card -->
        <div class="bg-gradient-to-b from-[#111213] via-[#0f1314] to-[#0b0c0d] rounded-xl p-4 flex flex-col">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-xs text-gray-400">Predicted DPR</div>
              <div id="focus-predicted-dpr" class="text-3xl font-extrabold text-white">82.0</div>
            </div>
            <div class="text-right">
              <div class="text-xs text-gray-400">Change</div>
              <div id="focus-dpr-change" class="text-sm font-semibold text-green-400">+29.3</div>
            </div>
          </div>

          <div class="mt-4 w-full h-36 bg-[#0b0b0b] rounded-md flex items-center justify-center">
            <canvas id="dpr-chart" class="w-full h-full"></canvas>
          </div>

          <div id="focus-badge" class="mt-3 inline-block px-3 py-1 text-xs rounded-full bg-[#1f2937] text-white">Solid Contributor</div>
        </div>

        <!-- DPR breakdown -->
        <div class="bg-[#0f1112] rounded-xl p-4">
          <div class="text-sm font-semibold text-white mb-3">DPR breakdown</div>
          <div id="focus-dpr-breakdown" class="space-y-2 text-sm">
            <div class="text-gray-500">No DPR breakdown available.</div>
          </div>
        </div>
      </div>

      <!-- Key stats table -->
      <div class="mt-4 bg-[#0f1112] rounded-xl p-4">
        <div class="flex items-center justify-between mb-3">
          <div class="text-sm font-semibold text-white">Key stats (per 90)</div>
          <div class="text-xs text-gray-400">Auto-updated</div>
        </div>
        <div class="max-h-44 overflow-auto custom-scroll pr-4">
          <table class="w-full text-sm">
            <tbody id="focus-key-stats">
              <tr><td class="py-2 text-gray-500">No key stats available.</td><td></td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Notes / Strengths / Development -->
      <div class="mt-4 bg-[#0f1112] rounded-xl p-4">
        <div class="text-sm font-semibold text-white mb-2">Notes & development</div>

        <div id="focus-notes" class="text-sm text-gray-300 leading-relaxed">Select a player to see notes.</div>

        <div class="mt-3">
          <div class="text-xs text-gray-400 mb-2">Strengths</div>
          <div id="focus-strengths" class="flex flex-wrap gap-2">
            <span class="text-xs px-2 py-1 rounded bg-green-900 text-green-200">—</span>
          </div>
        </div>

        <div class="mt-3">
          <div class="text-xs text-gray-400 mb-2">Development areas</div>
          <div id="focus-development" class="flex flex-wrap gap-2">
            <span class="text-xs px-2 py-1 rounded bg-[#17202b] text-gray-400">—</span>
          </div>
        </div>
      </div>

      <!-- Action buttons -->
      <div class="mt-4 flex gap-2">
        <button 
            type="button"
            id="btn-view-profile" 
            data-player-name=""
            class="flex-1 bg-white text-black py-2 rounded-md font-semibold hover:opacity-90 cursor-pointer">
              View full Performance
        </button>
        <button id="btn-compare" class="flex-1 bg-transparent border border-neutral-700 text-white py-2 rounded-md font-semibold hover:bg-neutral-900 cursor-pointer">Compare</button>
        <!-- Export button 
        <button id="btn-export" class="w-12 bg-transparent border border-neutral-700 text-white py-2 rounded-md font-semibold hover:bg-neutral-900 cursor-pointer">⤓</button>
        -->
      </div>

      <!-- Compare tray (insert just after the Action buttons in focus-detail-template) -->
      <div id="compare-tray" class="mt-3 hidden items-center gap-3" aria-live="polite">
        <div class="flex items-center gap-2">
          <div class="text-xs text-gray-400">Compare</div>
          <div id="compare-avatars" class="flex items-center gap-2"></div>
        </div>
        <div class="ml-auto flex items-center gap-2">
          <button id="btn-compare-manage" class="bg-transparent border border-neutral-700 text-white py-1 px-3 rounded-md text-sm hover:bg-neutral-900 disabled:opacity-60" disabled>
            Add current
          </button>
          <button id="btn-compare-now" class="bg-white text-black py-1 px-3 rounded-md text-sm font-semibold disabled:opacity-60" disabled>
            Compare now
          </button>
          <button id="btn-compare-clear" class="bg-transparent text-gray-400 py-1 px-2 rounded-md text-sm hover:bg-neutral-900" title="Clear compare selection" aria-label="Clear compare selection">✕</button>
        </div>
      </div>
    </div>
    <!-- /focus-detail-template -->

    <!-- Compare modal (updated: centered metric names, scrollable, left panel labeled Reference) -->
    <div id="compare-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="compare-modal-title">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" data-close="overlay"></div>

      <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="max-w-5xl w-full bg-[#0b0c0d] rounded-2xl shadow-2xl overflow-hidden">
          <!-- Header -->
          <div class="p-4 border-b border-neutral-800 flex items-center justify-between">
            <div>
              <h2 id="compare-modal-title" class="text-lg font-semibold text-white">Compare players</h2>
              <div id="compare-modal-sub" class="text-xs text-gray-400">Side-by-side view · maximum 2 players</div>
            </div>
            <div class="flex items-center gap-2">
              <button id="btn-compare-export" class="text-sm px-3 py-1 rounded bg-transparent border border-neutral-700 text-white hover:bg-neutral-900" title="Export comparison">Export</button>
              <button id="btn-compare-close" class="text-sm px-3 py-1 rounded bg-transparent text-gray-400 hover:bg-neutral-900" aria-label="Close compare">Close</button>
            </div>
          </div>

          <!-- Player headers -->
          <div class="p-4 grid grid-cols-3 gap-4 items-start">
            <!-- Left: Reference player (distinct visual) -->
            <div id="compare-player-a" class="bg-[#0f1112] rounded-xl p-4 border-2 border-transparent">
              <div class="flex items-start gap-3">
                <div id="compare-a-avatar" class="w-14 h-14 rounded-full bg-gradient-to-br from-gray-700 to-gray-900 flex items-center justify-center text-xl font-bold text-white">A</div>
                <div class="min-w-0">
                  <div class="flex items-center gap-2">
                    <div id="compare-a-name" class="text-sm font-semibold text-white truncate">Player A</div>
                    <span id="compare-a-label" class="ml-2 text-xs px-2 py-0.5 rounded-full bg-green-700 text-green-100">Reference</span>
                  </div>
                  <div id="compare-a-sub" class="text-xs text-gray-400 truncate mt-1">Team / Position</div>
                </div>
                <div class="ml-auto text-right">
                  <div id="compare-a-rating" class="text-lg font-extrabold text-white">—</div>
                  <div class="text-xs text-gray-400">Rating</div>
                </div>
              </div>
              <div class="mt-3">
                <div class="text-xs text-gray-400 mb-1">DPR</div>
                <div id="compare-a-dpr" class="text-2xl font-extrabold text-white">—</div>
              </div>
            </div>

            <!-- Middle: metric heading (visual placeholder; actual metric list appears in the scroll area below) -->
            <div class="flex items-end justify-center h-full">
              <div class="text-xs text-gray-400">Metric (per 90)&nbsp &nbsp &nbsp</div>
            </div>

            <!-- Right: Compared player -->
            <div id="compare-player-b" class="bg-[#0f1112] rounded-xl p-4">
              <div class="flex items-start gap-3">
                <div id="compare-b-avatar" class="w-14 h-14 rounded-full bg-gradient-to-br from-gray-700 to-gray-900 flex items-center justify-center text-xl font-bold text-white">B</div>
                <div class="min-w-0">
                  <div id="compare-b-name" class="text-sm font-semibold text-white truncate">Player B</div>
                  <div id="compare-b-sub" class="text-xs text-gray-400 truncate mt-1">Team / Position</div>
                </div>
                <div class="ml-auto text-right">
                  <div id="compare-b-rating" class="text-lg font-extrabold text-white">—</div>
                  <div class="text-xs text-gray-400">Rating</div>
                </div>
              </div>
              <div class="mt-3">
                <div class="text-xs text-gray-400 mb-1">DPR</div>
                <div id="compare-b-dpr" class="text-2xl font-extrabold text-white">—</div>
              </div>
            </div>
          </div>

          <!-- Stats area: single scroll container so rows align exactly; three columns inside -->
          <div class="px-4 pb-4">
            <div id="compare-stats-scroll" class="w-full max-h-72 overflow-y-auto rounded-md bg-transparent p-2">
              <div class="grid grid-cols-3 gap-4 items-start">
                <!-- Left values (right aligned) -->
                <div id="compare-a-stats" class="space-y-1"></div>

                <!-- Metric names (centered) -->
                <div id="compare-metric-names" class="space-y-1 flex flex-col items-center"></div>

                <!-- Right values (left aligned) -->
                <div id="compare-b-stats" class="space-y-1"></div>
              </div>
            </div>
          </div>

          <!-- Footer with controls -->
          <div class="p-4 border-t border-neutral-800 flex items-center justify-between">
            <div class="text-xs text-gray-400">Tip: remove players from the tray to change comparison. Shift-click a player card to add quickly.</div>
            <div class="flex gap-2">
              <button id="btn-compare-swap" class="text-sm px-3 py-1 rounded bg-transparent border border-neutral-700 text-white hover:bg-neutral-900">Swap sides</button>
              <button id="btn-compare-done" class="text-sm px-3 py-1 rounded bg-white text-black font-semibold">Done</button>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<script src="<?php echo base_url('assets/js/comparePlayer.js') . '?v=' . time(); ?>"></script>

<script>
/*
  CI-friendly base: use a CodeIgniter URL helper to produce proper site URL.
  Ensure this partial is loaded by a CI view so `site_url()` is available.
*/
const CI_BASE_URL = "<?= site_url(); ?>";

(function () {
  // NAV key used to persist target across redirect
  const NAV_KEY = 'focus_navigate_target_player';

  // If there is a pending navigation target (we arrived via redirect), set a global flag
  // immediately so other scripts (like matchSummary rendering) can respect it.
  try {
    const pending = sessionStorage.getItem(NAV_KEY);
    if (pending) {
      // when present, prevent initial summary from immediately overriding focus UI
      window.__prevent_initial_summary_override = true;
      // keep the raw pending value handy for other scripts if useful
      window.__focus_navigation_pending_raw = pending;
    } else {
      window.__prevent_initial_summary_override = false;
      window.__focus_navigation_pending_raw = null;
    }
  } catch (e) {
    // sessionStorage might be blocked in some environments
    window.__prevent_initial_summary_override = false;
    window.__focus_navigation_pending_raw = null;
  }

  // --- helpers ---
  function el(id) { return document.getElementById(id); }
  function escapeHtml(s) {
    return String(s ?? '')
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;')
      .replace(/'/g,'&#039;');
  }

  function showInitialSummary() { const s = el('focus-initial-summary'); if (s) s.style.display = ''; }
  function hideInitialSummary() { const s = el('focus-initial-summary'); if (s) s.style.display = 'none'; }
  function showDetailTemplate() { const d = el('focus-detail-template'); if (d) d.style.display = ''; }
  function hideDetailTemplate() { const d = el('focus-detail-template'); if (d) d.style.display = 'none'; }

  // sanitize a name-like input (remove rating-like tokens and markup)
  function sanitizeNameInput(raw) {
    if (!raw) return '';
    let s = String(raw).replace(/<[^>]*>/g, '').replace(/\u00A0/g, ' ').trim();
    s = s.replace(/^[+\-]?\d{1,2}(?:\.\d)?\s*[-–:—]?\s*/, '');
    s = s.replace(/\s*[-–:—]?\s*[+\-]?\d{1,2}(?:\.\d)?\s*$/, '');
    s = s.replace(/(?:^|[\s,])([+\-]?\d{1,2}(?:\.\d)?)(?=[\s,]|$)/g, ' ').trim();
    s = s.replace(/\s+/g, ' ').replace(/^[,;:\-\s]+|[,;:\-\s]+$/g, '').trim();
    return s;
  }

  function format2(v) {
    if (v === null || v === undefined || Number.isNaN(Number(v)) || v === '') return '—';
    const n = Number(v);
    return n.toFixed(2);
  }

  // Render players table (used for three summary tables)
  function renderPlayersTable(wrapperId, tbodyId, items, metricKeys, metricLabels = {}, limit = 0) {
    const wrapper = document.getElementById(wrapperId);
    if (!wrapper) return;
    const tbody = wrapper.querySelector(`#${tbodyId}`);
    if (!tbody) return;
    tbody.innerHTML = '';

    if (!Array.isArray(items) || items.length === 0) {
      const tdCount = 2 + (metricKeys ? metricKeys.length : 0);
      tbody.innerHTML = `<tr><td class="py-2 text-gray-400 text-center" colspan="${tdCount}">No players</td></tr>`;
      return;
    }

    const list = (limit && limit > 0) ? items.slice(0, limit) : items;

    // build dynamic header if table exists
    const table = wrapper.querySelector('table');
    if (table) {
      const thead = table.querySelector('thead') || (function(){ const th = document.createElement('thead'); table.insertBefore(th, table.firstChild); return th; })();
      const headerCols = ['#', 'Name'].concat(metricKeys.map(k => metricLabels[k] || k.replace(/_/g,' ').replace(/\b\w/g, ch => ch.toUpperCase())));
      thead.innerHTML = `<tr class="text-xs text-gray-400">${headerCols.map(c => `<th class="py-2 px-2 ${c === '#' ? 'w-8' : (c === 'Name' ? 'text-left' : 'text-right')}">${escapeHtml(c)}</th>`).join('')}</tr>`;
    }

    list.forEach((p, i) => {
      const index = i + 1;
      const nameRaw = p.name ?? p.player_name ?? p.raw_name ?? (p.surname ? (String(p.surname)) : '-');
      const name = escapeHtml(nameRaw);

      const metricCells = metricKeys.map(k => {
        const raw = (p.hasOwnProperty(k) ? p[k] : (p[k] === 0 ? 0 : undefined));
        const formatted = format2(raw);
        return `<td class="py-1 px-2 text-right text-white font-semibold">${escapeHtml(formatted)}</td>`;
      }).join('');

      const row = document.createElement('tr');
      row.className = 'hover:bg-neutral-800 cursor-pointer';
      row.setAttribute('data-player-name', String(nameRaw || '').trim());
      row.innerHTML = `<td class="py-1 px-2 text-gray-400 text-center">${index}</td>
                       <td class="py-1 px-2 text-white">${name}</td>
                       ${metricCells}`;
      tbody.appendChild(row);
    });
  }

  // render match recommendations (cards/chips)
  function renderMatchRecs(containerId, recs) {
    const container = el(containerId); if (!container) return;
    container.innerHTML = '';
    if (!recs || typeof recs !== 'object') { container.textContent = 'No recommendations'; return; }
    function badgesHtml(arr, max = 3) {
      if (!Array.isArray(arr) || arr.length === 0) return '<div class="text-gray-400 text-sm">—</div>';
      return '<div class="flex flex-wrap gap-2">' + arr.slice(0, max).map(name => `<span class="text-xs px-2 py-1 rounded-full bg-neutral-800 text-white truncate" title="${escapeHtml(name)}">${escapeHtml(name)}</span>`).join('') + '</div>';
    }
    const must = Array.isArray(recs.must_start) ? recs.must_start : [];
    const rotation = Array.isArray(recs.rotation_candidates) ? recs.rotation_candidates : [];
    const impact = Array.isArray(recs.impact_subs) ? recs.impact_subs : [];
    const dev = Array.isArray(recs.development_opportunities) ? recs.development_opportunities : [];

    const html = `
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div>
          <div class="text-xs text-green-300 font-semibold mb-1">Must start</div>
          <div class="bg-[#0b0d0e] p-2 rounded">${badgesHtml(must, 4)}</div>
          <div class="mt-1 text-xs text-gray-400">Count: <span class="text-white font-semibold">${must.length || 0}</span></div>
        </div>
        <div>
          <div class="text-xs text-amber-300 font-semibold mb-1">Rotation candidates</div>
          <div class="bg-[#0b0d0e] p-2 rounded">${badgesHtml(rotation, 4)}</div>
          <div class="mt-1 text-xs text-gray-400">Count: <span class="text-white font-semibold">${rotation.length || 0}</span></div>
        </div>
        <div>
          <div class="text-xs text-sky-300 font-semibold mb-1">Impact subs</div>
          <div class="bg-[#0b0d0e] p-2 rounded">${badgesHtml(impact, 4)}</div>
          <div class="mt-1 text-xs text-gray-400">Count: <span class="text-white font-semibold">${impact.length || 0}</span></div>
        </div>
        <div>
          <div class="text-xs text-gray-300 font-semibold mb-1">Development opportunities</div>
          <div class="bg-[#0b0d0e] p-2 rounded">${badgesHtml(dev, 4)}</div>
          <div class="mt-1 text-xs text-gray-400">Count: <span class="text-white font-semibold">${dev.length || 0}</span></div>
        </div>
      </div>
    `;
    container.innerHTML = html;
  }

  function summaryHasData(summary) {
    if (!summary) return false;
    if (Array.isArray(summary.top_scorers?.players) && summary.top_scorers.players.length > 0) return true;
    if (Array.isArray(summary.top_defenders?.players) && summary.top_defenders.players.length > 0) return true;
    if (Array.isArray(summary.top_passers?.players) && summary.top_passers.players.length > 0) return true;
    if (summary.match_recommendations && Object.keys(summary.match_recommendations).length > 0) return true;
    return false;
  }

  // render initial summary (calls renderPlayersTable)
  // NOTE: respects window.__prevent_initial_summary_override (set when we arrived after redirect)
  function renderInitialSummary(summary) {
    const scorers = summary?.top_scorers?.players ?? [];
    const defs = summary?.top_defenders?.players ?? [];
    const passers = summary?.top_passers?.players ?? [];
    const recs = summary?.match_recommendations ?? {};

    renderPlayersTable('summary-top-scorers-section', 'summary-top-scorers', scorers,
      ['goals_p90','shots_p90','shot_accuracy_pct'], { goals_p90:'G/90', shots_p90:'S/90', shot_accuracy_pct:'Acc%' }, 5);

    renderPlayersTable('summary-top-defenders-section', 'summary-top-defenders', defs,
      ['tackles_p90','clearances_p90','interceptions_p90','defensive_actions_p90'],
      { tackles_p90:'Tkl/90', clearances_p90:'Clr/90', interceptions_p90:'Int/90', defensive_actions_p90:'DefAct/90' }, 5);

    renderPlayersTable('summary-top-passers-section', 'summary-top-passers', passers,
      ['passes_p90','pass_accuracy','key_passes_p90','progressive_passes_p90'],
      { passes_p90:'Pass/90', pass_accuracy:'Acc', key_passes_p90:'KP/90', progressive_passes_p90:'Prog/90' }, 5);

    if (el('summary-top-scorers-count')) el('summary-top-scorers-count').textContent = (scorers.length || '—');
    if (el('summary-top-defenders-count')) el('summary-top-defenders-count').textContent = (defs.length || '—');
    if (el('summary-top-passers-count')) el('summary-top-passers-count').textContent = (passers.length || '—');

    if (typeof renderMatchRecs === 'function') renderMatchRecs('summary-match-recs', recs);

    // Respect redirect-activation flag to avoid immediately hiding detail template.
    const preventOverride = !!window.__prevent_initial_summary_override;
    if (summaryHasData(summary)) {
      if (!preventOverride) {
        showInitialSummary();
        hideDetailTemplate();
      } else {
        // If override is active, prefer keeping detail open (some other script will trigger the focus).
        hideInitialSummary();
        showDetailTemplate();
      }
    } else {
      // No summary data — show detail template
      hideInitialSummary();
      showDetailTemplate();
    }
  }

  // expose
  window.renderInitialSummary = function (summary) { try { renderInitialSummary(summary); } catch (err) { console.error('renderInitialSummary error', err); } };

  window.addEventListener('matchSummaryLoaded', function (e) { try { renderInitialSummary(e && e.detail ? e.detail : null); } catch (err) { console.error('matchSummaryLoaded handler error', err); } });

  if (typeof window !== 'undefined' && window.matchSummary) {
    try { renderInitialSummary(window.matchSummary); } catch (err) { console.error(err); }
  }

  // ---------------------
  // Focus name helpers and card lookups
  // ---------------------
  function toLookupNameFromFull(full) {
    if (!full) return '';
    const s = String(full).trim();
    if (/,/.test(s) && s.split(',').length >= 2) return s.replace(/\s+/g,' ').trim();
    const parts = s.split(/\s+/).filter(Boolean);
    if (parts.length === 1) return parts[0];
    const last = parts[parts.length - 1];
    const given = parts.slice(0, parts.length - 1).join(' ');
    return `${last}, ${given}`.replace(/\s+/g,' ').trim();
  }
  function fullNameFromLookup(lookup) {
    if (!lookup) return '';
    const s = String(lookup).trim();
    if (!/,/.test(s)) return s;
    const [last, given] = s.split(',',2).map(x => x.trim());
    return (given ? (given + ' ' + last) : last).replace(/\s+/g,' ').trim();
  }
  function initialsFromFullName(name) {
    if (!name) return '';
    let s = String(name).replace(/\s+/g,' ').trim();
    if (s.includes(',')) {
      const [last, given] = s.split(',',2).map(x => x.trim());
      const firstGiven = (given || '').split(/\s+/)[0] || '';
      return ((firstGiven.charAt(0) || '') + (last.charAt(0) || '')).toUpperCase();
    }
    const parts = s.split(/\s+/).filter(Boolean).map(p => p.replace(/[.,]/g,''));
    if (parts.length === 1) return parts[0].slice(0,2).toUpperCase();
    return ( (parts[0].charAt(0) || '') + (parts[parts.length-1].charAt(0) || '') ).toUpperCase();
  }

  function setFocusedPlayer(name, subtitle) {
    if (!name) return;
    const cleanedInput = sanitizeNameInput(name);
    let displayFull = cleanedInput || name;
    if (/,/.test(displayFull) && displayFull.split(',').length >= 2) displayFull = fullNameFromLookup(displayFull);
    const lookup = toLookupNameFromFull(displayFull);
    window.__lastSelectedPlayerName = lookup;
    const focusNameEl = el('focus-fullname');
    const focusInitialsEl = el('focus-avatar-initials');
    const focusSubtitleEl = el('focus-subtitle');
    const profileBtn = el('btn-view-profile');

    if (focusNameEl) focusNameEl.textContent = displayFull;
    if (focusInitialsEl) focusInitialsEl.textContent = initialsFromFullName(displayFull) || '??';
    if (typeof subtitle !== 'undefined' && focusSubtitleEl) focusSubtitleEl.textContent = subtitle || focusSubtitleEl.textContent;
    if (profileBtn) profileBtn.setAttribute('data-player-name', lookup);
  }

  // Format & apply dpr change value into #focus-dpr-change and color it
  function applyDprChange(value) {
    const elChange = el('focus-dpr-change');
    if (!elChange) return;
    try {
      if (value === null || value === undefined || value === '') {
        elChange.textContent = '—';
        elChange.classList.remove('text-green-400','text-red-400','text-gray-400','text-white');
        elChange.classList.add('text-gray-400');
        return;
      }
      // Accept numeric or string like "+12.3" or "-3.2" or "12.3"
      const raw = String(value).trim();
      // Remove percent/extra chars but allow negative/decimal
      const num = Number(raw.replace(/[^0-9\.\-]/g,''));
      if (!Number.isFinite(num)) {
        // not numeric — display as-is neutral
        elChange.textContent = raw;
        elChange.classList.remove('text-green-400','text-red-400');
        elChange.classList.add('text-gray-400');
        return;
      }
      const sign = num > 0 ? '+' : (num < 0 ? '' : ''); // positive show +
      const formatted = (num % 1 === 0) ? `${sign}${num}` : `${sign}${num.toFixed(1)}`;
      elChange.textContent = formatted;
      // update color classes
      elChange.classList.remove('text-gray-400','text-white','text-green-400','text-red-400');
      if (num > 0) elChange.classList.add('text-green-400');
      else if (num < 0) elChange.classList.add('text-red-400');
      else elChange.classList.add('text-white');
    } catch (err) {
      console.warn('applyDprChange error', err);
    }
  }

  // Populate focus panel from a player object (structure matches controller output)
  function populateFocusFromPlayerObject(p) {
    if (!p || typeof p !== 'object') return;
    try {
      // Prefer the canonical name fields
      const full = (p.full_name || p.player_name || p.name || p.raw_name || '').trim();
      const subtitle = (p.position || p.club || p.team || '') + '';
      // update primary focus fields (name, subtitle, initials via existing helper)
      if (full) setFocusedPlayer(full, subtitle);

      // jersey
      if (el('focus-jersey')) el('focus-jersey').textContent = (p.jersey_number || p.jersey || '—');

      // minutes
      if (el('focus-minutes')) {
        const minutes = (p.minutes_played !== undefined && p.minutes_played !== null && p.minutes_played !== '') ? String(p.minutes_played) : (p.minutes || '—');
        el('focus-minutes').textContent = minutes;
      }

      // rating & DPR
      if (el('focus-rating')) el('focus-rating').textContent = (p.rating !== undefined && p.rating !== null && p.rating !== '') ? String(p.rating) : '—';
      if (el('focus-predicted-dpr')) el('focus-predicted-dpr').textContent = ((p.predicted_dpr !== undefined && p.predicted_dpr !== null) ? String(p.predicted_dpr) : (p.dpr !== undefined && p.dpr !== null && p.dpr !== '') ? String(p.dpr) : '—');
      if (el('focus-dpr')) el('focus-dpr').textContent = (p.dpr !== undefined && p.dpr !== null && p.dpr !== '') ? String(p.dpr) : '—';

      // **dpr_change** — primary requirement
      // controller key: 'dpr_change' (also accept camelCase variants)
      const changeVal = (p.dpr_change !== undefined ? p.dpr_change : (p.dprChange !== undefined ? p.dprChange : null));
      applyDprChange(changeVal);

      // DPR breakdown (flexible: object or array)
      const breakdownEl = el('focus-dpr-breakdown');
      if (breakdownEl) {
        breakdownEl.innerHTML = '';
        const db = (p.dpr_breakdown || p.dprBreakdown || null);
        if (!db) {
          breakdownEl.innerHTML = '<div class="text-gray-500">No DPR breakdown available.</div>';
        } else if (Array.isArray(db)) {
          db.forEach(item => {
            const key = item.label || item.name || item.metric || '';
            const val = item.value ?? item.score ?? '';
            const row = document.createElement('div');
            row.className = 'flex items-center justify-between';
            row.innerHTML = `<div class="text-xs text-gray-400">${escapeHtml(String(key))}</div><div class="text-sm text-white font-semibold">${escapeHtml(String(val))}</div>`;
            breakdownEl.appendChild(row);
          });
        } else if (typeof db === 'object') {
          Object.keys(db).forEach(k => {
            const v = db[k];
            const row = document.createElement('div');
            row.className = 'flex items-center justify-between';
            row.innerHTML = `<div class="text-xs text-gray-400">${escapeHtml(String(k))}</div><div class="text-sm text-white font-semibold">${escapeHtml(String(v))}</div>`;
            breakdownEl.appendChild(row);
          });
        } else {
          breakdownEl.innerHTML = `<div class="text-gray-500">${escapeHtml(String(db))}</div>`;
        }
      }

      // Key stats (key_stats_p90 or key_stats)
      const keyStatsEl = el('focus-key-stats');
      if (keyStatsEl) {
        keyStatsEl.innerHTML = '';
        const ks = (p.key_stats_p90 || p.key_stats || p.key_stats_p90 || null);
        if (!ks) {
          keyStatsEl.innerHTML = '<tr><td class="py-2 text-gray-500">No key stats available.</td><td></td></tr>';
        } else if (Array.isArray(ks)) {
          ks.forEach(row => {
            const label = row.label || row.name || row.metric || '';
            const val = row.value ?? row.v ?? '';
            const tr = document.createElement('tr');
            tr.innerHTML = `<td class="py-2 text-xs text-gray-400">${escapeHtml(String(label))}</td><td class="py-2 text-sm text-white font-semibold text-right">${escapeHtml(String(val))}</td>`;
            keyStatsEl.appendChild(tr);
          });
        } else if (typeof ks === 'object') {
          Object.keys(ks).forEach(k => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td class="py-2 text-xs text-gray-400">${escapeHtml(String(k))}</td><td class="py-2 text-sm text-white font-semibold text-right">${escapeHtml(String(ks[k]))}</td>`;
            keyStatsEl.appendChild(tr);
          });
        } else {
          keyStatsEl.innerHTML = `<tr><td class="py-2 text-gray-500">${escapeHtml(String(ks))}</td><td></td></tr>`;
        }
      }

      // Notes / strengths / development
      if (el('focus-notes')) el('focus-notes').textContent = p.notes || '—';

      const strengthsEl = el('focus-strengths');
      if (strengthsEl) {
        strengthsEl.innerHTML = '';
        const sarr = Array.isArray(p.key_strengths) ? p.key_strengths : (Array.isArray(p.keyStrengths) ? p.keyStrengths : []);
        if (sarr.length === 0) strengthsEl.innerHTML = '<span class="text-xs px-2 py-1 rounded bg-green-900 text-green-200">—</span>';
        else sarr.forEach(s => { const sp = document.createElement('span'); sp.className='text-xs px-2 py-1 rounded bg-green-900 text-green-200'; sp.textContent = s; strengthsEl.appendChild(sp); });
      }

      const devEl = el('focus-development');
      if (devEl) {
        devEl.innerHTML = '';
        const darr = Array.isArray(p.development_areas) ? p.development_areas : (Array.isArray(p.developmentAreas) ? p.developmentAreas : []);
        if (darr.length === 0) devEl.innerHTML = '<span class="text-xs px-2 py-1 rounded bg-[#17202b] text-gray-400">—</span>';
        else darr.forEach(d => { const sp = document.createElement('span'); sp.className='text-xs px-2 py-1 rounded bg-[#17202b] text-gray-400'; sp.textContent = d; devEl.appendChild(sp); });
      }

      // small badge suggestion based on DPR (non-essential)
      const badgeEl = el('focus-badge');
      if (badgeEl) {
        const numericDpr = (p.dpr !== undefined && p.dpr !== null) ? Number(String(p.dpr).replace(/[^0-9\.\-]/g,'')) : null;
        if (Number.isFinite(numericDpr)) {
          if (numericDpr >= 85) { badgeEl.textContent = 'Top performer'; badgeEl.className = 'mt-3 inline-block px-3 py-1 text-xs rounded-full bg-green-700 text-white'; }
          else if (numericDpr >= 70) { badgeEl.textContent = 'Solid Contributor'; badgeEl.className = 'mt-3 inline-block px-3 py-1 text-xs rounded-full bg-[#1f2937] text-white'; }
          else { badgeEl.textContent = 'Needs review'; badgeEl.className = 'mt-3 inline-block px-3 py-1 text-xs rounded-full bg-[#3b3b3b] text-white'; }
        }
      }
    } catch (err) {
      console.warn('populateFocusFromPlayerObject error', err);
    }
  }

  // Expose a standardized global hook so other scripts can populate focus panel easily
  window.showPlayerInFocus = function (playerObj) {
    try {
      if (!playerObj || typeof playerObj !== 'object') return;
      populateFocusFromPlayerObject(playerObj);
    } catch (e) {
      console.warn('showPlayerInFocus error', e);
    }
  };

  // extract name heuristics from element
  function extractNameFromElement(elm) {
    if (!elm) return '';
    try {
      const rawJson = elm.getAttribute && elm.getAttribute('data-player');
      if (rawJson) {
        let obj = null;
        try { obj = JSON.parse(rawJson); }
        catch (e) {
          try { obj = JSON.parse(rawJson.replace(/&quot;/g, '"').replace(/&#039;/g, "'")); } catch (e2) { obj = null; }
        }
        if (obj) {
          const full = obj.full_name || obj.raw_name || obj.player_name || obj.name || null;
          const surname = obj.surname || null;
          if (full && String(full).trim()) return toLookupNameFromFull(String(full).trim());
          if (surname && String(surname).trim()) return String(surname).trim();
        }
      }
    } catch (err) { /* swallow */ }

    const attr = elm.getAttribute && elm.getAttribute('data-player-name');
    if (attr) return sanitizeNameInput(attr.trim());

    const nmEl = elm.querySelector && (elm.querySelector('.player-name') || elm.querySelector('.name'));
    if (nmEl && nmEl.textContent && nmEl.textContent.trim()) return sanitizeNameInput(nmEl.textContent.trim());

    const text = (elm.textContent || '').replace(/\s+/g,' ').trim();
    if (!text) return '';
    const cleaned = sanitizeNameInput(text);
    const words = cleaned.split(/\s+/).filter(Boolean);
    if (words.length >= 2) return words.slice(0, Math.min(4, words.length)).join(' ');
    return cleaned;
  }

  // normalized name for comparing
  function normNameForCompare(s) {
    if (!s) return '';
    return String(s || '')
      .replace(/<[^>]*>/g, '')
      .replace(/\u00A0/g, ' ')
      .replace(/^[+\-]?\d{1,2}(?:\.\d)?\s*[-–:—]?\s*/,'')
      .replace(/\s*[-–:—]?\s*[+\-]?\d{1,2}(?:\.\d)?\s*$/,'')
      .replace(/\s+/g, ' ')
      .trim()
      .toLowerCase();
  }

  function extractNameFromDataPlayer(dp) {
    if (!dp) return '';
    dp = String(dp).trim();
    if (!dp) return '';
    if (dp.startsWith('{') || dp.startsWith('[')) {
      try {
        const obj = JSON.parse(dp);
        return (obj.full_name || obj.raw_name || obj.player_name || obj.name || obj.surname || '') + '';
      } catch (e) {
        try {
          const decoded = dp.replace(/&quot;/g, '"').replace(/&#039;/g, "'").replace(/&amp;/g,'&');
          const obj2 = JSON.parse(decoded);
          return (obj2.full_name || obj2.raw_name || obj2.player_name || obj2.name || obj2.surname || '') + '';
        } catch (e2) {
          return dp;
        }
      }
    }
    return dp;
  }

  function findPlayerCardByName(name) {
    if (!name) return null;
    const container = document.getElementById('player-card-container');
    if (!container) return null;
    const candidates = container.querySelectorAll('.player-card');
    const target = normNameForCompare(name);
    if (!target) return null;

    for (const c of candidates) {
      let candidate = '';
      try {
        const dpn = c.getAttribute && c.getAttribute('data-player-name');
        if (dpn && dpn.trim()) candidate = dpn.trim();
      } catch (e) {}
      if (!candidate) {
        try {
          const dp = c.getAttribute && c.getAttribute('data-player');
          if (dp) candidate = extractNameFromDataPlayer(dp);
        } catch (e) {}
      }
      if (!candidate) {
        try {
          const nameEl = c.querySelector && (c.querySelector('.player-name') || c.querySelector('.name'));
          if (nameEl && (nameEl.textContent || '').trim()) candidate = nameEl.textContent.trim();
        } catch (e) {}
      }
      if (!candidate) {
        try { candidate = (c.textContent || '').trim(); } catch (e) { candidate = ''; }
      }
      if (normNameForCompare(candidate) === target) return c;
    }
    return null;
  }

  // small helper: wait for selector with timeout
  function waitForSelector(selector, timeoutMs = 5000, interval = 100) {
    return new Promise((resolve) => {
      const start = Date.now();
      (function poll() {
        const elSel = document.querySelector(selector);
        if (elSel) return resolve(elSel);
        if (Date.now() - start >= timeoutMs) return resolve(null);
        setTimeout(poll, interval);
      })();
    });
  }

  // Determine if current page is player_performance page
  function isOnPlayerPerformancePage() {
    try {
      const path = window.location.pathname || '';
      return path.includes('/player_performance') || path.endsWith('player_performance.php') || window.location.href.includes('player_performance.php') || window.location.href.includes('playerperformancecontroller');
    } catch (e) { return false; }
  }

  // table-row -> card activation handler (with redirect-to-player_performance if needed)
  (function () {
    const tableSelectors = ['#summary-top-scorers', '#summary-top-defenders', '#summary-top-passers'];

    document.addEventListener('click', function (ev) {
      const row = ev.target && ev.target.closest ? ev.target.closest('tr') : null;
      if (!row) return;
      const tbody = row.parentElement;
      if (!tbody || tbody.tagName.toLowerCase() !== 'tbody') return;
      const tbodyId = tbody.id || '';
      if (!tableSelectors.includes('#' + tbodyId)) return;

      let name = (row.getAttribute('data-player-name') || '').trim();
      if (!name) {
        const tds = row.querySelectorAll('td');
        if (tds && tds.length >= 2) name = (tds[1].textContent || '').trim();
        else if (tds && tds.length === 1) name = (tds[0].textContent || '').trim();
        else name = (row.textContent || '').trim();
      }
      name = sanitizeNameInput(name);
      if (!name) return;

      // If not on player_performance page, save and redirect first
      if (!isOnPlayerPerformancePage()) {
        try {
          const displayName = (/,/.test(name) ? fullNameFromLookup(name) : name);
          sessionStorage.setItem(NAV_KEY, JSON.stringify({ name: name, display: displayName, timestamp: Date.now() }));
          // Also set the global override flag immediately so when the new page loads
          // the initial summary renderer won't stomp over the focus UI.
          window.__prevent_initial_summary_override = true;
        } catch (e) {
          console.warn('Could not write sessionStorage for navigation target', e);
        }
        // Redirect to the canonical player performance page path.
        // Use CI_BASE_URL to keep CodeIgniter routing intact; adjust endpoint if your controller differs.
        window.location.href = CI_BASE_URL + '/reports/playerperformancecontroller/index';
        return;
      }

      // --- On player_performance page: try find card and trigger click (preferred) ---
      // prefer display form for activation (convert if needed)
      const displayForm = (/,/.test(name) ? fullNameFromLookup(name) : name);

      let card = findPlayerCardByName(displayForm);
      if (!card && displayForm !== name) card = findPlayerCardByName(name);

      if (card) {
        try {
          if (typeof card.focus === 'function') card.focus();
          if (typeof card.click === 'function') card.click();
          else card.dispatchEvent(new MouseEvent('click', { bubbles:true, cancelable:true }));
        } catch (e) {
          try { card.dispatchEvent(new MouseEvent('click', { bubbles:true, cancelable:true })); } catch (e2) {}
        }
        try { hideInitialSummary(); } catch (e) {}
        try { showDetailTemplate(); } catch (e) {}
        return;
      }

      // If no card found, try showPlayerInFocus if available (pass display name)
      const displayToUse = (/,/.test(name) ? fullNameFromLookup(name) : name);
      if (typeof window.showPlayerInFocus === 'function') {
        try {
          window.showPlayerInFocus({ full_name: displayToUse, raw_name: name, player_name: displayToUse });
          try { hideInitialSummary(); } catch(e){}
          try { showDetailTemplate(); } catch(e){}
          return;
        } catch (err) { console.warn('showPlayerInFocus error', err); }
      }

      // final fallback
      try {
        setFocusedPlayer(name);
        hideInitialSummary();
        showDetailTemplate();
      } catch (err) { console.error('fallback setFocusedPlayer failed', err); }
    }, { capture: true });
  })();

    // update focus when a .player-card is clicked (quick local display) — ENHANCED to parse data-player JSON
  document.addEventListener('click', function (ev) {
    const card = ev.target.closest && ev.target.closest('.player-card');
    if (card) {
      hideInitialSummary();
      showDetailTemplate();

      // always set name (best-effort)
      const nm = extractNameFromElement(card);
      if (nm) setFocusedPlayer(nm, card.getAttribute('data-player-subtitle') || '');

      // try parse JSON from data-player attribute (controller returns the full shape there)
      try {
        const raw = card.getAttribute && card.getAttribute('data-player');
        if (raw) {
          let parsed = null;
          try { parsed = JSON.parse(raw); }
          catch (e1) {
            // try decode common HTML entity encodings
            try { parsed = JSON.parse(raw.replace(/&quot;/g, '"').replace(/&#039;/g, "'")); } catch (e2) { parsed = null; }
          }
          if (parsed && typeof parsed === 'object') {
            // prefer normalized keys (controller shape expected)
            populateFocusFromPlayerObject(parsed);
            return;
          }
        }
      } catch (err) {
        console.warn('Failed to parse data-player JSON on card', err);
      }

      // fallback: attempt to read lightweight attributes (if present)
      try {
        const altChange = card.getAttribute && (card.getAttribute('data-dpr-change') || card.getAttribute('data-dpr_change'));
        if (altChange) applyDprChange(altChange);
      } catch (err) { /* ignore */ }
    }
  }, { capture: true });


  // btn-view-profile minor wiring
  (function (){
    const btn = el('btn-view-profile');
    const focusFullnameEl = el('focus-fullname');
    if (!btn) return;

    function extractPlayerName(raw) {
      if (!raw) return '';
      raw = String(raw).replace(/\r|\n|\t/g,' ').trim();
      const cleaned = sanitizeNameInput(raw);
      let m = cleaned.match(/([A-Za-zÀ-ÖØ-öø-ÿ'`-]+,\s*[A-Za-zÀ-ÖØ-öø-ÿ'`-]+(?:\s[A-Za-z'`-]+)?)/);
      if (m && m[1]) return m[1].trim();
      m = cleaned.match(/([A-Z][a-z'`-]+)\s+([A-Z][a-z'`-]+)/);
      if (m && m[1] && m[2]) return `${m[2]}, ${m[1]}`;
      return cleaned.replace(/<[^>]*>/g,'').trim();
    }

    const rawAttr = btn.getAttribute('data-player-name') || (focusFullnameEl ? focusFullnameEl.textContent : '');
    let cleanName = extractPlayerName(rawAttr);
    if ((!cleanName || !/,/.test(cleanName)) && focusFullnameEl && (focusFullnameEl.textContent || '').trim()) {
      cleanName = toLookupNameFromFull(focusFullnameEl.textContent.trim());
    }
    if (cleanName) btn.setAttribute('data-player-name', cleanName);

    btn.addEventListener('click', function (ev) {
      ev.preventDefault();
      const name = (btn.getAttribute('data-player-name') || (focusFullnameEl ? focusFullnameEl.textContent.trim() : '')).trim();
      if (!name) return;
      if (typeof window.openPlayerProfile === 'function') {
        try { window.openPlayerProfile(name); return; } catch(e){/*ignore*/}
      }
      document.dispatchEvent(new CustomEvent('openPlayerProfile', { detail: { name } }));
    });
  })();

  // apply last selected if present
  if (window.__lastSelectedPlayerName) {
    try { setFocusedPlayer(window.__lastSelectedPlayerName); } catch(e) {}
  }

  // If we arrived here after a redirect from a summary click, activate the requested player card (if possible)
  (function () {
    try {
      const raw = sessionStorage.getItem(NAV_KEY);
      if (!raw) return;
      // remove immediately so we cannot loop accidentally
      try { sessionStorage.removeItem(NAV_KEY); } catch (e) {}
      let obj = null;
      try { obj = JSON.parse(raw); } catch (e) { obj = { name: String(raw || '').trim() }; }
      const origName = obj && obj.name ? String(obj.name).trim() : '';
      const displayFromStorage = obj && obj.display ? String(obj.display).trim() : '';
      if (!origName && !displayFromStorage) {
        // clear override flag if nothing to do
        window.__prevent_initial_summary_override = false;
        return;
      }

      // derive display name
      const displayName = displayFromStorage || (/,/.test(origName) ? fullNameFromLookup(origName) : origName);
      const tryNames = [];
      if (displayName) tryNames.push(displayName);
      if (origName && origName !== displayName) tryNames.push(origName);

      // Wait for DOM ready & player card container (up to timeout), then attempt match
      (async function activateAfterRedirect() {
        // Wait for DOMContentLoaded if not ready
        if (document.readyState === 'loading') {
          await new Promise(res => document.addEventListener('DOMContentLoaded', res));
        }

        // Slight delay to allow other page initialization (match summary, card rendering) to run first.
        await new Promise(res => setTimeout(res, 50));

        // Wait for the player-card container to appear (if this page renders them)
        const container = await waitForSelector('#player-card-container', 5000);
        if (container) {
          // attempt to find card (try display first then raw)
          for (const nameToTry of tryNames) {
            if (!nameToTry) continue;
            const card = findPlayerCardByName(nameToTry);
            if (card) {
              try {
                if (typeof card.focus === 'function') card.focus();
                if (typeof card.click === 'function') card.click();
                else card.dispatchEvent(new MouseEvent('click', { bubbles:true, cancelable:true }));
              } catch (e) {
                try { card.dispatchEvent(new MouseEvent('click', { bubbles:true, cancelable:true })); } catch(e2) {}
              }
              try { hideInitialSummary(); } catch (e) {}
              try { showDetailTemplate(); } catch (e) {}
              // release override after a small delay so subsequent summary renders behave normally
              setTimeout(() => { window.__prevent_initial_summary_override = false; }, 1000);
              return;
            }
          }
        }

        // If no card found, try global showPlayerInFocus (pass display name)
        if (typeof window.showPlayerInFocus === 'function') {
          try {
            window.showPlayerInFocus({ full_name: displayName, raw_name: origName || displayName, player_name: displayName });
            try { hideInitialSummary(); } catch(e){}
            try { showDetailTemplate(); } catch(e){}
            setTimeout(() => { window.__prevent_initial_summary_override = false; }, 1000);
            return;
          } catch (err) {
            console.warn('showPlayerInFocus error after redirect', err);
          }
        }

        // Last resort: set local focused player (use displayName)
        try {
          setFocusedPlayer(displayName || origName);
          hideInitialSummary();
          showDetailTemplate();
        } catch (err) {
          console.error('Failed to set focus after redirect', err);
        } finally {
          // release override flag after fail/success; ensure page will not stay locked
          setTimeout(() => { window.__prevent_initial_summary_override = false; }, 1000);
        }
      })();
    } catch (e) {
      console.warn('Activation after redirect failed', e);
      // ensure override flag doesn't remain stuck
      setTimeout(() => { window.__prevent_initial_summary_override = false; }, 1000);
    }
  })();

  // expose UI helpers globally (so other scripts can call them)
  window.showInitialSummary = showInitialSummary;
  window.hideInitialSummary = hideInitialSummary;
  window.showDetailTemplate = showDetailTemplate;
  window.hideDetailTemplate = hideDetailTemplate;
})();
</script>
