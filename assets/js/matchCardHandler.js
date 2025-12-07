/**
 * match-library.js
 * Full standalone script to fetch and render match cards,
 * wire card-options, start-tagging and remove-match handlers,
 * and include debug logs for easy troubleshooting.
 */

(() => {
const API_BASE = (window.APP_BASE_URL ? window.APP_BASE_URL.replace(/\/$/, '') + '/' : '') + 'match/librarycontroller';
const API_BASE_METADATA = (window.APP_BASE_URL ? window.APP_BASE_URL.replace(/\/$/, '') + '/' : '') + 'match/metadatacontroller';
const API_START_TAGGING = API_BASE + '/start_tagging';
const API_REMOVE_MATCH   = API_BASE_METADATA + '/remove_match';
const TAGGING_PAGE_BASE  = (window.APP_BASE_URL ? window.APP_BASE_URL.replace(/\/$/, '') + '/' : '') + 'studio/mediacontroller/index';


  // Status → color map
  const STATUS_COLORS = {
    'ready': '#48ADF9',
    'tagging in progress': '#B14D35',
    'completed': '#209435',
    'waiting for video': '#B6BABD'
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
    if (ev.target.closest('.card-options-btn') || ev.target.closest('#card-options-btn')) {
      return;
    }
    closeAllMatchOptions();
  });

  // Attach options handlers for an individual card clone
  function attachOptionsHandlers(matchCardClone, matchId) {
    logDebug('attachOptionsHandlers called for matchId=', matchId);

    // Ensure the clone has a class so outside-click check works
    matchCardClone.classList.add('match-card');
    matchCardClone.dataset.matchId = matchId;

    // Find elements inside this clone with robust selector fallbacks
    const cardOptionsBtn = matchCardClone.querySelector('#card-options-btn, .card-options-btn, [data-role="card-options-btn"]');
    const matchOptionsEl  = matchCardClone.querySelector('#match-options, .match-options, [data-role="match-options"]');
    const startBtn        = matchCardClone.querySelector('#start-tagging-btn, .start-tagging-btn, [data-role="start-tagging-btn"]');
    const removeBtn       = matchCardClone.querySelector('#remove-match-card-btn, .remove-match-card-btn, [data-role="remove-match-card-btn"]');

    if (!cardOptionsBtn) {
      logWarn('card-options-btn NOT found for match', matchId, matchCardClone);
      return;
    }
    if (!matchOptionsEl) {
      logWarn('match-options panel NOT found for match', matchId, matchCardClone);
      return;
    }

    logDebug('Found elements for match', matchId, { cardOptionsBtn, matchOptionsEl, startBtn, removeBtn });

    // Toggle menu on options button click
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

        // ✅ Position to the right of the button (2px gap) unless it would overflow the viewport.
        const rect = cardOptionsBtn.getBoundingClientRect();
        matchOptionsEl.style.position = 'absolute';
        matchOptionsEl.style.top = `${rect.top + window.scrollY}px`;
        matchOptionsEl.style.zIndex = 9999;

        // compute menu width (fallback to 275 if not available)
        const menuWidth = (matchOptionsEl.offsetWidth && matchOptionsEl.offsetWidth > 0)
          ? matchOptionsEl.offsetWidth
          : 275;

        // desired left (to the right of button)
        const desiredLeft = rect.right + window.scrollX + 2;

        // If panel would overflow the viewport on the right, move it left as requested:
        if ((rect.right + menuWidth + 2) > window.innerWidth) {
          // user-specified behavior
          matchOptionsEl.style.left = `${rect.right + window.scrollX - 273}px`;
        } else {
          matchOptionsEl.style.left = `${desiredLeft}px`;
        }

        // ensure we don't push it off the left edge — clamp to viewport + small padding
        const numericLeft = parseFloat(matchOptionsEl.style.left) || 0;
        const minLeft = window.scrollX + 8; // 8px padding
        if (numericLeft < minLeft) {
          matchOptionsEl.style.left = `${minLeft}px`;
        }

        logDebug('Opened options panel for', matchId, {
          left: matchOptionsEl.style.left,
          top: matchOptionsEl.style.top
        });
      }

    });

    // START TAGGING action
    if (startBtn) {
      startBtn.addEventListener('click', async (ev) => {
        ev.stopPropagation();
        logDebug('start-tagging clicked for', matchId);

        // UI feedback
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
            logWarn('start-tagging error payload', payload);
            alert(msg);
          } else {
            logInfo('start-tagging success', matchId, payload);
            // redirect if provided
            if (payload.redirect_url) {
              window.location.href = payload.redirect_url;
              return;
            }
            // default redirect
            if (TAGGING_PAGE_BASE) {
              // TAGGING_PAGE_BASE can be set to site_url('tagging') in the match-library page
                window.location.href = TAGGING_PAGE_BASE + '?match_id=' + encodeURIComponent(matchId);
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
        if (!confirm('This would also delete your tagging data and progress. Are you sure?')) return;

        // UI feedback
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
            logWarn('remove-match error payload', payload);
            alert(msg);
            // restore button
            removeBtn.textContent = origText;
            removeBtn.removeAttribute('disabled');
          } else {
            logInfo('remove-match success', matchId);
            // remove UI card
            matchCardClone.remove();

            // reload the page so the match list updates (short delay to allow UI to settle)
            setTimeout(function(){
              // Replace current page with reload so back-button behavior remains clean
              window.location.reload();
            }, 220);
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
    const fetchUrl = 'get_all_matches';

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
        const viewAllBtnText = monthCardClone.querySelector('#month-name-in-btn, .month-name-in-btn, .month-name-in-btn, [data-role="month-name-in-btn"], .month-name-in-btn');

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

          // Thumbnail — replace your existing thumbnail handling with this
          const thumbnailEl = matchCardClone.querySelector('#thumbnail, .thumbnail, [data-role="thumbnail"]');
          if (thumbnailEl) {
            // support either backend key name
            const thumbRaw = match.video_thumbnail || match.thumbnailUrl || match.thumbnail || null;

            if (thumbRaw) {
              // build an absolute URL if the backend returned a relative path
              let thumbUrl = thumbRaw;
              if (!/^https?:\/\//i.test(thumbUrl)) {
                // prefer ASSET_BASE_URL if available (your script uses it), otherwise use origin
                const base = (window.ASSET_BASE_URL && window.ASSET_BASE_URL.replace(/\/$/, ''))
                            || window.location.origin;
                thumbUrl = base + '/' + thumbUrl.replace(/^\/+/, '');
              }

              // set background-image properly (use url(...))
              thumbnailEl.style.backgroundImage = `url("${thumbUrl}")`;
              thumbnailEl.style.backgroundSize = 'cover';
              thumbnailEl.style.backgroundPosition = 'center';
              thumbnailEl.style.backgroundImage = 'opacity-20';
            } else {
              // optional: clear background if none
              thumbnailEl.style.backgroundImage = '';
            }
          }

          // Status & color
          const statusContainer = matchCardClone.querySelector('.border-blue-500, .status-container, [data-role="status-container"]');
          const statusDot = matchCardClone.querySelector('#status-color, .status-dot, [data-role="status-dot"]');
          const statusText = matchCardClone.querySelector('#status, .status-text, [data-role="status-text"]');

          if (statusContainer && statusDot && statusText) {
            // remove template color classes
            statusContainer.classList.remove('border-blue-500');
            statusDot.classList.remove('bg-blue-500');

            const key = (match.status || '').toString().toLowerCase().trim();
            const color = STATUS_COLORS[key] || '#B6BABD';

            statusContainer.style.borderStyle = 'solid';
            statusContainer.style.borderWidth = statusContainer.style.borderWidth || '1px';
            statusContainer.style.borderColor = color;
            statusDot.style.backgroundColor = color;
            statusText.textContent = match.status || '';
          }

          // Match name / date
          const matchNameEl = matchCardClone.querySelector('#match-name, .match-name, [data-role="match-name"]');
          const matchDateEl = matchCardClone.querySelector('#match-date, .match-date, [data-role="match-date"]');
          if (matchNameEl) matchNameEl.textContent = match.matchName || '';
          if (matchDateEl) matchDateEl.textContent = match.matchDate || '';

          // Attach options handlers for this card (must happen before appending)
          attachOptionsHandlers(matchCardClone, match.matchId);

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
