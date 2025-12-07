<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Partial: player_modal.php
 * Renders a hidden modal and JS to fetch + display player profile.
 *
 * NOTE:
 * - Uses REPORT_MATCH_NAME hidden input already set on the page.
 * - Also resolves sanitized match name on server (fallback to session or GET).
 */

// 1. Get Match ID/Name (Prefer GET, fallback to Session)
$raw_match_id   = $this->input->get('match_id', true);
$raw_match_name = $this->input->get('match_name', true);

$match_id = $raw_match_id ?: $this->session->userdata('current_match_id');
$match_name = $raw_match_name ?: $this->session->userdata('current_match_name');

// 2. Sanitize for file/view usage (match folder)
$sanitized_match_id = $match_id ? preg_replace('/[^A-Za-z0-9_\-]/', '', $match_id) : null;
$sanitized_match_name = $match_name ? preg_replace('/[^A-Za-z0-9_\-]/', '', $match_name) : null;

// Construct base relative paths (no leading slash so base_url() works reliably)
$match_folder = $sanitized_match_name ?: '';
$heatmaps_rel = "output/matches/{$match_folder}/heatmaps/";
$json_insights_rel = "output/matches/{$match_folder}/san_beda_university_player_insights.json";
$json_derived_rel = "output/matches/{$match_folder}/san_beda_university_players_derived_metrics.json";

// Use base_url() to build absolute URL to assets (adjust if output path served differently)
$base_url = rtrim(base_url(), '/');
$heatmaps_base_url = $base_url . '/' . $heatmaps_rel;
$json_insights_url = $base_url . '/' . $json_insights_rel;
$json_derived_url = $base_url . '/' . $json_derived_rel;
?>

