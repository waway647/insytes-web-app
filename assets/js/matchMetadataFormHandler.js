// assets/js/matchMetadataFormHandler.js
// Requires Alpine.js present on the page.
// Usage: in your HTML form element use x-data="matchMetadataForm()" x-init="init()" @submit.prevent="submitForm"
(function () {
  // expose as Alpine component factory
  window.matchMetadataForm = function matchMetadataForm() {
    return {
      // element refs (populated in init)
      formEl: null,
      seasonEl: null,
      competitionEl: null,
      venueEl: null,
      myTeamEl: null,
      opponentTeamEl: null,

      // hidden input refs
      refs: {},

      init() {
        // store form and visible span elements
        this.formEl = this.$el || document.querySelector('#new-match-panel-form') || document.querySelector('form#new-match-panel-form');
        // visible spans (these IDs exist in library.php)
        this.seasonEl = document.getElementById('season');
        this.competitionEl = document.getElementById('competition');
        this.venueEl = document.getElementById('venue');
        this.myTeamEl = document.getElementById('my-team');
        this.opponentTeamEl = document.getElementById('opponent-team');

        // hidden inputs defined in HTML (see library.php modifications)
        this.refs.season_id = this.formEl.querySelector('input[name="season_id"]');
        this.refs.season_name = this.formEl.querySelector('input[name="season_name"]');

        this.refs.competition_id = this.formEl.querySelector('input[name="competition_id"]');
        this.refs.competition_name = this.formEl.querySelector('input[name="competition_name"]');

        this.refs.venue_id = this.formEl.querySelector('input[name="venue_id"]');
        this.refs.venue_name = this.formEl.querySelector('input[name="venue_name"]');

        this.refs.my_team_id = this.formEl.querySelector('input[name="my_team_id"]');
        this.refs.my_team_name = this.formEl.querySelector('input[name="my_team_name"]');

        this.refs.opponent_team_id = this.formEl.querySelector('input[name="opponent_team_id"]');
        this.refs.opponent_team_name = this.formEl.querySelector('input[name="opponent_team_name"]');

        // Observe changes on spans and mirror them immediately
        const obsConfig = { characterData: true, childList: true, subtree: true, attributes: true, attributeFilter: ['data-team-id', 'data-season-id', 'data-competition-id', 'data-venue-id', 'data-id'] };

        if (this.seasonEl) {
          const mo = new MutationObserver(() => this._syncSeason());
          mo.observe(this.seasonEl, obsConfig);
          this._syncSeason();
        }
        if (this.competitionEl) {
          const mo = new MutationObserver(() => this._syncCompetition());
          mo.observe(this.competitionEl, obsConfig);
          this._syncCompetition();
        }
        if (this.venueEl) {
          const mo = new MutationObserver(() => this._syncVenue());
          mo.observe(this.venueEl, obsConfig);
          this._syncVenue();
        }
        if (this.myTeamEl) {
          const mo = new MutationObserver(() => this._syncMyTeam());
          mo.observe(this.myTeamEl, obsConfig);
          this._syncMyTeam();
        }
        if (this.opponentTeamEl) {
          const mo = new MutationObserver(() => this._syncOpponentTeam());
          mo.observe(this.opponentTeamEl, obsConfig);
          this._syncOpponentTeam();
        }

        // ensure create button is type="submit" OR listens to submit event
        // (no further wiring required here)
        console.log('matchMetadataForm initialized');
      },

      // -------------------------
      // sync helpers: visible -> hidden inputs
      // -------------------------
      _syncSeason() {
        if (!this.seasonEl) return;
        const id = (this.seasonEl.dataset && (this.seasonEl.dataset.seasonId || this.seasonEl.dataset.id)) || '';
        const name = (this.seasonEl.textContent || '').trim();
        if (this.refs.season_id) this.refs.season_id.value = id;
        if (this.refs.season_name) this.refs.season_name.value = name;
      },

      _syncCompetition() {
        if (!this.competitionEl) return;
        const id = (this.competitionEl.dataset && (this.competitionEl.dataset.competitionId || this.competitionEl.dataset.id)) || '';
        const name = (this.competitionEl.textContent || '').trim();
        if (this.refs.competition_id) this.refs.competition_id.value = id;
        if (this.refs.competition_name) this.refs.competition_name.value = name;
      },

      _syncVenue() {
        if (!this.venueEl) return;
        const id = (this.venueEl.dataset && (this.venueEl.dataset.venueId || this.venueEl.dataset.id)) || '';
        const name = (this.venueEl.textContent || '').trim();
        if (this.refs.venue_id) this.refs.venue_id.value = id;
        if (this.refs.venue_name) this.refs.venue_name.value = name;
      },

      _syncMyTeam() {
        if (!this.myTeamEl) return;
        const id = (this.myTeamEl.dataset && (this.myTeamEl.dataset.teamId || this.myTeamEl.dataset.teamid)) || '';
        const name = (this.myTeamEl.textContent || '').trim();
        if (this.refs.my_team_id) this.refs.my_team_id.value = id;
        if (this.refs.my_team_name) this.refs.my_team_name.value = name;
      },

      _syncOpponentTeam() {
        if (!this.opponentTeamEl) return;
        const id = (this.opponentTeamEl.dataset && (this.opponentTeamEl.dataset.teamId || this.opponentTeamEl.dataset.teamid)) || '';
        const name = (this.opponentTeamEl.textContent || '').trim();
        if (this.refs.opponent_team_id) this.refs.opponent_team_id.value = id;
        if (this.refs.opponent_team_name) this.refs.opponent_team_name.value = name;
      },

      // -------------------------
      // collect players from tbody (reads dataset.* and UI state)
      // -------------------------
      _collectPlayers(tbodyId) {
        const tbody = document.getElementById(tbodyId);
        if (!tbody) return [];

        // decide team id for this tbody (fall back to hidden inputs if dataset missing)
        let teamId = '';
        if (tbodyId === 'my-players-tbody') {
            teamId = (this.myTeamEl && (this.myTeamEl.dataset.teamId || this.myTeamEl.dataset.teamid)) || (this.refs.my_team_id ? this.refs.my_team_id.value : '') || '';
        } else if (tbodyId === 'opponent-players-tbody') {
            teamId = (this.opponentTeamEl && (this.opponentTeamEl.dataset.teamId || this.opponentTeamEl.dataset.teamid)) || (this.refs.opponent_team_id ? this.refs.opponent_team_id.value : '') || '';
        } else {
            // generic fallback: try to read a nearby span with data-team-id
            const possible = tbody.closest && tbody.closest('.flex') ? tbody.closest('.flex').querySelector('[data-team-id]') : null;
            teamId = (possible && (possible.dataset.teamId || possible.dataset.teamid)) || '';
        }

        const rows = Array.from(tbody.querySelectorAll('tr'));
        const players = rows.map((tr, idx) => {
            const player_id = tr.dataset.selectedId || '';
            const name = tr.dataset.selectedName || (tr.querySelector('.player-name') ? tr.querySelector('.player-name').textContent.trim() : '');
            const jersey = tr.dataset.selectedJersey || (tr.querySelector('.jersey-num') ? tr.querySelector('.jersey-num').textContent.trim() : '');
            const position = tr.dataset.selectedPosition || (tr.querySelector('.player-position') ? tr.querySelector('.player-position').textContent.trim() : '');
            const xi = !!(tr.querySelector('input[type="checkbox"]') && tr.querySelector('input[type="checkbox"]').checked);
            return {
            rowIndex: tr.dataset.rowIndex || (idx + 1),
            team_id: String(teamId || ''),
            player_id,
            name,
            jersey,
            position,
            xi: xi ? '1' : '0'
            };
        });

        // filter out completely empty placeholders (optional)
        return players.filter(p => p.name || p.player_id);
        },

      // -------------------------
      // submit handler (FormData -> fetch POST)
      // -------------------------
      async submitForm(ev) {
        // ensure latest mirrors
        this._syncSeason();
        this._syncCompetition();
        this._syncVenue();
        this._syncMyTeam();
        this._syncOpponentTeam();

        // optionally block default (Alpine @submit.prevent handles this)
        ev && ev.preventDefault && ev.preventDefault();

        // client-side checks
        const matchDateInput = this.formEl.querySelector('input[name="match_date"]');
        if (!matchDateInput || !matchDateInput.value) {
          alert('Please select a match date');
          return;
        }
        if (!this.refs.my_team_id.value && !this.refs.my_team_name.value) {
          alert('Please choose your team');
          return;
        }
        if (!this.refs.opponent_team_id.value && !this.refs.opponent_team_name.value) {
          alert('Please choose opponent team');
          return;
        }

        // Build FormData (will include hidden inputs automatically)
        const fd = new FormData(this.formEl);

        // my players
        const myPlayers = this._collectPlayers('my-players-tbody');
        myPlayers.forEach((p, i) => {
          fd.append(`my_players[${i}][rowIndex]`, p.rowIndex);
          fd.append(`my_players[${i}][team_id]`, p.team_id);
          fd.append(`my_players[${i}][player_id]`, p.player_id);
          fd.append(`my_players[${i}][name]`, p.name);
          fd.append(`my_players[${i}][jersey]`, p.jersey);
          fd.append(`my_players[${i}][position]`, p.position);
          fd.append(`my_players[${i}][xi]`, p.xi);
        });

        // opponent players
        const opponentPlayers = this._collectPlayers('opponent-players-tbody');
        opponentPlayers.forEach((p, i) => {
          fd.append(`opponent_players[${i}][rowIndex]`, p.rowIndex);
          fd.append(`opponent_players[${i}][team_id]`, p.team_id);
          fd.append(`opponent_players[${i}][player_id]`, p.player_id);
          fd.append(`opponent_players[${i}][name]`, p.name);
          fd.append(`opponent_players[${i}][jersey]`, p.jersey);
          fd.append(`opponent_players[${i}][position]`, p.position);
          fd.append(`opponent_players[${i}][xi]`, p.xi);
        });

        // UI feedback
        const submitBtn = this.formEl.querySelector('button[type="submit"], #create-match-btn');
        const originalText = submitBtn ? submitBtn.textContent : null;
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.textContent = 'Saving...';
          submitBtn.classList.add('opacity-70', 'cursor-default');
        }

        try {
          const action = this.formEl.getAttribute('action') || ((window.APP_BASE_URL ? window.APP_BASE_URL.replace(/\/$/, '') + '/' : '') + 'match/metadataController/create_match');
          const res = await fetch(action, {
            method: 'POST',
            credentials: 'same-origin',
            body: fd
          });

          if (!res.ok) {
            const text = await res.text();
            throw new Error('Server: ' + (text || res.status));
          }

          const json = await res.json().catch(() => ({}));
          if (json && (json.success || json.id)) {
            // success - close UI and optionally refresh or show toast
            alert('Match saved successfully');

            // hide or reset the panel
            const panel = document.getElementById('new-match-panel');
            if (panel) panel.classList.add('hidden');

            // reload the page so the match list updates (short delay to allow UI to settle)
            setTimeout(function(){
              // Replace current page with reload so back-button behavior remains clean
              window.location.reload();
            }, 220);

          } else {
            throw new Error(json && json.message ? json.message : 'Save failed');
          }
        } catch (err) {
          console.error('Create match error', err);
          alert('Failed to save match: ' + (err.message || err));
        } finally {
          if (submitBtn) {
            // In case reload hasn't happened (or on error), restore button state
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            submitBtn.classList.remove('opacity-70', 'cursor-default');
          }
        }
      }
    };
  };

  // If Alpine is available globally, register a named component helper for convenience
  if (window.Alpine && typeof window.Alpine.data === 'function') {
    window.Alpine.data('matchMetadataForm', window.matchMetadataForm);
  }
})();