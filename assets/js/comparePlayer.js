// comparePlayer.js

(function () {
  // Compare module (with centered metric column, single scroll, left "Reference" visual)
  const KEY = 'compare_players_v1';
  const MAX = 2;
  function el(id) { return document.getElementById(id); }

  function showToast(msg, timeout = 2000) {
    const t = document.createElement('div');
    t.className = 'fixed bottom-6 left-1/2 -translate-x-1/2 z-60 bg-black/80 text-white px-4 py-2 rounded-md text-sm shadow';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.classList.add('opacity-0'), timeout - 250);
    setTimeout(() => t.remove(), timeout);
  }

  function loadCompare() {
    try {
      const raw = sessionStorage.getItem(KEY);
      if (!raw) return [];
      return JSON.parse(raw) || [];
    } catch (e) { return []; }
  }
  function saveCompare(arr) {
    try { sessionStorage.setItem(KEY, JSON.stringify(arr.slice(0, MAX))); } catch (e) {}
  }

  // Build snapshot from focus UI (same as before)
  function buildPlayerSnapshotFromFocus() {
    const name = (el('focus-fullname') && el('focus-fullname').textContent.trim()) || '';
    if (!name) return null;
    const subtitle = (el('focus-subtitle') && el('focus-subtitle').textContent.trim()) || '';
    const rating = (el('focus-rating') && el('focus-rating').textContent.trim()) || '';
    const dpr = (el('focus-predicted-dpr') && el('focus-predicted-dpr').textContent.trim()) || '';
    const initials = (el('focus-avatar-initials') && el('focus-avatar-initials').textContent.trim()) || '';

    const stats = {};
    const rows = el('focus-key-stats') ? el('focus-key-stats').querySelectorAll('tr') : [];
    rows.forEach(r => {
      const tds = r.querySelectorAll('td');
      if (tds && tds.length >= 2) {
        const key = (tds[0].textContent || '').trim();
        const val = (tds[1].textContent || '').trim();
        if (key) stats[key] = val || '—';
      }
    });

    return { name, subtitle, rating, dpr, initials, stats };
  }

  function buildPlayerSnapshotFromCard(cardEl) {
    if (!cardEl) return null;
    try {
      const raw = cardEl.getAttribute('data-player');
      if (raw) {
        let obj = null;
        try { obj = JSON.parse(raw); } catch(e) {
          obj = JSON.parse(raw.replace(/&quot;/g,'"').replace(/&#039;/g,"'"));
        }
        if (obj) {
          const name = obj.full_name || obj.player_name || obj.name || obj.raw_name || '';
          const subtitle = (obj.club || obj.team || obj.position || '') + '';
          const rating = obj.rating || '';
          const dpr = obj.predicted_dpr || obj.dpr || '';
          const initials = (name || '').split(/\s+/).map(p => p[0]).slice(0,2).join('').toUpperCase();
          const stats = obj.key_stats || {};
          return { name, subtitle, rating: String(rating), dpr: String(dpr), initials, stats };
        }
      }
    } catch (e) {
      // ignore parse errors
    }
    const nameEl = cardEl.querySelector && (cardEl.querySelector('.player-name') || cardEl.querySelector('.name'));
    const name = (nameEl && nameEl.textContent.trim()) || (cardEl.getAttribute && cardEl.getAttribute('data-player-name')) || (cardEl.textContent || '').trim();
    const subtitle = cardEl.getAttribute && (cardEl.getAttribute('data-player-subtitle') || '') || '';
    const initials = (name || '').split(/\s+/).map(p => p[0]).slice(0,2).join('').toUpperCase();
    return { name, subtitle, rating: '', dpr: '', initials, stats: {} };
  }

  function addFocusedToCompare() {
    const snap = buildPlayerSnapshotFromFocus();
    if (!snap || !snap.name) {
      showToast('No player selected to compare.');
      return;
    }
    addToCompare(snap);
  }

  function addToCompare(snap) {
    if (!snap || !snap.name) return;
    const list = loadCompare();
    const normalized = snap.name.trim().toLowerCase();
    if (list.find(p => (p.name || '').trim().toLowerCase() === normalized)) {
      showToast('Player already in comparison.');
      return;
    }
    if (list.length >= MAX) {
      showToast('Maximum of 2 players can be compared. Remove one first.');
      return;
    }
    list.push(snap);
    saveCompare(list);
    updateTrayUI();
    showToast('Added to compare');
    if (list.length === MAX) {
      openCompareModal();
    }
  }

  function removeFromCompare(idx) {
    const list = loadCompare();
    if (idx < 0 || idx >= list.length) return;
    list.splice(idx,1);
    saveCompare(list);
    updateTrayUI();
  }
  function clearCompare() {
    saveCompare([]);
    updateTrayUI();
  }

  function updateTrayUI() {
    const list = loadCompare();
    const tray = el('compare-tray');
    const avatars = el('compare-avatars');
    const btnAdd = el('btn-compare-manage');
    const btnNow = el('btn-compare-now');
    const btnClear = el('btn-compare-clear');

    if (!tray || !avatars) return;
    avatars.innerHTML = '';

    if (list.length === 0) {
      tray.classList.add('hidden');
      if (btnAdd) btnAdd.disabled = true;
      if (btnNow) btnNow.disabled = true;
      return;
    }

    list.forEach((p, i) => {
      const btn = document.createElement('div');
      btn.className = 'relative bg-[#111214] rounded-full w-8 h-8 flex items-center justify-center text-xs font-semibold text-white cursor-pointer hover:scale-105';
      btn.title = p.name || 'Player';
      btn.innerHTML = `<span>${(p.initials || (p.name||'').split(' ').map(x=>x[0]||'').slice(0,2).join('').toUpperCase())}</span>
                       <button class="absolute -top-2 -right-2 w-5 h-5 text-xs rounded-full bg-red-600 flex items-center justify-center border border-black" data-remove-index="${i}" aria-label="Remove player">×</button>`;
      btn.addEventListener('click', function (ev) {
        ev.stopPropagation();
        const name = p.name || '';
        if (!name) return;
        if (typeof window.showPlayerInFocus === 'function') {
          try { window.showPlayerInFocus({ full_name: name, player_name: name }); } catch (e) {}
        } else {
          try { if (typeof window.setFocusedPlayer === 'function') window.setFocusedPlayer(name); } catch(e){}
        }
      });
      avatars.appendChild(btn);
    });

    avatars.querySelectorAll('button[data-remove-index]').forEach(b => {
      b.addEventListener('click', function (ev) {
        ev.stopPropagation();
        const idx = Number(this.getAttribute('data-remove-index'));
        removeFromCompare(idx);
      });
    });

    tray.classList.remove('hidden');
    if (btnAdd) btnAdd.disabled = false;
    if (btnNow) btnNow.disabled = (list.length < 2);
    if (btnClear) btnClear.disabled = false;
  }

  function escapeHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  function parseStatNumber(s) {
    if (s === null || s === undefined) return null;
    const str = String(s).trim();
    if (!str) return null;
    const cleaned = str.replace(/[, ]+/g, '').replace(/%/g, '').replace(/[^0-9\.\-]/g, '');
    if (cleaned === '' || cleaned === '-' ) return null;
    const v = parseFloat(cleaned);
    return Number.isFinite(v) ? v : null;
  }

  function computeDiffSummary(a, b) {
    if (!a || !b) return '—';
    const num = (v) => { const n = parseFloat(String(v || '').replace(/[^\d\.\-]/g,'')); return Number.isFinite(n) ? n : null; };
    const dprA = num(a.dpr), dprB = num(b.dpr);
    if (dprA !== null && dprB !== null) {
      const diff = (dprA - dprB);
      const sign = diff > 0 ? '+' : (diff < 0 ? '' : '');
      return `${sign}${diff.toFixed(2)} DPR`;
    }
    const countA = Object.keys(a.stats || {}).length;
    const countB = Object.keys(b.stats || {}).length;
    if (countA !== countB) return `${Math.abs(countA - countB)} stat(s) diff`;
    return '—';
  }

  // Set header fields (also toggle visual flag for left "Reference" look)
  function renderPlayerHeader(prefix, snap, isReference = false) {
    if (!prefix) return;
    const nameEl = el(`${prefix}-name`);
    const subEl = el(`${prefix}-sub`);
    const avatarEl = el(`${prefix}-avatar`);
    const ratingEl = el(`${prefix}-rating`);
    const dprEl = el(`${prefix}-dpr`);

    if (nameEl) nameEl.textContent = snap && snap.name ? snap.name : '—';
    if (subEl) subEl.textContent = snap && snap.subtitle ? snap.subtitle : '—';
    if (avatarEl) avatarEl.textContent = (snap && snap.initials) ? snap.initials : ((snap && snap.name) ? (snap.name.split(' ').map(p=>p[0]).slice(0,2).join('')) : '—');
    if (ratingEl) ratingEl.textContent = snap && snap.rating ? snap.rating : '—';
    if (dprEl) dprEl.textContent = snap && snap.dpr ? snap.dpr : '—';

    // Apply distinct visual for left/reference panel
    if (prefix === 'compare-a') {
      const panel = el('compare-player-a');
      const badge = el('compare-a-label');
      if (panel) {
        if (isReference) {
          panel.classList.add('border-green-600', 'shadow-[0_6px_20px_rgba(0,128,62,0.08)]');
          panel.classList.remove('border-transparent');
        } else {
          panel.classList.remove('border-green-600', 'shadow-[0_6px_20px_rgba(0,128,62,0.08)]');
          panel.classList.add('border-transparent');
        }
      }
      if (badge) badge.style.display = isReference ? '' : 'none';
    }
  }

  // Render comparison into three columns inside a single scroll container
  function renderComparisonPanels(aSnap, bSnap) {
    const aStatsEl = el('compare-a-stats');
    const bStatsEl = el('compare-b-stats');
    const namesEl = el('compare-metric-names');
    if (!aStatsEl || !bStatsEl || !namesEl) return;

    // small helper: clean metric label (remove "P90" and tidy /90/per 90 and stray separators)
    function cleanMetricLabel(raw) {
      if (!raw && raw !== 0) return '';
      let s = String(raw);
      s = s.replace(/\bP90\b/gi, '');
      s = s.replace(/\/\s*90\b/gi, '').replace(/\bP\/90\b/gi, '').replace(/\bper\s*90\b/gi, '');
      s = s.replace(/[\(\)]/g, '')
          .replace(/[_\-\–\—\:]+/g, ' ')
          .replace(/\/+/g, ' ')
          .replace(/\s+/g, ' ')
          .trim();
      return s || String(raw);
    }

    // union of stat keys
    const keys = new Set();
    (aSnap && aSnap.stats) && Object.keys(aSnap.stats).forEach(k => keys.add(k));
    (bSnap && bSnap.stats) && Object.keys(bSnap.stats).forEach(k => keys.add(k));
    const allKeys = Array.from(keys);

    if (allKeys.length === 0) {
      aStatsEl.innerHTML = '<div class="text-gray-500">No stats available.</div>';
      namesEl.innerHTML = '<div class="text-gray-500">—</div>';
      bStatsEl.innerHTML = '<div class="text-gray-500">No stats available.</div>';
      return;
    }

    // clear containers
    aStatsEl.innerHTML = '';
    namesEl.innerHTML = '';
    bStatsEl.innerHTML = '';

    allKeys.forEach(k => {
      const rawA = (aSnap && aSnap.stats && (aSnap.stats[k] !== undefined && aSnap.stats[k] !== null)) ? aSnap.stats[k] : '—';
      const rawB = (bSnap && bSnap.stats && (bSnap.stats[k] !== undefined && bSnap.stats[k] !== null)) ? bSnap.stats[k] : '—';

      const na = parseStatNumber(rawA);
      const nb = parseStatNumber(rawB);

      // compute classes
      let aClass = 'text-sm font-semibold';
      let bClass = 'text-sm font-semibold';
      const neutralClass = 'text-white';

      // compute cleaned label early
      const cleanedLabel = cleanMetricLabel(k);
      const cleanedKey = String(k || '').toLowerCase();
      const cleanedLabelForMatch = String(cleanedLabel || '').toLowerCase();

      // detect "unsuccessful passes" metrics (handles variants like unsuccessful_passes, unsuccessful passes, unsuccessful_passes_p90)
      const isUnsuccessfulPasses = /unsuccessful[_\s]?passes/i.test(cleanedKey) || /unsuccessful[_\s]?passes/i.test(cleanedLabelForMatch);

      if (na !== null || nb !== null) {
        // both numeric
        if (na !== null && nb !== null) {
          if (isUnsuccessfulPasses) {
            // For unsuccessful passes: higher = worse → higher = red, lower = green
            if (na > nb) {
              aClass += ' text-red-400';
              bClass += ' text-green-400';
            } else if (nb > na) {
              bClass += ' text-red-400';
              aClass += ' text-green-400';
            } else {
              aClass += ' ' + neutralClass;
              bClass += ' ' + neutralClass;
            }
          } else {
            // Default: higher = better → higher green, lower red
            if (na > nb) {
              aClass += ' text-green-400';
              bClass += ' text-red-400';
            } else if (na < nb) {
              aClass += ' text-red-400';
              bClass += ' text-green-400';
            } else {
              aClass += ' ' + neutralClass;
              bClass += ' ' + neutralClass;
            }
          }

        // only left present
        } else if (na !== null && nb === null) {
          // keep present value neutral/positive visual (existing behavior)
          if (isUnsuccessfulPasses) aClass += ' ' + neutralClass;
          else aClass += ' text-green-400';
          bClass += ' ' + neutralClass;

        // only right present
        } else if (nb !== null && na === null) {
          if (isUnsuccessfulPasses) bClass += ' ' + neutralClass;
          else bClass += ' text-green-400';
          aClass += ' ' + neutralClass;

        } else {
          aClass += ' ' + neutralClass;
          bClass += ' ' + neutralClass;
        }
      } else {
        // non-numeric values — neutral display
        aClass += ' ' + neutralClass;
        bClass += ' ' + neutralClass;
      }

      // left value (right-aligned)
      const aRow = document.createElement('div');
      aRow.className = 'flex items-center justify-end py-1';
      aRow.innerHTML = `<div class="${escapeHtml(aClass)}">${escapeHtml(String(rawA))}</div>`;
      aStatsEl.appendChild(aRow);

      // metric name (center) — cleaned
      const mRow = document.createElement('div');
      mRow.className = 'flex items-center justify-center py-1';
      mRow.innerHTML = `<div class="text-sm text-gray-300 text-center px-2">${escapeHtml(cleanedLabel)}</div>`;
      namesEl.appendChild(mRow);

      // right value (left-aligned)
      const bRow = document.createElement('div');
      bRow.className = 'flex items-center justify-start py-1';
      bRow.innerHTML = `<div class="${escapeHtml(bClass)}">${escapeHtml(String(rawB))}</div>`;
      bStatsEl.appendChild(bRow);
    });
  }

  function openCompareModal() {
    const list = loadCompare();
    if (list.length < 2) {
      showToast('Select two players to compare.');
      return;
    }
    const modal = el('compare-modal');
    if (!modal) return;

    // left panel is reference by default (we mark panel A as reference)
    renderPlayerHeader('compare-a', list[0] || {}, true);
    renderPlayerHeader('compare-b', list[1] || {}, false);
    renderComparisonPanels(list[0] || {}, list[1] || {});

    const diff = computeDiffSummary(list[0] || {}, list[1] || {});
    if (el('compare-diff')) el('compare-diff').textContent = diff;

    modal.classList.remove('hidden');
    // focus close button
    setTimeout(() => { const closeBtn = el('btn-compare-close'); if (closeBtn) closeBtn.focus(); }, 50);

    // scroll to top of stats area
    const sc = el('compare-stats-scroll');
    if (sc) sc.scrollTop = 0;
  }

  function closeCompareModal() {
    const modal = el('compare-modal');
    if (!modal || modal.classList.contains('hidden')) return;
    modal.classList.add('hidden');
    const manage = el('btn-compare-manage');
    if (manage) manage.focus();
  }

  function swapCompareSides() {
    const list = loadCompare();
    if (list.length < 2) return;
    list.reverse();
    saveCompare(list);
    updateTrayUI();
    if (el('compare-modal') && !el('compare-modal').classList.contains('hidden')) {
      openCompareModal();
    }
  }

  // Wiring
  document.addEventListener('DOMContentLoaded', function () {
    try { sessionStorage.removeItem(KEY); } catch (e) { /* ignore */ }
    
    updateTrayUI();

    const btnCompare = el('btn-compare');
    if (btnCompare) {
      btnCompare.addEventListener('click', function (ev) {
        ev.preventDefault();
        addFocusedToCompare();
      });
    }

    const btnManage = el('btn-compare-manage');
    if (btnManage) {
      btnManage.addEventListener('click', function (ev) {
        ev.preventDefault();
        addFocusedToCompare();
      });
    }

    const btnNow = el('btn-compare-now');
    if (btnNow) btnNow.addEventListener('click', function () { openCompareModal(); });

    const btnClear = el('btn-compare-clear');
    if (btnClear) btnClear.addEventListener('click', function () { clearCompare(); });

    const btnClose = el('btn-compare-close');
    if (btnClose) btnClose.addEventListener('click', function () { closeCompareModal(); });

    const btnDone = el('btn-compare-done');
    if (btnDone) btnDone.addEventListener('click', function () { closeCompareModal(); });

    const btnSwap = el('btn-compare-swap');
    if (btnSwap) btnSwap.addEventListener('click', function () { swapCompareSides(); });

    const btnExport = el('btn-compare-export');
    if (btnExport) {
      btnExport.addEventListener('click', function () {
        const list = loadCompare();
        if (!list || list.length < 2) { showToast('Need two players to export.'); return; }
        const keys = new Set();
        list.forEach(p => Object.keys(p.stats || {}).forEach(k => keys.add(k)));
        const allKeys = Array.from(keys);
        const rows = [];
        const header = ['Player','DPR','Rating'].concat(allKeys);
        rows.push(header.join(','));
        list.forEach(p => {
          const row = [ `"${(p.name||'').replace(/"/g,'""')}"`, `"${(p.dpr||'').replace(/"/g,'""')}"`, `"${(p.rating||'').replace(/"/g,'""')}"` ];
          allKeys.forEach(k => { row.push(`"${((p.stats||{})[k]||'').toString().replace(/"/g,'""')}"`); });
          rows.push(row.join(','));
        });
        const csv = rows.join('\n');
        const blob = new Blob([csv], { type:'text/csv' });
        const u = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = u;
        a.download = 'player-compare.csv';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(u);
      });
    }

    const modal = el('compare-modal');
    if (modal) {
      modal.addEventListener('click', function (ev) {
        if (ev.target && ev.target.getAttribute && ev.target.getAttribute('data-close') === 'overlay') {
          closeCompareModal();
        }
      });
    }

    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape') {
        if (el('compare-modal') && !el('compare-modal').classList.contains('hidden')) {
          closeCompareModal();
        }
      }
    });

    // shift-click a .player-card to add to compare quickly
    document.addEventListener('click', function (ev) {
      const card = ev.target && ev.target.closest && ev.target.closest('.player-card');
      if (!card) return;
      if (ev.shiftKey) {
        ev.preventDefault();
        const snap = buildPlayerSnapshotFromCard(card);
        if (!snap || !snap.name) {
          showToast('Could not read player details from card.');
          return;
        }
        addToCompare(snap);
      }
    });
  });
})();
