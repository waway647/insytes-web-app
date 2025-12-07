/**
 * matchCardResultsHandler.js
 * Reports + match-card results + search + filter (season, competition)
 *
 * Replaces your existing file. Designed to be defensive:
 * - Works if your HTML has id="searchInput" or not (falls back to placeholder selector).
 * - Injects a modal if one doesn't exist.
 * - Uses controller-provided data.seasons and data.competitions when available.
 */

(() => {
  const API_BASE = (window.APP_BASE_URL ? window.APP_BASE_URL.replace(/\/$/, '') + '/' : '') + 'reports/reportscontroller';
  const API_START_TAGGING = API_BASE + '/start_tagging';
  const API_REMOVE_MATCH   = API_BASE + '/remove_match';
  const TAGGING_PAGE_BASE  = (window.APP_BASE_URL ? window.APP_BASE_URL.replace(/\/$/, '') + '/' : '') + 'studio/mediacontroller/index';
  const REPORT_PAGE_BASE = (window.APP_BASE_URL ? window.APP_BASE_URL.replace(/\/$/, '') + '/' : '/') + 'reports/overviewcontroller/index';
  const FETCH_URL = API_BASE + '/get_all_match_reports';

  const RESULT_COLORS = {
    'win': '#209435',
    'lose': '#B14D35',
    'draw': '#B6BABD'
  };

  // State
  let ALL_MATCHES_FLAT = [];
  let ORIGINAL_RESPONSE_SEASON = null;
  let DATA_SEASONS = null;
  let DATA_COMPETITIONS = null;
  let monthCardTemplateClone = null;
  let matchCardTemplateClone = null;

  // Logging
  function logDebug(...args) { console.debug('[match-results]', ...args); }
  function logInfo(...args) { console.info('[match-results]', ...args); }
  function logWarn(...args) { console.warn('[match-results]', ...args); }
  function logError(...args) { console.error('[match-results]', ...args); }

  function debounce(fn, wait = 200) {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), wait);
    };
  }

  // Re-use attachOptionsHandlers from your file (slightly adapted to avoid duplication)
  function attachOptionsHandlers(matchCardClone, matchId, matchNameConfig = '') {
    // same code as before but without re-defining everything; keep behavior identical
    matchCardClone.classList.add('match-card');
    matchCardClone.dataset.matchId = String(matchId ?? '');
    matchCardClone.dataset.matchNameConfig = String(matchNameConfig ?? '');

    if (!matchNameConfig) {
      const possibleNameEl = matchCardClone.querySelector('[data-role="match-name"], .match-name, #match-name');
      const possibleNameText = possibleNameEl ? (possibleNameEl.textContent || '').trim() : '';
      if (possibleNameText) {
        matchCardClone.dataset.matchNameConfig = String(possibleNameText);
      }
    }

    const cardOptionsBtn = matchCardClone.querySelector('#card-options-btn, .card-options-btn, [data-role="card-options-btn"]');
    const matchOptionsEl  = matchCardClone.querySelector('#match-options, .match-options, [data-role="match-options"]');
    const startBtn        = matchCardClone.querySelector('#start-tagging-btn, .start-tagging-btn, [data-role="start-tagging-btn"]');
    const removeBtn       = matchCardClone.querySelector('#remove-match-card-btn, .remove-match-card-btn, [data-role="remove-match-card-btn"]');

    if (cardOptionsBtn && matchOptionsEl) {
      cardOptionsBtn.addEventListener('click', (ev) => {
        ev.preventDefault(); ev.stopPropagation();
        document.querySelectorAll('.match-options.open, #match-options.open').forEach(el => {
          if (el !== matchOptionsEl) { el.classList.remove('open'); el.classList.add('hidden'); }
        });
        const isOpen = matchOptionsEl.classList.contains('open');
        if (isOpen) { matchOptionsEl.classList.remove('open'); matchOptionsEl.classList.add('hidden'); }
        else {
          matchOptionsEl.classList.remove('hidden'); matchOptionsEl.classList.add('open');
          const rect = cardOptionsBtn.getBoundingClientRect();
          matchOptionsEl.style.position = 'absolute';
          matchOptionsEl.style.left = `${rect.right + window.scrollX + 2}px`;
          matchOptionsEl.style.top = `${rect.top + window.scrollY}px`;
          matchOptionsEl.style.zIndex = 9999;
        }
      });
    }

    if (startBtn) {
      startBtn.addEventListener('click', async (ev) => {
        ev.stopPropagation();
        const origText = startBtn.textContent;
        startBtn.textContent = 'Starting...';
        startBtn.setAttribute('disabled', 'disabled');
        if (matchOptionsEl) { matchOptionsEl.classList.remove('open'); matchOptionsEl.classList.add('hidden'); }

        try {
          const payloadBody = { match_id: matchId };
          const effectiveMatchName = matchNameConfig || matchCardClone.dataset.matchNameConfig || '';
          if (effectiveMatchName) payloadBody.match_name = effectiveMatchName;

          const res = await fetch(API_START_TAGGING, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payloadBody)
          });
          let payload;
          try { payload = await res.json(); } catch (e) { payload = { success: res.ok }; }

          if (!res.ok || payload.success === false) {
            alert(payload.message || `Failed to start tagging for ${matchId}`);
          } else {
            if (payload.redirect_url) {
              let redirect = String(payload.redirect_url);
              if (!/[\?&]match_id=/.test(redirect)) redirect += (redirect.includes('?') ? '&' : '?') + 'match_id=' + encodeURIComponent(matchId);
              if (effectiveMatchName && !/[\?&]match_name=/.test(redirect)) redirect += '&match_name=' + encodeURIComponent(effectiveMatchName);
              window.location.href = redirect; return;
            }
            if (TAGGING_PAGE_BASE) {
              window.location.href = TAGGING_PAGE_BASE + '?match_id=' + encodeURIComponent(matchId) + (effectiveMatchName ? '&match_name=' + encodeURIComponent(effectiveMatchName) : '');
              return;
            }
            alert('Tagging started.');
          }
        } catch (err) {
          alert('Network error while starting tagging.');
        } finally {
          startBtn.textContent = origText;
          startBtn.removeAttribute('disabled');
        }
      });
    }

    if (removeBtn) {
      removeBtn.addEventListener('click', async (ev) => {
        ev.stopPropagation();
        if (!confirm('Remove this match from the library? This action cannot be undone.')) return;
        const origText = removeBtn.textContent;
        removeBtn.textContent = 'Removing...';
        removeBtn.setAttribute('disabled', 'disabled');
        if (matchOptionsEl) { matchOptionsEl.classList.remove('open'); matchOptionsEl.classList.add('hidden'); }
        try {
          const res = await fetch(API_REMOVE_MATCH, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ match_id: matchId })
          });
          let payload;
          try { payload = await res.json(); } catch (e) { payload = { success: res.ok }; }
          if (!res.ok || payload.success === false) {
            alert(payload.message || `Failed to remove match ${matchId}`);
            removeBtn.textContent = origText; removeBtn.removeAttribute('disabled');
          } else {
            matchCardClone.remove();
          }
        } catch (err) {
          alert('Network error while removing match.');
          removeBtn.textContent = origText; removeBtn.removeAttribute('disabled');
        }
      });
    }

    const clickableArea = matchCardClone.querySelector('.match-card-body') || matchCardClone;
    clickableArea.style.cursor = 'pointer';
    clickableArea.addEventListener('click', (ev) => {
      if (ev.target.closest('.card-options-btn') || ev.target.closest('#card-options-btn') || ev.target.closest('#match-options')) {
        return;
      }
      const effectiveMatchName = matchNameConfig || matchCardClone.dataset.matchNameConfig || (matchCardClone.querySelector('[data-role="match-name"], .match-name, #match-name')?.textContent || '').trim() || '';
      const url = REPORT_PAGE_BASE + '?match_id=' + encodeURIComponent(matchId) + (effectiveMatchName ? '&match_name=' + encodeURIComponent(effectiveMatchName) : '');
      window.location.href = url;
    });
  }

  // Create filter modal markup and append to document if not present
  function ensureFilterModalExists() {
    if (document.getElementById('match-filter-modal')) return;

    const modalHtml = `
      <div id="match-filter-modal" class="hidden fixed inset-0 z-50 items-center justify-center">
        <div id="match-filter-modal-backdrop" class="absolute inset-0 bg-black/60"></div>
        <div class="relative z-10 w-full max-w-md p-6 rounded-lg bg-[#0b0b0b] border border-white/6 shadow-2xl">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-white">Filter matches</h3>
            <button id="match-filter-close" class="text-white/70 hover:text-white">✕</button>
          </div>
          <div class="flex flex-col gap-4">
            <label class="flex flex-col text-sm text-white">
              <span class="mb-1">Season</span>
              <select id="filter-season" class="px-3 py-2 rounded bg-[#0f0f0f] border border-white/6 text-white text-sm">
                <option value="">All seasons</option>
              </select>
            </label>
            <label class="flex flex-col text-sm text-white">
              <span class="mb-1">Competition</span>
              <select id="filter-competition" class="px-3 py-2 rounded bg-[#0f0f0f] border border-white/6 text-white text-sm">
                <option value="">All competitions</option>
              </select>
            </label>
          </div>
          <div class="mt-6 flex justify-end gap-3">
            <button id="filter-clear" class="px-4 py-2 rounded bg-transparent border border-white/6 text-white text-sm hover:bg-white/5">Clear</button>
            <button id="filter-apply" class="px-4 py-2 rounded bg-[#1d1d1d] text-white text-sm hover:bg-[#232323]">Apply</button>
          </div>
        </div>
      </div>
    `;
    const div = document.createElement('div');
    div.innerHTML = modalHtml;
    document.body.appendChild(div.firstElementChild);

    // wire modal controls (basic)
    document.getElementById('match-filter-modal-backdrop').addEventListener('click', hideFilterModal);
    document.getElementById('match-filter-close').addEventListener('click', hideFilterModal);
    document.getElementById('filter-apply').addEventListener('click', (ev) => { hideFilterModal(); applyFiltersAndSearch(); });
    document.getElementById('filter-clear').addEventListener('click', (ev) => {
      const seasonSelect = document.getElementById('filter-season');
      const compSelect = document.getElementById('filter-competition');
      if (seasonSelect) seasonSelect.value = '';
      if (compSelect) compSelect.value = '';
      hideFilterModal();
      applyFiltersAndSearch();
    });
  }

  function showFilterModal() {
    const modal = document.getElementById('match-filter-modal');
    if (!modal) return;
    modal.classList.remove('hidden');
    const sel = modal.querySelector('select, button, [tabindex]');
    if (sel) sel.focus();
  }
  function hideFilterModal() {
    const modal = document.getElementById('match-filter-modal');
    if (!modal) return;
    modal.classList.add('hidden');
  }

  // Flatten controller response months -> flat array
  function flattenApiResponse(data) {
    const flat = [];
    (data.months || []).forEach(month => {
      (month.matches || []).forEach(m => {
        // attempt to parse timestamp from raw_match_date if available
        let ts = null;
        if (m.raw_match_date) {
          const parsed = Date.parse(m.raw_match_date);
          if (!isNaN(parsed)) ts = parsed;
        } else if (m.match_date) {
          const parsed = Date.parse(m.match_date);
          if (!isNaN(parsed)) ts = parsed;
        } else if (m.matchDate && month.year) {
          const parsed = Date.parse(`${m.matchDate} ${month.year}`);
          if (!isNaN(parsed)) ts = parsed;
        }
        flat.push(Object.assign({
          matchTimestamp: ts,
          monthName: month.monthName,
          year: month.year
        }, m));
      });
    });
    logDebug('flattenApiResponse result', { total: flat.length, seasonsProvided: Array.isArray(data.seasons) ? data.seasons.length : 0 });
    return flat;
  }

  // Render from flat matches array (group into months)
  function groupMatchesByMonth(flatMatches) {
    const grouped = {};
    flatMatches.forEach(m => {
      const ts = m.matchTimestamp || null;
      const monthName = m.monthName || (ts ? new Date(ts).toLocaleString('default', {month:'long'}) : 'Unknown');
      const year = m.year || (ts ? new Date(ts).getFullYear() : 'Unknown');
      const key = `${monthName}_${year}`;
      if (!grouped[key]) grouped[key] = { monthName, year, matches: [] };
      grouped[key].matches.push(m);
    });

    const months = Object.values(grouped).sort((a,b) => {
      const at = new Date(a.matches[0].matchTimestamp || 0).getTime();
      const bt = new Date(b.matches[0].matchTimestamp || 0).getTime();
      return bt - at;
    });

    months.forEach(m => {
      m.matches.sort((x,y) => (new Date(y.matchTimestamp || 0)) - (new Date(x.matchTimestamp || 0)));
    });

    return months;
  }

  function renderLibraryFromFlat(flatMatches) {
    const monthCardsContainer = document.getElementById('month-cards-container');
    const monthCardTemplate = document.getElementById('month-card') || monthCardTemplateClone;
    if (!monthCardsContainer || !monthCardTemplate) {
      logError('Month container or month-card template not found.');
      return;
    }

    if (!monthCardTemplateClone) {
      monthCardTemplateClone = monthCardTemplate.cloneNode(true);
      if (document.getElementById('month-card')) monthCardTemplate.remove();
    }
    if (!matchCardTemplateClone) {
      const inside = monthCardTemplateClone.querySelector('#match-card, .match-card-template, [data-role="match-card"]') ||
                    document.querySelector('#match-card, .match-card-template, [data-role="match-card"]');
      if (!inside) { logError('Match card template not found.'); return; }
      matchCardTemplateClone = inside.cloneNode(true);
    }

    monthCardsContainer.innerHTML = '';

    const months = groupMatchesByMonth(flatMatches);
    months.forEach(month => {
      const monthClone = monthCardTemplateClone.cloneNode(true);
      monthClone.removeAttribute('id');

      const monthHeader = monthClone.querySelector('#month-name, #month-header, .month-name, [data-role="month-name"]');
      const yearEl = monthClone.querySelector('#year, .year, [data-role="year"]');
      const viewAllBtnText = monthClone.querySelector('#month-name-in-btn, .month-name-in-btn, [data-role="month-name-in-btn"]');

      if (monthHeader) monthHeader.textContent = month.monthName || '';
      if (yearEl) yearEl.textContent = month.year || '';
      if (viewAllBtnText) viewAllBtnText.textContent = month.monthName || '';

      const matchCardsContainer = monthClone.querySelector('#match-cards-container, .match-cards-container, [data-role="match-cards-container"]');
      if (!matchCardsContainer) { logWarn('match-cards-container not found inside month card clone', month); return; }
      matchCardsContainer.innerHTML = '';

      month.matches.forEach(match => {
        const matchCard = matchCardTemplateClone.cloneNode(true);
        if (matchCard.hasAttribute('id')) matchCard.removeAttribute('id');

        // Result pill
        const resultEl = matchCard.querySelector('#my-team-result, [data-role="my-team-result"]');
        const rawResult = match.MyTeamResult ?? match.myTeamResult ?? match.result ?? match.my_team_result ?? '';
        const normalized = (String(rawResult || '').trim()).toLowerCase();
        const color = RESULT_COLORS[normalized] || '#B6BABD';
        if (resultEl) {
          resultEl.textContent = rawResult || '';
          resultEl.style.color = '#fff';
          resultEl.style.backgroundColor = color;
          resultEl.style.padding = '6px 10px';
          resultEl.style.borderRadius = '8px';
          resultEl.style.display = 'inline-block';
          resultEl.style.fontWeight = '600';
        }

        // <-- FIXED: use `matchCard` (the actual clone) here, not matchCardClone -->
        const matchNameEl = matchCard.querySelector('#match-name, .match-name, [data-role="match-name"]');
        const matchDateEl = matchCard.querySelector('#match-date, .match-date, [data-role="match-date"]');
        const matchId = match.matchId ?? match.id ?? match.match_id ?? match.id_str ?? '';

        // compute display name (prefer server matchName)
        const serverMatchName = (match.matchName || match.match_name || '').toString().trim();
        const fallbackConfig = match.matchNameConfig ?? match.name ?? match.match_name ?? '';
        const opponent = (match.opponent_team_name || '').toString().trim();
        const matchNameToShow = serverMatchName || fallbackConfig || (opponent ? `vs. ${opponent}` : '');

        if (matchNameEl) matchNameEl.textContent = matchNameToShow;
        if (matchDateEl) matchDateEl.textContent = match.matchDate || match.date || '';

        // pass the config / canonical name into attachOptionsHandlers
        const handlerNameArg = (match.matchNameConfig && match.matchNameConfig.toString().trim()) || matchNameToShow;
        attachOptionsHandlers(matchCard, matchId, handlerNameArg);
        matchCardsContainer.appendChild(matchCard);
      });

      monthCardsContainer.appendChild(monthClone);
    });
  }

  // populate dropdowns, prefer controller-provided arrays
  async function populateFilterDropdowns() {
    const seasonSelect = document.getElementById('filter-season');
    const compSelect = document.getElementById('filter-competition');
    if (!seasonSelect || !compSelect) return;

    // clear except All option
    seasonSelect.querySelectorAll('option:not([value=""])').forEach(n => n.remove());
    compSelect.querySelectorAll('option:not([value=""])').forEach(n => n.remove());

    if (Array.isArray(DATA_SEASONS) && DATA_SEASONS.length) {
      DATA_SEASONS.forEach(s => {
        const o = document.createElement('option'); o.value = s; o.textContent = s; seasonSelect.appendChild(o);
      });
    }
    if (Array.isArray(DATA_COMPETITIONS) && DATA_COMPETITIONS.length) {
      DATA_COMPETITIONS.forEach(c => {
        const o = document.createElement('option'); o.value = c; o.textContent = c; compSelect.appendChild(o);
      });
    }

    // fallback derive from ALL_MATCHES_FLAT
    if (seasonSelect.options.length <= 1 || compSelect.options.length <= 1) {
      const seasonsSet = new Set();
      const compsSet = new Set();
      if (ORIGINAL_RESPONSE_SEASON) seasonsSet.add(ORIGINAL_RESPONSE_SEASON);
      ALL_MATCHES_FLAT.forEach(m => {
        if (m.season) seasonsSet.add(m.season);
        if (!m.season && m.matchTimestamp) {
          const y = new Date(m.matchTimestamp).getFullYear();
          seasonsSet.add(`${y}/${y+1}`);
        }
        if (m.competition) compsSet.add(m.competition);
        if (m.competition_name) compsSet.add(m.competition_name);
      });
      const seasons = Array.from(seasonsSet).filter(Boolean).sort().reverse();
      const comps = Array.from(compsSet).filter(Boolean).sort();
      seasons.forEach(s => { const o = document.createElement('option'); o.value = s; o.textContent = s; seasonSelect.appendChild(o); });
      comps.forEach(c => { const o = document.createElement('option'); o.value = c; o.textContent = c; compSelect.appendChild(o); });
    }
  }

  // Filters + search
  function applyFiltersAndSearch() {
    const searchEl = document.getElementById('searchInput') || document.querySelector('input[placeholder="Search"]');
    const q = (searchEl?.value || '').trim().toLowerCase();
    const seasonSel = document.getElementById('filter-season');
    const compSel = document.getElementById('filter-competition');
    const selectedSeasonRaw = seasonSel ? seasonSel.value : '';
    const selectedCompetitionRaw = compSel ? compSel.value : '';
    const selectedSeason = selectedSeasonRaw ? selectedSeasonRaw.toString().trim().toLowerCase() : '';
    const selectedCompetition = selectedCompetitionRaw ? selectedCompetitionRaw.toString().trim().toLowerCase() : '';

    logDebug('applyFiltersAndSearch start', { totalItems: ALL_MATCHES_FLAT.length, selectedSeasonRaw, selectedCompetitionRaw, q });

    let filtered = ALL_MATCHES_FLAT.slice();

    // derive season helper
    function deriveSeasonFromTimestamp(ts) {
      if (!ts) return '';
      const dt = new Date(ts);
      if (isNaN(dt.getTime())) return '';
      const monthNum = dt.getMonth() + 1;
      const year = dt.getFullYear();
      if (monthNum >= 7) return `${year}/${year+1}`;
      return `${year-1}/${year}`;
    }

    // season filter
    if (selectedSeason) {
      const before = filtered.length;
      filtered = filtered.filter(m => {
        const provided = (m.season || '').toString().trim().toLowerCase();
        const derived = deriveSeasonFromTimestamp(m.matchTimestamp || (m.raw_match_date ? Date.parse(m.raw_match_date) : (m.match_date ? Date.parse(m.match_date) : NaN)));
        return (provided && provided === selectedSeason) || (derived && derived === selectedSeason);
      });
      logDebug('season filter applied', { selectedSeasonRaw, beforeCount: before, afterCount: filtered.length });
    }

    // competition filter
    if (selectedCompetition) {
      const before = filtered.length;
      filtered = filtered.filter(m => {
        const compFields = [
          m.competition, m.competition_name, m.matchCompetition, m.matchName, m.matchNameConfig
        ].map(x => (x || '').toString().trim().toLowerCase());
        for (let v of compFields) {
          if (!v) continue;
          if (v === selectedCompetition) return true;
          if (v.includes(selectedCompetition)) return true;
        }
        return false;
      });
      logDebug('competition filter applied', { selectedCompetitionRaw, beforeCount: before, afterCount: filtered.length });
    }

    // search
    if (q) {
      const before = filtered.length;
      filtered = filtered.filter(m => {
        return (
          (m.matchName || '').toString().toLowerCase().includes(q) ||
          (m.matchDate || '').toString().toLowerCase().includes(q) ||
          (m.status || '').toString().toLowerCase().includes(q) ||
          (m.matchNameConfig || '').toString().toLowerCase().includes(q) ||
          (m.competition || '').toString().toLowerCase().includes(q) ||
          (m.season || '').toString().toLowerCase().includes(q)
        );
      });
      logDebug('search applied', { q, beforeCount: before, afterCount: filtered.length });
    }

    // update season header
    const seasonHeader = document.getElementById('season-header');
    if (seasonHeader) {
      if (!selectedSeasonRaw) seasonHeader.textContent = 'All Season';
      else seasonHeader.textContent = `${selectedSeasonRaw} Season`;
    }

    logDebug('filtered final counts', { before: ALL_MATCHES_FLAT.length, after: filtered.length });
    renderLibraryFromFlat(filtered);
  }

  // Main fetch + setup
  async function fetchAndRenderMatches() {
    logInfo('fetchAndRenderMatches start');
    ensureFilterModalExists();

    const seasonHeader = document.getElementById('season-header');
    const monthCardsContainer = document.getElementById('month-cards-container');
    const monthCardTemplate = document.getElementById('month-card');

    if (!monthCardsContainer || !monthCardTemplate) {
      logError('Month container or month-card template not found.');
      return;
    }

    // find inner match-card template to clone later
    let matchCardTemplate = monthCardTemplate.querySelector('#match-card, .match-card-template, [data-role="match-card"]');
    if (!matchCardTemplate) matchCardTemplate = document.querySelector('#match-card, .match-card-template, [data-role="match-card"]');
    if (!matchCardTemplate) { logError('Match card template not found.'); return; }

    const matchCardTemplateCloneLocal = matchCardTemplate.cloneNode(true);
    // remove the original month template from the DOM and keep a clone for later
    monthCardTemplate.remove();

    try {
      const res = await fetch(FETCH_URL);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();

      // Flatten controller response into ALL_MATCHES_FLAT
      ALL_MATCHES_FLAT = flattenApiResponse(data);

      // Capture top-level lists if provided
      if (Array.isArray(data.seasons)) DATA_SEASONS = data.seasons;
      if (Array.isArray(data.competitions)) DATA_COMPETITIONS = data.competitions;
      if (seasonHeader && data.season) {
        ORIGINAL_RESPONSE_SEASON = data.season;
        seasonHeader.textContent = `${data.season} Season`;
      }

      // Now normalize each flattened match and ensure we keep server-provided matchName
      ALL_MATCHES_FLAT = ALL_MATCHES_FLAT.map(m => {
        const normalizedSeason = (m.season || m.season_name || '').toString().trim();
        const normalizedCompetition = (m.competition || m.competition_name || '').toString().trim();

        const seasonNorm = normalizedSeason ? normalizedSeason.toLowerCase() : '';
        const compNorm   = normalizedCompetition ? normalizedCompetition.toLowerCase() : '';

        // Prefer server-provided matchName, fallback to opponent or existing config
        const serverMatchName = (m.matchName || m.match_name || '').toString().trim();
        const opponent = (m.opponent_team_name || '').toString().trim();
        const matchNameFinal = serverMatchName || (opponent ? `vs. ${opponent}` : (m.matchNameConfig || ''));

        return Object.assign({
          matchId: m.matchId || m.match_id || m.id,
          thumbnailUrl: m.thumbnailUrl || m.video_thumbnail || m.thumbnail,
          status: m.status,
          statusColor: m.statusColor || RESULT_COLORS[(m.status || '').toLowerCase().trim()] || '#B6BABD',
          matchName: matchNameFinal,               // <- ensure matchName is set from server if present
          matchNameConfig: m.matchNameConfig || m.match_name_config || '',
          matchDate: m.matchDate || m.match_date || '',
          competition: normalizedCompetition || null,
          season: normalizedSeason || null,
          // normalized helper properties
          _season_norm: seasonNorm,
          _competition_norm: compNorm,
          matchTimestamp: m.matchTimestamp ?? m.matchTimestamp // keep existing timestamp if provided by controller
        }, m);
      });

      // Keep a stable clone for rendering
      monthCardTemplateClone = monthCardTemplate.cloneNode(true);
      matchCardTemplateClone = matchCardTemplateCloneLocal;

      // Populate dropdowns using controller lists or derive from data
      await populateFilterDropdowns();

      // Wire search input (select by id or placeholder fallback)
      const searchEl = document.getElementById('searchInput') || document.querySelector('input[placeholder="Search"]');
      if (searchEl) {
        searchEl.addEventListener('input', debounce(() => applyFiltersAndSearch(), 250));
      } else {
        logWarn('Search input not found - add id="searchInput" to the input for best reliability.');
      }

      // Wire filter button
      const matchFilterBtn = document.getElementById('match-filter-btn');
      if (matchFilterBtn) {
        matchFilterBtn.addEventListener('click', (ev) => {
          ev.stopPropagation();
          populateFilterDropdowns().then(showFilterModal);
        });
      }

      // Apply default season filter on first load: prefer ORIGINAL_RESPONSE_SEASON, else keep All
      const seasonSelect = document.getElementById('filter-season');
      const initialSeason = ORIGINAL_RESPONSE_SEASON || '';
      if (seasonSelect && initialSeason) {
        // if not present in options, add it then set value
        if (![...seasonSelect.options].some(o => o.value === initialSeason)) {
          const op = document.createElement('option'); op.value = initialSeason; op.textContent = initialSeason; seasonSelect.appendChild(op);
        }
        seasonSelect.value = initialSeason;
      }

      // Finally apply filters & render (this will render filtered list)
      applyFiltersAndSearch();

      logInfo('fetchAndRenderMatches completed successfully');
    } catch (err) {
      logError('Failed to fetch matches:', err);
      monthCardsContainer.innerHTML = `<p class="text-red-500 text-center">We couldn't load the match list. Please try again later.</p>`;
    }
  }

  // DOM ready
  document.addEventListener('DOMContentLoaded', () => {
    logInfo('DOM ready - starting fetchAndRenderMatches');
    fetchAndRenderMatches();
    // Close options & modal on ESC globally
    document.addEventListener('keyup', (ev) => {
      if (ev.key === 'Escape') {
        const modal = document.getElementById('match-filter-modal');
        if (modal && !modal.classList.contains('hidden')) hideFilterModal();
        document.querySelectorAll('.match-options.open, #match-options.open').forEach(el => { el.classList.remove('open'); el.classList.add('hidden'); });
      }
    });
  });

})();
