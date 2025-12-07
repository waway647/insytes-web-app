/**
 * matchCardResultsHandler.js
 * Full standalone script to fetch and render match cards,
 * wire card-options, start-tagging and remove-match handlers,
 * and include debug logs for easy troubleshooting.
 */

(() => {
  const API_BASE = (window.APP_BASE_URL ? window.APP_BASE_URL.replace(/\/$/, '') + '/' : '') + 'reports/reportscontroller';
  const API_START_TAGGING = API_BASE + '/start_tagging';
  const API_REMOVE_MATCH   = API_BASE + '/remove_match';
  const TAGGING_PAGE_BASE  = (window.APP_BASE_URL ? window.APP_BASE_URL.replace(/\/$/, '') + '/' : '') + 'studio/mediacontroller/index';

  // <-- Set this to the route that renders your match report page -->
  // Example: site_url('reports/match') or 'reports/match_report'
  const REPORT_PAGE_BASE = (window.APP_BASE_URL ? window.APP_BASE_URL.replace(/\/$/, '') + '/' : '/') + 'reports/overviewcontroller/index';

  // Status → color map
  const RESULT_COLORS = {
    'win': '#209435',   // green
    'lose': '#B14D35',  // red
    'draw': '#B6BABD'   // neutral
  };

  // Utilities
  function logDebug(...args) { console.debug('[match-library]', ...args); }
  function logInfo(...args) { console.info('[match-library]', ...args); }
  function logWarn(...args) { console.warn('[match-library]', ...args); }
  function logError(...args) { console.error('[match-library]', ...args); }

  // Close all open option menus
  function closeAllMatchOptions() {
    document.querySelectorAll('.match-options.open, #match-options.open').forEach(el => {
      el.classList.remove('open');
      el.classList.add('hidden');
    });
  }

  // Close options on ESC
  document.addEventListener('keyup', (ev) => {
    if (ev.key === 'Escape') {
      closeAllMatchOptions();
    }
  });

  // Close options when clicking outside any .match-card
  document.addEventListener('click', (ev) => {
    if (ev.target.closest('.match-card') || ev.target.closest('.card-options-btn') || ev.target.closest('#card-options-btn')) {
      return;
    }
    closeAllMatchOptions();
  });

  /**
   * attachOptionsHandlers
   * now accepts matchId and matchName so we can store dataset and use in report nav
   */
  function attachOptionsHandlers(matchCardClone, matchId, matchNameConfig = '') {
    logDebug('attachOptionsHandlers called for matchId=', matchId, 'matchNameConfig=', matchNameConfig);

    // Ensure the clone has a class so outside-click check works
    matchCardClone.classList.add('match-card');
    // Normalize and store as strings on dataset so other code can always read them
    matchCardClone.dataset.matchId = String(matchId ?? '');
    matchCardClone.dataset.matchNameConfig = String(matchNameConfig ?? '');

    // If matchNameConfig is empty, try to read it from the DOM inside the clone
    if (!matchNameConfig) {
      const possibleNameEl = matchCardClone.querySelector('[data-role="match-name"], .match-name, #match-name');
      const possibleNameText = possibleNameEl ? (possibleNameEl.textContent || '').trim() : '';
      if (possibleNameText) {
        matchNameConfig = possibleNameText;
        matchCardClone.dataset.matchNameConfig = String(possibleNameText);
        logDebug('Recovered matchNameConfig from DOM for', matchId, possibleNameText);
      }
    }

    // Find elements inside this clone with robust selector fallbacks
    const cardOptionsBtn = matchCardClone.querySelector('#card-options-btn, .card-options-btn, [data-role="card-options-btn"]');
    const matchOptionsEl  = matchCardClone.querySelector('#match-options, .match-options, [data-role="match-options"]');
    const startBtn        = matchCardClone.querySelector('#start-tagging-btn, .start-tagging-btn, [data-role="start-tagging-btn"]');
    const removeBtn       = matchCardClone.querySelector('#remove-match-card-btn, .remove-match-card-btn, [data-role="remove-match-card-btn"]');

    if (!cardOptionsBtn) {
      logWarn('card-options-btn NOT found for match', matchId, matchCardClone);
      // continue: card can still be clickable to report
    }
    if (!matchOptionsEl) {
      logWarn('match-options panel NOT found for match', matchId, matchCardClone);
    }

    logDebug('Found elements for match', matchId, { cardOptionsBtn, matchOptionsEl, startBtn, removeBtn });

    // Toggle menu on options button click
    if (cardOptionsBtn && matchOptionsEl) {
      cardOptionsBtn.addEventListener('click', (ev) => {
        ev.preventDefault();
        ev.stopPropagation();
        logDebug('card-options-btn clicked', matchId, ev.target);

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
          logDebug('Closed options panel for', matchId);
        } else {
          matchOptionsEl.classList.remove('hidden');
          matchOptionsEl.classList.add('open');

          // Position to the right of the button (2px gap)
          const rect = cardOptionsBtn.getBoundingClientRect();
          matchOptionsEl.style.position = 'absolute';
          matchOptionsEl.style.left = `${rect.right + window.scrollX + 2}px`;
          matchOptionsEl.style.top = `${rect.top + window.scrollY}px`;
          matchOptionsEl.style.zIndex = 9999;

          logDebug('Opened options panel for', matchId, {
            left: matchOptionsEl.style.left,
            top: matchOptionsEl.style.top
          });
        }
      });
    }

    // START TAGGING action
    if (startBtn) {
      startBtn.addEventListener('click', async (ev) => {
        ev.stopPropagation();
        logDebug('start-tagging clicked for', matchId);

        // UI feedback
        const origText = startBtn.textContent;
        startBtn.textContent = 'Starting...';
        startBtn.setAttribute('disabled', 'disabled');
        if (matchOptionsEl) { matchOptionsEl.classList.remove('open'); matchOptionsEl.classList.add('hidden'); }

        try {
          // Build payload: always include match_id and include match_name when available
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
            const msg = payload.message || `Failed to start tagging for ${matchId}`;
            logWarn('start-tagging error payload', payload);
            alert(msg);
          } else {
            logInfo('start-tagging success', matchId, payload);

            // If server returned a redirect_url, prefer it but ensure params exist
            if (payload.redirect_url) {
              let redirect = String(payload.redirect_url);
              // Append match_id if missing
              if (!/[\?&]match_id=/.test(redirect)) {
                redirect += (redirect.includes('?') ? '&' : '?') + 'match_id=' + encodeURIComponent(matchId);
              }
              // Append match_name if missing and we have one
              if (effectiveMatchName && !/[\?&]match_name=/.test(redirect)) {
                redirect += '&match_name=' + encodeURIComponent(effectiveMatchName);
              }
              window.location.href = redirect;
              return;
            }

            // Default redirect to tagging page - include both params if available
            if (TAGGING_PAGE_BASE) {
              const redirectUrl = TAGGING_PAGE_BASE + '?match_id=' + encodeURIComponent(matchId) +
                                  (effectiveMatchName ? '&match_name=' + encodeURIComponent(effectiveMatchName) : '');
              window.location.href = redirectUrl;
              return;
            }

            alert('Tagging started.');
          }
        } catch (err) {
          logError('Network error while starting tagging', err);
          alert('Network error while starting tagging.');
        } finally {
          startBtn.textContent = origText;
          startBtn.removeAttribute('disabled');
        }
      });
    } else {
      logDebug('start-tagging-btn not found in card for', matchId);
    }

    // REMOVE MATCH action
    if (removeBtn) {
      removeBtn.addEventListener('click', async (ev) => {
        ev.stopPropagation();
        logDebug('remove-match clicked for', matchId);

        // confirmation
        if (!confirm('Remove this match from the library? This action cannot be undone.')) return;

        // UI feedback
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
            const msg = payload.message || `Failed to remove match ${matchId}`;
            logWarn('remove-match error payload', payload);
            alert(msg);
            // restore button
            removeBtn.textContent = origText;
            removeBtn.removeAttribute('disabled');
          } else {
            logInfo('remove-match success', matchId);
            // remove UI card
            matchCardClone.remove();
          }
        } catch (err) {
          logError('Network error while removing match', err);
          alert('Network error while removing match.');
          removeBtn.textContent = origText;
          removeBtn.removeAttribute('disabled');
        }
      });
    } else {
      logDebug('remove-match-card-btn not found in card for', matchId);
    }

    // NAVIGATE TO REPORT on card click (but ignore clicks on interactive controls)
    // Use .match-card-body or the whole card if no body exists.
    const clickableArea = matchCardClone.querySelector('.match-card-body') || matchCardClone;
    clickableArea.style.cursor = 'pointer';
    clickableArea.addEventListener('click', (ev) => {
      // if user clicked an options button or inside options, ignore
      if (ev.target.closest('.card-options-btn') ||
          ev.target.closest('#card-options-btn') ||
          ev.target.closest('#match-options') ||
          ev.target.closest('.start-tagging-btn') ||
          ev.target.closest('.remove-match-card-btn')) {
        return;
      }

      // Determine effective match name using multiple fallbacks
      const effectiveMatchName = matchNameConfig ||
                                 matchCardClone.dataset.matchNameConfig ||
                                 (matchCardClone.querySelector('[data-role="match-name"], .match-name, #match-name')?.textContent || '').trim() ||
                                 '';

      // 🔍 Explicit debug confirmation:
      console.group('%c[Match Card Clicked]', 'color: green; font-weight: bold;');
      console.log('Match ID   :', matchId);
      console.log('Match Name :', effectiveMatchName);
      console.log('Navigating to:', REPORT_PAGE_BASE + '?match_id=' + encodeURIComponent(matchId) + (effectiveMatchName ? '&match_name=' + encodeURIComponent(effectiveMatchName) : ''));
      console.groupEnd();

      logDebug('match card clicked for report nav', matchId, effectiveMatchName);
      const url = REPORT_PAGE_BASE + '?match_id=' + encodeURIComponent(matchId) + (effectiveMatchName ? '&match_name=' + encodeURIComponent(effectiveMatchName) : '');
      window.location.href = url;
    });
  }

  // Main: fetch data and render cards
  async function fetchAndRenderMatches() {
    logInfo('fetchAndRenderMatches start');

    // DOM references
    const seasonHeader = document.getElementById('season-header');
    const monthCardsContainer = document.getElementById('month-cards-container');
    const monthCardTemplate = document.getElementById('month-card');

    if (!monthCardsContainer || !monthCardTemplate) {
      logError('Month container or month-card template not found.');
      return;
    }

    // Get the inner match card template from month template (fallback robust)
    let matchCardTemplate = monthCardTemplate.querySelector('#match-card, .match-card-template, [data-role="match-card"]');
    if (!matchCardTemplate) {
      // try searching globally (rare)
      matchCardTemplate = document.querySelector('#match-card, .match-card-template, [data-role="match-card"]');
    }

    if (!matchCardTemplate) {
      logError('Match card template not found.');
      return;
    }

    // Clone the inner template before removing the parent template from DOM
    const matchCardTemplateClone = matchCardTemplate.cloneNode(true);

    // Remove the original month template from DOM (we will generate many)
    monthCardTemplate.remove();

    // Fetch API route (adjust if needed)
    const fetchUrl = API_BASE + '/get_all_match_reports';

    try {
      const res = await fetch(fetchUrl);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();

      // Clear container
      monthCardsContainer.innerHTML = '';

      // Update season header if available
      if (seasonHeader && data.season) seasonHeader.textContent = `${data.season} Season`;

      // Iterate months
      (data.months || []).forEach(month => {
        // Clone month card
        const monthCardClone = monthCardTemplate.cloneNode(true);
        monthCardClone.removeAttribute('id');

        // Populate month header (robust selectors)
        const monthHeader = monthCardClone.querySelector('#month-name, #month-header, .month-name, [data-role="month-name"]');
        const yearEl = monthCardClone.querySelector('#year, .year, [data-role="year"]');
        const viewAllBtnText = monthCardClone.querySelector('#month-name-in-btn, .month-name-in-btn, [data-role="month-name-in-btn"]');

        if (monthHeader) monthHeader.textContent = month.monthName || '';
        if (yearEl) yearEl.textContent = month.year || '';
        if (viewAllBtnText) viewAllBtnText.textContent = month.monthName || '';

        // Get inner match container
        const matchCardsContainer = monthCardClone.querySelector('#match-cards-container, .match-cards-container, [data-role="match-cards-container"]');
        if (!matchCardsContainer) {
          logWarn('match-cards-container not found inside month card clone', month);
          return; // skip this month
        }
        // Clear any template children
        matchCardsContainer.innerHTML = '';

        // Iterate matches
        (month.matches || []).forEach(match => {
          // Clone match template
          const matchCardClone = matchCardTemplateClone.cloneNode(true);
          // Remove ids to avoid duplication in the DOM (we query inside clone)
          if (matchCardClone.hasAttribute('id')) matchCardClone.removeAttribute('id');

          // Result
          const resultEl = matchCardClone.querySelector('#my-team-result, [data-role="my-team-result"]');
          // tolerant lookup of the result value from the backend object
          const rawResult = match.MyTeamResult ?? match.myTeamResult ?? match.result ?? match.my_team_result ?? '';
          const normalized = (String(rawResult || '').trim()).toLowerCase();
          const color = RESULT_COLORS[normalized] || '#B6BABD';

          // apply value and styling
          if (resultEl) {
            // set text
            resultEl.textContent = rawResult || '';

            // visual: white text on colored pill that has slight padding and rounded corners
            resultEl.style.color = '#fff';
            resultEl.style.backgroundColor = color;
            resultEl.style.padding = '6px 10px';
            resultEl.style.borderRadius = '8px';
            resultEl.style.display = 'inline-block';
            resultEl.style.fontWeight = '600';
            resultEl.style.boxShadow = '0 1px 3px rgba(0,0,0,0.25)';
          }

          // Match name / date
          const matchNameEl = matchCardClone.querySelector('#match-name, .match-name, [data-role="match-name"]');
          const matchDateEl = matchCardClone.querySelector('#match-date, .match-date, [data-role="match-date"]');
          const matchId = match.matchId ?? match.id ?? match.match_id ?? match.id_str ?? '';
          const matchNameConfig = match.matchNameConfig ?? match.name ?? match.match_name ?? '';

          if (matchNameEl) matchNameEl.textContent = matchNameConfig || '';
          if (matchDateEl) matchDateEl.textContent = match.matchDate || match.date || '';

          // Attach options handlers for this card (must happen before appending)
          attachOptionsHandlers(matchCardClone, matchId, matchNameConfig);

          // Append to inner container
          matchCardsContainer.appendChild(matchCardClone);
        });

        // Append populated month card to main container
        monthCardsContainer.appendChild(monthCardClone);
      });

      logInfo('fetchAndRenderMatches completed successfully');
    } catch (err) {
      logError('Failed to fetch matches:', err);
      monthCardsContainer.innerHTML = `<p class="text-red-500 text-center">We couldn't load the match library. Please try again later.</p>`;
    }
  }

  // Run on DOM ready
  document.addEventListener('DOMContentLoaded', () => {
    logInfo('DOM ready, starting fetchAndRenderMatches');
    fetchAndRenderMatches();
  });

})();
