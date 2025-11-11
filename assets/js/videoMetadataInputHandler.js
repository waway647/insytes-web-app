// assets/js/videoMetadataInputHandler.js
(function () {
  'use strict';

  // server-provided global (must be set in HTML before this script)
  const UPLOAD_BASE = (typeof window !== 'undefined' && window.UPLOAD_BASE) ? window.UPLOAD_BASE : null;

  // Config
  const ALLOWED_EXTENSIONS = ['mp4', 'mov'];
  const ALLOWED_MIME = ['video/mp4', 'video/quicktime'];
  const MAX_FILE_SIZE_BYTES = 5 * 1024 * 1024 * 1024; // 5 GB

  let container, fileInput, dropzone, previewArea, previewVideo, fileNameEl, fileStatusEl, fileSizeEl, durationEl, resolutionEl, removeBtn, uploadBtn, errorEl, formEl;
  
  // state
  let currentFile = null;
  let currentObjectUrl = null;

  // utilities
  function bytesToSize(bytes) {
    if (!bytes) return '0 B';
    const sizes = ['B','KB','MB','GB','TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    const value = bytes / Math.pow(1024, i);
    return `${value.toFixed(i ? 1 : 0)} ${sizes[i]}`;
  }

  function formatDuration(s) {
    if (!isFinite(s) || isNaN(s)) return '--:--';
    const total = Math.floor(s);
    const minutes = Math.floor(total / 60);
    const seconds = total % 60;
    return `${minutes}:${String(seconds).padStart(2, '0')}`;
  }

  function showError(message) {
    if (!errorEl) return;
    errorEl.textContent = message;
    errorEl.classList.remove('hidden');
  }

  function clearError() {
    if (!errorEl) return;
    errorEl.textContent = '';
    errorEl.classList.add('hidden');
  }

  function validateFile(file) {
    clearError();
    if (!file) return { ok: false, reason: 'No file selected' };

    const nameParts = file.name.split('.');
    const ext = (nameParts.length > 1) ? nameParts.pop().toLowerCase() : '';
    if (!ALLOWED_EXTENSIONS.includes(ext)) {
      return { ok: false, reason: 'Invalid file extension. Only .mp4 and .mov allowed.' };
    }

    if (file.type && !ALLOWED_MIME.includes(file.type)) {
      return { ok: false, reason: 'Invalid file type. Only MP4 and MOV are allowed.' };
    }

    if (MAX_FILE_SIZE_BYTES && file.size > MAX_FILE_SIZE_BYTES) {
      return { ok: false, reason: `File is too large. Maximum allowed is ${bytesToSize(MAX_FILE_SIZE_BYTES)}.` };
    }

    return { ok: true };
  }

  function revokeCurrentObjectUrl() {
    if (currentObjectUrl) {
      URL.revokeObjectURL(currentObjectUrl);
      currentObjectUrl = null;
    }
  }

  function handleFileSelection(file) {
    if (!previewVideo || !previewArea || !uploadBtn) return;

    revokeCurrentObjectUrl();
    currentFile = null;
    previewVideo.removeAttribute('src');
    previewVideo.load();

    if (!file) {
      previewArea.classList.add('hidden');
      uploadBtn.disabled = true;
      return;
    }

    const validated = validateFile(file);
    if (!validated.ok) {
      showError(validated.reason);
      previewArea.classList.add('hidden');
      uploadBtn.disabled = true;
      return;
    }

    const objectUrl = URL.createObjectURL(file);
    currentObjectUrl = objectUrl;
    currentFile = file;

    previewArea.classList.remove('hidden');
    if (fileNameEl) fileNameEl.textContent = file.name;
    if (fileSizeEl) fileSizeEl.textContent = bytesToSize(file.size);
    if (fileStatusEl) fileStatusEl.textContent = 'Loading metadata...';
    if (durationEl) durationEl.textContent = '--:--';
    if (resolutionEl) resolutionEl.textContent = '—';
    clearError();

    previewVideo.src = objectUrl;
    previewVideo.load();

    const onLoadedMeta = function () {
      const dur = previewVideo.duration;
      const w = previewVideo.videoWidth || '—';
      const h = previewVideo.videoHeight || '—';
      if (durationEl) durationEl.textContent = formatDuration(dur);
      if (resolutionEl) resolutionEl.textContent = (w && h) ? `${w} x ${h}` : '—';
      if (fileStatusEl) fileStatusEl.textContent = 'Ready to upload';
      uploadBtn.disabled = false;
      previewVideo.removeEventListener('loadedmetadata', onLoadedMeta);
    };

    const onError = function () {
      showError('Unable to load video metadata. The file may be corrupted or unsupported.');
      if (fileStatusEl) fileStatusEl.textContent = 'Error';
      uploadBtn.disabled = true;
      previewVideo.removeEventListener('error', onError);
    };

    previewVideo.addEventListener('loadedmetadata', onLoadedMeta);
    previewVideo.addEventListener('error', onError);
  }

  function onInputChange(e) {
    const f = e.target.files && e.target.files[0];
    handleFileSelection(f);
  }

  function onRemove() {
    revokeCurrentObjectUrl();
    currentFile = null;
    if (fileInput) fileInput.value = '';
    if (previewArea) previewArea.classList.add('hidden');
    if (uploadBtn) uploadBtn.disabled = true;
    clearError();
  }

  function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }
  function highlight() { if (dropzone) dropzone.classList.add('ring', 'ring-indigo-200'); }
  function unhighlight() { if (dropzone) dropzone.classList.remove('ring', 'ring-indigo-200'); }

  function onDrop(e) {
    preventDefaults(e);
    unhighlight();
    const dt = e.dataTransfer;
    if (!dt || !dt.files || dt.files.length === 0) return;
    const file = dt.files[0];
    try {
      const dataTransfer = new DataTransfer();
      dataTransfer.items.add(file);
      if (fileInput) fileInput.files = dataTransfer.files;
    } catch (err) {
      // fallback: we'll keep currentFile and intercept submit
    }
    handleFileSelection(file);
  }

  function onSubmitDefault(e) {
    e.preventDefault();
    clearError();

    const fileToSubmit = (fileInput && fileInput.files && fileInput.files[0]) || currentFile;
    if (!fileToSubmit) {
      showError('Please choose a video file to upload.');
      return;
    }

    const validation = validateFile(fileToSubmit);
    if (!validation.ok) {
      showError(validation.reason);
      return;
    }

    if (!formEl || !formEl.action) {
      showError('Upload form is misconfigured.');
      return;
    }

    const formData = new FormData();
    formData.append('video_file', fileToSubmit);

    // include any hidden fields
    const hiddenInputs = formEl.querySelectorAll('input[type="hidden"], input[name="match_id"]');
    hiddenInputs.forEach(inp => {
      if (inp.name && inp.value) formData.append(inp.name, inp.value);
    });

    // UI feedback
    if (uploadBtn) {
      uploadBtn.disabled = true;
      uploadBtn.textContent = 'Uploading...';
    }
    if (fileStatusEl) fileStatusEl.textContent = 'Uploading...';

    fetch(formEl.action, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    })
      .then(res => res.json())
      .then(data => {
        console.log('[Upload response]', data);
        if (data.success) {
          // ✅ success: refresh or redirect
          fileStatusEl.textContent = 'Upload complete!';
          setTimeout(() => {
            window.location.reload(); // or window.history.back();
          }, 1000);
        } else {
          showError(data.error || 'Upload failed.');
          fileStatusEl.textContent = 'Upload failed.';
        }
      })
      .catch(err => {
        console.error('Upload error:', err);
        showError('Upload failed — see console for details.');
      })
      .finally(() => {
        if (uploadBtn) {
          uploadBtn.disabled = false;
          uploadBtn.textContent = 'Upload';
        }
      });
  }

  function setVideoUploadFormAction(matchId) {
    if (!formEl) {
      console.error('[setVideoUploadFormAction] No form element found.');
      return false;
    }

    // Resolve the matchId (priority: argument → global → dataset)
    const resolvedMatchId =
      matchId ??
      window.SERVER_MATCH_ID ??
      formEl.dataset.matchId ??
      null;

    if (!resolvedMatchId) {
      console.warn('[setVideoUploadFormAction] No matchId found, upload may fail.');
    }

    // Resolve the upload base URL
    let base = window.UPLOAD_BASE || '';
    if (!base) {
      console.warn('[setVideoUploadFormAction] No UPLOAD_BASE defined, using fallback.');
      base = 'studio/taggingcontroller/upload_video/';
    }
    if (!base.endsWith('/')) base += '/';

    // Final form action URL
    const action = base + encodeURIComponent(String(resolvedMatchId ?? 'default'));
    formEl.action = action;

    console.log('[setVideoUploadFormAction] Using upload URL:', action);
    return true;
  }

  // init
  function init() {
    container = document.getElementById('video-input-container');
    if (!container) return;

    fileInput = container.querySelector('#match-upload');
    dropzone = container.querySelector('#video-dropzone');
    previewArea = container.querySelector('#video-preview-area');
    previewVideo = container.querySelector('#video-preview');
    fileNameEl = container.querySelector('#video-file-name');
    fileStatusEl = container.querySelector('#video-file-status');
    fileSizeEl = container.querySelector('#video-file-size');
    durationEl = container.querySelector('#video-duration');
    resolutionEl = container.querySelector('#video-resolution');
    removeBtn = container.querySelector('#remove-video-btn');
    uploadBtn = container.querySelector('#upload-btn');
    errorEl = container.querySelector('#video-error');
    formEl = container.querySelector('#video-upload-form') || document.getElementById('video-upload-form');

    if (!fileInput || !dropzone || !formEl) return;

    fileInput.addEventListener('change', onInputChange);
    if (removeBtn) removeBtn.addEventListener('click', onRemove);
    ['dragenter','dragover','dragleave','drop'].forEach(e => dropzone.addEventListener(e, preventDefaults, false));
    ['dragenter','dragover'].forEach(e => dropzone.addEventListener(e, highlight, false));
    ['dragleave','drop'].forEach(e => dropzone.addEventListener(e, unhighlight, false));
    dropzone.addEventListener('drop', onDrop, false);
    formEl.addEventListener('submit', onSubmitDefault);

    if (previewArea) previewArea.classList.add('hidden');
    if (uploadBtn) uploadBtn.disabled = true;
    clearError();

    // set form action
    setVideoUploadFormAction();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // expose helper for manual calls if needed
  window.setVideoUploadFormAction = setVideoUploadFormAction;
})();
