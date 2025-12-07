<div class="w-full flex flex-col gap-6 px-6 font-sans antialiased text-slate-200">

    <!-- Position Counters -->
    <div class="w-full flex gap-4 justify-between py-2">
        <div class="w-full flex flex-col bg-gradient-to-b from-[#1b1b1b] to-[#141414] rounded-2xl items-center p-6 gap-1 shadow hover:shadow-lg transition">
            <span class="text-3xl text-white font-bold"><?php echo isset($defenders_count) ? $defenders_count : 0; ?></span>
            <span class="text-sm text-gray-400 text-center">Defenders</span>
        </div>
        <div class="w-full flex flex-col bg-gradient-to-b from-[#1b1b1b] to-[#141414] rounded-2xl items-center p-6 gap-1 shadow hover:shadow-lg transition">
            <span class="text-3xl text-white font-bold"><?php echo isset($midfielders_count) ? $midfielders_count : 0; ?></span>
            <span class="text-sm text-gray-400 text-center">Midfielders</span>
        </div>
        <div class="w-full flex flex-col bg-gradient-to-b from-[#1b1b1b] to-[#141414] rounded-2xl items-center p-6 gap-1 shadow hover:shadow-lg transition">
            <span class="text-3xl text-white font-bold"><?php echo isset($attackers_count) ? $attackers_count : 0; ?></span>
            <span class="text-sm text-gray-400 text-center">Attackers</span>
        </div>
    </div>

    <!-- Players Count -->
    <div class="w-full py-2">
        <p class="text-white text-lg font-semibold">
            All Players in this match (<?php echo isset($players_count) ? $players_count : 0; ?>)
        </p>
    </div>

    <!-- Player Cards Grid -->
    <div id="player-card-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6 py-2">
        <?php if (!empty($players)): ?>
            <?php foreach ($players as $idx => $player): ?>
                <?php $player_json_attr = htmlspecialchars(json_encode($player), ENT_QUOTES, 'UTF-8'); ?>
                <div
                    class="player-card bg-gradient-to-b from-[#1b1b1b] to-[#141414] rounded-2xl shadow hover:shadow-xl transition transform hover:-translate-y-1 hover:scale-105 cursor-pointer flex flex-col justify-between"
                    data-player="<?php echo $player_json_attr; ?>"
                    data-player-index="<?php echo $idx; ?>"
                    role="button"
                    tabindex="0"
                >
                    <!-- Player Rating -->
                    <div class="p-4">
                        <span class="text-xl text-white font-bold"><?php echo isset($player['rating']) ? htmlspecialchars($player['rating']) : '-'; ?></span>
                    </div>

                    <!-- Player Info -->
                    <div class="w-full px-4 py-3 flex justify-between">
                        <div class="flex flex-col gap-1">
                            <span class="text-xl text-white font-bold truncate max-w-[120px]" title="<?php echo htmlspecialchars($player['raw_name']); ?>">
                                <?php echo htmlspecialchars($player['surname'] ?? '-'); ?>
                            </span>
                            <span class="text-xs text-gray-400 font-semibold truncate max-w-[150px]">
                                <?php echo htmlspecialchars($player['given_name'] ?? '-'); ?>
                            </span>
                        </div>
                        <div class="flex flex-col justify-between items-center pl-2">
                            <span class="text-md text-white font-bold"><?php echo htmlspecialchars($player['jersey_number'] ?? '-'); ?></span>
                            <span class="text-sm text-gray-300 font-normal"><?php echo htmlspecialchars($player['position'] ?? '-'); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full text-center py-10">
                <span class="text-gray-500">No player data available.</span>
            </div>
        <?php endif; ?>
    </div>
</div>


<?php $this->load->view('partials/player_modal'); ?>

