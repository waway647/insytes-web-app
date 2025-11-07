/**
 * modalManager.js
 * - Loads modal HTML from server via load_modal controller endpoint
 * - Caches fetched HTML
 * - Injects into a single modal root (#app-modal-root)
 * - Centers modal with a dark backdrop
 * - Exposes openModal(modalKey, { itemId, context }), closeModal(), prefetch(modalKey)
 *
 * Include this before dropdownHandler.js.
 */

const ModalManager = (() => {
  const cache = new Map();               // modalKey => html
  const rootId = 'app-modal-root';
  const DEFAULT_ENDPOINT = 'match/librarycontroller/load_modal';

  // small shared style (injected once)
  const STYLE_ID = 'modal-manager-styles';
  const STYLE = `
    /* ModalManager styles */
    #${rootId} { z-index: 1200; position: fixed; inset: 0; display: block; pointer-events: none; }
    #${rootId} .mm-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; opacity:0; transition:opacity .18s ease; pointer-events:auto; }
    #${rootId} .mm-overlay.open { opacity: 1; }
  `;

  let lastActiveElement = null;
  let escListener = null;

  function injectStylesOnce() {
    if (document.getElementById(STYLE_ID)) return;
    const style = document.createElement('style');
    style.id = STYLE_ID;
    style.innerHTML = STYLE;
    document.head.appendChild(style);
  }

  function ensureRoot() {
    injectStylesOnce();
    let root = document.getElementById(rootId);
    if (!root) {
      root = document.createElement('div');
      root.id = rootId;
      document.body.appendChild(root);
    }
    return root;
  }

  function makeUrl(modalKey) {
    const base = (window.APP_BASE_URL && typeof window.APP_BASE_URL === 'string')
      ? window.APP_BASE_URL.replace(/\/$/, '') + '/'
      : (window.location.origin + '/index.php/');
    return `${base}${DEFAULT_ENDPOINT}?name=${encodeURIComponent(modalKey)}`;
  }

  async function fetchModalHtml(modalKey) {
    if (cache.has(modalKey)) return cache.get(modalKey);
    const url = makeUrl(modalKey);
    const resp = await fetch(url, { credentials: 'same-origin' });
    if (!resp.ok) throw new Error(`Failed to load modal (${resp.status})`);
    const html = await resp.text();
    cache.set(modalKey, html);
    return html;
  }

  // Cleanup existing modal root content (but keep cache)
  function clearRootContent() {
    const root = document.getElementById(rootId);
    if (!root) return;
    root.innerHTML = '';
  }

  // open modal: inject overlay + centered dialog and place fetched HTML inside dialog
  async function openModal(modalKey, opts = {}) {
    const root = ensureRoot();

    // remember focus to restore later
    lastActiveElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;

    // put a skeleton so user sees immediate UI
    root.innerHTML = `
      <div class="mm-overlay" role="presentation">
        <div class="mm-skel mm-skeleton">Loading…</div>
      </div>
    `;

    // grab overlay element
    const overlay = root.querySelector('.mm-overlay');

    // attach basic events: clicking overlay outside dialog closes modal
    overlay.addEventListener('click', (ev) => {
      // if clicking overlay (not the dialog or children)
      if (ev.target === overlay) closeModal();
    });

    try {
      const html = await fetchModalHtml(modalKey);

            // replace overlay content with the centered dialog and insert fetched html inside
      root.innerHTML = `
        <div class="mm-overlay" role="presentation">
          <div class="mm-dialog" role="dialog" aria-modal="true" aria-hidden="true" data-modal-hosted="true"></div>
        </div>
      `;

      const overlay2 = root.querySelector('.mm-overlay');
      const dialog = overlay2.querySelector('.mm-dialog');

      // insert the fetched html into dialog
      dialog.innerHTML = html;

      // (existing hostedRoot / dataset code omitted here for brevity)

      // show overlay (fade-in)
      requestAnimationFrame(() => overlay2.classList.add('open'));

      // overlay click outside the dialog -> CLOSE or RESET as desired
      // NOTE: we attach to overlay2 (the current overlay node), not the earlier skeleton overlay.
      overlay2.addEventListener('click', (ev) => {
        if (ev.target === overlay2) {
          // If you want clicking backdrop to CLOSE the modal:
          closeModal();
          // If you want backdrop to RESET modal content instead, call your reset function here.
          // resetDialogContent(); // <-- use this if desired instead of closeModal()
        }
      });

      const cancelBtn = document.getElementById('cancel-btn');

      cancelBtn.addEventListener('click', (ev) => {
        if (cancelBtn) {
            ev.preventDefault();
            closeModal();
        }
      });

      // if fetched html contains a root with data-modal attribute, mark it hosted
      const hostedRoot = dialog.querySelector('[data-modal]');
      if (hostedRoot) {
        hostedRoot.setAttribute('data-modal-hosted', 'true');
      } else {
        // if no [data-modal], wrap content with an inner container so closing logic can find a root
        // (we already are inside .mm-dialog)
      }

      // set data attributes from opts on the dialog
      if (opts.itemId) dialog.dataset.itemId = opts.itemId;
      if (opts.context) dialog.dataset.context = opts.context;

      // show overlay (fade-in)
      requestAnimationFrame(() => overlay2.classList.add('open'));

      // set aria-hidden false
      dialog.setAttribute('aria-hidden', 'false');

      // focus first focusable element
      const focusable = dialog.querySelector('input, select, textarea, button, [tabindex]:not([tabindex="-1"])');
      if (focusable) {
        focusable.focus();
      } else {
        // focus dialog itself for accessibility
        dialog.setAttribute('tabindex', '-1');
        dialog.focus();
      }

      // add ESC key handler
      escListener = (e) => { if (e.key === 'Escape') closeModal(); };
      document.addEventListener('keydown', escListener);

      // hook for initialization (e.g., populate edit modal using opts.itemId)
      if (typeof window.onModalOpened === 'function') {
        try { window.onModalOpened(dialog, modalKey, opts); } catch (err) { /* ignore */ }
      }

      return dialog;
    } catch (err) {
      // show error in overlay
      root.innerHTML = `
        <div class="mm-overlay open" role="presentation">
          <div class="mm-dialog" role="dialog" aria-modal="true" aria-hidden="false">
            <div class="mm-skeleton modal-error">Failed to load modal</div>
          </div>
        </div>
      `;
      throw err;
    }
  }

  // close modal; if silent true, do not restore focus event or remove listeners? We'll still cleanup.
  function closeModal(silent = false) {
    const root = document.getElementById(rootId);
    if (!root) return;

    // remove ESC handler immediately
    if (escListener) {
      document.removeEventListener('keydown', escListener);
      escListener = null;
    }

    const overlay = root.querySelector('.mm-overlay');

    if (overlay) {
      // start fade-out
      overlay.classList.remove('open');

      // clear after transitionend (one-time listener)
      const onTransitionEnd = (ev) => {
        if (ev.target === overlay) {
          overlay.removeEventListener('transitionend', onTransitionEnd);
          clearRootContent();
        }
      };
      overlay.addEventListener('transitionend', onTransitionEnd);

      // fallback: in case transitionend doesn't fire (ensure cleanup)
      setTimeout(() => {
        if (document.getElementById(rootId)) clearRootContent();
      }, 300);
    } else {
      // no overlay element — clear immediately
      clearRootContent();
    }

    // restore focus to last active element
    if (!silent && lastActiveElement && typeof lastActiveElement.focus === 'function') {
      try { lastActiveElement.focus(); } catch (err) { /* ignore */ }
    }
  }


  // background prefetch
  function prefetch(modalKey) {
    if (!cache.has(modalKey)) {
      fetchModalHtml(modalKey).catch(() => { /* ignore */ });
    }
  }

  return {
    openModal,
    closeModal,
    prefetch,
    _cache: cache
  };
})();