<!-- PLAYER PROFILE MODAL (ENHANCED UI/UX) -->
<div id="player-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" aria-hidden="true" role="dialog" aria-modal="true">
  <!-- backdrop -->
  <div id="player-modal-backdrop" class="absolute inset-0 bg-black/60 backdrop-blur-sm" data-close="overlay" aria-hidden="true"></div>

  <!-- modal card -->
  <div role="document" aria-labelledby="player-modal-title"
       class="relative z-10 w-full max-w-5xl max-h-[90vh] overflow-hidden rounded-2xl bg-[#0f1112] shadow-lg flex flex-col transition-transform duration-200 transform scale-95 opacity-0" id="player-modal-card">
    <!-- loading overlay (spinner + message) -->
    <div id="player-modal-loading" class="absolute inset-0 z-40 bg-black/40 hidden items-center justify-center">
      <div class="flex flex-col items-center gap-3">
        <svg class="animate-spin w-8 h-8 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8z"></path>
        </svg>
        <div class="text-sm text-gray-200">Loading player profile…</div>
      </div>
    </div>

    <!-- header -->
    <div class="flex items-start justify-between gap-4 p-4 border-b border-neutral-800 sticky top-0 bg-[#0f1112] z-30">
      <div class="flex items-center gap-4 min-w-0">
        <div id="player-modal-avatar" class="w-16 h-16 rounded-full bg-gradient-to-br from-gray-700 to-gray-900 flex items-center justify-center text-2xl font-bold text-white flex-shrink-0">
          <span id="player-modal-initials">AB</span>
        </div>
        <div class="min-w-0">
          <h2 id="player-modal-title" class="text-lg text-white font-semibold truncate">Player name</h2>
          <div id="player-modal-sub" class="text-xs text-gray-400 truncate">#10 • CDM • San Beda University</div>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <button id="player-copy-name" type="button" class="text-xs px-3 py-1 rounded bg-transparent border border-neutral-700 text-gray-300 hover:bg-neutral-900" title="Copy player name">Copy</button>
        <button id="player-json-download" type="button" class="text-xs px-3 py-1 rounded bg-transparent border border-neutral-700 text-gray-300 hover:bg-neutral-900" title="Export full player performance">Export</button>
        <button id="player-modal-close" class="Text-sm px-3 py-1 rounded bg-transparent border border-neutral-700 text-gray-300 hover:bg-neutral-900 cursor-pointer" aria-label="Close player modal">Close</button>
      </div>
    </div>

    <!-- body: scrollable two-column layout -->
    <div class="overflow-auto min-h-0 p-4 custom-scroll" style="max-height: calc(90vh - 112px);" id="player-modal-body">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- LEFT column (narrow) -->
        <aside class="col-span-1 flex flex-col gap-4">

          <!-- Predicted DPR card - updated to show actual DPR above predicted DPR -->
          <div class="bg-[#0b0d0e] rounded-xl p-4 flex flex-col justify-between">
            <div class="flex items-center justify-between gap-4">
              <div>
                <!-- Actual / observed DPR (small, above predicted) -->
                <div class="text-xs text-gray-400">DPR</div>
                <div id="player-dpr-actual" class="text-3xl font-extrabold text-white mb-3">—</div>

                <!-- Predicted DPR (primary large number) -->
                <div class="text-xs text-gray-400">Predicted DPR</div>
                <div class="flex items-center gap-3">
                  <div id="player-dpr" class="text-2xl font-extrabold text-white">—</div>
                  <div id="player-dpr-change" class="text-sm font-semibold text-gray-400" aria-live="polite">—</div>
                </div>
              </div>
              <div class="text-right">
                <div class="text-xs text-gray-400">Minutes</div>
                <div id="player-minutes" class="text-lg font-semibold text-white">—</div>
              </div>
            </div>
            <div class="mt-3 text-sm text-gray-300" id="player-badge">—</div>
          </div>

          <!-- DPR breakdown -->
          <div class="bg-[#0f1112] rounded-xl p-4">
            <div class="text-sm font-semibold text-white mb-3">DPR breakdown</div>
            <div id="player-dpr-breakdown" class="text-sm text-gray-300 space-y-2">
              <div class="text-gray-400">Loading…</div>
            </div>
          </div>

          <!-- Quick facts -->
          <div class="bg-[#0f1112] rounded-xl p-3">
            <div class="text-sm font-semibold text-white mb-3">Quick facts</div>
            <div class="text-sm text-gray-300 space-y-1">
              <div><span class="text-gray-400">Status:</span> <span id="player-status" class="text-white font-semibold">—</span></div>
              <div><span class="text-gray-400">Tactical role:</span> <span id="player-role" class="text-white font-semibold">—</span></div>
              <div><span class="text-gray-400">Team:</span> <span id="player-team" class="text-white font-semibold">—</span></div>
            </div>
          </div>

        </aside>

        <!-- RIGHT column (wide) -->
        <section class="col-span-1 lg:col-span-2 flex flex-col gap-4">

          <!-- Enhanced insight (full width of right column) -->
          <div class="bg-[#0f1112] rounded-xl p-4 w-full">
            <div class="flex items-center justify-between">
              <div>
                <div class="text-sm font-semibold text-white mb-2">Enhanced insight</div>
                <div id="player-enhanced-insight" class="text-sm text-gray-300 leading-relaxed">—</div>
              </div>
            </div>

            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <div class="text-xs text-gray-400 mb-1">Strengths</div>
                <ul id="player-strengths" class="list-disc list-inside text-sm text-gray-300"></ul>
              </div>
              <div>
                <div class="text-xs text-gray-400 mb-1">Development areas</div>
                <ul id="player-dev-areas" class="list-disc list-inside text-sm text-gray-300"></ul>
              </div>
            </div>
          </div>

          <!-- Merged statistics table (full width) -->
          <div class="bg-[#0b0d0e] rounded-xl p-4 w-full">
            <div class="flex items-center justify-between mb-3">
              <div class="text-sm font-semibold text-white">Statistics</div>
              <div class="text-xs text-gray-400 pr-6">Raw values & Per 90</div>
            </div>

            <div class="max-h-64 overflow-auto custom-scroll pr-4">
              <table class="w-full text-sm table-fixed">
                <thead class="sticky top-0 bg-[#0b0d0e] z-20">
                  <tr class="text-xs text-gray-400">
                    <th class="text-left py-2 px-2">Metric</th>
                    <th class="text-right py-2 px-2 w-28">Raw</th>
                    <th class="text-right py-2 px-2 w-28">Per 90</th>
                  </tr>
                </thead>
                <tbody id="player-stats-table-body">
                  <!-- skeleton rows (initial) -->
                  <tr><td class="py-2 text-gray-400" colspan="3">Loading…</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Movement maps (full width) -->
          <div class="bg-[#0b0d0e] rounded-xl p-3 w-full">
            <div class="flex items-center justify-between mb-2">
              <div class="text-sm font-semibold text-white">Movement Maps</div>
              <div class="text-xs text-gray-400">Heatmap & zones</div>
            </div>

            <div class="grid grid-cols-1 gap-3">
              <div class="bg-[#0f1112] rounded p-2 flex items-center justify-center relative">
                <img id="player-heatmap-img" src="" alt="Heatmap" class="object-contain w-full" loading="lazy" />
                <button id="player-heatmap-zoom" class="absolute top-2 right-2 text-xs px-2 py-1 rounded bg-black/50 text-white border border-neutral-700 hidden">View expanded image</button>
              </div>
              <div class="bg-[#0f1112] rounded p-2 flex items-center justify-center relative">
                <img id="player-zones-img" src="" alt="Zones" class="object-contain w-full" loading="lazy" />
                <button id="player-zones-zoom" class="absolute top-2 right-2 text-xs px-2 py-1 rounded bg-black/50 text-white border border-neutral-700 hidden">View expanded image</button>
              </div>

              <div class="text-xs text-gray-400">If images do not appear, confirm the files exist in the match heatmaps folder.</div>
            </div>
          </div>

        </section>
      </div>
    </div> <!-- /body -->

    <!-- footer: actions -->
    <div class="flex items-center justify-end gap-2 p-4 border-t border-neutral-800">
      <button id="player-modal-close-2" class="text-sm px-3 py-1 rounded bg-white text-black font-semibold hover:opacity-90 cursor-pointer">Close</button>
    </div>
  </div>
</div>