<script>
/* Focus panel binder (DOMContentLoaded-aware) - includes strengths + development chips */
(function () {
  const CHART_JS_CDN = 'https://cdn.jsdelivr.net/npm/chart.js';

  function htmlEntityDecode(s) {
    if (typeof s !== 'string') return s;
    return s.replace(/&quot;/g, '"').replace(/&#039;/g, "'").replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>');
  }

  function safeParsePlayerJson(raw) {
    if (!raw) return null;
    try { return JSON.parse(raw); }
    catch (err) {
      try { return JSON.parse(htmlEntityDecode(raw)); }
      catch (err2) {
        try {
          const attempt = raw.replace(/&quot;/g, '"').replace(/&#039;/g, "'").replace(/&amp;/g, '&');
          return JSON.parse(attempt);
        } catch (err3) {
          console.error('safeParsePlayerJson failed', err, err2, err3);
          return null;
        }
      }
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('player-card-container');
    const focusPanel = document.getElementById('focus-panel');
    const focusWrapper = document.getElementById('focus-panel-wrapper');
    const dprCanvas = document.getElementById('dpr-chart');

    if (!container || !focusPanel || !focusWrapper) {
      console.warn('Player focus binder: required DOM elements missing.');
      return;
    }

    let currentlySelectedCard = null;
    let dprChartInstance = null;

    function setText(id, text) {
      const el = document.getElementById(id);
      if (!el) return;
      el.textContent = text !== undefined && text !== null && String(text) !== '' ? String(text) : '-';
    }
    function setHTML(id, html) {
      const el = document.getElementById(id);
      if (!el) return;
      el.innerHTML = html ?? '';
    }
    // --- Replace the initialsFromName function with this:
    function initialsFromName(name) {
      if (!name) return '';

      // normalize whitespace and remove extraneous punctuation except commas (we need comma to detect "Surname, Given")
      let s = String(name).replace(/\s+/g, ' ').trim();

      // if input contains a comma, assume "Surname, Given ..." and pick first letter of first given name + first letter of surname
      if (s.includes(',')) {
        const [surnamePart, givenPart] = s.split(',', 2).map(x => x.trim());
        const surname = (surnamePart || '').split(/\s+/)[0].replace(/[^\p{L}\p{N}-]/gu, '');
        const givenWords = (givenPart || '').split(/\s+/).filter(Boolean);
        const firstGiven = (givenWords[0] || '').replace(/[^\p{L}\p{N}-]/gu, '');
        const a = (firstGiven.charAt(0) || '').toUpperCase();
        const b = (surname.charAt(0) || '').toUpperCase();
        return (a + b) || '';
      }

      // otherwise split into words, remove common suffixes and punctuation
      let parts = s.split(/\s+/).filter(Boolean).map(p => p.replace(/[.,]/g, ''));

      // drop common suffixes like Jr, Sr, II, III, PhD, MD (case-insensitive)
      const suffixRegex = /^(jr|sr|ii|iii|iv|v|phd|md)$/i;
      if (parts.length > 1 && suffixRegex.test(parts[parts.length - 1])) {
        parts.pop();
      }

      if (parts.length === 0) return '';
      if (parts.length === 1) {
        // single name -> return first two letters
        return parts[0].slice(0, 2).toUpperCase();
      }

      // multi-word name -> first letter of first word + first letter of last word
      const firstInitial = (parts[0].charAt(0) || '').toUpperCase();
      const lastInitial = (parts[parts.length - 1].charAt(0) || '').toUpperCase();
      return (firstInitial + lastInitial) || '';
    }
    function fmtLabel(k) {
      if (!k) return '';
      return k.replace(/[_\-]/g, ' ').replace(/\b\w/g, ch => ch.toUpperCase());
    }
    function escapeHtml(s) {
      return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
    }

    function loadChartJsOnce() {
      return new Promise((resolve, reject) => {
        if (window.Chart) return resolve(window.Chart);
        const s = document.createElement('script');
        s.src = CHART_JS_CDN;
        s.onload = () => resolve(window.Chart);
        s.onerror = (e) => reject(new Error('Failed to load Chart.js: ' + e));
        document.head.appendChild(s);
      });
    }

    async function renderDprChart(breakdownObj) {
      if (!dprCanvas) return;
      const ctx = dprCanvas.getContext('2d');
      if (!breakdownObj || typeof breakdownObj !== 'object') {
        if (dprChartInstance) { dprChartInstance.destroy(); dprChartInstance = null; }
        ctx && ctx.clearRect(0, 0, dprCanvas.width, dprCanvas.height);
        return;
      }
      const labels = [], data = [];
      for (const [k, v] of Object.entries(breakdownObj)) {
        labels.push(fmtLabel(k));
        data.push(typeof v === 'number' ? v : parseFloat(v) || 0);
      }
      try {
        const Chart = window.Chart || (await loadChartJsOnce());
        const colors = generateColors(data.length);
        if (dprChartInstance) {
          dprChartInstance.data.labels = labels;
          dprChartInstance.data.datasets = [{ label:'DPR components', data, backgroundColor: colors, borderRadius:6, barThickness:18 }];
          dprChartInstance.update();
        } else {
          dprChartInstance = new Chart(ctx, {
            type: 'bar',
            data: { labels, datasets: [{ label:'DPR components', data, backgroundColor: colors, borderRadius:6, barThickness:18 }] },
            options: {
              maintainAspectRatio:false,
              plugins:{ legend:{ display:false }, tooltip:{ callbacks:{ label: function(context){ return `${context.label}: ${context.raw}`; } } } },
              scales:{ x:{ ticks:{ color:'#cbd5e1' }, grid:{ display:false } }, y:{ beginAtZero:true, ticks:{ color:'#9ca3af' }, grid:{ color:'rgba(255,255,255,0.03)' } } }
            }
          });
        }
      } catch (err) { console.error('Chart error', err); }
    }

    function generateColors(n) {
      const palette = ['rgba(99,102,241,0.9)','rgba(16,185,129,0.9)','rgba(14,165,233,0.9)','rgba(234,88,12,0.9)','rgba(236,72,153,0.9)','rgba(249,115,22,0.9)','rgba(79,70,229,0.9)'];
      const out = [];
      for (let i=0;i<n;i++) out.push(palette[i % palette.length]);
      return out;
    }

    function renderDprBreakdownRows(breakdownObj) {
      const el = document.getElementById('focus-dpr-breakdown'); if (!el) return;
      if (!breakdownObj || typeof breakdownObj !== 'object') { el.innerHTML = '<div class="text-gray-500">No DPR breakdown available.</div>'; return; }
      const rows = [];
      for (const [k, v] of Object.entries(breakdownObj)) {
        const val = (typeof v === 'number') ? v : parseFloat(v) || 0;
        rows.push(`<div class="flex justify-between items-center"><div class="text-gray-300 text-sm">${fmtLabel(k)}</div><div class="text-white font-semibold text-sm">${val}</div></div>`);
      }
      el.innerHTML = rows.join('');
    }

    function renderKeyStats(statsObj) {
      const el = document.getElementById('focus-key-stats'); if (!el) return;
      if (!statsObj || typeof statsObj !== 'object') { el.innerHTML = '<tr><td class="py-2 text-gray-500">No key stats available.</td><td></td></tr>'; return; }
      const rows = [];
      for (const [k, v] of Object.entries(statsObj)) rows.push(`<tr><td class="py-2 text-gray-300">${fmtLabel(k)}</td><td class="py-2 text-right text-white font-semibold">${String(v)}</td></tr>`);
      el.innerHTML = rows.join('');
    }

    // render chips: variant = 'strength' | 'dev'
    function renderChips(id, arr, variant='dev') {
      const el = document.getElementById(id);
      if (!el) return;
      if (!Array.isArray(arr) || arr.length === 0) {
        el.innerHTML = variant === 'strength'
          ? '<span class="text-xs px-2 py-1 rounded bg-green-900 text-green-200">No strengths listed</span>'
          : '<span class="text-xs px-2 py-1 rounded bg-[#17202b] text-gray-400">No development notes</span>';
        return;
      }
      // normalize, trim, dedupe
      const normalized = Array.from(new Set(arr.map(x => (x||'').toString().trim()).filter(Boolean)));
      const html = normalized.map(item => {
        if (variant === 'strength') {
          return `<span class="text-xs px-2 py-1 rounded bg-green-800 text-green-100">${escapeHtml(item)}</span>`;
        } else {
          return `<span class="text-xs px-2 py-1 rounded bg-[#17202b] text-gray-300">${escapeHtml(item)}</span>`;
        }
      }).join('');
      el.innerHTML = html;
    }

    async function renderFocus(p) {
      console.log('renderFocus called with:', p);
      const surname = p.surname ?? '';
      const given = p.given_name ?? '';
      const rawName = p.raw_name ?? '';
      const playerName = p.player_name ?? '';
      const full_name_prop = p.full_name ?? null;

      // Build a sensible fullName:
      // prefer explicit full_name property, then "given + surname" if both available,
      // otherwise prefer given_name/raw_name/player_name/name, and finally surname as a last resort.
      let fullName = full_name_prop || '';
      if (!fullName) {
        if (given && surname) {
          fullName = `${given} ${surname}`;
        } else {
          fullName = given || rawName || playerName || p.name || surname || '';
        }
      }

      const position = p.position ?? p.pos ?? '';
      const jersey = p.jersey_number ?? p.number ?? '';
      const rating = (p.rating !== undefined && p.rating !== null) ? p.rating : (p.match_rating ?? null);
      const predictedDpr = p.predicted_dpr ?? p.predictedDPR ?? p.dpr ?? null;
      const dprChange = p.dpr_change ?? p.dprChange ?? null;
      const minutes = p.minutes_played ?? p.minutes ?? null;
      const notes = p.notes ?? (p.match_recommendation ?? '') ?? '';
      const dpr_breakdown = p.dpr_breakdown ?? p.dprBreakdown ?? null;
      const key_stats = p.key_stats_p90 ?? p.key_stats ?? null;
      const badge = p.badge ?? null;

      // prefer separate arrays
      const strengths = Array.isArray(p.key_strengths) ? p.key_strengths : (typeof p.key_strengths === 'string' ? p.key_strengths.split(/[,;\n|]+/) : []);
      const devAreas = Array.isArray(p.development_areas) ? p.development_areas : (typeof p.development_areas === 'string' ? p.development_areas.split(/[,;\n|]+/) : []);

      // Notes: original notes plus summary of strengths (short)
      let notesText = notes || '';
      const normalizedStrengths = Array.from(new Set((strengths||[]).map(x => (x||'').toString().trim()).filter(Boolean)));
      if (normalizedStrengths.length > 0) {
        notesText = notesText ? (notesText) + '\n\n' : '';
      }

      setText('focus-fullname', fullName || '-');
      setText('focus-subtitle', position ? (position + (p.team ? ' • ' + p.team : '')) : (p.team ?? '-'));
      setText('focus-rating', rating !== null ? rating : '-');

      const initials = initialsFromName(fullName || surname || given);
      const avatarEl = document.getElementById('focus-avatar-initials');
      if (avatarEl) avatarEl.textContent = initials;

      setText('focus-position', position || '-');
      setText('focus-jersey', jersey !== null && jersey !== undefined && String(jersey) !== '' ? String(jersey) : '-');
      setText('focus-minutes', minutes !== null && minutes !== undefined ? String(minutes) : '-');

      setText('focus-dpr', (typeof p.dpr === 'number' || typeof p.dpr === 'string') ? String(p.dpr) : (predictedDpr !== null ? String(predictedDpr) : '-'));
      setText('focus-predicted-dpr', predictedDpr !== null ? String(predictedDpr) : '-');
      setText('focus-dpr-change', dprChange !== null ? (String(dprChange).startsWith('-') ? String(dprChange) : '+' + String(dprChange)) : '-');

      const badgeEl = document.getElementById('focus-badge'); if (badgeEl) badgeEl.textContent = badge ?? '';

      // set notes (with strengths summary appended)
      setText('focus-notes', notesText);

      // render strengths and development chips separately
      renderChips('focus-strengths', normalizedStrengths, 'strength');
      renderChips('focus-development', devAreas, 'dev');

      // DPR, key stats, chart
      renderDprBreakdownRows(dpr_breakdown);
      renderKeyStats(key_stats);
      if (dpr_breakdown && typeof dpr_breakdown === 'object') {
        await renderDprChart(dpr_breakdown);
      } else {
        await renderDprChart(null);
      }
    }

        // keeps track of the previously applied style classes so clear is deterministic
    const SELECTED_CLASSES = [
      'bg-indigo-500',        // background
      'hover:bg-indigo-500/80', // hover bg override
      'ring-2',               // focus ring
      'ring-indigo-400',      // ring color
      'player-card--selected' // semantic marker (optional)
    ];

    function removeSelectedClasses(el) {
      if (!el || !el.classList) return;
      SELECTED_CLASSES.forEach(c => el.classList.remove(c));
      // also remove inline background-color if applied earlier as style
      if (el.style && el.style.backgroundColor) el.style.backgroundColor = '';
    }

    function highlightCard(card) {
      if (!card) return;

      // If the card is already selected, do nothing here (toggle handled elsewhere)
      if (card === currentlySelectedCard) {
        // ensure classes present
        SELECTED_CLASSES.forEach(c => card.classList.add(c));
        return;
      }

      // remove selection classes from previously selected card
      if (currentlySelectedCard && currentlySelectedCard !== card) {
        removeSelectedClasses(currentlySelectedCard);
        // optionally restore subtle transform/hover if you removed it previously
        currentlySelectedCard.classList.remove('transform-none'); // example
      }

      // add selection classes to new card
      SELECTED_CLASSES.forEach(c => card.classList.add(c));

      // give a11y focus and ensure keyboard scroller consistency
      try { card.focus({ preventScroll: true }); } catch (e) { /* ignore on older browsers */ }

      // smooth scroll into view (but keep nearest block to avoid jumping)
      card.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });

      currentlySelectedCard = card;
    }

    function activateCard(card) {
      const raw = card.getAttribute('data-player');
      const obj = safeParsePlayerJson(raw);
      if (!obj) {
        console.error('activateCard: failed to parse player object.');
        return;
      }

      // If the clicked card is already active, toggle off
      if (card === currentlySelectedCard) {
        clearFocus();
        return;
      }

      // perform highlight and render focus
      highlightCard(card);

      // render details into focus panel
      renderFocus(obj);
    }

    // helper: clear the focus panel and revert to initial summary
    function clearFocus() {
      // remove UI highlight from currently selected card
      if (currentlySelectedCard) {
        removeSelectedClasses(currentlySelectedCard);
        // remove any additional selection-specific inline style or attributes
        currentlySelectedCard.removeAttribute('aria-selected');
        try { currentlySelectedCard.blur(); } catch (e) { /* ignore */ }
      }

      // destroy chart instance if present
      if (dprChartInstance) {
        try { dprChartInstance.destroy(); } catch (e) { /* ignore */ }
        dprChartInstance = null;
      }

      currentlySelectedCard = null;

      // Reset visible focus fields to sensible placeholders
      setText('focus-fullname', 'Select a player');
      setText('focus-subtitle', '-');
      setText('focus-rating', '-');
      const avatarEl = document.getElementById('focus-avatar-initials');
      if (avatarEl) avatarEl.textContent = 'JS';
      setText('focus-position', '-');
      setText('focus-jersey', '-');
      setText('focus-minutes', '-');
      setText('focus-dpr', '-');
      setText('focus-predicted-dpr', '-');
      setText('focus-dpr-change', '-');
      setText('focus-notes', 'Select a player to see notes.');

      // DPR breakdown / key stats / chips -> minimal placeholders
      const dprBreakEl = document.getElementById('focus-dpr-breakdown');
      if (dprBreakEl) dprBreakEl.innerHTML = '<div class="text-gray-500">No DPR breakdown available.</div>';
      const keyStatsEl = document.getElementById('focus-key-stats');
      if (keyStatsEl) keyStatsEl.innerHTML = '<tr><td class="py-2 text-gray-500">No key stats available.</td><td></td></tr>';
      const strengthsEl = document.getElementById('focus-strengths');
      if (strengthsEl) strengthsEl.innerHTML = '<span class="text-xs px-2 py-1 rounded bg-green-900 text-green-200">—</span>';
      const devEl = document.getElementById('focus-development');
      if (devEl) devEl.innerHTML = '<span class="text-xs px-2 py-1 rounded bg-[#17202b] text-gray-400">—</span>';

      // show the initial summary and hide the detail template (use global helpers if available)
      if (typeof window.showInitialSummary === 'function') {
        try { window.showInitialSummary(); } catch (e) { /* ignore */ }
      } else {
        const s = document.getElementById('focus-initial-summary'); if (s) s.style.display = '';
      }
      if (typeof window.hideDetailTemplate === 'function') {
        try { window.hideDetailTemplate(); } catch (e) { /* ignore */ }
      } else {
        const d = document.getElementById('focus-detail-template'); if (d) d.style.display = 'none';
      }
    }


    // Toggle-aware click handler
    container.addEventListener('click', function (ev) {
      const card = ev.target.closest('.player-card');
      if (!card) return;

      // If clicking the currently active card -> toggle off
      if (card === currentlySelectedCard) {
        clearFocus();
        return;
      }

      // Otherwise activate
      activateCard(card);
      // Hide initial summary and show detail template (preserve current behaviour)
      if (typeof window.hideInitialSummary === 'function') {
        try { window.hideInitialSummary(); } catch (e) { /* ignore */ }
      } else {
        const s = document.getElementById('focus-initial-summary'); if (s) s.style.display = 'none';
      }
      if (typeof window.showDetailTemplate === 'function') {
        try { window.showDetailTemplate(); } catch (e) { /* ignore */ }
      } else {
        const d = document.getElementById('focus-detail-template'); if (d) d.style.display = '';
      }
    });

    // Toggle-aware keyboard handler (Enter / Space)
    container.addEventListener('keydown', function (ev) {
      const card = ev.target.closest('.player-card');
      if (!card) return;
      if (ev.key === 'Enter' || ev.key === ' ') {
        ev.preventDefault();
        if (card === currentlySelectedCard) {
          clearFocus();
        } else {
          activateCard(card);
          if (typeof window.hideInitialSummary === 'function') {
            try { window.hideInitialSummary(); } catch (e) { /* ignore */ }
          } else {
            const s = document.getElementById('focus-initial-summary'); if (s) s.style.display = 'none';
          }
          if (typeof window.showDetailTemplate === 'function') {
            try { window.showDetailTemplate(); } catch (e) { /* ignore */ }
          } else {
            const d = document.getElementById('focus-detail-template'); if (d) d.style.display = '';
          }
        }
      }
    });

    window.showPlayerInFocus = function (playerObj) {
      const normName = (playerObj.full_name || playerObj.raw_name || playerObj.name || playerObj.player_name || playerObj.surname || '').toString().trim().toLowerCase();
      if (normName) {
        const cards = container.querySelectorAll('.player-card');
        for (const c of cards) {
          const raw = c.getAttribute('data-player');
          const obj = safeParsePlayerJson(raw);
          if (!obj) continue;
          const candidateName = (obj.full_name || obj.raw_name || obj.name || obj.player_name || obj.surname || '').toString().trim().toLowerCase();
          if (candidateName && candidateName === normName) { activateCard(c); return; }
        }
      }
      renderFocus(playerObj);
    };
  });
})();
</script>
