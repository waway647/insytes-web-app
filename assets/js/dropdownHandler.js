/**
 * dropdownHandler.js
 * Modified to use ModalManager for Add/Edit modal flows.
 *
 * NOTE: modalManager.js must be loaded before this file.
 */

(() => {
  const API_BASE = (window.APP_BASE_URL ? window.APP_BASE_URL.replace(/\/$/, '') + '/' : '') + 'match/librarycontroller';

  const BUTTON_MAP = {
    'season-btn': { type: 'season', span: 'season' },
    'competition-btn': { type: 'competition', span: 'competition' },
    'venue-btn': { type: 'venue', span: 'venue' },
    'my-team-btn': { type: 'team', span: 'my-team' },
    'opponent-team-btn': { type: 'team', span: 'opponent-team' }
  };

  let currentContext = null;
  let currentTrigger = null;
  let selectedUtilityItemId = null;

  let dynamicDropdown = null;
  let dynamicActionUtility = null;
  let dropdownItemContainer = null;
  let addItemContainer = null;
  let addItemBtn = null;

    /**
   * Create the action utility DOM structure (returned element will be appended to body).
   * Markup intentionally mirrors your PHP markup but uses classes for repeated labels.
   */
  function createActionUtility() {
    const wrapper = document.createElement('div');
    wrapper.id = 'dynamic-action-utility';
    // base styles to make it a floating panel (can be overridden by CSS)
    wrapper.style.position = 'absolute';
    wrapper.style.display = 'none';
    wrapper.style.zIndex = '2';
    wrapper.style.pointerEvents = 'auto';
    // small safety defaults so it's visible while debugging
    wrapper.style.background = '#131313'; // match your palette

    // inner content (keeps IDs used by your code for clicks)
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

  /**
   * Ensures both the dropdown and the action utility exist.
   * Guarantees dynamic-action-utility is appended to document.body so it's not clipped.
   */
  function ensureDropdownExists() {
    if (!dynamicDropdown) {
      dynamicDropdown = document.getElementById('dynamic-dropdown');
      // don't grab action here yet — we'll ensure it below
      if (dynamicDropdown) {
        dropdownItemContainer = dynamicDropdown.querySelector('#dropdown-item-container');
        addItemContainer = dynamicDropdown.querySelector('#add-item-container');
        addItemBtn = dynamicDropdown.querySelector('#add-item-btn');
        // keep the dropdown itself absolutely positioned / hidden by default
        dynamicDropdown.style.position = dynamicDropdown.style.position || 'absolute';
        dynamicDropdown.style.display = dynamicDropdown.style.display || 'none';
        // keep zIndex low so the action utility (on body) can be above it
        dynamicDropdown.style.zIndex = dynamicDropdown.style.zIndex || 1;
      } else {
        // fallback: create minimal dropdown (this mirrors previous fallback)
        dynamicDropdown = document.createElement('div');
        dynamicDropdown.id = 'dynamic-dropdown';
        dynamicDropdown.style.position = 'absolute';
        dynamicDropdown.style.display = 'none';
        dynamicDropdown.style.zIndex = 1;
        dynamicDropdown.className = 'pb-4 bg-[#131313]';
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

    // Try to find existing element in DOM (maybe it was statically included)
    if (!dynamicActionUtility) {
      dynamicActionUtility = document.getElementById('dynamic-action-utility');
    }

    // If an element exists but is not appended to body, move it (prevents clipping)
    if (dynamicActionUtility) {
      if (dynamicActionUtility.parentElement !== document.body) {
        document.body.appendChild(dynamicActionUtility);
      }
      // normalize styles (safeguard)
      dynamicActionUtility.style.position = 'absolute';
      dynamicActionUtility.style.display = dynamicActionUtility.style.display || 'none';
      dynamicActionUtility.style.zIndex = dynamicActionUtility.style.zIndex || '9999';
      dynamicActionUtility.style.pointerEvents = 'auto';
      dynamicActionUtility.classList.remove('hidden');
    } else {
      // create it and append to body (so it is guaranteed top-level)
      dynamicActionUtility = createActionUtility();
      document.body.appendChild(dynamicActionUtility);
    }

    // debug trace
    console.log('⚙️ ensureDropdownExists', {
      dynamicDropdownFound: !!dynamicDropdown,
      dropdownItemContainerFound: !!dropdownItemContainer,
      dynamicActionUtilityFound: !!dynamicActionUtility,
      dynamicActionUtilityParent: dynamicActionUtility && dynamicActionUtility.parentElement && dynamicActionUtility.parentElement.tagName
    });
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
    currentTrigger = triggerEl;
    try {
      const data = await apiFetch(`/get_items/${encodeURIComponent(contextType)}`, 'GET');
      renderItems(data.items || []);
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
        console.log('🟦 CLICK actionBtn', {
          target: ev.target,
          currentTarget: ev.currentTarget,
          parentRow: ev.target.closest('[data-item-id]')
        });
        ensureDropdownExists();
        // find the nearest row ancestor to the button
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
  }

  function selectItem(itemId, itemName) {
    if (!currentContext) return;
    const spanId = Object.values(BUTTON_MAP).find(v => v.type === currentContext)?.span;
    if (spanId) {
      const spanEl = document.getElementById(spanId);
      if (spanEl) spanEl.textContent = itemName;
    } else if (currentTrigger) {
      const triggerId = currentTrigger.id;
      const mapping = BUTTON_MAP[triggerId];
      if (mapping) {
        const spanEl = document.getElementById(mapping.span);
        if (spanEl) spanEl.textContent = itemName;
      }
    }
    if (currentTrigger) {
      currentTrigger.dataset.selectedId = itemId;
      currentTrigger.dataset.selectedName = itemName;
    }
    closeDropdown();
  }

  function showActionUtilityFor(itemEl) {
    if (!itemEl) return;
    ensureDropdownExists();
    if (!dynamicActionUtility) return;
    console.log('🟨 showActionUtilityFor START', { itemEl });

    // prefer dataset on the row; fallback to inner text
    const itemId = itemEl.dataset ? itemEl.dataset.itemId : undefined;
    const itemName = (itemEl.dataset && itemEl.dataset.itemName) || (itemEl.textContent && itemEl.textContent.trim()) || 'Item';
    console.log('🟨 Found dataset:', { itemId, itemName });

    if (!itemId) {
      // if no ID present, try to extract from a child or attribute (compat fallback)
      console.warn('showActionUtilityFor: item has no data-item-id, using fallback id');
    }

    // toggle if same item
    if (selectedUtilityItemId === itemId && dynamicActionUtility.style.display === 'block') {
      console.log('🟨 Toggling OFF same utility');
      hideActionUtility();
      return;
    }

    selectedUtilityItemId = itemId;

    // position next to row
    const rect = itemEl.getBoundingClientRect();
    const viewportWidth = document.documentElement.clientWidth;
    const preferredLeft = rect.right + window.scrollX + 8;

    //console.log('📍 Position calc', { rect, left, top });

    // ensure utilityWidth measured (temporarily show it offscreen if needed)
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

    console.log('🟩 Action utility positioned & shown', {
      computedLeft: dynamicActionUtility.style.left,
      computedTop: dynamicActionUtility.style.top,
      display: dynamicActionUtility.style.display,
      zIndex: dynamicActionUtility.style.zIndex
    });
  }

  function hideActionUtility() {
    if (dynamicActionUtility) dynamicActionUtility.style.display = 'none';
    selectedUtilityItemId = null;
  }

  function openAddModalForContext() {
    if (!currentContext) return;
    const modalKeyMap = {
      season: 'add_new_season',
      competition: 'add_new_competition',
      venue: 'add_new_venue',
      team: 'add_new_team'
    };
    const modalKey = modalKeyMap[currentContext] || `add_new_${currentContext}`;
    if (typeof ModalManager === 'undefined') {
      const modalId = `add-new-${currentContext}-modal`;
      document.dispatchEvent(new CustomEvent('open-modal', { detail: { modalId, context: currentContext } }));
      return;
    }
    ModalManager.openModal(modalKey, { context: currentContext }).catch(err => console.warn('Failed to open modal', err));
  }

  function openEditModalForSelectedItem() {
    if (!currentContext || !selectedUtilityItemId) return;
    const modalKeyMap = {
      season: 'edit_season',
      competition: 'edit_competition',
      venue: 'edit_venue',
      team: 'edit_team'
    };
    const modalKey = modalKeyMap[currentContext] || `edit_${currentContext}`;
    if (typeof ModalManager === 'undefined') {
      const modalId = `edit-${currentContext}-modal`;
      document.dispatchEvent(new CustomEvent('open-modal', { detail: { modalId, context: currentContext, id: selectedUtilityItemId } }));
      hideActionUtility();
      return;
    }
    ModalManager.openModal(modalKey, { context: currentContext, itemId: selectedUtilityItemId }).catch(err => console.warn('Failed to open edit modal', err));
    hideActionUtility();
  }

  async function removeSelectedItem() {
    if (!currentContext || !selectedUtilityItemId) return;
    const confirmDelete = confirm('Are you sure you want to remove this item?');
    if (!confirmDelete) return;
    try {
      const resp = await apiFetch('/delete_item', 'POST', { type: currentContext, id: selectedUtilityItemId });
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

    // simpler and more robust: find closest element that has data-item-id
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

  window.dropdownHandler = {
    openDropdown,
    closeDropdown,
    apiFetch,
    _internal: { BUTTON_MAP }
  };

})();
