(function(){
  // Bind to images inside your media cards
  const selectableImgs = Array.from(document.querySelectorAll('.media-card img, .group img'))
    .filter(img => img && img.src); // pick images that have src

  if (!selectableImgs.length) return;

  const overlay = document.getElementById('zoom-overlay');
  const viewer = document.getElementById('zoom-viewer');
  const caption = document.getElementById('zoom-caption');
  const closeBtn = document.getElementById('zoom-close');
  const zoomInBtn = document.getElementById('zoom-zoom-in');
  const zoomOutBtn = document.getElementById('zoom-zoom-out');
  const downloadLink = document.getElementById('zoom-download');

  let clone;               // cloned DOM element used for animated transition
  let currentImgSrc = '';  // url of currently-opened image
  let currentTitle = '';
  let isOpen = false;
  let scale = 1;

  // utility: get rect including scroll offsets
  function getRect(el){
    const r = el.getBoundingClientRect();
    return { left: r.left + window.scrollX, top: r.top + window.scrollY, width: r.width, height: r.height };
  }

  // create clone positioned exactly over thumbnail
  function createCloneFromThumbnail(imgEl){
    const rect = getRect(imgEl);
    const c = document.createElement('img');
    c.src = imgEl.src;
    c.className = 'zoom-clone';
    c.style.left = rect.left + 'px';
    c.style.top = rect.top + 'px';
    c.style.width = rect.width + 'px';
    c.style.height = rect.height + 'px';
    c.style.opacity = '1';
    c.setAttribute('aria-hidden','true');
    document.body.appendChild(c);
    return c;
  }

  // compute target rectangle (centered viewer area)
  function computeTargetRect(cloneRect){
    const maxW = Math.min(window.innerWidth * 0.96, 1400);
    const maxH = Math.min(window.innerHeight * 0.92, 900);
    // preserve aspect ratio from original cloneRect
    const aspect = cloneRect.width / cloneRect.height || 1;
    let targetW = maxW, targetH = targetW / aspect;
    if (targetH > maxH) { targetH = maxH; targetW = targetH * aspect; }
    const left = (window.innerWidth - targetW) / 2 + window.scrollX;
    const top = (window.innerHeight - targetH) / 2 + window.scrollY;
    return { left, top, width: targetW, height: targetH };
  }

  // perform forward animation (thumb -> centered clone -> reveal viewer)
  function openWithTransition(imgEl){
    if (isOpen) return;
    isOpen = true;
    const src = imgEl.src;
    const title = imgEl.getAttribute('alt') || imgEl.getAttribute('title') || '';

    currentImgSrc = src; currentTitle = title; scale = 1;

    // create clone positioned on thumbnail
    clone = createCloneFromThumbnail(imgEl);
    overlay.classList.add('animating');
    overlay.setAttribute('aria-hidden','false');
    overlay.style.display = 'flex';
    // ensure repaint
    requestAnimationFrame(() => {
      const startRect = getRect(imgEl);
      const targetRect = computeTargetRect(startRect);
      // move/scale clone to target
      clone.style.left = targetRect.left + 'px';
      clone.style.top = targetRect.top + 'px';
      clone.style.width = targetRect.width + 'px';
      clone.style.height = targetRect.height + 'px';

      // fade overlay in
      overlay.classList.add('open');

      // when transition ends, render real viewer image and remove clone
      const onEnd = (ev) => {
        clone.removeEventListener('transitionend', onEnd);
        // create the final image inside the viewer
        const finalImg = document.createElement('img');
        finalImg.src = src;
        finalImg.alt = title || '';
        finalImg.style.transform = 'scale(1)';
        finalImg.id = 'zoom-final-img';
        // remove any existing final img
        const existing = viewer.querySelector('#zoom-final-img');
        if (existing) existing.remove();
        viewer.appendChild(finalImg);
        caption.textContent = title || '';
        downloadLink.href = src;
        // remove clone (optional: keep momentarily and fade)
        clone.style.opacity = '0';
        setTimeout(()=> { clone && clone.remove(); clone = null; overlay.classList.remove('animating'); }, 120);
        // focus viewer for keyboard interactions
        viewer.focus();
      };
      clone.addEventListener('transitionend', onEnd);
    });
  }

  // reverse transition (viewer -> thumbnail)
  function closeWithTransition(){
    if (!isOpen) return;
    // find original thumbnail element with same src (prefer visible one)
    const orig = selectableImgs.find(i => i.src === currentImgSrc) || null;
    const finalImg = document.getElementById('zoom-final-img');

    // if clone exists remove
    if (clone) { clone.remove(); clone = null; }

    // create clone from final viewer position
    const rect = finalImg ? getRect(finalImg) : { left: (window.innerWidth/2)+window.scrollX, top: (window.innerHeight/2)+window.scrollY, width: finalImg ? finalImg.clientWidth : 300, height: finalImg ? finalImg.clientHeight : 200 };
    clone = document.createElement('img');
    clone.src = currentImgSrc;
    clone.className = 'zoom-clone';
    clone.style.left = rect.left + 'px';
    clone.style.top = rect.top + 'px';
    clone.style.width = rect.width + 'px';
    clone.style.height = rect.height + 'px';
    clone.style.opacity = '1';
    document.body.appendChild(clone);

    // remove the final image from viewer to avoid double visuals
    if (finalImg) finalImg.remove();
    caption.textContent = '';

    // compute destination (if original thumbnail exists)
    const targetRect = orig ? getRect(orig) : { left: window.innerWidth/2 + window.scrollX, top: window.innerHeight + window.scrollY, width: rect.width*0.6, height: rect.height*0.6 };

    // animate clone to thumbnail rect
    requestAnimationFrame(() => {
      // make overlay less visible during closing
      overlay.classList.add('animating');
      overlay.style.opacity = '0';
      clone.style.left = targetRect.left + 'px';
      clone.style.top = targetRect.top + 'px';
      clone.style.width = targetRect.width + 'px';
      clone.style.height = targetRect.height + 'px';

      const onEnd = () => {
        clone && clone.remove();
        clone = null;
        overlay.classList.remove('open', 'animating');
        overlay.style.display = 'none';
        overlay.setAttribute('aria-hidden','true');
        isOpen = false;
        currentImgSrc = '';
      };
      clone.addEventListener('transitionend', function handler(){ clone.removeEventListener('transitionend', handler); onEnd(); });
    });
  }

  // attach click handlers
  selectableImgs.forEach(img => {
    img.style.cursor = 'zoom-in';
    img.addEventListener('click', (ev) => {
      ev.preventDefault();
      openWithTransition(img);
    });

    // also trigger on key enter/space for accessibility
    img.setAttribute('tabindex','0');
    img.addEventListener('keydown', (ev) => {
      if (ev.key === 'Enter' || ev.key === ' ') {
        ev.preventDefault();
        openWithTransition(img);
      }
    });
  });

  // toolbar actions
  closeBtn.addEventListener('click', closeWithTransition);
  overlay.addEventListener('click', (ev) => {
    // click on background closes, clicks inside viewer should not
    if (ev.target === overlay) closeWithTransition();
  });

  // zoom controls affect final image
  function applyScale(newScale){
    scale = Math.max(1, Math.min(5, newScale));
    const finalImg = document.getElementById('zoom-final-img');
    if (finalImg) finalImg.style.transform = `scale(${scale})`;
  }
  zoomInBtn.addEventListener('click', () => applyScale(scale + 0.25));
  zoomOutBtn.addEventListener('click', () => applyScale(scale - 0.25));
  downloadLink.addEventListener('click', () => {
    // href already set when opening
  });

  // keyboard support
  window.addEventListener('keydown', (ev) => {
    if (!isOpen) return;
    if (ev.key === 'Escape') closeWithTransition();
    if (ev.key === '+' || ev.key === '=') applyScale(scale + 0.25);
    if (ev.key === '-' || ev.key === '_') applyScale(scale - 0.25);
    if (ev.key === '0') applyScale(1);
  });

  // basic pan with mouse when zoomed
  let dragging = false, dragStart = {x:0,y:0}, imgStart = {x:0,y:0};
  viewer.addEventListener('mousedown', (ev) => {
    const finalImg = document.getElementById('zoom-final-img');
    if (!finalImg || scale <= 1) return;
    dragging = true;
    dragStart = { x: ev.clientX, y: ev.clientY };
    imgStart = { x: finalImg._tx || 0, y: finalImg._ty || 0 };
    finalImg.style.cursor = 'grabbing';
    ev.preventDefault();
  });
  window.addEventListener('mousemove', (ev) => {
    if (!dragging) return;
    const finalImg = document.getElementById('zoom-final-img');
    if (!finalImg) return;
    const dx = ev.clientX - dragStart.x;
    const dy = ev.clientY - dragStart.y;
    const tx = imgStart.x + dx;
    const ty = imgStart.y + dy;
    finalImg._tx = tx; finalImg._ty = ty;
    finalImg.style.transform = `translate(${tx}px, ${ty}px) scale(${scale})`;
  });
  window.addEventListener('mouseup', () => {
    if (!dragging) return;
    dragging = false;
    const finalImg = document.getElementById('zoom-final-img');
    if (finalImg) finalImg.style.cursor = '';
  });

  // touch pan handlers for mobile
  let touchStart = null;
  viewer.addEventListener('touchstart', (ev) => {
    if (ev.touches.length !== 1) return;
    const finalImg = document.getElementById('zoom-final-img');
    if (!finalImg || scale <= 1) return;
    touchStart = { x: ev.touches[0].clientX, y: ev.touches[0].clientY, tx: finalImg._tx||0, ty: finalImg._ty||0 };
  }, { passive: true });
  viewer.addEventListener('touchmove', (ev) => {
    if (!touchStart || ev.touches.length !== 1) return;
    const finalImg = document.getElementById('zoom-final-img');
    if (!finalImg) return;
    const dx = ev.touches[0].clientX - touchStart.x;
    const dy = ev.touches[0].clientY - touchStart.y;
    finalImg._tx = touchStart.tx + dx; finalImg._ty = touchStart.ty + dy;
    finalImg.style.transform = `translate(${finalImg._tx}px, ${finalImg._ty}px) scale(${scale})`;
  }, { passive: true });
  viewer.addEventListener('touchend', () => { touchStart = null; });

})();