<script type="text/javascript">
(function () {
  // Delay initialization until DOM is ready to avoid querying missing elements.
  function initPlayerModalScript() {
    try {
      // Server-provided sanitized match name + asset URLs (unchanged)
      const SANITIZED_MATCH_NAME = <?= json_encode($sanitized_match_name ?: '') ?>;
      const HEATMAPS_BASE = <?= json_encode($heatmaps_base_url) ?>; // includes trailing folder
      const JSON_INSIGHTS_URL = <?= json_encode($json_insights_url) ?>;
      const JSON_DERIVED_URL = <?= json_encode($json_derived_url) ?>;

      // Query DOM elements safely
      const modal = document.getElementById('player-modal');
      const modalCard = document.getElementById('player-modal-card');
      const loadingOverlay = document.getElementById('player-modal-loading');
      const backdrop = document.getElementById('player-modal-backdrop');
      const closeBtns = [document.getElementById('player-modal-close'), document.getElementById('player-modal-close-2')];
      const copyBtn = document.getElementById('player-copy-name');
      const jsonDownload = document.getElementById('player-json-download');
      const heatZoomBtn = document.getElementById('player-heatmap-zoom');
      const zonesZoomBtn = document.getElementById('player-zones-zoom');

      const btnViewProfile = document.getElementById('btn-view-profile'); // existing button
      const initialNameEl = document.getElementById('focus-fullname'); // uses this as fallback

      // Modal DOM targets
      const elTitle = document.getElementById('player-modal-title');
      const elSub = document.getElementById('player-modal-sub');
      const elInitials = document.getElementById('player-modal-initials');
      const elDpr = document.getElementById('player-dpr');
      const elDprActual = document.getElementById('player-dpr-actual');
      const elDprChange = document.getElementById('player-dpr-change');
      const elMinutes = document.getElementById('player-minutes');
      const elBadge = document.getElementById('player-badge');
      const elDprBreakdown = document.getElementById('player-dpr-breakdown');
      const elStatsBody = document.getElementById('player-stats-table-body');
      const elEnhanced = document.getElementById('player-enhanced-insight');
      const elStrengths = document.getElementById('player-strengths');
      const elDevAreas = document.getElementById('player-dev-areas');
      const elTeam = document.getElementById('player-team');
      const elStatus = document.getElementById('player-status');
      const elRole = document.getElementById('player-role');
      const imgHeat = document.getElementById('player-heatmap-img');
      const imgZones = document.getElementById('player-zones-img');

      // Cache object used by the Export button (kept in outer scope)
      let lastExportPlayer = null;

      // If essential elements are missing, log and safely abort initialization (no throw).
      if (!modal || !modalCard || !elStatsBody) {
        console.warn('Player modal: essential DOM nodes missing; initialization aborted.', { modal: !!modal, modalCard: !!modalCard, elStatsBody: !!elStatsBody });
        // still expose safe no-op functions so callers won't break
        window.populateModalForPlayer = function () { console.warn('populateModalForPlayer: modal not initialized'); };
        window.openPlayerProfile = function () { console.warn('openPlayerProfile: modal not initialized'); return false; };
        return;
      }

      // small helpers (kept compatible with previous functions)
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
      function sanitizeInputName(raw) {
        if (!raw) return '';
        let s = String(raw).trim();
        s = s.replace(/<[^>]*>/g, '').trim();
        s = s.replace(/^[+\-]?\d+(\.\d+)?\s*[-–:—]?\s*/,'').trim();
        const trailingRatingMatch = s.match(/^([^,]+),\s*[+\-]?\d+(\.\d+)?$/);
        if (trailingRatingMatch) return trailingRatingMatch[1].trim();
        s = s.replace(/\s+[+\-]?\d+(\.\d+)?\s*$/,'').trim();
        s = s.replace(/^[\-–:—\s]+|[\-–:—\s]+$/g, '').trim();
        return s;
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
      function formatStat(v, decimals=2) {
        if (v === null || v === undefined || Number.isNaN(Number(v))) return '—';
        const n = Number(v);
        return n.toFixed(decimals).replace(/\.00$/, '.00');
      }
      function playerFileNameFromDisplay(name) {
        if (!name) return '';
        let s = String(name).trim();
        s = s.replace(/,\s*/g, ',_');
        s = s.replace(/\s+/g, '_');
        s = s.replace(/[^A-Za-z0-9,._\-]/g, '');
        return s;
      }
      function normalizeNameKey(s) {
        if (!s) return '';
        return String(s).replace(/\./g, '').replace(/\s+/g, ' ').replace(/\s*,\s*/g, ', ').trim().toLowerCase();
      }

      // UI open/close + focus trap (defensive)
      let lastActiveElement = null;
      function showLoading(show) {
        if (!loadingOverlay) return;
        if (show) loadingOverlay.classList.remove('hidden');
        else loadingOverlay.classList.add('hidden');
      }
      function openModal() {
        try {
          if (!modal || !modalCard) return;
          lastActiveElement = document.activeElement;
          modal.classList.remove('hidden');
          modal.setAttribute('aria-hidden','false');
          // animate in
          setTimeout(() => {
            modalCard.classList.remove('scale-95','opacity-0');
            modalCard.classList.add('scale-100','opacity-100');
          }, 10);
          document.documentElement.style.overflow = 'hidden';
          if (closeBtns[0]) closeBtns[0].focus();
          trapFocus(modal);
        } catch (e) {
          console.error('openModal error', e);
          // fallback: ensure modal is visible
          modal.classList.remove('hidden');
        }
      }
      function closeModal() {
        try {
          if (!modal || !modalCard) return;
          modalCard.classList.remove('scale-100','opacity-100');
          modalCard.classList.add('scale-95','opacity-0');
          setTimeout(() => {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden','true');
            document.documentElement.style.overflow = '';
            if (lastActiveElement && typeof lastActiveElement.focus === 'function') lastActiveElement.focus();
          }, 180);
        } catch (e) {
          console.error('closeModal error', e);
          modal.classList.add('hidden');
        }
      }

      // minimal focus trap
      function trapFocus(container) {
        try {
          const focusable = Array.from(container.querySelectorAll('a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'))
            .filter(el => el.offsetWidth > 0 || el.offsetHeight > 0 || el === document.activeElement);
          if (!focusable.length) return;
          const first = focusable[0], last = focusable[focusable.length -1];
          function keyHandler(e) {
            if (e.key === 'Tab') {
              if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
              } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
              }
            } else if (e.key === 'Escape') {
              closeModal();
            }
          }
          document.addEventListener('keydown', keyHandler);
          const observer = new MutationObserver(() => {
            if (modal.classList.contains('hidden')) {
              document.removeEventListener('keydown', keyHandler);
              observer.disconnect();
            }
          });
          observer.observe(modal, { attributes: true, attributeFilter: ['class'] });
        } catch (e) {
          // non-fatal
        }
      }

      // fetch match JSONs
      async function fetchMatchJsons() {
        showLoading(true);
        const [insightsResp, derivedResp] = await Promise.allSettled([
          fetch(JSON_INSIGHTS_URL, { credentials: 'same-origin' }),
          fetch(JSON_DERIVED_URL, { credentials: 'same-origin' })
        ]);
        let insights = null, derived = null;
        try { if (insightsResp.status === 'fulfilled' && insightsResp.value.ok) insights = await insightsResp.value.json(); } catch(e){ console.warn('insights parse error', e); }
        try { if (derivedResp.status === 'fulfilled' && derivedResp.value.ok) derived = await derivedResp.value.json(); } catch(e){ console.warn('derived parse error', e); }
        showLoading(false);
        return { insights, derived };
      }

      // skeleton renderer
      function renderStatsSkeleton() {
        if (!elStatsBody) return;
        elStatsBody.innerHTML = '';
        for (let i=0;i<6;i++) {
          const tr = document.createElement('tr');
          tr.innerHTML = `<td class="py-2 px-2"><div class="h-3 bg-neutral-800 rounded w-3/4 animate-pulse"></div></td>
                          <td class="py-2 px-2 text-right"><div class="h-3 bg-neutral-800 rounded w-12 ml-auto animate-pulse"></div></td>
                          <td class="py-2 px-2 text-right"><div class="h-3 bg-neutral-800 rounded w-12 ml-auto animate-pulse"></div></td>`;
          elStatsBody.appendChild(tr);
        }
      }

      // DPR change display helper
      function applyDprChangeModal(value) {
        if (!elDprChange) return;
        elDprChange.classList.remove('text-green-400','text-red-400','text-gray-400','text-white');
        if (value === null || value === undefined || value === '') {
          elDprChange.textContent = '—';
          elDprChange.classList.add('text-gray-400');
          return;
        }
        const raw = String(value).trim();
        const num = Number(raw.replace(/[^0-9\.\-]/g,''));
        if (!Number.isFinite(num)) {
          elDprChange.textContent = raw;
          elDprChange.classList.add('text-gray-400');
          return;
        }
        const sign = num > 0 ? '+' : (num < 0 ? '' : '');
        const formatted = (Math.abs(num) % 1 === 0) ? `${sign}${num}` : `${sign}${num.toFixed(1)}`;
        if (num > 0) elDprChange.innerHTML = `<span class="inline-block mr-1">▲</span>${formatted}`, elDprChange.classList.add('text-green-400');
        else if (num < 0) elDprChange.innerHTML = `<span class="inline-block mr-1">▼</span>${formatted}`, elDprChange.classList.add('text-red-400');
        else elDprChange.textContent = formatted, elDprChange.classList.add('text-white');
      }

      // image fallback helper
      function handleImageError(imgEl) {
        if (!imgEl) return;
        imgEl.dataset.failed = '1';
        imgEl.src = 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(
          `<svg xmlns="http://www.w3.org/2000/svg" width="800" height="450"><rect width="100%" height="100%" fill="#0f1112"/><text x="50%" y="50%" fill="#888" font-size="20" text-anchor="middle" dominant-baseline="middle">No image</text></svg>`
        );
      }

      function exportFullPlayer(dataObj) {
      try {
        if (!dataObj || typeof dataObj !== 'object') return;

        // Helpers
        const safe = v => (v === null || v === undefined) ? '' : v;
        const stringifyIfObject = v => {
          if (v === null || v === undefined) return '';
          if (typeof v === 'object') return JSON.stringify(v);
          return String(v);
        };
        const escapeCsv = (val) => {
          const s = String(val ?? '');
          // If contains quote/comma/newline -> wrap and double quotes
          if (/[",\r\n]/.test(s)) return `"${s.replace(/"/g, '""')}"`;
          return s;
        };

        // Metadata (prefer merged player object)
        const player = dataObj.player || {};
        const fullname = safe(dataObj.fullName || player.name || player.player_name || player.full_name || '');
        const lookupName = safe(dataObj.lookupName || '');
        const number = safe(player.number ?? player.jersey ?? player.jersey_number ?? '');
        const position = safe(player.position || player.pos || '');
        const team = safe(player.team || (dataObj.playerInsight && dataObj.playerInsight.team) || '');
        const minutes = safe(player.minutes_played ?? player.minutes ?? '');
        const dpr = safe(player.dpr ?? player.predicted_dpr ?? player.predictedDpr ?? '');
        const predicted_dpr = safe(player.predicted_dpr ?? player.predictedDpr ?? player.dpr ?? '');
        const dpr_change = safe(player.dpr_change ?? player.dprChange ?? '');
        const status = safe(player.status ?? '');
        const role = safe(player.tactical_role ?? player.tactical ?? '');
        const badge = safe(player.badge ?? '');

        // Raw & per90 (these were set into lastExportPlayer earlier)
        const rawRoot = (dataObj.raw && typeof dataObj.raw === 'object') ? dataObj.raw : {};
        const per90Root = (dataObj.per90 && typeof dataObj.per90 === 'object') ? dataObj.per90 : {};

        // Build metric map: { metricKey: { raw: val, p90: val } }
        const metricMap = Object.create(null);

        // Flatten raw: nested categories -> metric keys like 'possession_passes'
        Object.keys(rawRoot).forEach(k => {
          const v = rawRoot[k];
          if (v && typeof v === 'object' && !Array.isArray(v)) {
            Object.keys(v).forEach(sub => {
              const base = `${k}_${sub}`.replace(/\W+/g, '_').toLowerCase();
              metricMap[base] = metricMap[base] || {};
              metricMap[base].raw = v[sub];
            });
          } else {
            const base = String(k).replace(/\W+/g, '_').toLowerCase();
            metricMap[base] = metricMap[base] || {};
            metricMap[base].raw = v;
          }
        });

        // Normalize per90 keys and merge
        Object.keys(per90Root).forEach(k => {
          let base = String(k);
          // remove trailing _p90 if present
          base = base.replace(/_p90$/i, '');
          base = base.replace(/\W+/g, '_').toLowerCase();
          metricMap[base] = metricMap[base] || {};
          metricMap[base].p90 = per90Root[k];
        });

        // Build CSV header: metadata then metric columns (raw + p90 for each metric)
        const metaHeaders = ['match', 'exported_at', 'player_full_name', 'lookup_name', 'number', 'position', 'team', 'minutes', 'dpr', 'dpr_change', 'status', 'role', 'badge'];
        const metricKeys = Object.keys(metricMap).sort();
        const metricHeaders = [];
        metricKeys.forEach(k => {
          metricHeaders.push(`${k}_raw`);
          metricHeaders.push(`${k}_p90`);
        });
        const headers = metaHeaders.concat(metricHeaders);

        // Single-row values
        const row = [];
        row.push(escapeCsv(SANITIZED_MATCH_NAME || ''));
        row.push(escapeCsv((new Date()).toISOString()));
        row.push(escapeCsv(fullname));
        row.push(escapeCsv(lookupName));
        row.push(escapeCsv(number));
        row.push(escapeCsv(position));
        row.push(escapeCsv(team));
        row.push(escapeCsv(minutes));
        row.push(escapeCsv(dpr));
        row.push(escapeCsv(dpr_change));
        row.push(escapeCsv(status));
        row.push(escapeCsv(role));
        row.push(escapeCsv(badge));

        metricKeys.forEach(k => {
          const ent = metricMap[k] || {};
          const rawVal = (ent.raw === undefined || ent.raw === null) ? '' : stringifyIfObject(ent.raw);
          const p90Val = (ent.p90 === undefined || ent.p90 === null) ? '' : String(ent.p90);
          row.push(escapeCsv(rawVal));
          row.push(escapeCsv(p90Val));
        });

        // CSV string with BOM for Excel compatibility
        const csv = '\uFEFF' + headers.join(',') + '\n' + row.join(',') + '\n';

        // Filename
        const fnameBase = (fullname || lookupName || 'player').toString().trim().replace(/\s+/g, '_').replace(/[^A-Za-z0-9_\-]/g, '');
        const matchPart = (SANITIZED_MATCH_NAME || 'match').toString().replace(/\s+/g, '_').replace(/[^A-Za-z0-9_\-]/g,'');
        const filename = `player-full-performance-${fnameBase}-${matchPart}.csv`;

        // Trigger download
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);

      } catch (err) {
        console.error('exportFullPlayer (CSV) error', err);
      }
    }

      // build merged stats (kept the previous approach)
      function buildMergedStatsTable(rawStatsObj, per90Obj) {
        const categories = [
          { key: 'possession', label: 'Possession' },
          { key: 'distribution', label: 'Distribution' },
          { key: 'attack', label: 'Attack' },
          { key: 'dribbles', label: 'Dribbles' },
          { key: 'defense', label: 'Defense' },
          { key: 'discipline', label: 'Discipline' }
        ];
        function labelForKey(k) { return String(k || '').replace(/_/g,' ').replace(/\b\w/g, ch => ch.toUpperCase()); }
        function findPer90Value(metricKey) {
          if (!per90Obj) return null;
          if (per90Obj.hasOwnProperty(metricKey)) return per90Obj[metricKey];
          if (per90Obj.hasOwnProperty(metricKey + '_p90')) return per90Obj[metricKey + '_p90'];
          const found = Object.keys(per90Obj).find(k => k.toLowerCase().includes(metricKey.toLowerCase()));
          if (found) return per90Obj[found];
          return null;
        }
        const rows = [];
        for (const cat of categories) {
          const rawSection = (rawStatsObj && rawStatsObj[cat.key]) ? rawStatsObj[cat.key] : null;
          const keysSet = new Set();
          if (rawSection && typeof rawSection === 'object') Object.keys(rawSection).forEach(k => keysSet.add(k));
          if (per90Obj && typeof per90Obj === 'object') {
            Object.keys(per90Obj).forEach(k => { if (k.toLowerCase().includes(cat.key) || k.toLowerCase().startsWith(cat.key)) keysSet.add(k); });
          }
          if (keysSet.size === 0) continue;
          rows.push({ type: 'category', label: cat.label });
          const keys = Array.from(keysSet).sort();
          keys.forEach(metricKey => {
            const rawVal = (rawSection && rawSection.hasOwnProperty(metricKey)) ? rawSection[metricKey] : null;
            const per90Val = findPer90Value(metricKey);
            rows.push({ type: 'metric', key: metricKey, label: labelForKey(metricKey), raw: rawVal, p90: per90Val });
          });
        }
        if (rows.length === 0 && per90Obj && typeof per90Obj === 'object') {
          rows.push({ type: 'category', label: 'General' });
          Object.keys(per90Obj).sort().forEach(k => rows.push({ type:'metric', key:k, label: labelForKey(k), raw: null, p90: per90Obj[k] }));
        }
        return rows;
      }

      // Populate modal (defensive)
      async function populateModalForPlayer(displayName) {
        if (!displayName) return;
        try {
          // show skeletons & loading overlay
          renderStatsSkeleton();
          showLoading(true);

          const sanitized = sanitizeInputName(displayName);
          let lookupName = sanitized;
          if (lookupName && !/,/.test(lookupName) && lookupName.split(/\s+/).length === 1 && initialNameEl && initialNameEl.textContent.trim()) {
            lookupName = toLookupNameFromFull(initialNameEl.textContent.trim());
          }
          if (/,/.test(lookupName) && /^\s*[^,]+,\s*[+\-]?\d+(\.\d+)?\s*$/.test(lookupName)) {
            if (initialNameEl && initialNameEl.textContent.trim()) lookupName = toLookupNameFromFull(initialNameEl.textContent.trim());
            else lookupName = lookupName.replace(/,\s*[+\-]?\d+(\.\d+)?\s*$/,'').trim();
          }
          if (!/,/.test(lookupName)) lookupName = toLookupNameFromFull(lookupName);
          const fullName = fullNameFromLookup(lookupName || displayName);

          // placeholders
          if (elTitle) elTitle.textContent = fullName || lookupName;
          if (elInitials) elInitials.textContent = initialsFromFullName(fullName || lookupName);
          if (elDpr) elDpr.textContent = '—';
          if (elDprActual) elDprActual.textContent = '—';
          if (elDprChange) elDprChange.textContent = '—';
          if (elMinutes) elMinutes.textContent = '—';
          if (elBadge) elBadge.textContent = '';
          if (elDprBreakdown) elDprBreakdown.innerHTML = '<div class="text-gray-400">Loading…</div>';
          if (elStatsBody) elStatsBody.innerHTML = '<tr><td class="py-2 text-gray-400" colspan="3">Loading…</td></tr>';
          if (elEnhanced) elEnhanced.textContent = '—';
          if (elStrengths) elStrengths.innerHTML = '';
          if (elDevAreas) elDevAreas.innerHTML = '';
          if (imgHeat) imgHeat.src = '';
          if (imgZones) imgZones.src = '';
          if (jsonDownload) { jsonDownload.href = JSON_INSIGHTS_URL; jsonDownload.target = '_blank'; }

          // Fetch JSONs
          const { insights, derived } = await fetchMatchJsons();

          // Resolve lookup
          let preferredLookup = lookupName;
          if ((!preferredLookup || String(preferredLookup).split(/\s+/).length === 1) && window.__lastSelectedPlayerName) preferredLookup = window.__lastSelectedPlayerName;
          if ((!preferredLookup || String(preferredLookup).split(/\s+/).length === 1) && initialNameEl && initialNameEl.textContent.trim()) preferredLookup = toLookupNameFromFull(initialNameEl.textContent.trim());
          const normalizedTarget = normalizeNameKey(preferredLookup);

          // find playerInsight
          let playerInsight = null;
          if (insights && Array.isArray(insights.players)) {
            playerInsight = insights.players.find(p => normalizeNameKey(p.name) === normalizedTarget) || null;
            if (!playerInsight) {
              playerInsight = insights.players.find(p => {
                const np = normalizeNameKey(p.name);
                return np === normalizedTarget || np.endsWith(normalizedTarget) || normalizedTarget.endsWith(np);
              }) || null;
            }
          }

          // playerDerived
          let playerDerived = null;
          if (derived && typeof derived === 'object') {
            const teamKey = Object.keys(derived)[0];
            const teamObj = derived[teamKey] || {};
            if (preferredLookup && teamObj.hasOwnProperty(preferredLookup)) playerDerived = teamObj[preferredLookup];
            if (!playerDerived && lookupName && teamObj.hasOwnProperty(lookupName)) playerDerived = teamObj[lookupName];
            if (!playerDerived) {
              const foundKey = Object.keys(teamObj).find(k => normalizeNameKey(k) === normalizedTarget || normalizeNameKey(k) === normalizeNameKey(preferredLookup || lookupName));
              if (foundKey) playerDerived = teamObj[foundKey];
            }
            if (!playerDerived) {
              const targetNoDots = normalizedTarget.replace(/\./g, '');
              const foundKey2 = Object.keys(teamObj).find(k => normalizeNameKey(k).replace(/\./g, '') === targetNoDots);
              if (foundKey2) playerDerived = teamObj[foundKey2];
            }
          }

          // merge
          const player = Object.assign({}, playerInsight || {}, playerDerived || {});

          // build raw / per90 early so they are available for both rendering and export
          const raw = player.raw_stats || {};
          const per90 = player.key_stats_p90 || player.key_stats || {};

          // Cache for export: store the merged player plus sources & helpful metadata
          lastExportPlayer = {
            player,
            playerInsight: playerInsight || null,
            playerDerived: playerDerived || null,
            raw: raw || null,
            per90: per90 || null,
            fullName: fullName || null,
            lookupName: lookupName || null,
            timestamp: Date.now()
          };


          // header quick facts
          const number = player.number ?? (player.number === 0 ? 0 : '—');
          const pos = player.position ?? (player.pos ?? '—');
          const team = player.team ?? (insights && insights.team ? insights.team : '—');
          const status = player.status ?? '—';
          const role = player.tactical_role ?? player.tactical ?? '—';
          const badge = player.badge ?? '';

          if (elSub) elSub.textContent = `#${number} • ${pos} • ${team}`;
          if (elTeam) elTeam.textContent = team;
          if (elStatus) elStatus.textContent = status;
          if (elRole) elRole.textContent = role;
          if (elBadge) elBadge.textContent = badge;

          // DPR / mins
          const actualDprVal = (player.dpr !== undefined && player.dpr !== null) ? player.dpr : (player.actual_dpr !== undefined ? player.actual_dpr : null);
          const predictedDprVal = (player.predicted_dpr !== undefined && player.predicted_dpr !== null) ? player.predicted_dpr : (player.predictedDpr !== undefined ? player.predictedDpr : null);

          if (elDprActual) elDprActual.textContent = (actualDprVal === null || actualDprVal === undefined) ? '—' : String(Number(actualDprVal).toFixed(1));
          if (elDpr) elDpr.textContent = (predictedDprVal === null || predictedDprVal === undefined) ? '—' : String(Number(predictedDprVal).toFixed(1));

          const mins = player.minutes_played ?? player.minutes ?? player.minutes_played ?? '—';
          if (elMinutes) elMinutes.textContent = (mins === null || mins === undefined) ? '—' : (isNaN(Number(mins)) ? String(mins) : String(Number(mins).toFixed(2)));

          // DPR change
          const changeVal = (player.dpr_change !== undefined ? player.dpr_change : (player.dprChange !== undefined ? player.dprChange : null));
          applyDprChangeModal(changeVal);

          // DPR breakdown
          if (elDprBreakdown) {
            elDprBreakdown.innerHTML = '';
            const breakdown = player.dpr_breakdown || player.DPR_breakdown || {};
            if (breakdown && Object.keys(breakdown).length) {
              Object.entries(breakdown).forEach(([k,v]) => {
                const label = k.replace(/_/g, ' ').replace(/\b\w/g, ch => ch.toUpperCase());
                const value = (v === null || v === undefined || Number.isNaN(Number(v))) ? '—' : Number(v).toFixed(1);
                const row = document.createElement('div');
                row.className = 'flex items-center justify-between text-sm';
                row.innerHTML = `<div class="text-gray-400">${label}</div><div class="text-white font-semibold">${value}</div>`;
                elDprBreakdown.appendChild(row);
              });
            } else {
              elDprBreakdown.innerHTML = '<div class="text-gray-400">No breakdown available.</div>';
            }
          }

          const rows = buildMergedStatsTable(raw, per90);

          if (elStatsBody) {
            elStatsBody.innerHTML = '';
            if (!rows || rows.length === 0) {
              elStatsBody.innerHTML = '<tr><td class="py-2 text-gray-400" colspan="3">No statistics available.</td></tr>';
            } else {
              for (const r of rows) {
                if (r.type === 'category') {
                  const tr = document.createElement('tr');
                  tr.innerHTML = `<td colspan="3" class="text-xs text-gray-400 bg-[#0f1112] py-1 px-2 font-semibold">${r.label}</td>`;
                  elStatsBody.appendChild(tr);
                } else if (r.type === 'metric') {
                  const tr = document.createElement('tr');
                  const rawDisplay = (r.raw === null || r.raw === undefined || r.raw === '') ? '—' : String(r.raw);
                  const p90Display = (r.p90 === null || r.p90 === undefined || r.p90 === '') ? '—' : (isNaN(Number(r.p90)) ? String(r.p90) : Number(r.p90).toFixed(2));
                  tr.innerHTML = `<td class="py-1 px-2 text-gray-300">${r.label}</td>
                                  <td class="py-1 px-2 text-right text-white font-semibold">${rawDisplay}</td>
                                  <td class="py-1 px-2 text-right text-white font-semibold">${p90Display}</td>`;
                  elStatsBody.appendChild(tr);
                }
              }
            }
          }

          // Enhanced insight + lists
          if (elEnhanced) elEnhanced.textContent = player.enhanced_insight || player.match_recommendation || '—';
          if (elStrengths) {
            elStrengths.innerHTML = '';
            const sList = player.key_strengths || [];
            if (Array.isArray(sList) && sList.length) sList.forEach(it => { const li = document.createElement('li'); li.textContent = it; elStrengths.appendChild(li); });
            else elStrengths.innerHTML = '<li class="text-gray-400">—</li>';
          }
          if (elDevAreas) {
            elDevAreas.innerHTML = '';
            const dList = player.development_areas || [];
            if (Array.isArray(dList) && dList.length) dList.forEach(it => { const li = document.createElement('li'); li.textContent = it; elDevAreas.appendChild(li); });
            else elDevAreas.innerHTML = '<li class="text-gray-400">—</li>';
          }

          // Movement maps
          const authoritativeNameForFiles = (playerInsight && playerInsight.name) ? playerInsight.name : (preferredLookup || lookupName || '');
          const fnameBase = playerFileNameFromDisplay(authoritativeNameForFiles);
          const heatmapUrl = HEATMAPS_BASE + encodeURIComponent(fnameBase + '_heatmap.png');
          const zonesUrl = HEATMAPS_BASE + encodeURIComponent(fnameBase + '_zones.png');

          if (imgHeat) { imgHeat.onerror = () => handleImageError(imgHeat); imgHeat.src = heatmapUrl; }
          if (imgZones) { imgZones.onerror = () => handleImageError(imgZones); imgZones.src = zonesUrl; }

          if (heatZoomBtn) { heatZoomBtn.onclick = () => window.open(heatmapUrl, '_blank', 'noopener'); heatZoomBtn.classList.remove('hidden'); }
          if (zonesZoomBtn) { zonesZoomBtn.onclick = () => window.open(zonesUrl, '_blank', 'noopener'); zonesZoomBtn.classList.remove('hidden'); }

          if (jsonDownload) { jsonDownload.href = JSON_INSIGHTS_URL; jsonDownload.target = '_blank'; }

          openModal();
        } catch (errInner) {
          console.error('populateModalForPlayer inner error', errInner);
        } finally {
          showLoading(false);
        }
      } // populateModalForPlayer

      // expose safe functions
      window.populateModalForPlayer = function(name) {
        try {
          return populateModalForPlayer(name);
        } catch (e) {
          console.error('populateModalForPlayer call error', e);
        }
      };
      window.openPlayerProfile = function(name) {
        try {
          if (typeof window.populateModalForPlayer === 'function') {
            window.populateModalForPlayer(String(name || '').trim());
            return true;
          }
        } catch (e) { console.error('openPlayerProfile error', e); }
        return false;
      };

      // event wiring (buttons, dynamic triggers)
      if (btnViewProfile) {
        btnViewProfile.addEventListener('click', function (ev) {
          try {
            ev.preventDefault();
            const rawAttr = btnViewProfile.getAttribute('data-player-name') || '';
            const focusFull = initialNameEl ? (initialNameEl.textContent || '').trim() : '';
            const lastSelected = window.__lastSelectedPlayerName || '';
            function looksLikeLookupWithGiven(s) {
              if (!s) return false;
              const t = String(s).trim();
              if (/,/.test(t)) {
                const after = t.split(',',2)[1] || '';
                return /\w/.test(after.trim()) && !/^[+\-]?\d+(\.\d+)?$/.test(after.trim());
              }
              return t.split(/\s+/).filter(Boolean).length > 1;
            }
            let candidateRaw = '';
            if (looksLikeLookupWithGiven(rawAttr)) candidateRaw = rawAttr;
            else if (looksLikeLookupWithGiven(focusFull)) candidateRaw = focusFull;
            else if (looksLikeLookupWithGiven(lastSelected)) candidateRaw = lastSelected;
            else candidateRaw = rawAttr || focusFull || lastSelected || '';
            let candidate = sanitizeInputName(candidateRaw || '');
            if (candidate && !/,/.test(candidate) && candidate.split(/\s+/).length === 1) {
              if (looksLikeLookupWithGiven(focusFull)) candidate = focusFull;
              else if (looksLikeLookupWithGiven(lastSelected)) candidate = lastSelected;
            }
            const lookupToUse = (candidate && /,/.test(candidate)) ? candidate : toLookupNameFromFull(candidate || '');
            if (/,/.test(lookupToUse) && /^\s*[^,]+,\s*[+\-]?\d+(\.\d+)?\s*$/.test(lookupToUse) && focusFull) {
              window.populateModalForPlayer(toLookupNameFromFull(focusFull));
              return;
            }
            window.populateModalForPlayer(lookupToUse);
          } catch (e) { console.error('btnViewProfile handler error', e); }
        });
      }

      document.addEventListener('click', function (ev) {
        try {
          const b = ev.target.closest && ev.target.closest('[data-open-player-profile]');
          if (!b) return;
          ev.preventDefault();
          const raw = b.getAttribute('data-player-name') || '';
          let cleaned = sanitizeInputName(raw || (initialNameEl ? initialNameEl.textContent : ''));
          if (!cleaned && initialNameEl) cleaned = initialNameEl.textContent.trim();
          if (cleaned && !/,/.test(cleaned) && cleaned.split(/\s+/).length === 1 && initialNameEl) cleaned = initialNameEl.textContent.trim();
          const lookup = /,/.test(cleaned) ? cleaned : toLookupNameFromFull(cleaned);
          window.populateModalForPlayer(lookup);
        } catch (e) { console.error('data-open-player-profile handler error', e); }
      });

      // copy name
      if (copyBtn) {
        copyBtn.addEventListener('click', function () {
          try {
            const text = (elTitle && elTitle.textContent) ? elTitle.textContent.trim() : '';
            if (!text) return;
            navigator.clipboard.writeText(text).then(() => {
              copyBtn.textContent = 'Copied';
              setTimeout(() => copyBtn.textContent = 'Copy', 1200);
            }).catch(() => { /* ignore */ });
          } catch (e) { console.error('copyBtn error', e); }
        });
      }

      // close handlers (backdrop, close buttons, ESC)
      if (backdrop) backdrop.addEventListener('click', closeModal);
      closeBtns.forEach(b => { if (b) b.addEventListener('click', closeModal); });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
      });

      // Export button: click -> download full player performance JSON
      if (jsonDownload) {
        jsonDownload.addEventListener('click', function (e) {
          try {
            e.preventDefault && e.preventDefault();
            if (!lastExportPlayer) {
              // graceful fallback: fetch/notify
              alert('Player profile not loaded yet — please open a player profile first.');
              return;
            }
            exportFullPlayer(lastExportPlayer);
          } catch (err) { console.error('jsonDownload click error', err); }
        });
      }

      // image zoom buttons: in case they are present (no-op otherwise)
      if (heatZoomBtn) heatZoomBtn.addEventListener('click', () => { try { if (imgHeat && imgHeat.src) window.open(imgHeat.src, '_blank', 'noopener'); } catch(e){} });
      if (zonesZoomBtn) zonesZoomBtn.addEventListener('click', () => { try { if (imgZones && imgZones.src) window.open(imgZones.src, '_blank', 'noopener'); } catch(e){} });

      // done initialization
      console.debug('Player modal initialized');
    } catch (initErr) {
      console.error('Player modal init error', initErr);
      // Expose safe no-op functions so callers don't break
      window.populateModalForPlayer = function () { console.warn('populateModalForPlayer: init failed'); };
      window.openPlayerProfile = function () { console.warn('openPlayerProfile: init failed'); return false; };
    }
  } // initPlayerModalScript

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPlayerModalScript);
  } else {
    // already ready
    setTimeout(initPlayerModalScript, 0);
  }
})();
</script>
