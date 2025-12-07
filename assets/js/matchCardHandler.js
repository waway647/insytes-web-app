/**
 * match-library.js
 * Full standalone script to fetch and render match cards,
 * and provide search + filter (season, competition) UI.
 *
 * Drop this in place of your existing match-library.js
 */

(() => {
  const API_BASE = (window.APP_BASE_URL ? window.APP_BASE_URL.replace(/\/$/, '') + '/' : '') + 'match/librarycontroller';
  const API_BASE_METADATA = (window.APP_BASE_URL ? window.APP_BASE_URL.replace(/\/$/, '') + '/' : '') + 'match/metadatacontroller';
  const API_START_TAGGING = API_BASE + '/start_tagging';
  const API_REMOVE_MATCH   = API_BASE_METADATA + '/remove_match';
  const TAGGING_PAGE_BASE  = (window.APP_BASE_URL ? window.APP_BASE_URL.replace(/\/$/, '') + '/' : '') + 'studio/mediacontroller/index';

  const STATUS_COLORS = {
    'ready': '#48ADF9',
    'tagging in progress': '#B14D35',
    'completed': '#209435',
    'waiting for video': '#B6BABD'
  };

  // State
  let ALL_MATCHES_FLAT = []; // flattened array of matches
  let ORIGINAL_RESPONSE_SEASON = null; // fallback season from API
  let monthCardTemplateClone = null;
  let matchCardTemplateClone = null;

  // New: hold top-level lists returned by controller (preferred for dropdowns)
  let DATA_SEASONS = null;
  let DATA_COMPETITIONS = null;

  // Simple logging helpers
  function logDebug(...args) { console.debug('[match-library]', ...args); }
  function logInfo(...args) { console.info('[match-library]', ...args); }
  function logWarn(...args) { console.warn('[match-library]', ...args); }
  function logError(...args) { console.error('[match-library]', ...args); }
  

  // debounce utility
  function debounce(fn, wait = 200) {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), wait);
    };
  }
  
  // compute current season string from a Date (e.g. Dec 2025 -> "2025/2026")
  function computeCurrentSeason(date = new Date()) {
    const y = date.getFullYear();
    const monthNum = date.getMonth() + 1; // 1..12
    return (monthNum >= 7) ? `${y}/${y + 1}` : `${y - 1}/${y}`;
  }

  // set #season-header text safely
  function setSeasonHeader(value) {
    const hdr = document.getElementById('season-header');
    if (!hdr) return;
    if (value && value.toString().trim()) {
      hdr.textContent = `${value.toString().trim()} Season`;
    } else {
      // fallback to computed current season
      hdr.textContent = `${computeCurrentSeason()} Season`;
    }
  }


  // Close all open option menus (keeps previous behavior)
  function closeAllMatchOptions() {
    document.querySelectorAll('.match-options.open, #match-options.open').forEach(el => {
      el.classList.remove('open');
      el.classList.add('hidden');
    });
  }

  // Close options on ESC
  document.addEventListener('keyup', (ev) => {
    if (ev.key === 'Escape') {
      // also close filter modal if open
      const filterModal = document.getElementById('match-filter-modal');
      if (filterModal && !filterModal.classList.contains('hidden')) {
        hideFilterModal();
        return;
      }
      closeAllMatchOptions();
    }
  });

  // Close card options when clicking outside
  document.addEventListener('click', (ev) => {
    if (ev.target.closest('.card-options-btn') || ev.target.closest('#card-options-btn')) {
      return;
    }
    closeAllMatchOptions();
  });

  // Attach options handlers for an individual card clone
  function attachOptionsHandlers(matchCardClone, matchId) {
    // Ensure the clone has a class so outside-click check works
    matchCardClone.classList.add('match-card');
    matchCardClone.dataset.matchId = matchId;

    const cardOptionsBtn = matchCardClone.querySelector('#card-options-btn, .card-options-btn, [data-role="card-options-btn"]');
    const matchOptionsEl  = matchCardClone.querySelector('#match-options, .match-options, [data-role="match-options"]');
    const startBtn        = matchCardClone.querySelector('#start-tagging-btn, .start-tagging-btn, [data-role="start-tagging-btn"]');
    const removeBtn       = matchCardClone.querySelector('#remove-match-card-btn, .remove-match-card-btn, [data-role="remove-match-card-btn"]');

    if (!cardOptionsBtn || !matchOptionsEl) return;

    cardOptionsBtn.addEventListener('click', (ev) => {
      ev.preventDefault();
      ev.stopPropagation();

      // close other open menus first
      document.querySelectorAll('.match-options.open, #match-options.open').forEach(el => {
        if (el !== matchOptionsEl) {
          el.classList.remove('open');
          el.classList.add('hidden');
        }
      });

      // toggle this panel
      const isOpen = matchOptionsEl.classList.contains('open');
      if (isOpen) {
        matchOptionsEl.classList.remove('open');
        matchOptionsEl.classList.add('hidden');
      } else {
        matchOptionsEl.classList.remove('hidden');
        matchOptionsEl.classList.add('open');

        // position logic (same as original)
        const rect = cardOptionsBtn.getBoundingClientRect();
        matchOptionsEl.style.position = 'absolute';
        matchOptionsEl.style.top = `${rect.top + window.scrollY}px`;
        matchOptionsEl.style.zIndex = 9999;

        const menuWidth = (matchOptionsEl.offsetWidth && matchOptionsEl.offsetWidth > 0) ? matchOptionsEl.offsetWidth : 275;
        const desiredLeft = rect.right + window.scrollX + 2;

        if ((rect.right + menuWidth + 2) > window.innerWidth) {
          matchOptionsEl.style.left = `${rect.right + window.scrollX - 273}px`;
        } else {
          matchOptionsEl.style.left = `${desiredLeft}px`;
        }

        const numericLeft = parseFloat(matchOptionsEl.style.left) || 0;
        const minLeft = window.scrollX + 8;
        if (numericLeft < minLeft) {
          matchOptionsEl.style.left = `${minLeft}px`;
        }
      }
    });

    // Start tagging
    if (startBtn) {
      startBtn.addEventListener('click', async (ev) => {
        ev.stopPropagation();

        const origText = startBtn.textContent;
        startBtn.textContent = 'Starting...';
        startBtn.setAttribute('disabled', 'disabled');
        matchOptionsEl.classList.remove('open'); matchOptionsEl.classList.add('hidden');

        try {
          const res = await fetch(API_START_TAGGING, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ match_id: matchId })
          });

          let payload;
          try { payload = await res.json(); } catch (e) { payload = { success: res.ok }; }

          if (!res.ok || payload.success === false) {
            const msg = payload.message || `Failed to start tagging for ${matchId}`;
            alert(msg);
          } else {
            if (payload.redirect_url) {
              window.location.href = payload.redirect_url;
              return;
            }
            if (TAGGING_PAGE_BASE) {
              window.location.href = TAGGING_PAGE_BASE + '?match_id=' + encodeURIComponent(matchId);
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

    // Remove match
    if (removeBtn) {
      removeBtn.addEventListener('click', async (ev) => {
        ev.stopPropagation();
        if (!confirm('This would also delete your tagging data and progress. Are you sure?')) return;

        const origText = removeBtn.textContent;
        removeBtn.textContent = 'Removing...';
        removeBtn.setAttribute('disabled', 'disabled');
        matchOptionsEl.classList.remove('open'); matchOptionsEl.classList.add('hidden');

        try {
          const res = await fetch(API_REMOVE_MATCH, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ match_id: matchId })
          });

          let payload;
          try { payload = await res.json(); } catch (e) { payload = { success: res.ok }; }

          if (!res.ok || payload.success === false) {
            const msg = payload.message || `Failed to remove match ${matchId}`;
            alert(msg);
            removeBtn.textContent = origText;
            removeBtn.removeAttribute('disabled');
          } else {
            // remove UI card
            const cardEl = document.querySelector(`.match-card[data-match-id="${matchId}"]`);
            if (cardEl) cardEl.remove();

            setTimeout(() => window.location.reload(), 220);
          }
        } catch (err) {
          alert('Network error while removing match.');
          removeBtn.textContent = origText;
          removeBtn.removeAttribute('disabled');
        }
      });
    }
  }

  // Helper: group flat matches array into months (same structure as backend 'months' expected earlier)
  function groupMatchesByMonth(flatMatches) {
    const grouped = {};
    flatMatches.forEach(m => {
      const ts = m.matchTimestamp || Date.now();
      const monthName = m.monthName || new Date(ts).toLocaleString('default', { month: 'long' });
      const year = m.year || new Date(ts).getFullYear();
      const key = `${monthName}_${year}`;
      if (!grouped[key]) grouped[key] = { monthName, year, matches: [] };
      grouped[key].matches.push(m);
    });

    // Convert to array and sort by timestamp desc
    const months = Object.values(grouped).sort((a,b) => {
      const at = new Date(a.matches[0].matchTimestamp || 0).getTime();
      const bt = new Date(b.matches[0].matchTimestamp || 0).getTime();
      return bt - at;
    });

    // Also sort matches within each month by date desc
    months.forEach(m => {
      m.matches.sort((x,y) => (new Date(y.matchTimestamp || 0)) - (new Date(x.matchTimestamp || 0)));
    });

    return months;
  }

  // Render library from flat matches array
  function renderLibraryFromFlat(flatMatches) {
    const monthCardsContainer = document.getElementById('month-cards-container');
    // Try to find the DOM template; if it's already removed, fall back to previously cloned template
    const monthCardTemplate = document.getElementById('month-card') || monthCardTemplateClone;

    if (!monthCardsContainer || !monthCardTemplate) {
      logError('Month container or month-card template not found.');
      return;
    }

    // If we haven't yet created a persistent clone, create it now from whichever template we have
    if (!monthCardTemplateClone) {
      monthCardTemplateClone = monthCardTemplate.cloneNode(true);
      // If the original template element is present in DOM, remove it so we keep only the clone for future clones
      if (document.getElementById('month-card')) {
        monthCardTemplate.remove();
      }
    }

    // Ensure matchCardTemplateClone exists (try to locate inside the cloned month template or in DOM)
    if (!matchCardTemplateClone) {
      // try inside the persistent monthCardTemplateClone
      let inside = monthCardTemplateClone.querySelector('#match-card, .match-card-template, [data-role="match-card"]');
      if (!inside) {
        // fallback to any match-card template left in DOM
        inside = document.querySelector('#match-card, .match-card-template, [data-role="match-card"]');
      }
      if (!inside) {
        logError('Match card template not found.');
        return;
      }
      matchCardTemplateClone = inside.cloneNode(true);
    }

    // Clear container
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
      if (!matchCardsContainer) {
        logWarn('match-cards-container not found inside month card clone', month);
        return;
      }
      matchCardsContainer.innerHTML = '';

      month.matches.forEach(match => {
        const matchCard = matchCardTemplateClone.cloneNode(true);
        if (matchCard.hasAttribute('id')) matchCard.removeAttribute('id');

        // Thumbnail handling
        const thumbnailEl = matchCard.querySelector('#thumbnail, .thumbnail, [data-role="thumbnail"]');
        if (thumbnailEl) {
          let thumbUrl = match.thumbnailUrl || match.video_thumbnail || '';
          if (thumbUrl) {
            if (!/^https?:\/\//i.test(thumbUrl)) {
              const base = (window.ASSET_BASE_URL && window.ASSET_BASE_URL.replace(/\/$/, '')) || window.location.origin;
              thumbUrl = base + '/' + thumbUrl.replace(/^\/+/, '');
            }
            thumbnailEl.style.backgroundImage = `url("${thumbUrl}")`;
            thumbnailEl.style.backgroundSize = 'cover';
            thumbnailEl.style.backgroundPosition = 'center';
          } else {
            thumbnailEl.style.backgroundImage = '';
          }
        }

        // Status
        const statusContainer = matchCard.querySelector('.border-blue-500, .status-container, [data-role="status-container"]');
        const statusDot = matchCard.querySelector('#status-color, .status-dot, [data-role="status-dot"]');
        const statusText = matchCard.querySelector('#status, .status-text, [data-role="status-text"]');

        if (statusContainer && statusDot && statusText) {
          statusContainer.classList.remove('border-blue-500');
          statusDot.classList.remove('bg-blue-500');

          const key = (match.status || '').toString().toLowerCase().trim();
          const color = STATUS_COLORS[key] || (match.statusColor || '#B6BABD');

          statusContainer.style.borderStyle = 'solid';
          statusContainer.style.borderWidth = statusContainer.style.borderWidth || '1px';
          statusContainer.style.borderColor = color;
          statusDot.style.backgroundColor = color;
          statusText.textContent = match.status || '';
        }

        // Name & date
        const matchNameEl = matchCard.querySelector('#match-name, .match-name, [data-role="match-name"]');
        const matchDateEl = matchCard.querySelector('#match-date, .match-date, [data-role="match-date"]');
        if (matchNameEl) matchNameEl.textContent = match.matchName || '';
        if (matchDateEl) matchDateEl.textContent = match.matchDate || '';

        // Attach handlers and append
        attachOptionsHandlers(matchCard, match.matchId);
        matchCardsContainer.appendChild(matchCard);
      });

      monthCardsContainer.appendChild(monthClone);
    });
  }

  // Utility: normalize and flatten API response into ALL_MATCHES_FLAT
  function flattenApiResponse(data) {
    const flat = [];
    (data.months || []).forEach(month => {
      (month.matches || []).forEach(m => {
        // keep optional raw timestamp if available
        // Prefer controller-provided raw_match_date (ISO) if available, else try to parse matchDate + month.year, else fallback to match_date
        let ts = null;
        // 1) If model or controller provided a numeric timestamp-like value
        if (m.matchTimestamp && !isNaN(new Date(m.matchTimestamp).getTime())) {
          ts = new Date(m.matchTimestamp).getTime();
        }
        // 2) If controller emitted raw_match_date (ISO or similar), try parse it
        else if (m.raw_match_date) {
          const parsed = Date.parse(m.raw_match_date);
          if (!isNaN(parsed)) ts = parsed;
        }
        // 3) If we have a human-friendly matchDate (like "Dec 03") and the month object has a year, combine and parse
        else if (m.matchDate && month.year) {
          const str = `${m.matchDate} ${month.year}`;
          const parsed = Date.parse(str);
          if (!isNaN(parsed)) ts = parsed;
        }
        // 4) fallback to controller's raw match_date (string) if present
        else if (m.match_date) {
          const parsed = Date.parse(m.match_date);
          if (!isNaN(parsed)) ts = parsed;
        } else if (m.raw_match_date_iso) {
          const parsed = Date.parse(m.raw_match_date_iso);
          if (!isNaN(parsed)) ts = parsed;
        }

        // NOTE: intentionally DO NOT fallback to Date.now() here.
        // If ts is still null, we'll keep it null so derived-season logic won't mistakenly treat it as current-season.

        flat.push(Object.assign({
          // keep matchTimestamp null when unknown
          matchTimestamp: ts,
          monthName: month.monthName,
          year: month.year
        }, m));
      });
    });

    // Debug: how many entries lack parseable timestamps? useful to verify cause.
    try {
      const missing = flat.filter(x => !x.matchTimestamp).length;
      console.debug('[match-library] flattenApiResponse: total', flat.length, 'missing timestamps', missing);
    } catch (e) { /* ignore if console unavailable */ }

    return flat;
  }

  function applyFiltersAndSearch() {
    const q = (document.getElementById('searchInput')?.value || '').trim().toLowerCase();
    const seasonSel = document.getElementById('filter-season');
    const compSel = document.getElementById('filter-competition');

    const selectedSeasonRaw = seasonSel ? seasonSel.value : '';
    const selectedCompetitionRaw = compSel ? compSel.value : '';

    // normalize selected values to compare with helper fields on items
    const selectedSeason = selectedSeasonRaw ? selectedSeasonRaw.toString().trim().toLowerCase() : '';
    const selectedCompetition = selectedCompetitionRaw ? selectedCompetitionRaw.toString().trim().toLowerCase() : '';

    // Start from the full set; we'll apply season -> competition -> search
    let filtered = ALL_MATCHES_FLAT.slice();

    console.debug('[match-library] applyFiltersAndSearch start', {
      totalItems: ALL_MATCHES_FLAT.length,
      selectedSeasonRaw, selectedCompetitionRaw, q
    });

    // Helper: derive season from timestamp (same heuristic used in controller)
    function deriveSeasonFromTimestamp(ts) {
      if (!ts) return '';
      const dt = new Date(ts);
      if (isNaN(dt.getTime())) return '';
      const monthNum = dt.getMonth() + 1; // 1..12
      const year = dt.getFullYear();
      if (monthNum >= 7) {
        return `${year}/${year + 1}`;
      } else {
        return `${year - 1}/${year}`;
      }
    }

    // --- SEASON FILTER (prioritize controller-provided season; derive ONLY if absent) ---
    if (selectedSeason) {
      const beforeCount = filtered.length;
      const passed = []; // full info for those included
      const failed = []; // small sample for those excluded

      filtered = filtered.filter(m => {
        // normalized helper & provided values
        const seasonNormHelper = (m._season_norm || '').toString().trim().toLowerCase(); // normalized helper if present
        const seasonProvided = (m.season || '').toString().trim(); // original provided value
        const seasonProvidedNorm = seasonProvided.toLowerCase();

        // derive only if there is no controller-provided season
        let derived = '';
        if (!seasonProvidedNorm && !seasonNormHelper) {
          const ts = m.matchTimestamp || (m.raw_match_date ? Date.parse(m.raw_match_date) : (m.match_date ? Date.parse(m.match_date) : NaN));
          derived = (function(ts){
            if (!ts) return '';
            const dt = new Date(ts);
            if (isNaN(dt.getTime())) return '';
            const monthNum = dt.getMonth() + 1;
            const year = dt.getFullYear();
            return (monthNum >= 7) ? `${year}/${year + 1}` : `${year - 1}/${year}`;
          })(ts) || '';
        }
        const derivedNorm = (derived || '').toString().trim().toLowerCase();

        // Decision: prefer helper -> provided -> derived (derived only valid if helper+provided absent)
        let isMatch = false;
        let reason = '';

        if (seasonNormHelper) {
          isMatch = (seasonNormHelper === selectedSeason);
          reason = `helper:${seasonNormHelper}`;
        } else if (seasonProvidedNorm) {
          isMatch = (seasonProvidedNorm === selectedSeason);
          reason = `provided:${seasonProvidedNorm}`;
        } else if (derivedNorm) {
          isMatch = (derivedNorm === selectedSeason);
          reason = `derived:${derivedNorm}`;
        } else {
          reason = 'no-season';
        }

        const info = {
          id: m.matchId || m.match_id || m.id,
          helper: seasonNormHelper || null,
          provided: seasonProvided || null,
          derived: derived || null,
          ts: m.matchTimestamp || null,
          reason
        };

        if (isMatch) {
          passed.push(info);
        } else {
          if (failed.length < 12) failed.push(info);
        }

        return isMatch;
      });

      console.debug('[match-library] season filter debug', {
        selectedSeasonRaw,
        beforeCount,
        afterCount: filtered.length,
        passedSampleCount: passed.length,
        failedSampleCount: failed.length,
        passed: passed.slice(0, 50),
        failed: failed
      });
    }

    // --- COMPETITION FILTER ---
    if (selectedCompetition) {
      filtered = filtered.filter(m => {
        if (m._competition_norm && m._competition_norm === selectedCompetition) return true;
        const compFields = [
          m.competition,
          m.competition_name,
          m.matchCompetition,
          m.matchName,
          m.matchNameConfig
        ].map(x => (x || '').toString().trim().toLowerCase());

        for (let v of compFields) {
          if (!v) continue;
          if (v === selectedCompetition) return true;
          if (v.includes(selectedCompetition)) return true;
        }
        return false;
      });
      console.debug('[match-library] competition filter applied', { selectedCompetitionRaw, afterCount: filtered.length });
    }

    // --- SEARCH QUERY FILTER ---
    if (q) {
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
      console.debug('[match-library] search query applied', { q, afterCount: filtered.length });
    }

    console.debug('[match-library] filtered counts final', { before: ALL_MATCHES_FLAT.length, after: filtered.length });

    // --- UPDATE SEASON HEADER ---
    // If user selects "All seasons"
    if (selectedSeason === "") {
        document.getElementById('season-header').textContent = "All Seasons";
    }
    // If user selects a specific season
    else {
        document.getElementById('season-header').textContent = `${selectedSeason} Season`;
    }

    // Render filtered list
    renderLibraryFromFlat(filtered);
  }

  // Filter modal open/close helpers
  function showFilterModal() {
    const modal = document.getElementById('match-filter-modal');
    if (!modal) return;
    modal.classList.remove('hidden');
    // trap focus lightly
    const sel = modal.querySelector('select, button, [tabindex]');
    if (sel) sel.focus();
  }
  function hideFilterModal() {
    const modal = document.getElementById('match-filter-modal');
    if (!modal) return;
    modal.classList.add('hidden');
  }

  // Populate filter dropdowns: prefer controller-provided lists (DATA_SEASONS/DATA_COMPETITIONS),
  // fallback to metadata endpoint, then derive from ALL_MATCHES_FLAT
  async function populateFilterDropdowns() {
    const seasonSelect = document.getElementById('filter-season');
    const compSelect = document.getElementById('filter-competition');

    if (!seasonSelect || !compSelect) return;

    // Clear existing (preserve first "All" option)
    seasonSelect.querySelectorAll('option:not([value=""])').forEach(n => n.remove());
    compSelect.querySelectorAll('option:not([value=""])').forEach(n => n.remove());

    // 1) Use controller-provided lists if available
    if (Array.isArray(DATA_SEASONS) && DATA_SEASONS.length) {
      DATA_SEASONS.forEach(s => {
        const o = document.createElement('option');
        o.value = s;
        o.textContent = s;
        seasonSelect.appendChild(o);
      });
    }
    if (Array.isArray(DATA_COMPETITIONS) && DATA_COMPETITIONS.length) {
      DATA_COMPETITIONS.forEach(c => {
        const o = document.createElement('option');
        o.value = c;
        o.textContent = c;
        compSelect.appendChild(o);
      });
    }

    // If both populated from controller, we're done
    if (seasonSelect.options.length > 1 && compSelect.options.length > 1) return;

    // 2) Try metadata endpoint (legacy)
    try {
      const metaUrl = (API_BASE_METADATA ? API_BASE_METADATA + '/get_filters' : null);
      if (metaUrl) {
        const resp = await fetch(metaUrl);
        if (resp.ok) {
          const payload = await resp.json();
          const seasons = payload.seasons || payload.season_list || null;
          const comps = payload.competitions || payload.competition_list || null;

          if (seasons && seasonSelect) {
            seasons.forEach(s => {
              const o = document.createElement('option');
              o.value = s;
              o.textContent = s;
              seasonSelect.appendChild(o);
            });
          }
          if (comps && compSelect) {
            comps.forEach(c => {
              const o = document.createElement('option');
              o.value = c;
              o.textContent = c;
              compSelect.appendChild(o);
            });
          }
          if ((seasons && seasons.length) || (comps && comps.length)) return;
        }
      }
    } catch (err) {
      logDebug('No metadata endpoint or failed to fetch metadata, deriving from matches.', err);
    }

    // 3) Fallback: derive distinct seasons & competitions from ALL_MATCHES_FLAT
    const seasonsSet = new Set();
    const compsSet = new Set();

    // Use ORIGINAL_RESPONSE_SEASON as a top-level season option if present
    if (ORIGINAL_RESPONSE_SEASON) seasonsSet.add(ORIGINAL_RESPONSE_SEASON);

    ALL_MATCHES_FLAT.forEach(m => {
      if (m.season) seasonsSet.add(m.season);
      // Basic derivation: if m.matchTimestamp -> create season like "2025/2026" using year and year+1 (heuristic)
      if (!m.season && m.matchTimestamp) {
        const y = new Date(m.matchTimestamp).getFullYear();
        seasonsSet.add(`${y}/${y+1}`);
      }
      if (m.competition) compsSet.add(m.competition);
      if (m.competition_name) compsSet.add(m.competition_name);
      if (m.matchNameConfig) {
        const maybe = m.matchNameConfig.split('_').slice(0,2).join(' ');
        if (maybe) compsSet.add(maybe);
      }
    });

    const seasons = Array.from(seasonsSet).filter(Boolean).sort().reverse();
    const comps = Array.from(compsSet).filter(Boolean).sort();

    seasons.forEach(s => {
      const o = document.createElement('option');
      o.value = s;
      o.textContent = s;
      seasonSelect.appendChild(o);
    });
    comps.forEach(c => {
      const o = document.createElement('option');
      o.value = c;
      o.textContent = c;
      compSelect.appendChild(o);
    });
  }

  // Main fetch + setup
  async function fetchAndRenderMatches() {
    logInfo('fetchAndRenderMatches start');

    const seasonHeader = document.getElementById('season-header');
    const monthCardsContainer = document.getElementById('month-cards-container');
    const monthCardTemplate = document.getElementById('month-card');

    if (!monthCardsContainer || !monthCardTemplate) {
      logError('Month container or month-card template not found.');
      return;
    }

    const fetchUrl = 'get_all_matches';

    try {
      const res = await fetch(fetchUrl);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();

      // Capture controller-provided top-level lists (if present)
      if (Array.isArray(data.seasons)) DATA_SEASONS = data.seasons;
      if (Array.isArray(data.competitions)) DATA_COMPETITIONS = data.competitions;

      if (data.season) {
        ORIGINAL_RESPONSE_SEASON = data.season;
        setSeasonHeader(data.season);
      }

      // Flatten
      ALL_MATCHES_FLAT = flattenApiResponse(data);

      // Attach season/competition metadata if present on each match (best-effort)
      ALL_MATCHES_FLAT = ALL_MATCHES_FLAT.map(m => {
        const normalizedSeason = (m.season || m.season_name || '').toString().trim();
        const normalizedCompetition = (m.competition || m.competition_name || '').toString().trim();

        const seasonNorm = normalizedSeason ? normalizedSeason.toLowerCase() : '';
        const compNorm   = normalizedCompetition ? normalizedCompetition.toLowerCase() : '';

        return Object.assign({
          matchId: m.matchId || m.match_id || m.id,
          thumbnailUrl: m.thumbnailUrl || m.video_thumbnail || m.thumbnail,
          status: m.status,
          statusColor: m.statusColor || STATUS_COLORS[(m.status || '').toLowerCase().trim()] || '#B6BABD',
          matchName: m.matchName || m.match_name || m.opponent_team_name || m.matchNameConfig || '',
          matchNameConfig: m.matchNameConfig || m.match_name_config || '',
          matchDate: m.matchDate || m.match_date || '',
          competition: normalizedCompetition || null,
          season: normalizedSeason || null,
          // normalized helper properties for safe compare:
          _season_norm: seasonNorm,
          _competition_norm: compNorm
        }, m);
      });

      // Populate dropdowns (after flattening). This will prefer DATA_SEASONS / DATA_COMPETITIONS if controller provided them.
      await populateFilterDropdowns();

      // Choose initial season to show on page load:
      // Prefer controller-provided ORIGINAL_RESPONSE_SEASON (set earlier from data.season),
      // otherwise fall back to a computed current season.
      const initialSeason = ORIGINAL_RESPONSE_SEASON || computeCurrentSeason();

      // If there's a season-select element, set it so UI reflects the applied filter
      const seasonSelectEl = document.getElementById('filter-season');
      if (seasonSelectEl) {
        // ensure value exists in dropdown; if not, add it
        let found = Array.from(seasonSelectEl.options).some(opt => opt.value === initialSeason);
        if (!found) {
          const opt = document.createElement('option');
          opt.value = initialSeason;
          opt.textContent = initialSeason;
          seasonSelectEl.appendChild(opt);
        }
        seasonSelectEl.value = initialSeason;
      }

      // Now apply filters (this will honor the season filter you just set)
      applyFiltersAndSearch();

      // stop here to avoid double-render; the applyFiltersAndSearch call does renderLibraryFromFlat
      return;

      // initial render (unfiltered)
      renderLibraryFromFlat(ALL_MATCHES_FLAT);

      logInfo('fetchAndRenderMatches completed successfully');
    } catch (err) {
      logError('Failed to fetch matches:', err);
      monthCardsContainer.innerHTML = `<p class="text-red-500 text-center">We couldn't load the match library. Please try again later.</p>`;
    }
  }

  // DOM ready wiring
  document.addEventListener('DOMContentLoaded', () => {
    logInfo('DOM ready, starting fetchAndRenderMatches');
    setSeasonHeader(computeCurrentSeason());
    fetchAndRenderMatches();

    // Search wiring
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
      searchInput.addEventListener('input', debounce(() => {
        applyFiltersAndSearch();
      }, 250));
    }

    // Filter modal wiring
    const matchFilterBtn = document.getElementById('match-filter-btn');
    const filterModal = document.getElementById('match-filter-modal');
    const filterBackdrop = document.getElementById('match-filter-modal-backdrop');
    const filterClose = document.getElementById('match-filter-close');
    const filterApply = document.getElementById('filter-apply');
    const filterClear = document.getElementById('filter-clear');

    if (matchFilterBtn) {
      matchFilterBtn.addEventListener('click', (ev) => {
        ev.stopPropagation();
        populateFilterDropdowns().then(showFilterModal);
      });
    }
    if (filterBackdrop) {
      filterBackdrop.addEventListener('click', hideFilterModal);
    }
    if (filterClose) {
      filterClose.addEventListener('click', hideFilterModal);
    }
    if (filterApply) {
      filterApply.addEventListener('click', (ev) => {
        hideFilterModal();
        applyFiltersAndSearch();
      });
    }
    if (filterClear) {
      filterClear.addEventListener('click', (ev) => {
        const seasonSelect = document.getElementById('filter-season');
        const compSelect = document.getElementById('filter-competition');
        if (seasonSelect) seasonSelect.value = '';
        if (compSelect) compSelect.value = '';
        hideFilterModal();
        // Reset search too? we keep search as-is but reapply filters (none)
        applyFiltersAndSearch();
      });
    }
  });

})();
