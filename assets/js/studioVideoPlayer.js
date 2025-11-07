document.addEventListener('DOMContentLoaded', () => {

    // --- 1. SELECT ELEMENTS ---
    const videoContainer = document.getElementById('video-player-container');
    const video = document.getElementById('video-player');

    // Time & Progress
    const currentTimeEl = document.getElementById('current-video-time-progress');
    const fullTimeEl = document.getElementById('full-video-time');
    const progressBar = document.getElementById('progress-bar');
    const bufferedBar = document.getElementById('buffered-bar');
    const progressBarContainer = document.getElementById('progress-bar-container');

    // Playback Buttons
    const playBtn = document.getElementById('play-btn');
    const pauseBtn = document.getElementById('pause-btn');
    const rewindBtn = document.getElementById('rewind-5s-btn'); 
    const forwardBtn = document.getElementById('forward-5s-btn');
    const reversePlayBtn = document.getElementById('reverse-play-btn');

    // Zoom Controls
    const zoomDropdownBtn = document.getElementById('zoom-percent-dropdown');
    const zoomDropdownText = zoomDropdownBtn.querySelector('span');
    const zoomOptionsContainer = document.getElementById('zoom-options');

    // --- 2. STATE VARIABLES ---
    const zoomLevels = [0.25, 0.5, 0.75, 1.0];
    let currentZoom = 1.0;
    let reversePlayInterval = null;
    let timeDisplayInterval = null; 

    // --- 3. HELPER FUNCTIONS ---
    
    /**
     * Formats time in seconds to HH:MM:SS:FF (approximating frames).
     * @param {number} timeInSeconds - The time in seconds.
     * @param {number} [frameRate=30] - The frame rate to approximate.
     */
    function formatTime(timeInSeconds, frameRate = 30) {
        if (isNaN(timeInSeconds) || timeInSeconds < 0) {
            return '00:00:00:00';
        }
        const totalSeconds = Math.floor(timeInSeconds);
        const hours = Math.floor(totalSeconds / 3600).toString().padStart(2, '0');
        const minutes = Math.floor((totalSeconds % 3600) / 60).toString().padStart(2, '0');
        const seconds = (totalSeconds % 60).toString().padStart(2, '0');
        
        // Approximate frames from the decimal part
        const frames = Math.floor((timeInSeconds - totalSeconds) * frameRate).toString().padStart(2, '0');
        
        return `${hours}:${minutes}:${seconds}:${frames}`;
    }

    /**
     * Sets the zoom level on the video player.
     * @param {number} scale - The zoom scale (e.g., 1.0, 0.75).
     */
    function setZoom(scale) {
        // Clamp scale between 25% and 100%
        currentZoom = Math.max(0.25, Math.min(scale, 1.0)); 
        video.style.transform = `scale(${currentZoom})`;
        zoomDropdownText.textContent = `${Math.round(currentZoom * 100)}%`;
        
        // Find the closest snap-level for the state
        currentZoom = zoomLevels.reduce((prev, curr) => {
            return (Math.abs(curr - scale) < Math.abs(prev - scale) ? curr : prev);
        });
    }

    /**
     * Stops the reverse play interval if it's active.
     */
    function stopReversePlay() {
        if (reversePlayInterval) {
            clearInterval(reversePlayInterval);
            reversePlayInterval = null;
        }
    }

    /**
     * Stops the custom time display interval.
     */
    function stopTimeDisplay() {
        if (timeDisplayInterval) {
            clearInterval(timeDisplayInterval);
            timeDisplayInterval = null;
        }
    }

    /**
     * Updates the time display and progress bar.
     */
    function updateUI() {
        // Update current time display (using the most recent video.currentTime)
        currentTimeEl.textContent = formatTime(video.currentTime);
        
        // Update progress bar width
        const progressPercent = (video.currentTime / video.duration) * 100;
        progressBar.style.width = `${progressPercent}%`;
    }
    
    // --- 4. EVENT LISTENERS ---

    // -- Playback Controls --
    playBtn.addEventListener('click', () => {
        // FIX 1: Only stop custom intervals/restart playback if the video is paused or in reverse mode.
        // If video.paused is false, it means the video is currently playing forward natively.
        if (video.paused || reversePlayInterval) { 
            stopReversePlay();
            stopTimeDisplay();
            
            // Use native playback
            video.playbackRate = 1; 
            video.play();
            // The 'play' event listener will handle starting timeDisplayInterval
        }
        // If the video is already playing forward, clicking 'play' does nothing, 
        // which prevents stopping and restarting the time display.
    });

    pauseBtn.addEventListener('click', () => {
        // 1. Stop custom intervals
        stopReversePlay(); 
        stopTimeDisplay();
        
        // 2. Pause native video
        video.pause();
        // Icons are handled by the 'pause' event listener below
    });

    rewindBtn.addEventListener('click', () => {
        // NOTE: Changed to 10 seconds to match the image icon context (10-sec-back.svg)
        video.currentTime = Math.max(0, video.currentTime - 10); 
        updateUI(); 
    });
    
    forwardBtn.addEventListener('click', () => {
        // NOTE: Changed to 10 seconds to match the image icon context (10-sec-forward.svg)
        video.currentTime = Math.min(video.duration, video.currentTime + 10); 
        updateUI(); 
    });
    
    reversePlayBtn.addEventListener('click', () => {
        if (reversePlayInterval) {
            // If already reversing, stop it (acts as a toggle to pause)

        } else {
            // Start reverse play
            video.pause();
            // Ensure no other playback mode is active
            
            // FIX 2: Use a smaller time step and a faster interval for smoother time-setting.
            // The value 1000ms (1 second) was causing the stutter/pause.
            
            // You are using 30FPS approximation in formatTime, so let's aim for that.
            const frameRate = 30;
            const frameDuration = 1 / frameRate; // Time in seconds to step back (1/30)
            const intervalTime = Math.floor(1000 / frameRate); // Interval in ms (approx 33ms)
            
            reversePlayInterval = setInterval(() => {
                // Check if we're near the start of the video
                if (video.currentTime <= frameDuration) { 
                    video.currentTime = 0;
                    stopReversePlay(); 
                    video.pause(); // Ensure pause state is set at the end
                } else {
                    // Step back exactly one frame duration
                    // Decrementing directly on currentTime is the smoothest method
                    video.currentTime -= frameDuration; 
                }
                updateUI(); // Continuously update UI for smooth progress bar/time
            }, intervalTime);
        }
    });

    // -- Video Player Events --
    video.addEventListener('loadedmetadata', () => {
        fullTimeEl.textContent = formatTime(video.duration);
        updateUI(); 
    });

    video.addEventListener('play', () => {
        // Start the smooth time display loop only when native video starts
        // And only if we are NOT in a reverse play interval
        if (!reversePlayInterval) {
             timeDisplayInterval = setInterval(updateUI, 33); // Run updateUI every 33ms (approx 30FPS)
        }
    });

    video.addEventListener('pause', () => {
        // Only stop the time display if we are not in reverse play mode
        // Reverse play handles its own time display update via its interval.
        if (!reversePlayInterval) {
            stopTimeDisplay(); 
        }
    });

    video.addEventListener('ended', () => {
        stopTimeDisplay(); 
    });

    // ... (rest of the code is unchanged) ...
    // Only use the native 'timeupdate' event for the buffered bar (which is slow/infrequent anyway)
    video.addEventListener('timeupdate', () => {
        // Update buffered bar
        if (video.buffered.length > 0) {
            // Find the end of the last buffered range
            const bufferedEnd = video.buffered.end(video.buffered.length - 1);
            const bufferedPercent = (bufferedEnd / video.duration) * 100;
            bufferedBar.style.width = `${bufferedPercent}%`;
        }
    });

    // -- Progress Bar (Scrubbing) --
    progressBarContainer.addEventListener('click', (e) => {
        stopReversePlay(); // Stop reverse play if scrubbing
        const rect = progressBarContainer.getBoundingClientRect();
        const clickX = e.clientX - rect.left;
        const width = rect.width;
        const clickPercent = clickX / width;
        
        video.currentTime = clickPercent * video.duration;
        updateUI(); // Manually update the UI after scrubbing
    });

    // Zoom Controls (Dropdown)
    zoomDropdownBtn.addEventListener('click', (e) => {
        console.log('Dropdown clicked, toggling hidden class');
        zoomOptionsContainer.classList.toggle('hidden');
        e.stopPropagation(); // Prevent click from bubbling to document
    });

    zoomOptionsContainer.addEventListener('click', (e) => {
        if (e.target.dataset.zoom) {
            const newZoom = parseFloat(e.target.dataset.zoom);
            setZoom(newZoom);
            zoomOptionsContainer.classList.add('hidden');
        }
    });

    // Close dropdown only when clicking outside the dropdown button and options
    document.addEventListener('click', (e) => {
        if (!zoomDropdownBtn.contains(e.target) && !zoomOptionsContainer.contains(e.target)) {
            zoomOptionsContainer.classList.add('hidden');
        }
    });

    // -- Zoom Controls (Scroll) --
    videoContainer.addEventListener('wheel', (e) => {
        e.preventDefault(); // Prevent page from scrolling
        
        let currentIndex = zoomLevels.indexOf(currentZoom);
        if (currentIndex === -1) currentIndex = zoomLevels.length - 1; // Default to 100%

        if (e.deltaY < 0) {
            // Scroll Up (Zoom In) - Move to the next *higher* index
            currentIndex = Math.min(currentIndex + 1, zoomLevels.length - 1);
        } else {
            // Scroll Down (Zoom Out) - Move to the next *lower* index
            currentIndex = Math.max(currentIndex - 1, 0);
        }

        setZoom(zoomLevels[currentIndex]);
    });
});