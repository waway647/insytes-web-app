<?php 
    $my_team = $this->session->userdata('my_team_abbreviation'); 
    $opponent_team = $this->session->userdata('opponent_team_abbreviation'); 
    $match_video_exist = $this->session->userdata('tagging_video_url'); 
    $match_thumbnail = $this->session->userdata('tagging_thumbnail_url');
    $video_file_name_for_tagging = $this->session->userdata('video_file_name_for_tagging');
?>
<style>
/* small styles for player icons & overlay */
#pitch-wrapper { position: relative; display: block; }
#pitch-overlay { position: absolute; inset: 0; pointer-events: auto; z-index: 5; }
.player-jersey {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:12px;
  font-weight:700;
  box-shadow: 0 1px 3px rgba(0,0,0,0.4);
  position: absolute;
  cursor: pointer;
  user-select: none;
  pointer-events: auto;
  touch-action: none; /* important for pointer events/touch */
  transition: left 0.3s ease; /* add transition for smooth flipping */
}
</style>

<div class="flex w-full h-full">
    <div class="flex flex-col w-180 h-full p-12 bg-[#2A2A2A] border-x-1 border-black text-[#B6BABD]">
        <?php if (!empty($match_video_exist)): ?>
        <div id="video-display-container" class="flex flex-col px-10 py-2 text-center gap-2 rounded-2xl">
            <img id="video-thumbnail" src="<?php echo base_url($match_thumbnail); ?>" class="rounded-2xl" alt="">
            <span id="video-file" class="text-[#B6BABD] text-xs"><?php echo $video_file_name_for_tagging ?></span>
        </div>
        <?php else: ?>
        <div id="video-input-container" class="w-full">
            <form id="video-upload-form"
                    action=""
                    method="post"
                    enctype="multipart/form-data"
                    class="flex flex-col gap-4">

                <!-- Dropzone / clickable area -->
                <label id="video-dropzone"
                    for="match-upload"
                    class="flex flex-col items-center justify-center py-9 w-full border-1 border-[#3b3b3b] rounded-2xl cursor-pointer bg-[#131313]">
                <div class="mb-3 flex items-center justify-center">
                    <!-- svg -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none">
                    <g id="Upload 02">
                        <path id="icon" d="M16.296 25.3935L19.9997 21.6667L23.7034 25.3935M19.9997 35V21.759M10.7404 27.3611H9.855C6.253 27.3611 3.33301 24.4411 3.33301 20.8391C3.33301 17.2371 6.253 14.3171 9.855 14.3171V14.3171C10.344 14.3171 10.736 13.9195 10.7816 13.4326C11.2243 8.70174 15.1824 5 19.9997 5C25.1134 5 29.2589 9.1714 29.2589 14.3171H30.1444C33.7463 14.3171 36.6663 17.2371 36.6663 20.8391C36.6663 24.4411 33.7463 27.3611 30.1444 27.3611H29.2589" stroke="#4F46E5" stroke-width="1.6" stroke-linecap="round" />
                    </g>
                    </svg>
                </div>

                <h2 id="drop-instruction" class="text-center text-gray-400 text-xs font-normal leading-4 mb-1">
                    MP4 or MOV
                </h2>
                <h4 class="text-center text-[#4b4b4b] text-sm font-medium leading-snug">Drag & Drop your video here or click to browse</h4>

                <!-- hidden file input -->
                <input id="match-upload"
                        name="video_file"
                        type="file"
                        accept=".mp4,.mov,video/mp4,video/quicktime"
                        class="hidden"
                        required
                        />
                </label>

                <!-- Preview & metadata area (populated by JS) -->
                <div id="video-preview-area" class="w-full grid gap-2 hidden">
                <div id="video-preview-card" class="flex items-start gap-4 p-3 border-1 border-[#3b3b3b] rounded-2xl bg-[#131313]">
                    <!-- left: video preview -->
                    <div class="w-40 h-24 bg-gray-100 rounded overflow-hidden flex items-center justify-center">
                    <video id="video-preview" class="w-full h-full object-cover" controls muted playsinline></video>
                    </div>

                    <!-- right: metadata -->
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <div>
                            <h4 id="video-file-name" class="text-[#4b4b4b] text-xs font-medium leading-snug">Filename.mp4</h4>
                            <p id="video-file-status" class="mt-2 text-indigo-400 text-xs opacity-50">Ready to upload</p>
                            </div>
                        </div>

                        <div class="mt-3 grid gap-1 text-xs text-[#4b4b4b]">
                            <div><strong>Size:</strong> <span id="video-file-size">0 MB</span></div>
                            <div><strong>Duration:</strong> <span id="video-duration">--:--</span></div>
                            <div><strong>Resolution:</strong> <span id="video-resolution">—</span></div>
                            <div id="video-error" class="text-red-600 text-xs hidden"></div>
                        </div>

                        <button id="remove-video-btn" type="button" class="mt-3 text-xs px-2 py-1 border-2 border-[#2A2A2A] text-[#808080] rounded hover:border-[#414141] cursor-pointer">Remove</button>
                    </div>
                </div>
                </div>

                <!-- Upload button -->
                <div class="flex items-center gap-2">
                <button id="upload-btn" type="submit" class="w-30 px-8 py-2 bg-[#1b1b1b] border-1 border-[#2a2a2a] hover:bg-[#131313] rounded-lg cursor-pointer" disabled>
                    Upload
                </button>
                <div id="upload-hint" class="text-xs text-[#797979]">Only .mp4 and .mov allowed.</div>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <div class="flex flex-col w-full h-full">
        <!-- Custom Video Player with Custom Controls -->
        <div class="flex flex-col w-full h-full text-xs">
            <div id="video-other-controls" class="flex w-full h-fit bg-[#1D1D1D]">
                <div class="relative">
                    <button id="zoom-percent-dropdown" class="flex gap-2 items-center px-3 py-1 text-white border-r-1 border-black cursor-pointer">
                        <span>100%</span>
                        <svg class="h-5 w-5 transition-transform duration-300 text-[#B6BABD] group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div id="zoom-options" class="absolute bottom-full left-0 mb-2 w-full bg-gray-700 rounded shadow-lg overflow-hidden hidden">
                        <div class="text-white text-center py-2 px-4 cursor-pointer hover:bg-gray-600" data-zoom="1.0">100%</div>
                        <div class="text-white text-center py-2 px-4 cursor-pointer hover:bg-gray-600" data-zoom="0.75">75%</div>
                        <div class="text-white text-center py-2 px-4 cursor-pointer hover:bg-gray-600" data-zoom="0.50">50%</div>
                        <div class="text-white text-center py-2 px-4 cursor-pointer hover:bg-gray-600" data-zoom="0.25">25%</div>
                    </div>
                </div>
                
                <div class="flex w-full justify-between">
                    <div class="flex px-6 justify-center items-center border-r-1 border-black">
                        <span id="current-video-time-progress" class="text-white">00:00:00:00</span>
                    </div>
                    
                    <div class="flex px-6 justify-center items-center border-l-1 border-black">
                        <span id="full-video-time" class="text-white">00:00:00:00</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col w-full h-full">
                <div id="video-player-container" class="flex items-center w-full h-full overflow-hidden border-y-1 border-black">
                    <video id="video-player" class="w-full object-fill transform-origin-top-left">
                        <source src="<?php echo base_url($match_video_exist); ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>

                <div id="custom-controls" class="flex flex-col w-full h-auto gap-3 p-3 bg-[#1D1D1D] border-b-1 border-black">
                    <div id="progress-bar-container" class="relative w-full h-4 cursor-pointer flex items-center">
                        <div class="relative w-full h-1.5 bg-gray-500/30 rounded-full transition-all duration-200">
                            <div id="buffered-bar" class="absolute left-0 top-0 h-full bg-gray-500/50 rounded-full pointer-events-none"></div>
                            <div id="progress-bar" class="absolute left-0 top-0 h-full bg-white rounded-full pointer-events-none"></div>
                        </div>
                    </div>

                    <div id="playback-basic-controls" class="flex w-full justify-center gap-6">
                        <img id="rewind-5s-btn" src="<?php echo base_url('assets/images/icons/10-sec-back.svg'); ?>" class="cursor-pointer" alt="Rewind 5s">
                        <img id="reverse-play-btn" src="<?php echo base_url('assets/images/icons/reverse.svg'); ?>" class="cursor-pointer" alt="Reverse Play">
                        <img id="pause-btn" src="<?php echo base_url('assets/images/icons/pause.svg'); ?>" class="cursor-pointer" alt="Pause">
                        <img id="play-btn" src="<?php echo base_url('assets/images/icons/play.svg'); ?>" class="cursor-pointer" alt="Play">
                        <img id="forward-5s-btn" src="<?php echo base_url('assets/images/icons/10-sec-forward.svg'); ?>" class="cursor-pointer" alt="Forward 5s">
                    </div>
                </div>
            </div>
        </div>
        <div class="flex w-full h-full pt-14 bg-[#1D1D1D]">
            <div class="flex w-full h-full bg-[#131313]">
        
            </div>
        </div>
    </div>
    <div class="flex flex-col w-300 h-full px-20 py-4 bg-[#1C1C1C] border-x-1 border-black text-[#B6BABD]">
        <div class="pb-4 border-b-1 border-b-[#2a2a2a]">
            <h1 class="font-semibold text-md">Options</h1>
        </div>
        <form 
            id="setup-config-form"
            action="" 
            method="post">
            <div class="flex w-full justify-between gap-20 py-4 border-b-1 border-b-[#2a2a2a]">
                <div class="flex flex-col w-full gap-2">
                    <p class="text-sm font-semibold"><?php echo $my_team ?> Jersey Color</p>
                    <input id="my-team-color" type="color" name="my_team_color" class="cursor-pointer w-full h-8 rounded-sm border-none outline-none focus:outline-none focus:ring-0 [appearance:none] [border:none]">
                </div>
                <div class="flex flex-col w-full gap-2">
                    <p class="text-sm font-semibold"><?php echo $opponent_team ?> Jersey Color</p>
                    <input id="opponent-team-color" type="color" name="opponent_team_color" class="cursor-pointer w-full h-8 rounded-sm">
                </div>
            </div>
            <div class="flex flex-col gap-5 py-4 border-b-1 border-b-[#2a2a2a]">
                <p class="text-sm font-semibold">Attacking Direction</p>
                <div class="flex w-full justify-between items-center gap-4">
                    <input type="hidden" name="attacking_direction">  
                    <div class="flex">
                        <div id="my-team-circle" class="flex justify-center items-center w-13 h-13 rounded-[1000px] bg-black">
                            <span class="text-xs"><?php echo $my_team ?></span>
                        </div>
                    </div>
                    <div class="flex w-10">
                        <img src="<?php echo base_url('assets/images/icons/arrow-right.svg'); ?>" class="w-full" alt="">
                    </div>
                    <div class="flex">
                        <button id="switch-attacking-direction-btn" type="button" class="px-6 py-2 bg-[#131313] text-sm rounded-sm cursor-pointer">Switch</button>
                    </div>
                    <div class="flex w-10">
                        <img src="<?php echo base_url('assets/images/icons/arrow-left.svg'); ?>" class="w-full" alt="">
                    </div>
                    <div class="flex">
                        <div id="opponent-team-circle" class="flex justify-center items-center w-13 h-13 rounded-[1000px] bg-black">
                            <span class="text-xs"><?php echo $opponent_team ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex w-full justify-between gap-20 py-4 border-b-1 border-b-[#2a2a2a]">
                <div class="flex flex-col w-full gap-2">
                    <p class="text-sm font-semibold"><?php echo $my_team ?> Roster</p>
                    <span id="all-my-team-player-jerseys-list" class="w-full text-wrap text-xs"></span>
                </div>
                <div class="flex flex-col w-full gap-2">
                    <p class="text-sm font-semibold"><?php echo $opponent_team ?> Roster</p>
                    <span id="all-opponent-team-player-jerseys-list" class="w-full text-wrap text-xs"></span>
                </div>
            </div>
            <div class="flex flex-col justify-between gap-2 py-6 border-b-1 border-b-[#2a2a2a]">
                <div id="pitch-wrapper" class="flex justify-center">
                    <img id="pitchmap" class="w-100" src="<?php echo base_url('assets/images/pitchmap/Football_field.svg'); ?>" alt="">
                    <div id="pitch-overlay" class="w-100"></div>
                </div>
            </div>
            <div class="pt-6 text-right">
                <button id="submit-form" type="submit" class="px-6 py-2 bg-[#6366F1] rounded-md font-semibold text-sm text-white hover:bg-[#5052ec] cursor-pointer">Save Configurations</button>
            </div>
        </form>
    </div>
</div>

<script src="<?php echo base_url(); ?>assets/js/studioVideoPlayer.js?<?php echo time(); ?>"></script>

<script>
    window.UPLOAD_BASE = <?php echo json_encode(site_url('studio/mediacontroller/upload_video/')); ?>;
    window.SERVER_MATCH_ID = <?php echo json_encode($match_id ?? null); ?>;
    window.API_GET_MATCH_DATA = <?php echo json_encode(site_url('studio/taggingcontroller/get_match_data')); ?>;

    (function resolveAndExposeMatchId() {
        function fromPath() {
            try {
            const parts = location.pathname.split('/').filter(Boolean);
            const t = parts.indexOf('tagging');
            if (t >= 0 && parts[t + 1] === 'index' && parts[t + 2]) {
                return decodeURIComponent(parts[t + 2]);
            }
            } catch (e) { /* ignore */ }
            return null;
        }

        function fromQuery() {
            try {
            return (new URL(location.href)).searchParams.get('match_id');
            } catch (e) { return null; }
        }

        const resolved = window.SERVER_MATCH_ID || fromPath() || fromQuery() || sessionStorage.getItem('current_match_id') || null;

        if (!resolved) {
            window.TAGGING_MATCH_ID = null;
            console.warn('[TAGGING] No match_id resolved from server / URL / sessionStorage.' );
            return;
        }

        sessionStorage.setItem('current_match_id', resolved);
        window.TAGGING_MATCH_ID = resolved;
    })();

    function mirrorX(x) {
        // The transformed x position, denoted as x', is given by the formula: x' = 100 - x
        return 100 - x;
    }

    let config = null;

    // Run main initialization after DOM is ready so pitch elements exist
    document.addEventListener('DOMContentLoaded', function () {
        (async function () {
            const matchId = window.TAGGING_MATCH_ID;

            // read from global defaults (so console/tools can inspect & change)
            var mode = window.mode || 'setup';
            var positionsLocked = (typeof window.positionsLocked !== 'undefined') ? window.positionsLocked : false;

            // keep global in sync if code changes them later
            function syncGlobals() {
            window.mode = mode;
            window.positionsLocked = positionsLocked;
            }
            syncGlobals();

            let tagStep = window.tagStep || null;

            let pitchOverlay = document.getElementById('pitch-overlay');
            let pitchImg = document.getElementById('pitchmap');

            function ensurePitchOverlayExists() {
                pitchImg = document.getElementById('pitchmap') || pitchImg;
                pitchOverlay = document.getElementById('pitch-overlay') || pitchOverlay;
                if (!pitchOverlay) {
                    if (pitchImg && pitchImg.parentElement) {
                        const wrapper = pitchImg.parentElement;
                        if (getComputedStyle(wrapper).position === 'static' || !wrapper.style.position) {
                            wrapper.style.position = wrapper.style.position || 'relative';
                        }
                        const overlay = document.createElement('div');
                        overlay.id = 'pitch-overlay';
                        overlay.style.position = 'absolute';
                        overlay.style.left = '0';
                        overlay.style.top = '0';
                        overlay.style.right = '0';
                        overlay.style.bottom = '0';
                        overlay.style.pointerEvents = 'auto';
                        overlay.style.zIndex = 5;
                        wrapper.appendChild(overlay);
                        pitchOverlay = overlay;
                    } else {
                        pitchOverlay = document.getElementById('pitch-container') || null;
                    }
                } else {
                    const wrapper = pitchOverlay.parentElement;
                    if (wrapper && (getComputedStyle(wrapper).position === 'static' || !wrapper.style.position)) {
                        wrapper.style.position = wrapper.style.position || 'relative';
                    }
                }
            }

            function ensurePitchReady(timeoutMs = 2000) {
                return new Promise((resolve) => {
                    pitchImg = document.getElementById('pitchmap') || pitchImg;
                    if (!pitchImg) return resolve();
                    if (pitchImg.complete && pitchImg.naturalWidth && pitchImg.naturalHeight) {
                        requestAnimationFrame(() => setTimeout(resolve, 0));
                        return;
                    }
                    let done = false;
                    function finish() {
                        if (done) return;
                        done = true;
                        requestAnimationFrame(() => setTimeout(resolve, 0));
                    }
                    pitchImg.addEventListener('load', finish, { once: true });
                    setTimeout(finish, timeoutMs);
                });
            }

            ensurePitchOverlayExists();

            if (!matchId) {
                console.warn('[TAGGING] No match_id provided.');
                alert('No match_id provided.');
                return;
            }

            console.debug('[TAGGING] Loading match data for', matchId);

            try {
                const res = await fetch(`${window.API_GET_MATCH_DATA}/${encodeURIComponent(matchId)}`);
                if (!res.ok) throw new Error(`HTTP ${res.status}`);

                const payload = await res.json();
                console.debug('[TAGGING] API payload:', payload);

                if (!payload.success) {
                    alert('Error: ' + (payload.message || 'Failed to load match data.'));
                    return;
                }

                // Initialize config safely FIRST
                config = payload.config || {};

function validateAndCorrectPositions() {
    const direction = config.match.attacking_direction || 'left-to-right';
    const isRTL = direction === 'right-to-left';
    const logs = [];

    ['home', 'away'].forEach(side => {
        if (!config[side] || !Array.isArray(config[side].starting11)) return;
        const isHome = side === 'home';
        const expectedMin = isRTL ? (isHome ? 51 : 0) : (isHome ? 0 : 51);
        const expectedMax = isRTL ? (isHome ? 100 : 50) : (isHome ? 50 : 100);

        config[side].starting11.forEach(player => {
            let x = Number(player.x);
            if (Number.isNaN(x)) return;
            if (x < expectedMin || x > expectedMax) {
                const original_x = x;
                x = mirrorX(x);
                player.x = x;
                const log = `Player ${player.number || player.id} (${player.name || 'unnamed'}) mirrored from X=${original_x} → X=${x} due to ${side} team attacking ${isRTL ? 'R→L' : 'L→R'}`;
                logs.push(log);
                console.log(log);
            }
        });
    });

    if (logs.length > 0) {
        console.warn('Position adjustments made:', logs);
    } else {
        console.debug('All player X coordinates are valid; no adjustments needed.');
    }
}

validateAndCorrectPositions();

                positionsLocked = !!(config.match && config.match.positions_locked);

                try {
                    let cfgMode = (config && (config.mode || (config.match && config.match.mode))) || 'setup';
                    cfgMode = String(cfgMode || '').toLowerCase();
                    if (cfgMode !== 'setup' && cfgMode !== 'tagging') cfgMode = 'setup';
                    mode = cfgMode;
                    window.mode = mode; // keep global in sync for devtools
                } catch (e) {
                    console.debug('[TAGGING] apply mode from config error', e);
                }

                applyPositionsLockedUI();

                try {
                    config.match = config.match || {};
                    if (!config.match.attacking_direction) config.match.attacking_direction = 'left-to-right';
                    // do NOT set flipHorizontal here — applyDirection() will set it consistently.
                } catch (e) { console.debug('[TAGGING] config apply defaults error', e); }


                try {
                    ensurePitchOverlayExists();
                    await ensurePitchReady();
                    renderPlayers(); // initial render
                } catch (e) {
                    console.error('[TAGGING] renderPlayers() failed (non-fatal):', e);
                }

                console.debug('[TAGGING] Config loaded successfully:', config);
            } catch (err) {
                console.error('[TAGGING] Failed to fetch match data:', err);
                alert('Failed to load match data. Check console for details.');
            }

            // wire UI for attacking direction
            const switchBtn = document.getElementById('switch-attacking-direction-btn');
            const attackingHiddenInput = document.querySelector('input[name="attacking_direction"]');

            const rowContainer = switchBtn ? switchBtn.parentElement && switchBtn.parentElement.parentElement : null;
            if (!rowContainer || !attackingHiddenInput) {
                console.warn('[TAGGING] Attacking direction controls not wired due to missing elements');
            }

            const myCircle = document.getElementById('my-team-circle');
            const opponentCircle = document.getElementById('opponent-team-circle');
            const myWrapper = myCircle ? myCircle.parentElement : null;
            const oppWrapper = opponentCircle ? opponentCircle.parentElement : null;

            config = config || {};
            config.match = config.match || {};
            if (!config.match.attacking_direction) {
                config.match.attacking_direction = 'left-to-right';
            }

            function applyDirection(dir) {
                dir = String(dir || '').toLowerCase();
                if (dir === 'left-to-right' || dir === 'left to right' || dir === 'ltr') {
                    config.match.attacking_direction = 'left-to-right';
                } else {
                    config.match.attacking_direction = 'right-to-left';
                }
                if (attackingHiddenInput) attackingHiddenInput.value = config.match.attacking_direction;
            }

            function applyDomOrder() {
                if (!rowContainer || !myWrapper || !oppWrapper) return;
                const children = Array.from(rowContainer.children);
                if (!children.includes(myWrapper) || !children.includes(oppWrapper)) return;

                const myIndex = children.indexOf(myWrapper);
                const oppIndex = children.indexOf(oppWrapper);
                const leftWrapper = myIndex < oppIndex ? myWrapper : oppWrapper;
                const rightWrapper = leftWrapper === myWrapper ? oppWrapper : myWrapper;

                const dir = String(config.match.attacking_direction || '').toLowerCase();
                const wantMyOnLeft = (dir === 'left-to-right');

                if (wantMyOnLeft) {
                    if (leftWrapper !== myCircle.parentElement) {
                        leftWrapper.appendChild(myCircle);
                        rightWrapper.appendChild(opponentCircle);
                    }
                } else {
                    if (leftWrapper !== opponentCircle.parentElement) {
                        leftWrapper.appendChild(opponentCircle);
                        rightWrapper.appendChild(myCircle);
                    }
                }
            }

            function toggleAttackingDirectionAndUI() {
                const current = String(config.match.attacking_direction || '').toLowerCase();
                const next = (current === 'left-to-right') ? 'right-to-left' : 'left-to-right';
                applyDirection(next);
                applyDomOrder();

                validateAndCorrectPositions();

                // Try to reposition existing player elements instead of recreating them.
                ensurePitchOverlayExists();
                const pitchContainer = document.getElementById('pitch-overlay') || pitchOverlay;
                if (pitchContainer && playersMap && Object.keys(playersMap).length > 0) {
                    // Clear dataset to force fallback to updated config
                    Object.values(playersMap).forEach(entry => {
                        if (entry.el) {
                            delete entry.el.dataset.x;
                            delete entry.el.dataset.y;
                        }
                    });

                    // For each rendered player element:
                    Object.entries(playersMap).forEach(([pid, entry]) => {
                        try {
                            const el = entry && entry.el;
                            if (!el) return;

                            // Determine the element's current stored logical coords (fallback to config)
                            let logicalX = (el.dataset && typeof el.dataset.x !== 'undefined' && el.dataset.x !== '') ? Number(el.dataset.x) : NaN;
                            let logicalY = (el.dataset && typeof el.dataset.y !== 'undefined' && el.dataset.y !== '') ? Number(el.dataset.y) : NaN;

                            if (Number.isNaN(logicalX) || Number.isNaN(logicalY)) {
                                // fallback: search in config for this player id
                                ['home','away'].some(side => {
                                    if (!config[side] || !Array.isArray(config[side].starting11)) return false;
                                    const p = config[side].starting11.find(pp => String(pp.id) === String(pid));
                                    if (p) {
                                        logicalX = Number(p.x || 50);
                                        logicalY = Number(p.y || 50);
                                        return true;
                                    }
                                    return false;
                                });
                            }

                            // Re-position the element using placePlayerOnContainer()
                            // placePlayerOnContainer uses effectiveFlip() to compute the visual position.
                            placePlayerOnContainer(el, logicalX, logicalY, pitchContainer);
                        } catch (err) {
                            console.debug('[toggleAttackingDirectionAndUI] reposition / persist error', err);
                        }
                    });

                    // done — we updated DOM positions without changing logical coords
                    return;
                }

                // Fallback: if no players rendered yet, do a full render (safe)
                renderPlayers();
            }

            applyDirection(config.match.attacking_direction);
            requestAnimationFrame(applyDomOrder);

            if (switchBtn) {
                switchBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    toggleAttackingDirectionAndUI();
                });
            }

            // team color inputs - apply live preview
            const myColorInput = document.querySelector('input[name="my_team_color"]');
            const oppColorInput = document.querySelector('input[name="opponent_team_color"]');

            function contrastColor(hex) {
                if (!hex) return '#fff';
                const h = hex.replace('#', '');
                const r = parseInt(h.substring(0,2),16);
                const g = parseInt(h.substring(2,4),16);
                const b = parseInt(h.substring(4,6),16);
                const yiq = ((r*299)+(g*587)+(b*114))/1000;
                return yiq >= 128 ? '#000' : '#fff';
            }
            function applyMyColor(hex) {
                if (!hex) return;
                if (myCircle) {
                    myCircle.style.backgroundColor = hex;
                    myCircle.style.color = contrastColor(hex);
                    config.home.jersey_text_color = myCircle.style.color;
                }
            }
            function applyOppColor(hex) {
                if (!hex) return;
                if (opponentCircle) {
                    opponentCircle.style.backgroundColor = hex;
                    opponentCircle.style.color = contrastColor(hex);
                    config.away.jersey_text_color = opponentCircle.style.color;
                }
            }

            if (myColorInput && myColorInput.value) applyMyColor(myColorInput.value);
            if (oppColorInput && oppColorInput.value) applyOppColor(oppColorInput.value);

            if (myColorInput) {
                myColorInput.addEventListener('input', (e) => applyMyColor(e.target.value));
                myColorInput.addEventListener('change', (e) => {
                    config.home = config.home || {};
                    config.home.jersey_color = e.target.value;
                });
            }
            if (oppColorInput) {
                oppColorInput.addEventListener('input', (e) => applyOppColor(e.target.value));
                oppColorInput.addEventListener('change', (e) => {
                    config.away = config.away || {};
                    config.away.jersey_color = e.target.value;
                });
            }

            // Recalculate positions when window resizes
            window.addEventListener('resize', () => {
                if (window._renderPlayersResizeTimer) clearTimeout(window._renderPlayersResizeTimer);
                window._renderPlayersResizeTimer = setTimeout(() => {
                    ensurePitchOverlayExists();
                    renderPlayers();
                }, 80);
            });

            // ---------------- Player rendering & pointer drag handlers ----------------
            let playersMap = {}; // maps player.id -> { el, cleanup }

            // ---------- helpers for color contrast ----------
            function parseCssColorToRGB(c) {
                if (!c || typeof c !== 'string') return null;
                c = c.trim();
                // hex: #rrggbb or #rgb
                if (c[0] === '#') {
                    let hex = c.slice(1);
                    if (hex.length === 3) {
                        hex = hex.split('').map(ch => ch + ch).join('');
                    }
                    if (hex.length !== 6) return null;
                    const r = parseInt(hex.substring(0,2), 16);
                    const g = parseInt(hex.substring(2,4), 16);
                    const b = parseInt(hex.substring(4,6), 16);
                    if (Number.isNaN(r) || Number.isNaN(g) || Number.isNaN(b)) return null;
                    return { r, g, b };
                }

                // rgb(a) -> "rgb(255, 0, 10)" or "rgba(255,0,0,0.5)"
                const rgbMatch = c.match(/rgba?\s*\(\s*([0-9]+)[^\d]+([0-9]+)[^\d]+([0-9]+)/i);
                if (rgbMatch) {
                    return { r: parseInt(rgbMatch[1], 10), g: parseInt(rgbMatch[2], 10), b: parseInt(rgbMatch[3], 10) };
                }

                // color names - use a temporary element to compute
                try {
                    const temp = document.createElement('div');
                    temp.style.color = c;
                    document.body.appendChild(temp);
                    const cs = getComputedStyle(temp).color;
                    document.body.removeChild(temp);
                    const csMatch = cs.match(/rgba?\s*\(\s*([0-9]+)[^\d]+([0-9]+)[^\d]+([0-9]+)/i);
                    if (csMatch) return { r: parseInt(csMatch[1],10), g: parseInt(csMatch[2],10), b: parseInt(csMatch[3],10) };
                } catch (err) {
                    // ignore
                }
                return null;
            }

            function yiqFromRGB(rgb) {
                if (!rgb) return null;
                return ((rgb.r * 299) + (rgb.g * 587) + (rgb.b * 114)) / 1000;
            }

            // returns '#000' or '#fff' depending on which contrasts better with backgroundColor
            function pickBlackOrWhiteForBackground(bgColor) {
                const rgb = parseCssColorToRGB(bgColor);
                if (!rgb) return '#000';
                const yiq = yiqFromRGB(rgb);
                return (yiq >= 128) ? '#000' : '#fff';
            }

            // check simple contrast (using yiq difference). Returns true if contrast is "high enough"
            function hasReasonableContrast(fgColor, bgColor) {
                const fg = parseCssColorToRGB(fgColor);
                const bg = parseCssColorToRGB(bgColor);
                if (!fg || !bg) return false;
                const yiqF = yiqFromRGB(fg);
                const yiqB = yiqFromRGB(bg);
                return Math.abs(yiqF - yiqB) >= 128; // heuristic threshold
            }

            // ---------- updated renderPlayers ----------
            function renderPlayers() {

                try {
                    ensurePitchOverlayExists();
                    const pitchContainer = document.getElementById('pitch-overlay') || pitchOverlay;

                    // inside renderPlayers(), after ensurePitchOverlayExists() and obtaining pitchContainer:
                    const existingEls = Array.from(pitchContainer.querySelectorAll('.player-jersey'));
                    existingEls.forEach(el => el.remove());
                    if (!pitchContainer) {
                        console.debug('[renderPlayers] no pitch container available');
                        return;
                    }
                    if (!config) {
                        console.debug('[renderPlayers] no config');
                        return;
                    }

                    // clear existing
                    Object.values(playersMap).forEach(entry => {
                        try {
                            if (entry.cleanup) entry.cleanup();
                            if (entry.el && entry.el.remove) entry.el.remove();
                        } catch(e) {}
                    });
                    playersMap = {};

                    // ensure overlay has absolute positioning
                    try { pitchContainer.style.position = pitchContainer.style.position || 'absolute'; } catch(e){}

                    ['home','away'].forEach(side => {
                        if (!config[side] || !Array.isArray(config[side].starting11)) return;
                        const teamInfo = config[side];

                        // resolve jersey color and text color (may be hex, rgb or named)
                        const jerseyColor = (teamInfo.jersey_color) || '#888';
                        const jerseyTextColor = (teamInfo.jersey_text_color) || null;

                        teamInfo.starting11.forEach(player => {
                            const div = document.createElement('div');
                            div.className = 'player-jersey';

                            // set background color
                            div.style.background = jerseyColor;

                            // decide text color:
                            // 1) Prefer explicit jerseyTextColor if present and contrasts enough with the bg
                            // 2) Otherwise, pick black or white that contrasts with background
                            let finalTextColor = null;
                            if (jerseyTextColor) {
                                // use provided text color when it gives reasonable contrast; otherwise fall back to computed black/white
                                if (hasReasonableContrast(jerseyTextColor, jerseyColor)) {
                                    finalTextColor = jerseyTextColor;
                                } else {
                                    finalTextColor = pickBlackOrWhiteForBackground(jerseyColor);
                                }
                            } else {
                                finalTextColor = pickBlackOrWhiteForBackground(jerseyColor);
                            }

                            div.style.color = finalTextColor;

                            div.style.cursor = (mode === 'setup' && !positionsLocked) ? 'grab' : 'pointer';
                            div.innerText = (player.number !== undefined && player.number !== null) ? String(player.number) : '';
                            div.dataset.playerId = player.id;
                            div.dataset.side = side;
                            div.dataset.name = player.name || '';
                            div.dataset.number = player.number || '';

                            const x = (typeof player.x !== 'undefined') ? Number(player.x) : 50;
                            const y = (typeof player.y !== 'undefined') ? Number(player.y) : (side === 'home' ? 70 : 30);

                            // append first so measurements are reliable
                            pitchContainer.appendChild(div);

                            // place with measured sizes
                            placePlayerOnContainer(div, x, y, pitchContainer);

                            // attach draggable if allowed
                            let cleanup = null;
                            if (mode === 'setup' && !positionsLocked) {
                                cleanup = makeDraggable(div, player, pitchContainer);
                            } else {
                                if (typeof window.playerClickHandler === 'function') {
                                    div.addEventListener('click', window.playerClickHandler);
                                }
                                if (mode === 'tagging') div.style.display = 'none';
                            }

                            playersMap[player.id] = { el: div, cleanup: cleanup };
                        });
                    });
                } catch (err) {
                    console.error('[renderPlayers] unexpected error', err);
                }
            }

            function placePlayerOnContainer(div, x, y, pitchContainerEl) {
                try {
                    const rect = pitchContainerEl.getBoundingClientRect();
                    if (!rect.width || !rect.height) {
                        const imgRect = (pitchImg && pitchImg.getBoundingClientRect && pitchImg.getBoundingClientRect()) || rect;
                        if (!imgRect.width || !imgRect.height) {
                            div.style.left = '0px';
                            div.style.top = '0px';
                            div.dataset.x = x;
                            div.dataset.y = y;
                            return;
                        }
                    }
                    const visualX = x;
                    const cx = (visualX / 100) * rect.width;
                    const cy = (y / 100) * rect.height;
                    const offsetW = div.offsetWidth || 28;
                    const offsetH = div.offsetHeight || 28;
                    div.style.left = (cx - (offsetW / 2)) + 'px';
                    div.style.top = (cy - (offsetH / 2)) + 'px';
                    div.dataset.x = x;
                    div.dataset.y = y;
                    div.dataset.visualX = visualX;
                } catch (err) {
                    console.debug('[placePlayerOnContainer] error', err);
                }
            }

            // pointer-based draggable (works with mouse + touch + pen)
            function makeDraggable(el, player, pitchContainerEl) {
                let isDragging = false;
                let pointerId = null;
                let startPointer = { x: 0, y: 0 };
                let startPos = { left: 0, top: 0 };

                function onPointerDown(e) {
                    // only left mouse button or touch/pen
                    if (e.pointerType === 'mouse' && e.button !== 0) return;
                    if (tagStep && typeof tagStep === 'string' && tagStep.startsWith && tagStep.startsWith('awaiting')) return;

                    e.preventDefault();
                    el.setPointerCapture && el.setPointerCapture(e.pointerId);
                    pointerId = e.pointerId;
                    isDragging = true;
                    startPointer = { x: e.clientX, y: e.clientY };
                    startPos = { left: parseFloat(el.style.left) || 0, top: parseFloat(el.style.top) || 0 };
                    el.style.cursor = 'grabbing';
                    el._isDragging = true;
                }

                function onPointerMove(e) {
                    if (!isDragging || e.pointerId !== pointerId) return;
                    const dx = e.clientX - startPointer.x;
                    const dy = e.clientY - startPointer.y;
                    const newLeft = startPos.left + dx;
                    const newTop = startPos.top + dy;

                    // Bound inside pitch container
                    const rect = pitchContainerEl.getBoundingClientRect();
                    const maxLeft = Math.max(0, rect.width - el.offsetWidth);
                    const maxTop = Math.max(0, rect.height - el.offsetHeight);
                    const clampedLeft = Math.max(0, Math.min(maxLeft, newLeft));
                    const clampedTop = Math.max(0, Math.min(maxTop, newTop));

                    el.style.left = clampedLeft + 'px';
                    el.style.top = clampedTop + 'px';
                }

                function onPointerUp(e) {
                    if (!isDragging || e.pointerId !== pointerId) return;
                    isDragging = false;
                    el.releasePointerCapture && el.releasePointerCapture(e.pointerId);
                    el.style.cursor = 'grab';
                    el._isDragging = false;
                    // compute center -> percent coords
                    try {
                        const rect = pitchContainerEl.getBoundingClientRect();
                        const cx = (parseFloat(el.style.left) || 0) + (el.offsetWidth / 2);
                        const cy = (parseFloat(el.style.top) || 0) + (el.offsetHeight / 2);
                        const pxVisual = Math.max(0, Math.min(100, (cx / rect.width) * 100));
                        const py = Math.max(0, Math.min(100, (cy / rect.height) * 100));
                        const pxLogical = pxVisual;
                        updatePlayerPositionInConfig(player.id, pxLogical, py);
                        el.dataset.x = pxLogical;
                        el.dataset.y = py;
                        // update player object's stored coords too
                        player.x = Number(pxLogical);
                        player.y = Number(py);
                    } catch (err) {
                        console.debug('[makeDraggable.onPointerUp] error', err);
                    }
                }

                el.addEventListener('pointerdown', onPointerDown);
                window.addEventListener('pointermove', onPointerMove);
                window.addEventListener('pointerup', onPointerUp);
                window.addEventListener('pointercancel', onPointerUp);

                // cleanup
                return function cleanup() {
                    try {
                        el.removeEventListener('pointerdown', onPointerDown);
                        window.removeEventListener('pointermove', onPointerMove);
                        window.removeEventListener('pointerup', onPointerUp);
                        window.removeEventListener('pointercancel', onPointerUp);
                    } catch (err) {}
                    el._isDragging = false;
                };
            }

            function updatePlayerPositionInConfig(pid, x, y) {
                try {
                    ['home','away'].forEach(side => {
                        if (!config[side] || !Array.isArray(config[side].starting11)) return;
                        config[side].starting11.forEach(p => {
                            if (p.id === pid) {
                                p.x = Number(x);
                                p.y = Number(y);
                            }
                        });
                    });
                } catch (err) {
                    console.debug('[updatePlayerPositionInConfig] error', err);
                }
            }

            // expose public
            window.renderPlayers = renderPlayers;
            window.getPlayersMap = function() { return playersMap; };

            /* ----------------- disable UI when positions are locked ----------------- */
            function applyPositionsLockedUI() {
            try {
                // derive current mode + lock state directly from config
                const matchCfg = (config && config.match) || {};
                const locked = !!matchCfg.positions_locked;
                const currentMode = (matchCfg.mode || config.mode || 'setup').toLowerCase();

                // elements to affect
                const myColor = document.getElementById('my-team-color');
                const oppColor = document.getElementById('opponent-team-color');
                const switchBtn = document.getElementById('switch-attacking-direction-btn');
                const submitBtn = document.getElementById('submit-form');

                // assign color values if config is present
                if (config.home && myColor) myColor.value = config.home.jersey_color || '';
                if (config.away && oppColor) oppColor.value = config.away.jersey_color || '';

                const setDisabled = (el, shouldDisable) => {
                    if (!el) return;
                    if (shouldDisable) {
                        el.setAttribute('disabled', 'true');
                        el.setAttribute('aria-disabled', 'true');
                        el.classList.add('opacity-50', 'cursor-not-allowed');
                        el.style.pointerEvents = 'none';
                        if (['BUTTON', 'INPUT', 'SELECT', 'TEXTAREA'].includes(el.tagName)) el.tabIndex = -1;
                    } else {
                        el.removeAttribute('disabled');
                        el.setAttribute('aria-disabled', 'false');
                        el.classList.remove('opacity-50', 'cursor-not-allowed');
                        el.style.pointerEvents = '';
                        if (['BUTTON', 'INPUT', 'SELECT', 'TEXTAREA'].includes(el.tagName)) el.tabIndex = 0;
                    }
                };

                // disable UI elements if locked
                setDisabled(myColor, locked);
                setDisabled(oppColor, locked);
                setDisabled(switchBtn, locked);
                setDisabled(submitBtn, locked);

                // Handle player icons / dragging states
                if (window.getPlayersMap) {
                    const pmap = window.getPlayersMap();
                    Object.values(pmap || {}).forEach(entry => {
                        if (!entry || !entry.el) return;
                        if (locked) {
                            // remove dragging handlers if present
                            if (entry.cleanup && typeof entry.cleanup === 'function') {
                                try { entry.cleanup(); } catch (e) {}
                                entry.cleanup = null;
                            }
                            entry.el.style.cursor = 'default';
                            entry.el.style.pointerEvents = 'none';
                        } else {
                            // unlock: restore pointer events + cursor depending on mode
                            entry.el.style.pointerEvents = '';
                            entry.el.style.cursor = (currentMode === 'setup') ? 'grab' : 'pointer';
                        }
                    });
                }

                // Optionally log for debugging
                console.debug(`[applyPositionsLockedUI] mode=${currentMode}, locked=${locked}`);
            } catch (err) {
                console.debug('[applyPositionsLockedUI] error', err);
            }
        }
        })();
    });

    async function fetchAndShowPlayers(url, targetElId) {
        const el = document.getElementById(targetElId);
        if (!el) return;
        el.innerText = 'Loading...';

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({})
            });

            const data = await res.json().catch(() => null);

            if (!res.ok) {
                console.error('Server error for', targetElId, data);
                el.innerText = '—';
                return;
            }

            if (!data || data.success !== true) {
                console.error('Bad response for', targetElId, data);
                el.innerText = '—';
                return;
            }

            const jerseyNums = (data.players || []).map(p => {
                if (!p) return null;
                if (typeof p === 'object') {
                    return (p.jersey ?? p.jersey_number ?? p.number ?? null);
                }
                return p;
            }).filter(n => n !== null && n !== undefined && n !== '');

            el.innerText = jerseyNums.length ? jerseyNums.join(', ') : '—';
        } catch (err) {
            console.error('Fetch error for', targetElId, err);
            el.innerText = '—';
        }
    }

    async function saveConfigurationsToBackend() {
        if (!window.TAGGING_MATCH_ID) {
            alert('No match id available to save.');
            return false;
        }
        // Defensive: ensure config exists
        if (typeof config === 'undefined' || config === null) {
            alert('No configuration available to save.');
            return false;
        }

        try {
            const payload = {
                match_id: window.TAGGING_MATCH_ID,
                config: config
            };

            config.match = config.match || {};
            config.mode = mode;

            const res = await fetch('<?php echo site_url('match/metadatacontroller/update_match_config_json'); ?>' , {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload),
                credentials: 'same-origin' // include cookies/session if needed
            });

            // Try to read JSON safely
            const data = await res.json().catch(() => null);

            if (!res.ok) {
                const msg = (data && data.message) ? data.message : `HTTP ${res.status}`;
                console.error('[saveConfigurationsToBackend] server error', res.status, data);
                alert('Failed to save configurations: ' + msg);
                return false;
            }

            // Expect backend to return { success: true } on success
            if (!data || data.success !== true) {
                const msg = (data && data.message) ? data.message : 'Unexpected response from server.';
                console.error('[saveConfigurationsToBackend] bad response', data);
                alert('Failed to save configurations: ' + msg);
                return false;
            }

            // success
            console.debug('[saveConfigurationsToBackend] saved successfully', data);
            // optional: show friendly UI feedback
            alert('Configurations saved successfully.');
            return true;
        } catch (err) {
            console.error('[saveConfigurationsToBackend] exception', err);
            alert('Failed to save configurations. Check console for details.');
            return false;
        }
    }

    // --- Hook the HTML form submit to use the API save function ---
    (function attachSaveHandler() {
        const form = document.getElementById('setup-config-form');
        if (!form) return;
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            try {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.dataset.prevText = submitBtn.innerText;
                    submitBtn.innerText = 'Saving...';
                }
                const ok = await saveConfigurationsToBackend();
                if (ok) {
                    // keep config in page, or reload fresh config if desired:
                    // location.reload();
                }
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerText = submitBtn.dataset.prevText || 'Save Configurations';
                }
            }
        });
    })();

    document.addEventListener('DOMContentLoaded', function() {
        fetchAndShowPlayers('<?php echo site_url("studio/mediacontroller/get_match_my_team_players"); ?>', 'all-my-team-player-jerseys-list');
        fetchAndShowPlayers('<?php echo site_url("studio/mediacontroller/get_match_opponent_team_players"); ?>', 'all-opponent-team-player-jerseys-list');
    });
</script>

<script src="<?php echo base_url(); ?>assets/js/videoMetadataInputHandler.js?<?php echo time(); ?>"></script>
<script src="<?php echo base_url(); ?>assets/js/teamRosterModalHandler.js?<?php echo time(); ?>"></script>