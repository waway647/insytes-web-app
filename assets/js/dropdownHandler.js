/**
 * dropdownHandler.js
 */

(() => {
  const API_BASE = (window.APP_BASE_URL ? window.APP_BASE_URL.replace(/\/$/, '') + '/' : '') + 'match/librarycontroller';

  // Distinct internal types for each team button to avoid collisions
  const BUTTON_MAP = {
    'season-btn': { type: 'season', span: 'season' },
    'competition-btn': { type: 'competition', span: 'competition' },
    'venue-btn': { type: 'venue', span: 'venue' },
    'my-team-btn': { type: 'my_team', span: 'my-team' },
    'opponent-team-btn': { type: 'opponent_team', span: 'opponent-team' }
  };

  // module-level state
  let currentContext = null;
  let currentTrigger = null;
  let currentType = null;
  let selectedUtilityItemId = null;

  let dynamicDropdown = null;
  let dynamicActionUtility = null;
  let dropdownItemContainer = null;
  let addItemContainer = null;
  let addItemBtn = null;

  function createActionUtility() {
    const wrapper = document.createElement('div');
    wrapper.id = 'dynamic-action-utility';
    wrapper.style.position = 'absolute';
    wrapper.style.display = 'none';
    wrapper.style.zIndex = '2';
    wrapper.style.pointerEvents = 'auto';
    wrapper.style.background = '#131313';
    wrapper.style.border = '1px solid #2a2a2a';

    wrapper.innerHTML = `
      <div class="flex pb-2 bg-[#131313]">
        <div id="action-utility-container" class="flex flex-col">
          <div id="edit-utility-item" class="flex justify-between gap-4 py-2 px-2 border-b-1 border-b-[#2A2A2A] cursor-pointer hover:bg-[#2A2A2A]">
            <img src="<?php echo base_url('assets/images/icons/edit.svg'); ?>" class="pl-2" alt="" />
            <span class="action-utility-item-value pl-2 text-[#B6BABD] font-medium text-sm">Edit</span>
          </div>
          <div id="remove-utility-item" class="flex gap-3 py-2 px-2 cursor-pointer hover:bg-[#2A2A2A]">
            <img src="<?php echo base_url('assets/images/icons/trash.svg'); ?>" class="pl-2" alt="" />
            <span class="action-utility-item-value text-[#B6BABD] font-medium text-sm">Remove</span>
          </div>
        </div>
      </div>
    `;
    return wrapper;
  }

  function ensureDropdownExists() {
    if (!dynamicDropdown) {
      dynamicDropdown = document.getElementById('dynamic-dropdown');
      if (dynamicDropdown) {
        dropdownItemContainer = dynamicDropdown.querySelector('#dropdown-item-container');
        addItemContainer = dynamicDropdown.querySelector('#add-item-container');
        addItemBtn = dynamicDropdown.querySelector('#add-item-btn');
        dynamicDropdown.style.position = dynamicDropdown.style.position || 'absolute';
        dynamicDropdown.style.display = dynamicDropdown.style.display || 'none';
        dynamicDropdown.style.zIndex = dynamicDropdown.style.zIndex || 1;
      } else {
        // fallback: create minimal dropdown
        dynamicDropdown = document.createElement('div');
        dynamicDropdown.id = 'dynamic-dropdown';
        dynamicDropdown.style.position = 'absolute';
        dynamicDropdown.style.display = 'none';
        dynamicDropdown.style.zIndex = 1;
        dynamicDropdown.className = 'pb-4 bg-[#131313] border-1 border-[#2a2a2a]';
        document.body.appendChild(dynamicDropdown);

        dropdownItemContainer = document.createElement('div');
        dropdownItemContainer.id = 'dropdown-item-container';
        dropdownItemContainer.className = 'pb-10';
        dynamicDropdown.appendChild(dropdownItemContainer);

        addItemContainer = document.createElement('div');
        addItemContainer.id = 'add-item-container';
        addItemContainer.className = 'w-full flex justify-center items-center py-4';
        dynamicDropdown.appendChild(addItemContainer);

        addItemBtn = document.createElement('p');
        addItemBtn.id = 'add-item-btn';
        addItemBtn.className = 'text-[#B6BABD] font-normal text-xs cursor-pointer hover:text-white';
        addItemBtn.textContent = 'Add';
        addItemContainer.appendChild(addItemBtn);
      }
    }

    // ensure action utility exists and is appended to body
    if (!dynamicActionUtility) {
      dynamicActionUtility = document.getElementById('dynamic-action-utility');
    }
    if (dynamicActionUtility) {
      if (dynamicActionUtility.parentElement !== document.body) {
        document.body.appendChild(dynamicActionUtility);
      }
      dynamicActionUtility.style.position = 'absolute';
      dynamicActionUtility.style.display = dynamicActionUtility.style.display || 'none';
      dynamicActionUtility.style.zIndex = dynamicActionUtility.style.zIndex || '9999';
      dynamicActionUtility.style.pointerEvents = 'auto';
      dynamicActionUtility.classList.remove('hidden');
    } else {
      dynamicActionUtility = createActionUtility();
      document.body.appendChild(dynamicActionUtility);
    }

    // Attach a single listener to addItemBtn (guard against double attachment)
    if (addItemBtn && !addItemBtn._listenerAttached) {
      addItemBtn.addEventListener('click', (ev) => {
        ev.stopPropagation();
        // If currentType is not set, try to infer from currentContext (backward compatibility)
        const typeToUse = currentType || currentContext;
        if (!typeToUse) {
          console.warn('addItemBtn clicked but no currentType/currentContext available');
          return;
        }

        // If opening player add modal, try to infer team info from currentTrigger (the row that opened the dropdown)
        if (typeToUse === 'player' && currentTrigger) {
          const inferred = inferTeamFromTrigger(currentTrigger);
          openAddModalForContext(typeToUse, inferred);
        } else {
          openAddModalForContext(typeToUse);
        }
      }, { passive: true });
      addItemBtn._listenerAttached = true;
    }
  }

  function inferTeamFromTrigger(triggerEl) {
    // tries to locate which side (my / opponent) the trigger belongs to and return teamId/teamName
    try {
      // prefer #my-team span, fallback to input[name="my_team"]
      const myTeamEl = document.getElementById('my-team') || document.querySelector('input[name="my_team"]');
      const opponentSpan = document.getElementById('opponent-team');

      // if the trigger is inside my-players-tbody or is the add-player button
      if (triggerEl.closest && triggerEl.closest('#my-players-tbody')) {
        return { teamId: myTeamEl?.dataset?.teamId || '', teamName: (myTeamEl?.value || myTeamEl?.textContent || '').trim() || '' };
      }
      if (triggerEl.id === 'add-player-btn') {
        return { teamId: myTeamEl?.dataset?.teamId || '', teamName: (myTeamEl?.value || myTeamEl?.textContent || '').trim() || '' };
      }

      // opponent side checks
      if (triggerEl.closest && triggerEl.closest('#opponent-players-tbody')) {
        return { teamId: opponentSpan?.dataset?.teamId || '', teamName: opponentSpan?.textContent?.trim() || '' };
      }
      if (triggerEl.id === 'add-opponent-player-btn') {
        return { teamId: opponentSpan?.dataset?.teamId || '', teamName: opponentSpan?.textContent?.trim() || '' };
      }

      // default: if trigger has dataset.selectedName or dataset.itemName, use that
      const candidateName = (triggerEl.dataset && (triggerEl.dataset.selectedName || triggerEl.dataset.itemName)) || '';
      return { teamId: (triggerEl.dataset && (triggerEl.dataset.selectedId || '')) || '', teamName: candidateName };
    } catch (err) {
      return { teamId: '', teamName: '' };
    }
  }

  function getTeamIdForTrigger(triggerEl) {
    // Try to infer from the trigger first (this keeps your existing inferTeamFromTrigger use)
    try {
      const inferred = inferTeamFromTrigger(triggerEl) || {};
      if (inferred.teamId) return String(inferred.teamId);

      // Try well-known page elements
      const myTeamSpan = document.getElementById('my-team') || document.querySelector('input[name="my_team"]');
      const oppTeamSpan = document.getElementById('opponent-team');

      // If trigger is obviously from the "my players" area, prefer my-team
      if (triggerEl && triggerEl.closest && triggerEl.closest('#my-players-tbody')) {
        if (myTeamSpan && (myTeamSpan.dataset?.teamId || myTeamSpan.value)) {
          return String(myTeamSpan.dataset?.teamId || myTeamSpan.value || '');
        }
      }
      // If trigger is from opponent area, prefer opponent-team
      if (triggerEl && triggerEl.closest && triggerEl.closest('#opponent-players-tbody')) {
        if (oppTeamSpan && oppTeamSpan.dataset?.teamId) return String(oppTeamSpan.dataset.teamId);
      }

      // Generic fallbacks: prefer my-team if set, otherwise opponent
      if (myTeamSpan && myTeamSpan.dataset?.teamId) return String(myTeamSpan.dataset.teamId);
      if (oppTeamSpan && oppTeamSpan.dataset?.teamId) return String(oppTeamSpan.dataset.teamId);

      // nothing found
      return '';
    } catch (err) {
      console.warn('[dropdownHandler] getTeamIdForTrigger failed', err);
      return '';
    }
  }

  async function apiFetch(path, method = 'GET', data = null) {
    let url = path.startsWith('http') ? path : API_BASE + (path.startsWith('/') ? path : '/' + path);
    const opts = { method, headers: {} };
    if (data) {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(data);
    }
    const res = await fetch(url, opts);
    if (!res.ok) {
      const text = await res.text();
      throw new Error(`API error ${res.status}: ${text}`);
    }
    return await res.json();
  }

  async function openDropdown(contextType, triggerEl) {
    ensureDropdownExists();
    currentContext = contextType;
    currentType = contextType;
    currentTrigger = triggerEl;

    // map internal types that use the same API
    let fetchType = contextType;
    if (contextType === 'my_team' || contextType === 'opponent_team') {
      fetchType = 'team'; // both use the same API endpoint
    }

    // Build fetch path
    let fetchPath = `/get_items?type=${encodeURIComponent(fetchType)}`;

    // If requesting players, attempt to infer the team and include team_id in the fetch
    let inferredTeamId = '';
    if (contextType === 'player') {
      try {
        // prefer the explicit inference function but also try other page elements
        inferredTeamId = getTeamIdForTrigger(triggerEl) || '';
        if (inferredTeamId) {
          fetchPath += `&team_id=${encodeURIComponent(inferredTeamId)}`;
          console.debug('[dropdownHandler] openDropdown: fetching player list with team_id', inferredTeamId);
        } else {
          console.debug('[dropdownHandler] openDropdown: no teamId inferred; fetching generic player list');
        }
      } catch (err) {
        console.warn('[dropdownHandler] openDropdown: inferTeamFromTrigger/getTeamIdForTrigger failed', err);
      }
    }

    try {
      const data = await apiFetch(fetchPath, 'GET');
      let items = Array.isArray(data.items) ? data.items.slice() : [];

      // Client-side filter as a fallback / extra safety:
      // ensure only players matching the selected team are shown when we have a team id
      if (contextType === 'player') {
        // prefer the team id already inferred; if empty, re-run fallback lookup (defensive)
        const teamIdToUse = inferredTeamId || getTeamIdForTrigger(triggerEl) || '';
        if (teamIdToUse) {
          items = items.filter(item => {
            // tolerate different naming conventions returned by the API
            const itemTeamId = item.team_id ?? item.teamId ?? item.team ?? '';
            // stringify both sides and compare
            return String(itemTeamId) === String(teamIdToUse);
          });
          console.debug('[dropdownHandler] openDropdown: client-side filtered players count', items.length);
        }
      }

      renderItems(items || []);
      positionDropdown(triggerEl);
      dynamicDropdown.style.display = 'block';
      hideActionUtility();
    } catch (err) {
      console.error('Failed to fetch dropdown items', err);
      renderItems([]);
      positionDropdown(triggerEl);
      dynamicDropdown.style.display = 'block';
    }
  }

  function closeDropdown() {
    if (dynamicDropdown) dynamicDropdown.style.display = 'none';
    hideActionUtility();
    currentContext = null;
    currentType = null;
    currentTrigger = null;
    selectedUtilityItemId = null;
  }

  function positionDropdown(triggerEl) {
    if (!dynamicDropdown || !triggerEl) return;
    const rect = triggerEl.getBoundingClientRect();
    dynamicDropdown.style.left = `${rect.left + window.scrollX}px`;
    dynamicDropdown.style.top = `${rect.bottom + window.scrollY + 6}px`;
  }

  function renderItems(items) {
    ensureDropdownExists();
    dropdownItemContainer.innerHTML = '';
    if (!Array.isArray(items) || items.length === 0) {
      const empty = document.createElement('div');
      empty.className = 'py-2 px-3 text-sm text-[#B6BABD]';
      empty.textContent = 'No items found';
      dropdownItemContainer.appendChild(empty);
      return;
    }

    items.forEach(item => {
      const row = document.createElement('div');
      row.className = 'flex justify-between gap-15 py-2 px-2 border-b-1 border-b-[#2A2A2A] bg-[#131313] hover:bg-[#2A2A2A]';
      row.dataset.itemId = String(item.id);
      row.dataset.itemName = item.name || item.title || '';
      if (typeof item.jersey !== 'undefined') row.dataset.itemJersey = String(item.jersey);
      if (typeof item.number !== 'undefined' && !row.dataset.itemJersey) row.dataset.itemJersey = String(item.number);
      if (typeof item.short_name !== 'undefined') row.dataset.itemShortName = item.short_name;
      if (typeof item.position !== 'undefined') row.dataset.itemPosition = item.position;
      row.style.cursor = 'pointer';

      const span = document.createElement('span');
      span.className = 'pl-2 text-[#B6BABD] font-medium text-sm';
      span.textContent = item.name || item.title || 'Unnamed';

      const actionBtn = document.createElement('button');
      actionBtn.className = 'action-utility-btn p-1 hover:bg-[#1a1a1a] rounded-full cursor-pointer';
      actionBtn.title = 'Actions';
      actionBtn.type = 'button';
      actionBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" /></svg>`;

      actionBtn.addEventListener('click', (ev) => {
        ev.stopPropagation();
        ensureDropdownExists();
        const rowEl = ev.target.closest('[data-item-id]') || ev.target.closest('.dropdown-item') || row;
        showActionUtilityFor(rowEl);
      });

      row.addEventListener('click', (ev) => {
        if (ev.target.closest('.action-utility-btn')) return;
        const id = row.dataset.itemId;
        const name = row.dataset.itemName || span.textContent.trim();
        selectItem(id, name);
      });

      row.appendChild(span);
      row.appendChild(actionBtn);
      dropdownItemContainer.appendChild(row);
    });

    // Auto-select session team for my-team-btn (if applicable)
    try {
      // run when currentType is exactly my_team (user opened my-team dropdown)
      if (currentType === 'my_team' && currentTrigger) {
        const myTeamEl = document.getElementById('my-team');
        if (myTeamEl) {
          const sessionTeamId = myTeamEl.dataset?.teamId || (window.SESSION_TEAM && window.SESSION_TEAM.id) || '';
          if (sessionTeamId) {
            const matched = items.find(it => String(it.id) === String(sessionTeamId));
            if (matched) {
              // programmatically select it (this will set data-team-id on the element via selectItem)
              selectItem(String(matched.id), matched.name || matched.title || '');
              // disable the my-team button from further manual selection
              const myBtn = document.getElementById('my-team-btn');
              if (myBtn) {
                myBtn.dataset.disabled = '1';
                myBtn.classList.add('opacity-70', 'cursor-default'); // visual hint
              }
            }
          }
        }
      }
    } catch (err) {
      console.warn('[dropdownHandler] auto-select session team failed', err);
    }
  }

  function selectItem(itemId, itemName) {
    if (!currentType && !currentContext) return;

    const effectiveType = currentType || currentContext;

    // Handle my_team / opponent_team explicitly
    if (effectiveType === 'my_team' || effectiveType === 'opponent_team') {
      const spanId = effectiveType === 'my_team' ? 'my-team' : 'opponent-team';
      const spanEl = document.getElementById(spanId);
      if (spanEl) {
        // set display name
        if (spanEl.tagName === 'INPUT' || spanEl.tagName === 'TEXTAREA') {
          spanEl.value = itemName;
        } else {
          spanEl.textContent = itemName;
        }
        // persist team id
        spanEl.dataset.teamId = itemId;
        console.debug('[dropdownHandler] selected team persisted', { type: effectiveType, spanId, itemId, itemName });

        // also mark the trigger (if it exists) so other flows can read it
        if (currentTrigger) {
          currentTrigger.dataset.selectedId = itemId;
          currentTrigger.dataset.selectedName = itemName;
          currentTrigger.dataset.teamId = itemId;
        }
      }
      closeDropdown();
      return;
    }

    // Explicitly handle season / competition / venue
    if (['season', 'competition', 'venue'].includes(effectiveType)) {
      // expect elements with ids: 'season', 'competition', 'venue'
      const spanEl = document.getElementById(effectiveType);
      if (spanEl) {
        // set visible name
        if (spanEl.tagName === 'INPUT' || spanEl.tagName === 'TEXTAREA') {
          spanEl.value = itemName;
        } else {
          spanEl.textContent = itemName;
        }
        // persist id as data-<type>-id (dataset uses camelCase)
        // e.g. dataset.seasonId -> data-season-id in HTML
        const datasetKey = `${effectiveType}Id`; // 'seasonId', 'competitionId', 'venueId'
        spanEl.dataset[datasetKey] = itemId;
        console.debug('[dropdownHandler] persisted id on span', { spanId: effectiveType, datasetKey, itemId, itemName });
      }

      // also set on the trigger for downstream flows
      if (currentTrigger) {
        currentTrigger.dataset.selectedId = itemId;
        currentTrigger.dataset.selectedName = itemName;
        currentTrigger.dataset[`${effectiveType}Id`] = itemId;
      }

      closeDropdown();
      return;
    }

    // Try to update mapped BUTTON_MAP spans first (existing logic)
    const mapping = Object.values(BUTTON_MAP).find(v => v.type === effectiveType);
    const spanId = mapping ? mapping.span : null;
    if (spanId) {
      const spanEl = document.getElementById(spanId);
      if (spanEl) {
        // set display name
        if (spanEl.tagName === 'INPUT' || spanEl.tagName === 'TEXTAREA') {
          spanEl.value = itemName;
        } else {
          spanEl.textContent = itemName;
        }

        // If mapping type somehow indicates team, persist a team id just in case
        if (mapping.type === 'team' || mapping.type === 'my_team' || mapping.type === 'opponent_team') {
          spanEl.dataset.teamId = itemId;
          console.debug('[dropdownHandler] persisted data-team-id on mapped element', { spanId, itemId, itemName });
        }

        // If mapping corresponds to season/competition/venue, also persist the id (extra safety)
        if (['season', 'competition', 'venue'].includes(mapping.type)) {
          const datasetKey = `${mapping.type}Id`;
          spanEl.dataset[datasetKey] = itemId;
          console.debug('[dropdownHandler] persisted data-*id on mapped element', { spanId, datasetKey, itemId, itemName });
        }
      }
    } else if (currentTrigger) {
      // fallback for triggers mapped via BUTTON_MAP (e.g., a trigger button id -> span mapping)
      const triggerId = currentTrigger.id;
      const mapping2 = BUTTON_MAP[triggerId];
      if (mapping2) {
        const spanEl = document.getElementById(mapping2.span);
        if (spanEl) {
          if (spanEl.tagName === 'INPUT' || spanEl.tagName === 'TEXTAREA') {
            spanEl.value = itemName;
          } else {
            spanEl.textContent = itemName;
          }
          if (mapping2.type === 'team' || mapping2.type === 'my_team' || mapping2.type === 'opponent_team') {
            spanEl.dataset.teamId = itemId;
            console.debug('[dropdownHandler] persisted data-team-id on mapping span', { mappingSpan: mapping2.span, itemId, itemName });
          }
          if (['season', 'competition', 'venue'].includes(mapping2.type)) {
            spanEl.dataset[`${mapping2.type}Id`] = itemId;
            console.debug('[dropdownHandler] persisted data-*id on mapping span', { mappingSpan: mapping2.span, type: mapping2.type, itemId, itemName });
          }
        }
      }
    }

    // Always write selectedId/Name to the trigger element for later flows
    if (currentTrigger) {
      currentTrigger.dataset.selectedId = itemId;
      currentTrigger.dataset.selectedName = itemName;
    }

    // Try to pull data from source row (to populate player rows)
    let sourceItemRow = null;
    try {
      sourceItemRow = dropdownItemContainer ? dropdownItemContainer.querySelector(`[data-item-id="${itemId}"]`) : null;
    } catch (e) {
      sourceItemRow = null;
    }

    if (sourceItemRow) {
      if (sourceItemRow.dataset.itemJersey) currentTrigger.dataset.selectedJersey = sourceItemRow.dataset.itemJersey;
      if (sourceItemRow.dataset.itemShortName) currentTrigger.dataset.selectedShortName = sourceItemRow.dataset.itemShortName;
      if (sourceItemRow.dataset.itemPosition) currentTrigger.dataset.selectedPosition = sourceItemRow.dataset.itemPosition;
    }

    try {
      const nameCell = currentTrigger.querySelector ? currentTrigger.querySelector('.player-name') : null;
      const jerseyCell = currentTrigger.querySelector ? currentTrigger.querySelector('.jersey-num') : null;

      if (nameCell) nameCell.textContent = itemName || '';
      if (jerseyCell) jerseyCell.textContent = currentTrigger.dataset.selectedJersey || '';
    } catch (err) {
      console.warn('Failed to update trigger UI cells:', err);
    }

    // Debug: confirm what was stored on the relevant UI elements
    console.debug('[dropdownHandler] selectItem complete', {
      effectiveType,
      itemId,
      itemName,
      currentTrigger: currentTrigger ? {
        id: currentTrigger.id,
        dataset: Object.assign({}, currentTrigger.dataset)
      } : null,
      mappedSpan: spanId ? (document.getElementById(spanId) ? {
        id: spanId,
        dataset: Object.assign({}, document.getElementById(spanId).dataset)
      } : null) : null
    });

    closeDropdown();
  }


  function showActionUtilityFor(itemEl) {
    if (!itemEl) return;
    ensureDropdownExists();
    if (!dynamicActionUtility) return;

    const itemId = itemEl.dataset ? itemEl.dataset.itemId : undefined;
    const itemName = (itemEl.dataset && itemEl.dataset.itemName) || (itemEl.textContent && itemEl.textContent.trim()) || 'Item';

    if (selectedUtilityItemId === itemId && dynamicActionUtility.style.display === 'block') {
      hideActionUtility();
      return;
    }

    selectedUtilityItemId = itemId;

    const rect = itemEl.getBoundingClientRect();
    const viewportWidth = document.documentElement.clientWidth;
    const preferredLeft = rect.right + window.scrollX + 8;

    dynamicActionUtility.style.display = 'block';
    dynamicActionUtility.style.left = '-9999px';
    dynamicActionUtility.style.top = '-9999px';
    const utilityWidth = dynamicActionUtility.offsetWidth || 220;

    let left = preferredLeft;
    if (preferredLeft + utilityWidth > viewportWidth - 8) {
      left = Math.max(window.scrollX + rect.left - utilityWidth - 8, 8);
    }
    dynamicActionUtility.style.left = `${left}px`;
    dynamicActionUtility.style.top = `${rect.top + window.scrollY}px`;
    dynamicActionUtility.style.display = 'block';
    dynamicActionUtility.classList.remove('hidden');
    dynamicActionUtility.style.zIndex = 2;
  }

  function hideActionUtility() {
    if (dynamicActionUtility) dynamicActionUtility.style.display = 'none';
    selectedUtilityItemId = null;
  }

  /**
   * Open Add Modal for a given type.
   * Accepts optional explicitType and optional extraData (e.g. {teamId, teamName})
   */
  function openAddModalForContext(explicitType, extraData = {}) {
    const type = explicitType || currentType || currentContext;
    if (!type) {
      console.warn('openAddModalForContext: no type available');
      return;
    }

    let fetchType = type;
    if (type === 'my_team' || type === 'opponent_team') {
      fetchType = 'team'; // both use the same API endpoint
    }

    const modalKeyMap = {
      season: 'add_new_season',
      competition: 'add_new_competition',
      venue: 'add_new_venue',
      team: 'add_new_team',
      player: 'add_new_player'
    };
    const modalKey = modalKeyMap[fetchType] || `add_new_${fetchType}`;

    const opts = Object.assign({ context: fetchType }, extraData);

    console.debug('🟢 [openAddModalForContext]', {
      explicitType,
      currentType,
      currentContext,
      fetchType,
      modalKey,
      extraData: opts
    });

    if (typeof ModalManager === 'undefined') {
      const modalId = `add-new-${fetchType}-modal`;
      const detail = Object.assign({ modalId, context: fetchType }, extraData);
      document.dispatchEvent(new CustomEvent('open-modal', { detail }));
      return;
    }

    // Pass extraData onto ModalManager so window.onModalOpened receives it
    ModalManager.openModal(modalKey, opts).catch(err => console.warn('Failed to open modal', err));
  }

  async function openEditModalForSelectedItem() {
    const type = currentType || currentContext;
    if (!type || !selectedUtilityItemId) return;

    let fetchType = type;
    if (type === 'my_team' || type === 'opponent_team') {
      fetchType = 'team'; // both use the same API endpoint
    }

    const modalKeyMap = {
      season: 'edit_season',
      competition: 'edit_competition',
      venue: 'edit_venue',
      team: 'edit_team',
      player: 'edit_player'
    };
    const modalKey = modalKeyMap[fetchType] || `edit_${fetchType}`;

    let extraData = {};

    try {
      if (fetchType === 'player') {
        // Fetch the player first to get team_id (if available)
        try {
          const resp = await apiFetch(`/get_item?type=${fetchType}&id=${selectedUtilityItemId}`);
          if (resp && resp.success && resp.item) {
            const p = resp.item;
            if (p.team_id) {
              extraData.teamId = String(p.team_id);
            }

            // Try to get teamName directly from player payload first
            let resolvedTeamName = p.team_name || p.teamName || p.team || '';

            // If not present, try fetching the team record
            if (!resolvedTeamName && extraData.teamId) {
              try {
                const teamResp = await apiFetch(`/get_item?type=team&id=${encodeURIComponent(extraData.teamId)}`);
                if (teamResp && teamResp.success && teamResp.item) {
                  resolvedTeamName = teamResp.item.name || teamResp.item.title || '';
                }
              } catch (err) {
                console.warn('[dropdownHandler] team lookup for edit modal failed', err);
              }
            }

            // Further fallbacks: DOM, session cache, dropdown items
            if (!resolvedTeamName && extraData.teamId) {
              try {
                // Check #my-team, input[name="my_team"], #opponent-team
                const myTeamEl = document.getElementById('my-team') || document.querySelector('input[name="my_team"]');
                const oppTeamEl = document.getElementById('opponent-team');
                if (myTeamEl && String(myTeamEl.dataset?.teamId || myTeamEl.value || '').trim() === String(extraData.teamId)) {
                  resolvedTeamName = (myTeamEl.value || myTeamEl.textContent || '').trim();
                } else if (oppTeamEl && String(oppTeamEl.dataset?.teamId || '').trim() === String(extraData.teamId)) {
                  resolvedTeamName = (oppTeamEl.textContent || '').trim();
                }
              } catch (err) {
                /* ignore DOM fallback errors */
              }
            }

            if (!resolvedTeamName && window.SESSION_TEAM && String(window.SESSION_TEAM.id) === String(extraData.teamId)) {
              resolvedTeamName = window.SESSION_TEAM.name || window.SESSION_TEAM.title || '';
            }

            if (!resolvedTeamName && typeof dropdownItemContainer !== 'undefined' && dropdownItemContainer) {
              try {
                const teamRow = dropdownItemContainer.querySelector(`[data-item-id="${extraData.teamId}"]`);
                if (teamRow) {
                  resolvedTeamName = teamRow.dataset.itemName || teamRow.textContent.trim() || '';
                }
              } catch (err) {
                /* ignore */
              }
            }

            if (resolvedTeamName) {
              extraData.teamName = String(resolvedTeamName);
            } else {
              // ensure we at least pass an empty string (so onModalOpened knows value was attempted)
              extraData.teamName = '';
            }
          } else {
            console.warn('[dropdownHandler] get_item returned no player item for edit modal', resp && resp.message);
          }
        } catch (err) {
          console.warn('[dropdownHandler] failed fetching player details before opening edit modal', err);
        }
      }
    } catch (err) {
      console.warn('[dropdownHandler] openEditModalForSelectedItem prepping extras failed', err);
    }

    if (typeof ModalManager === 'undefined') {
      // legacy dispatch path — include extras if available
      const modalId = `edit-${fetchType}-modal`;
      const detail = Object.assign({ modalId, context: fetchType, id: selectedUtilityItemId }, extraData);
      document.dispatchEvent(new CustomEvent('open-modal', { detail }));
      hideActionUtility();
      return;
    }

    // Use ModalManager and pass itemId + extras (teamId/teamName when available)
    const openOpts = Object.assign({ context: fetchType, itemId: selectedUtilityItemId }, extraData);
    ModalManager.openModal(modalKey, openOpts).catch(err => console.warn('Failed to open edit modal', err));
    hideActionUtility();
  }

  async function removeSelectedItem() {
    const type = currentType || currentContext;
    if (!type || !selectedUtilityItemId) return;
    const confirmDelete = confirm('Are you sure you want to remove this item?');
    if (!confirmDelete) return;
    try {
      const resp = await apiFetch('/delete_item', 'POST', { type, id: selectedUtilityItemId });
      if (resp.success) {
        const row = dropdownItemContainer.querySelector(`[data-item-id="${selectedUtilityItemId}"]`);
        if (row) row.remove();
        hideActionUtility();
        alert(resp.message || 'Deleted');
      } else {
        alert(resp.message || 'Failed to delete');
      }
    } catch (err) {
      console.error('Remove failed', err);
      alert('Delete request failed');
    }
  }

  document.addEventListener('click', async (e) => {
    ensureDropdownExists();

    const btn = e.target.closest(Object.keys(BUTTON_MAP).map(id => `#${id}`).join(', '));
    if (btn) {
      // If a button has data-disabled flag, ignore clicks
      if (btn.dataset && btn.dataset.disabled === '1') {
        e.stopPropagation();
        return;
      }

      const mapping = BUTTON_MAP[btn.id];
      if (!mapping) return;
      if (dynamicDropdown.style.display === 'block' && currentTrigger === btn) {
        closeDropdown();
      } else {
        openDropdown(mapping.type, btn);
      }
      e.stopPropagation();
      return;
    }

    // dropdown item clicks (support clicking rows inside our dynamically created container)
    const itemRow = e.target.closest('[data-item-id]');
    if (itemRow && dropdownItemContainer && dropdownItemContainer.contains(itemRow)) {
      if (e.target.closest('.action-utility-btn')) {
        showActionUtilityFor(itemRow);
        e.stopPropagation();
        return;
      }
      const id = itemRow.dataset.itemId;
      const name = itemRow.dataset.itemName || itemRow.textContent.trim();
      selectItem(id, name);
      e.stopPropagation();
      return;
    }

    // in-DOM add button click (works with both the dropdown add button and legacy #add-item-btn)
    if (e.target.closest('#add-item-btn') || e.target.id === 'add-item-btn') {
      openAddModalForContext();
      e.stopPropagation();
      return;
    }

    const editBtn = e.target.closest('#edit-utility-item');
    if (editBtn && dynamicActionUtility && dynamicActionUtility.contains(editBtn)) {
      openEditModalForSelectedItem();
      e.stopPropagation();
      return;
    }
    const removeBtn = e.target.closest('#remove-utility-item');
    if (removeBtn && dynamicActionUtility && dynamicActionUtility.contains(removeBtn)) {
      await removeSelectedItem();
      e.stopPropagation();
      return;
    }

    if (dynamicDropdown && !dynamicDropdown.contains(e.target) && dynamicDropdown.style.display === 'block') {
      closeDropdown();
    }
    if (dynamicActionUtility && !dynamicActionUtility.contains(e.target)) {
      hideActionUtility();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeDropdown();
  });

  // expose api (also expose currentType for debugging if needed)
  window.dropdownHandler = {
    openDropdown,
    closeDropdown,
    apiFetch,
    getCurrentType: () => currentType,
    openAddModalForContext, // <-- exposed so other in-page scripts (library.php init) can call it
    _internal: { BUTTON_MAP }
  };

  // When a modal is opened, ModalManager should call window.onModalOpened(dialog, modalKey, opts)
  window.onModalOpened = async (dialog, modalKey, opts = {}) => {
    console.groupCollapsed(`🟢 [onModalOpened] Modal Triggered: ${modalKey}`);
    console.log("Dialog Element:", dialog);
    console.log("Options received (opts):", opts);
    console.groupEnd();

    // --- EDIT MODAL ---
    if (opts.itemId) {
      console.log("🟡 Edit modal detected for item:", opts.itemId);
      const { context: type, itemId } = opts;
      try {
        const data = await apiFetch(`/get_item?type=${type}&id=${itemId}`);
        if (!data.success) {
          console.warn('❌ Failed to fetch item details:', data.message);
          return;
        }
        const item = data.item;
        console.log("✅ Loaded item details:", item);

        for (const key in item) {
          const input = dialog.querySelector(`[name="${key}"]`);
          if (input) {
            input.value = item[key];
            input.dispatchEvent(new Event('input', { bubbles: true }));
          }
        }
      } catch (err) {
        console.error('💥 Error loading item for edit modal', err);
      }
      return;
    }

    // --- ADD MODAL PREFILL ---
    if (opts.context === 'player' && (opts.teamId || opts.teamName)) {
      console.log("🟢 Add-player modal detected");
      console.log("➡️ Prefilling with:", {
        teamId: opts.teamId,
        teamName: opts.teamName
      });

      try {
        const teamIdInput = dialog.querySelector('input[name="team_id"]');
        const teamNameInput =
          dialog.querySelector('input[x-model="teamName"]') ||
          dialog.querySelector('input[name="team_name"]') ||
          dialog.querySelector('input[type="text"]');

        if (teamIdInput) {
          console.log("Filling team_id input:", teamIdInput, "→", opts.teamId);
          teamIdInput.value = opts.teamId || '';
          teamIdInput.dispatchEvent(new Event('input', { bubbles: true }));
        } else {
          console.warn("⚠️ team_id input not found in modal");
        }

        if (teamNameInput) {
          console.log("Filling team_name input:", teamNameInput, "→", opts.teamName);
          teamNameInput.value = opts.teamName || '';
          teamNameInput.dispatchEvent(new Event('input', { bubbles: true }));
        } else {
          console.warn("⚠️ team_name input not found in modal");
        }
      } catch (err) {
        console.warn('💥 Failed to prefill add-player modal with team info', err);
      }
    } else {
      console.log("ℹ️ No team info passed or not a player modal. opts.context =", opts.context);
    }
  };

})();
