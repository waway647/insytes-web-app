<?php $match_video_exist = $this->session->userdata('tagging_video_url'); ?>

<div class="flex h-full w-full">
    <div class="flex flex-col w-full h-full">
        <!-- Custom Video Player with Custom Controls -->
        <div class="flex flex-col w-full h-[70%] text-xs">
    
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
              
              <div class="flex px-6 justify-center items-center border-r-1 border-black">
                  <span id="current-video-time-progress" class="text-white">00:00:00:00</span>
              </div>
              
              <div class="flex w-full">
                  <div id="team-score-container" class="flex w-full justify-center items-center">
                      <span id="home-name-video" class="flex w-full justify-end px-4 text-white"></span>
                      <span id="home-score-video" class="flex px-2 text-white border-x-1 border-black"></span>
                  </div>
                  <div class="flex px-2 justify-center items-center">
                      <span id="match-minute-video" class="px-6 text-white"></span>
                  </div>
                  <div id="away-score-container" class="flex w-full justify-center items-center">
                      <span id="away-score-video" class="flex px-2 text-white border-x-1 border-black"></span>
                      <span id="away-name-video" class="flex w-full justify-baseline px-4 text-white"></span>
                  </div>
              </div>
              
              <div class="flex px-6 justify-center items-center border-l-1 border-black">
                  <span id="full-video-time" class="text-white">00:00:00:00</span>
              </div>
          </div>

          <div class="flex flex-col w-full h-full border-1 border-black">
            <div id="video-player-container" class="flex items-center w-full h-full overflow-hidden border-b-1 border-black">
                <video id="video-player" class="w-full object-fill transform-origin-top-left">
                    <source src="<?php echo base_url($match_video_exist); ?>" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>

            <div id="custom-controls" class="flex flex-col w-full h-auto gap-3 p-3 bg-[#1D1D1D]">
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

        <!-- Tagging Timeline Display -->
        

        <div class="flex flex-col w-full h-[30%] justify-end text-white px-4 border-r-1 border-black">
            <h1 class="p-4 font-bold">Tags</h1>

            <div id="event-card" class="w-full p-2 bg-gray-700 rounded-2xl text-xs">
                <p>ID: <span id="id"></span></p>
                <p>Match: <span id="match"></span></p>
                <p>Team: <span id="team"></span></p>
                <p>Event: <span id="event"></span></p>
                <p>Player: <span id="player"></span></p>
                <p>Match Time: <span id="match-time"></span></p>
                <p>Half Period: <span id="half-period"></span></p>
                <p>Video Timestamp: <span id="video-timestamp"></span></p>
                <p>Origin X: <span id="origin-x"></span></p>
                <p>Origin Y: <span id="origin-y"></span></p>
                <p>Outcome: <span id="outcome"></span></p>
            </div>
        </div>
    </div>

    <div class="bg-[#131313]">
        <div class="mt-3 flex gap-2 items-center">
          <div id="info-text" class="px-3 py-1 bg-gray-700 text-white rounded">Mode: Setup</div>
          <button id="cancel-tag-btn" class="px-3 py-1 bg-red-600 text-white rounded hidden">Cancel Tag</button>
          <button id="save-tag-btn" class="px-3 py-1 bg-green-600 text-white rounded hidden">Save Tag</button>
        </div>

        <div class="flex w-100 m-10">
            <img id="pitchmap" class="w-100" src="<?php echo base_url('assets/images/pitchmap/Football_field.svg'); ?>" alt="">
        </div>

        <div class="flex flex-col gap-2 px-6 font-medium text-white">
            <h3>Type</h3>
            <div id="pass-type-buttons" class="flex flex-row w-full">
              <button id="short-pass-btn" class="w-full py-2 bg-gray-800 cursor-pointer text-sm hover:bg-gray-950">Short</button>
              <button id="long-pass-btn" class="w-full py-2 bg-gray-800 cursor-pointer text-sm hover:bg-gray-950">Long</button>
              <button id="through-ball-btn" class="w-full py-2 bg-gray-800 cursor-pointer text-sm hover:bg-gray-950">Through Ball</button>
              <button id="cross-btn" class="w-full py-2 bg-gray-800 cursor-pointer text-sm hover:bg-gray-950">Cross</button>
            </div>

            <div id="shot-type-buttons" class="flex flex-row w-full">
              <button id="header-shot-btn" class="w-full py-2 bg-gray-800 cursor-pointer text-sm hover:bg-gray-950">Header</button>
              <button id="right-foot-shot-btn" class="w-full py-2 bg-gray-800 cursor-pointer text-sm hover:bg-gray-950">Right Foot</button>
              <button id="left-foot-shot-btn" class="w-full py-2 bg-gray-800 cursor-pointer text-sm hover:bg-gray-950">Left Foot</button>
            </div>

            <div id="duel-type-buttons" class="flex flex-row w-full">
              <button id="aerial-duel-btn" class="w-full py-2 bg-gray-800 cursor-pointer text-sm hover:bg-gray-950">Aerial</button>
              <button id="ground-duel-btn" class="w-full py-2 bg-gray-800 cursor-pointer text-sm hover:bg-gray-950">Ground</button>
            </div>

            <h3>Outcome</h3>
            <div id="pass-outcome-buttons" class="flex flex-row w-full">
              <button id="pass-successful-btn" class="w-full py-2 bg-gray-800 cursor-pointer text-sm hover:bg-gray-950">Successful</button>
              <button id="pass-unsuccessful-btn" class="w-full py-2 bg-gray-800 cursor-pointer text-sm hover:bg-gray-950">Unsuccessful</button>
              <button id="pass-intercepted-btn" class="w-full py-2 bg-gray-800 cursor-pointer text-sm hover:bg-gray-950">Intercepted</button>
            </div>
            <div id="shot-outcome-buttons" class="flex flex-row w-full">
              <button id="shot-goal-btn" class="w-full py-2 bg-gray-800 cursor-pointer text-sm hover:bg-gray-950">Goal</button>
              <button id="shot-on-target-btn" class="w-full py-2 bg-gray-800 cursor-pointer text-sm hover:bg-gray-950">On Target</button>
              <button id="shot-off-target-btn" class="w-full py-2 bg-gray-800 cursor-pointer text-sm hover:bg-gray-950">Off Target</button>
              <button id="shot-blocked-btn" class="w-full py-2 bg-gray-800 cursor-pointer text-sm hover:bg-gray-950">Blocked</button>
            </div>
            <div id="duel-outcome-buttons" class="flex flex-row w-full">
              <button id="duel-successful-btn" class="w-full py-2 bg-gray-800 cursor-pointer text-sm hover:bg-gray-950">Successful</button>
              <button id="duel-unsuccessful-btn" class="w-full py-2 bg-gray-800 cursor-pointer text-sm hover:bg-gray-950">Unsuccessful</button>
            </div>
            <div id="penalty-outcome-buttons" class="flex flex-row w-full">
              <button id="penalty-goal-btn" class="w-full py-2 bg-gray-800 cursor-pointer text-sm hover:bg-gray-950">Goal</button>
              <button id="penalty-saved-btn" class="w-full py-2 bg-gray-800 cursor-pointer text-sm hover:bg-gray-950">Saved</button>
              <button id="penalty-off-target-btn" class="w-full py-2 bg-gray-800 cursor-pointer text-sm hover:bg-gray-950">Off Target</button>
            </div>
            <div id="reusable-outcome-buttons" class="flex flex-row w-full">
              <button id="successful-btn" class="w-full py-2 bg-gray-800 cursor-pointer text-sm hover:bg-gray-950">Successful</button>
              <button id="unsuccessful-btn" class="w-full py-2 bg-gray-800 cursor-pointer text-sm hover:bg-gray-950">Unsuccessful</button>
            </div>

            <h3>Key Pass</h3>
            <div id="key-pass-buttons" class="flex flex-row w-full">
              <button id="key-pass-yes-btn" class="w-full py-2 bg-gray-800 cursor-pointer text-sm hover:bg-gray-950">Yes</button>
              <button id="key-pass-no-btn" class="w-full py-2 bg-gray-800 cursor-pointer text-sm hover:bg-gray-950">No</button>
            </div>
        </div>
    </div>

    <div class="flex flex-col w-[70%] h-full bg-[#1C1C1C] items-center">
        <div class="flex w-full">
            <button id="substitution-btn" class="ctr-btn w-full py-2 bg-indigo-950 text-white cursor-pointer hover:bg-gray-700">Substitutions</button>
            <button id="half-time-btn" class="ctr-btn w-full py-2 bg-gray-600 cursor-pointer hover:bg-gray-700">End 1st Half</button>
            <button id="kick-off-2nd-half-btn" class="ctr-btn w-full py-2 bg-gray-600 cursor-pointer hover:bg-gray-700">Start 2nd Half</button>
            <button id="full-time-btn" class="ctr-btn w-full py-2 bg-gray-600 cursor-pointer hover:bg-gray-700">End 2nd Half</button>
        </div>

        <div class="flex w-full mt-4 justify-center items-center">
            <h2 class="text-white text-3xl font-bold"><span id="match-minute">00:00</span></h2>
        </div>

        <div class="flex w-full mt-4 justify-center items-center space-x-6">
            <div class="flex flex-col items-center">
                <h2 id="home-name" class="text-white text-xl font-bold"></h2>
                <span id="home-score" class="text-white text-3xl font-bold">0</span>
            </div>
            <div class="text-white text-3xl font-bold"> - </div>
            <div class="flex flex-col items-center">
                <h2 id="away-name" class="text-white text-xl font-bold"></h2>
                <span id="away-score" class="text-white text-3xl font-bold">0</span>
            </div>
        </div>

        <div id="substitutions-panel" class="flex flex-col mt-4 w-full">
          <!-- substitutions team buttons -->
          <div id="substitutions-btn" class="flex w-fit mb-4">
              <button id="home-team-substitution-btn" class="ctr-btn w-full px-4 py-1 bg-blue-600 text-white cursor-pointer hover:bg-blue-700">Home</button>
              <button id="away-team-substitution-btn" class="ctr-btn w-full px-4 py-1 bg-green-600 text-white cursor-pointer hover:bg-green-700">Away</button>
          </div>

          <!-- players-on-pitch-display reusable component for 11 players currently playing -->
          <div id="players-on-pitch-container" class="w-full border-5 border-gray-700 relative">
            <img id="vertical-pitch" class="object-fill" src="<?php echo base_url('assets/images/pitchmap/Football_field.svg'); ?>" alt="">
            <!-- dynamic 11 players icons will be appended here -->
          </div>

          <!-- reusable component ui display for players on the bench -->
          <div id="players-on-bench-display" class="w-full h-50 border-5 border-gray-700 relative">
            <h1 class="text-white">Bench Players</h1>
            
            <div id="bench-players-container" w-full h-full>
              <!-- dynamic all bench players icons in 2 rows will be appended here -->

            </div>
          </div>

          <div class="w-full h-10 border-5 border-gray-700 relative">
            <button id="cancel-substitution-btn" class="px-3 py-1 bg-red-600 text-white rounded cursor-pointer hover:bg-red-700">Cancel Substitutions</button>
            <button id="save-substitution-btn" class="px-3 py-1 bg-green-600 text-white rounded cursor-pointer hover:bg-green-700">Save Substitutions</button>
          </div>
        </div>

        <div id="kick-off-buttons" class="flex w-full mt-4 hidden">
            <button id="kick-off-home-btn" class="ctr-btn w-full py-2 bg-gray-600 cursor-pointer hover:bg-gray-700">Kickoff</button>
            <button id="kick-off-away-btn" class="ctr-btn w-full py-2 bg-gray-600 cursor-pointer hover:bg-gray-700">Kickoff</button>
        </div>

        <div id="tagging-events-button" class="flex flex-col w-full h-full justify-between">
          <div class="mt-4 flex h-[60%]">
            <div class="flex flex-col w-full h-full border-5 border-gray-700">
                <div class="flex w-full h-[50%] border-b border-gray-700">
                    <div class="flex flex-col w-full h-full border-r border-gray-700">
                        <button id="tag-pass-btn" data-event="Pass" class="ctr-btn w-full h-full py-1 bg-green-500 border-b border-gray-700 cursor-pointer hover:bg-green-600">Pass</button>
                        <button id="tag-shot-btn" data-event="Shot" class="ctr-btn w-full h-full py-1 bg-red-500 border-b border-gray-700 cursor-pointer hover:bg-red-600">Shot</button>
                        <button id="tag-dribble-btn" data-event="Dribble" class="ctr-btn w-full h-full py-1 bg-yellow-500 cursor-pointer hover:bg-yellow-600">Dribble</button>
                    </div>

                    <div class="flex flex-col w-full h-full">
                        <div class="flex flex-col w-full h-full border-b border-gray-700">
                            <button id="tag-interception-btn" data-event="Interception" class="ctr-btn w-full h-full py-1 bg-blue-500 border-b border-gray-700 cursor-pointer hover:bg-blue-600">Interception</button>
                            <button id="tag-recovery-btn" data-event="Recovery" class="ctr-btn w-full h-full py-1 bg-purple-500 cursor-pointer hover:bg-purple-600">Recovery</button>
                        </div>

                        <div class="flex flex-col w-full h-full">
                            <button id="tag-tackle-btn" data-event="Tackle" class="ctr-btn w-full h-full py-1 bg-cyan-600 border-b border-gray-700 cursor-pointer hover:bg-cyan-700">Tackle</button>
                            <button id="tag-duel-btn" data-event="Duel" class="ctr-btn w-full h-full py-1 bg-indigo-600 border-b border-gray-700 cursor-pointer hover:bg-indigo-700">Duel</button>
                            <button id="tag-clearance-btn" data-event="Clearance" class="ctr-btn w-full h-full py-1 bg-teal-600 cursor-pointer hover:bg-teal-700">Clearance</button>
                        </div>
                    </div>
                </div>

                <div class="flex w-full h-[25%] border-b border-gray-700">
                    <div class="flex w-[75%] h-full border-r border-gray-700">
                        <button id="tag-out-off-play-btn" data-event="Out of Play" class="ctr-btn w-full h-full py-1 bg-stone-600 border-r border-gray-700 cursor-pointer hover:bg-stone-800">Out of Play</button>
                        <button id="tag-possession-loss-btn" data-event="Possession Loss" class="ctr-btn w-full h-full py-1 bg-stone-600 border-r border-gray-700 cursor-pointer hover:bg-stone-800">Possession Loss</button>
                        <button id="tag-offside-btn" data-event="Offside" class="ctr-btn w-full h-full py-1 bg-stone-600 cursor-pointer border-r border-gray-700 hover:bg-stone-800">Offside</button>
                    </div>

                    <div class="flex flex-col w-[25%] h-full">
                        <button id="tag-foul-btn" data-event="Foul" class="ctr-btn w-full h-full py-1 bg-red-400 border-b border-gray-700 cursor-pointer hover:bg-red-500">Foul</button>
                        <button id="tag-yellow-card-btn" data-event="Yellow Card" class="ctr-btn w-full h-full py-1 bg-yellow-600 border-b border-gray-700 cursor-pointer hover:bg-yellow-700">Yellow Card</button>
                        <button id="tag-red-card-btn" data-event="Red Card" class="ctr-btn w-full h-full py-1 bg-red-800 cursor-pointer hover:bg-red-900">Red Card</button>
                    </div>
                </div>

                <div class="flex w-full h-[25%]">
                    <button id="tag-throw-in-btn" data-event="Throw In" class="ctr-btn w-full h-full py-1 bg-gray-400 border-r border-gray-700 cursor-pointer hover:bg-gray-500">Throw In</button>
                    <button id="tag-corner-btn" data-event="Corner" class="ctr-btn w-full h-full py-1 bg-gray-400 border-r border-gray-700 cursor-pointer hover:bg-gray-500">Corner</button>
                    <button id="tag-free-kick-btn" data-event="Free Kick" class="ctr-btn w-full h-full py-1 bg-gray-400 border-r border-gray-700 cursor-pointer hover:bg-gray-500">Free Kick</button>
                    <button id="tag-penalty-btn" data-event="Penalty" class="ctr-btn w-full h-full py-1 bg-gray-400 cursor-pointer hover:bg-gray-500">Penalty</button>
                </div>
            </div>

            <div class="flex flex-col w-full h-full border-5 border-gray-700">
                <div class="flex w-full h-[50%] border-b border-gray-700">
                    <div class="flex flex-col w-full h-full border-r border-gray-700">
                        <div class="flex flex-col w-full h-full border-b border-gray-700">
                            <!-- duplicates now have -2 suffix to avoid duplicate id collisions -->
                            <button id="tag-interception-btn-2" data-event="Interception" class="ctr-btn w-full h-full py-1 bg-blue-500 border-b border-gray-700 cursor-pointer hover:bg-blue-600">Interception</button>
                            <button id="tag-recovery-btn-2" data-event="Recovery" class="ctr-btn w-full h-full py-1 bg-purple-500 cursor-pointer hover:bg-purple-600">Recovery</button>
                        </div>

                        <div class="flex flex-col w-full h-full">
                            <button id="tag-tackle-btn-2" data-event="Tackle" class="ctr-btn w-full h-full py-1 bg-cyan-600 border-b border-gray-700 cursor-pointer hover:bg-cyan-700">Tackle</button>
                            <button id="tag-duel-btn-2" data-event="Duel" class="ctr-btn w-full h-full py-1 bg-indigo-600 border-b border-gray-700 cursor-pointer hover:bg-indigo-700">Duel</button>
                            <button id="tag-clearance-btn-2" data-event="Clearance" class="ctr-btn w-full h-full py-1 bg-teal-600 cursor-pointer hover:bg-teal-700">Clearance</button>
                        </div>
                    </div>

                    <div class="flex flex-col w-full h-full">
                        <button id="tag-pass-btn-2" data-event="Pass" class="ctr-btn w-full h-full py-1 bg-green-500 border-b border-gray-700 cursor-pointer hover:bg-green-600">Pass</button>
                        <button id="tag-shot-btn-2" data-event="Shot" class="ctr-btn w-full h-full py-1 bg-red-500 border-b border-gray-700 cursor-pointer hover:bg-red-600">Shot</button>
                        <button id="tag-dribble-btn-2" data-event="Dribble" class="ctr-btn w-full h-full py-1 bg-yellow-500 cursor-pointer hover:bg-yellow-600">Dribble</button>
                    </div>
                </div>

                <div class="flex w-full h-[25%] border-b border-gray-700">
                    <div class="flex flex-col w-[25%] h-full border-r border-gray-700">
                        <button id="tag-foul-btn-2" data-event="Foul" class="ctr-btn w-full h-full py-1 bg-red-400 border-b border-gray-700 cursor-pointer hover:bg-red-500">Foul</button>
                        <button id="tag-yellow-card-btn-2" data-event="Yellow Card" class="ctr-btn w-full h-full py-1 bg-yellow-600 border-b border-gray-700 cursor-pointer hover:bg-yellow-700">Yellow Card</button>
                        <button id="tag-red-card-btn-2" data-event="Red Card" class="ctr-btn w-full h-full py-1 bg-red-800 cursor-pointer hover:bg-red-900">Red Card</button>
                    </div>

                    <div class="flex w-[75%] h-full">
                        <button id="tag-offside-btn-2" data-event="Offside" class="ctr-btn w-full h-full py-1 bg-stone-600 border-r border-gray-700 cursor-pointer hover:bg-stone-800">Offside</button>
                        <button id="tag-possession-lost-btn-2" data-event="Possession Loss" class="ctr-btn w-full h-full py-1 bg-stone-600 border-r border-gray-700 cursor-pointer hover:bg-stone-800">Possession Loss</button>
                        <button id="tag-out-off-play-btn-2" data-event="Out of Play" class="ctr-btn w-full h-full py-1 bg-stone-600 border-r border-gray-700 cursor-pointer hover:bg-stone-800">Out of Play</button>
                      </div>
                </div>

                <div class="flex w-full h-[25%]">
                    <button id="tag-penalty-btn-2" data-event="Penalty" class="ctr-btn w-full h-full py-1 bg-gray-400 cursor-pointer hover:bg-gray-500">Penalty</button>
                    <button id="tag-free-kick-btn-2" data-event="Free Kick" class="ctr-btn w-full h-full py-1 bg-gray-400 border-r border-gray-700 cursor-pointer hover:bg-gray-500">Free Kick</button>
                    <button id="tag-corner-btn-2" data-event="Corner" class="ctr-btn w-full h-full py-1 bg-gray-400 border-r border-gray-700 cursor-pointer hover:bg-gray-500">Corner</button>
                    <button id="tag-throw-in-btn-2" data-event="Throw In" class="ctr-btn w-full h-full py-1 bg-gray-400 border-r border-gray-700 cursor-pointer hover:bg-gray-500">Throw In</button>
                </div>
            </div>
          </div> 
        
            <div class="flex w-full justify-end p-6 border-t border-[#2a2a2a]">
              <button 
                id="submit-form" 
                type="submit" 
                class="px-6 py-2 bg-[#6366F1] rounded-md font-semibold text-sm text-white hover:bg-[#5052ec] cursor-pointer"
              >
                Finish and Save Tagging Data
              </button>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo base_url(); ?>assets/js/studioVideoPlayer.js?<?php echo time(); ?>"></script>

<script>
/*
  Tagging UI state-machine & orchestration
  (original comments preserved)
*/

window.SERVER_MATCH_ID = <?php echo json_encode($match_id ?? null); ?>;
window.API_GET_MATCH_DATA = <?php echo json_encode(site_url('studio/taggingcontroller/get_match_data')); ?>;

(function resolveAndExposeMatchId() {
  function fromPath() {
    try {
      const parts = location.pathname.split('/').filter(Boolean);
      // Attempt to locate 'tagging' then 'index' then the next segment
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
    // keep previous behavior: window.TAGGING_MATCH_ID will be null and your code will handle it
    window.TAGGING_MATCH_ID = null;
    console.warn('[TAGGING] No match_id resolved from server / URL / sessionStorage.');
    return;
  }

  // Persist per-tab (avoids multi-tab collisions)
  sessionStorage.setItem('current_match_id', resolved);
  window.TAGGING_MATCH_ID = resolved;

  // Ensure address bar has /tagging/index/{match_id} path (won't reload page)
  try {
    const parts = location.pathname.split('/').filter(Boolean);
    const t = parts.indexOf('tagging');
    const alreadyHasSegment = (t >= 0 && parts[t + 1] === 'index' && parts[t + 2]);
  } catch (e) {
    console.debug('Failed to update URL path with match_id:', e);
  }
})();

let mode = 'setup'; // 'setup' | 'tagging'
let positionsLocked = false;
let config = null;

(async function () {
  const base = 'http://localhost/github/insytes-web-app/index.php/studio/'; // your base
  const endpoints = {
    savePositions: base + 'taggingcontroller/save_positions',
    saveEvent: base + 'taggingcontroller/save_event',
    undoEvent: base + 'taggingcontroller/undo_event',
    getEvents: (id) => base + 'taggingcontroller/get_events/' + encodeURIComponent(id)
  };

  // DOM refs
  const pitchImg = document.getElementById('pitchmap');
  const pitchContainer = pitchImg && pitchImg.parentElement;
  const video = document.getElementById('video-player');
  const matchMinuteEl = document.getElementById('match-minute');
  const matchMinuteElVid = document.getElementById('match-minute-video');
  const infoText = document.getElementById('info-text');
  const saveTagBtn = document.getElementById('save-tag-btn');
  const cancelTagBtn = document.getElementById('cancel-tag-btn');

  // kickoff UI elements
  const kickOffButtonsContainer = document.getElementById('kick-off-buttons');
  const kickOffHomeBtn = document.getElementById('kick-off-home-btn');
  const kickOffAwayBtn = document.getElementById('kick-off-away-btn');
  const taggingEventsButtonContainer = document.getElementById('tagging-events-button');

  // type/outcome groups
  const passTypeGroup = document.getElementById('pass-type-buttons');
  const shotTypeGroup = document.getElementById('shot-type-buttons');
  const duelTypeGroup = document.getElementById('duel-type-buttons');
  const passOutcomeGroup = document.getElementById('pass-outcome-buttons');
  const shotOutcomeGroup = document.getElementById('shot-outcome-buttons');
  const duelOutcomeGroup = document.getElementById('duel-outcome-buttons');
  const penaltyOutcomeGroup = document.getElementById('penalty-outcome-buttons');
  const outcomeGroup = document.getElementById('reusable-outcome-buttons');

  // NEW FEATURE: Key Pass buttons
  const keyPassYesBtn = document.getElementById('key-pass-yes-btn'); // NEW FEATURE
  const keyPassNoBtn = document.getElementById('key-pass-no-btn'); // NEW FEATURE
  const keyPassButtons = [keyPassYesBtn, keyPassNoBtn].filter(Boolean);

  // Substitutions
  const substitutionToggleBtn = document.getElementById('substitution-btn');
  const substitutionsPanel = document.getElementById('substitutions-panel');
  const substitutionsBtnContainer = document.getElementById('substitutions-btn');
  const homeSubsBtn = document.getElementById('home-team-substitution-btn');
  const awaySubsBtn = document.getElementById('away-team-substitution-btn');
  const playersOnPitchContainer = document.getElementById('players-on-pitch-container');
  const verticalPitchImg = document.getElementById('vertical-pitch');
  const benchPlayersContainer = document.getElementById('bench-players-container');
  const saveSubsBtn = document.getElementById('save-substitution-btn');
  const cancelSubsBtn = document.getElementById('cancel-substitution-btn');

  // autodiscover tag buttons
  const tagButtons = Array.from(document.querySelectorAll('button[id^="tag-"]'))
    .map(btn => {
      const canonical = (btn.dataset.event || btn.innerText || '').trim();
      btn.dataset.event = canonical;
      btn.dataset.side = btn.id && btn.id.endsWith('-2') ? 'away' : 'home';
      return btn;
    });

  // state
  let playersMap = {}; // id -> DOM element
  let selectedTeam = null; // will be set at kickoff: 'home'|'away'
  let kickoffVideoTime = null; // when kickoff button clicked (video.currentTime)
  let halfOffsetSeconds = 0; // offset to add for second half (if any)
  let half = 0;
  let originalDirection = null;
  let flipHorizontal = false;
  let eventsList = [];

  // state specific to substitutionstagStep
  let subsPitchMap = {}; // playerId -> DOM element (for players-on-pitch in subs UI)
  let subsBenchMap = {};  // benchId -> DOM element
  let subsActive = false;
  let subsOpenFor = null;
  let originalTeamSnapshots = {};
  let substitutionsList = [];

  // tag state
  // steps: 'idle' -> 'awaiting_origin' -> 'awaiting_type' -> 'awaiting_outcome' -> 'awaiting_player_1' -> 'awaiting_player_2' -> 'awaiting_end' -> 'ready'
  let currentTag = null;
  let tagStep = 'idle';

  const matchId = window.TAGGING_MATCH_ID;

  if (!matchId) {
    console.warn('[TAGGING] No match_id provided.');
    alert('No match_id provided.');
    return;
  }

  console.debug('[TAGGING] Loading match data for', matchId);

  try {
    const res = await fetch(`${window.API_GET_MATCH_DATA}/${encodeURIComponent(matchId)}`);

    if (!res.ok) {
      throw new Error(`HTTP ${res.status}`);
    }

    const payload = await res.json();
    console.debug('[TAGGING] API payload:', payload);

    if (!payload.success) {
      alert('Error: ' + (payload.message || 'Failed to load match data.'));
      return;
    }

    // ✅ Initialize config safely
    config = payload.config || {};
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

    // Safe function calls
    if (typeof renderPlayers === 'function') renderPlayers();
    if (typeof showTeamNameScoreboard === 'function') showTeamNameScoreboard();

    console.debug('[TAGGING] Config loaded successfully:', config);

    if (mode === 'tagging') {
      console.debug('[TAGGING] Tagging mode active: applying kickoff setup');

      // Show kickoff buttons, hide event buttons
      if (kickOffButtonsContainer) kickOffButtonsContainer.classList.remove('hidden');
      if (taggingEventsButtonContainer) taggingEventsButtonContainer.classList.add('hidden');

      updateInfoText('Select which team kicks off (click a Kickoff button).');

      // Disable dragging and reset click handlers for all players
      Object.values(playersMap || {}).forEach(entry => {
        const div = entry.el || entry;
        if (!div) return;

        if (div._dragCleanup) {
          try { div._dragCleanup(); } catch (e) {}
          delete div._dragCleanup;
        }

        try { div.removeEventListener('click', playerClickHandler); } catch (e) {}
        div.addEventListener('click', playerClickHandler);
        div.style.cursor = 'pointer';
        div.style.display = ''; // ✅ don’t hide players, just make them clickable
      });

      // Disable action buttons until kickoff selected
      setActionButtonsEnabled(false);
      setTypeOutcomeGroupsActive([]);

      // Reset kickoff metadata
      kickoffVideoTime = null;
      selectedTeam = null;
      halfOffsetSeconds = 0;
      half = 0;

      // Apply positions locked UI (so players can’t be moved)
      if (typeof applyPositionsLockedUI === 'function') {
        applyPositionsLockedUI();
      }
    }

  } catch (err) {
    console.error('[TAGGING] Failed to fetch match data:', err);
    alert('Failed to load match data. Check console for details.');
  }

  // helper: get team data and bench from config with fallbacks
  function getTeamData(side) {
    if (!config || !config[side]) return null;
    const team = config[side];
    const benchCandidates = ['bench', 'substitutes', 'bench_players', 'sub'];
    let bench = null;
    for (const k of benchCandidates) {
      if (Array.isArray(team[k])) { bench = team[k]; break; }
    }
    if (!bench) {
      const allPlayers = Array.isArray(team.players) ? team.players.slice() : [];
      const startingIds = Array.isArray(team.starting11) ? team.starting11.map(p => p.id) : [];
      bench = allPlayers.filter(p => !startingIds.includes(p.id));
    }
    return { team, bench: bench || [] };
  }

  // toggles panel visibility
  if (substitutionToggleBtn) {
    substitutionToggleBtn.addEventListener('click', () => {
      subsActive = !subsActive;
      substitutionsPanel.style.display = subsActive ? 'block' : 'none';
      taggingEventsButtonContainer.classList.add('hidden');
      if (!subsActive) cancelSubstitutions();
    });
  }

  // open substitutions UI for given side
  async function openSubstitutionsFor(side) {
    if (!config || !config[side]) return;
    subsOpenFor = side;
    originalTeamSnapshots[side] = {
      starting11: (config[side].starting11 || []).map(p => ({ ...p })),
      bench: (getTeamData(side).bench || []).map(p => ({ ...p }))
    };
    // ensure panel is visible
    substitutionsPanel.style.display = 'block';
    subsActive = true;
    renderSubstitutionUI();
  }

  // render UI for the currently selected team ONLY
  function renderSubstitutionUI() {
    // cleanup previous DOM nodes we created
    if (playersOnPitchContainer) {
      playersOnPitchContainer.querySelectorAll('.subs-player-jersey').forEach(e => e.remove());
    }
    if (benchPlayersContainer) benchPlayersContainer.innerHTML = '';
    subsPitchMap = {};
    subsBenchMap = {};

    if (!subsOpenFor) return; // nothing to show

    // ensure container/layout
    if (playersOnPitchContainer) {
      playersOnPitchContainer.style.position = 'relative';
      if (verticalPitchImg) {
        verticalPitchImg.style.width = '100%';
        verticalPitchImg.style.height = '100%';
        verticalPitchImg.style.objectFit = 'fill';
      }
    }

    // render only the selected side's starting11 on the vertical pitch
    const side = subsOpenFor;
    const teamData = getTeamData(side);
    if (!teamData || !Array.isArray(teamData.team.starting11)) return;

    teamData.team.starting11.forEach(player => {
      const div = document.createElement('div');
      div.className = 'subs-player-jersey';
      div.dataset.playerId = player.id;
      div.dataset.side = side;
      div.dataset.name = player.name || '';
      div.dataset.number = player.number || '';
      div.style.cssText = `
        position:absolute;
        z-index:2000;
        width:32px;
        height:32px;
        border-radius:50%;
        display:flex;
        justify-content:center;
        align-items:center;
        cursor:pointer;
        user-select:none;
        font-size:12px;
        font-weight:700;
        color:${teamData.team.jersey_text_color || '#fff'};
        background:${teamData.team.jersey_color || '#888'};
        outline: 2px solid rgba(255,255,255,0.9);
      `;
      const numberSpan = document.createElement('span');
      numberSpan.className = 'player-number';
      numberSpan.innerText = player.number || '';
      div.appendChild(numberSpan);
      const x = typeof player.x !== 'undefined' ? Number(player.x) : 50;
      const y = typeof player.y !== 'undefined' ? Number(player.y) : (side === 'home' ? 70 : 30);
      placeSubPlayer(div, x, y, playersOnPitchContainer);
      div.addEventListener('click', subsPitchClickHandler);
      subsPitchMap[player.id] = div;
      playersOnPitchContainer.appendChild(div);
    });

    // render bench for selected team (2 rows)
    renderBenchFor(side);
  }

  // place element inside a container using logical x/y (0-100)
  function placeSubPlayer(el, x, y, container) {
    const rect = container.getBoundingClientRect();
    const width = rect.width || container.clientWidth || 400;
    const height = rect.height || container.clientHeight || 600;
    const visualX = x;
    const cx = (visualX / 100) * width;
    const cy = (y / 100) * height;
    el.style.left = (cx - (el.offsetWidth / 2)) + 'px';
    el.style.top = (cy - (el.offsetHeight / 2)) + 'px';
    el.dataset.x = x;
    el.dataset.y = y;
  }

  // bench layout: 2 rows, fill width
  function renderBenchFor(side) {
    if (!benchPlayersContainer) return;
    benchPlayersContainer.innerHTML = '';
    subsBenchMap = {};
    const teamData = getTeamData(side);
    if (!teamData) return;
    const bench = Array.isArray(teamData.bench) ? teamData.bench : [];

    const grid = document.createElement('div');
    grid.style.display = 'grid';
    grid.style.gridTemplateRows = 'repeat(2, 1fr)';
    grid.style.gridAutoColumns = '1fr';
    grid.style.gridAutoFlow = 'column';
    grid.style.gap = '8px';
    grid.style.alignItems = 'center';
    grid.style.justifyItems = 'center';
    grid.style.width = '100%';
    bench.forEach(p => {
      const b = document.createElement('div');
      b.className = 'bench-player';
      b.dataset.playerId = p.id;
      b.dataset.side = side;
      b.dataset.name = p.name || '';
      b.dataset.number = p.number || '';
      b.style.cssText = `
        width:36px;
        height:36px;
        border-radius:6px;
        display:flex;
        align-items:center;
        justify-content:center;
        cursor:pointer;
        user-select:none;
        font-weight:700;
        color:${teamData.team.jersey_text_color || '#fff'};
        background:${teamData.team.jersey_color || '#666'};
      `;
      b.innerText = p.number || '';
      b.addEventListener('click', benchClickHandler);
      subsBenchMap[p.id] = b;
      grid.appendChild(b);
    });
    benchPlayersContainer.appendChild(grid);
  }

  // selection state for swaps (first click and second click)
  let subsSelection = null; // { type: 'pitch'|'bench', id, side }

  function subsPitchClickHandler(e) {
    e.stopPropagation();
    const el = e.currentTarget;
    const pid = el.dataset.playerId;
    const side = el.dataset.side;
    handleSubSelection({ type: 'pitch', id: pid, side });
  }
  function benchClickHandler(e) {
    e.stopPropagation();
    const el = e.currentTarget;
    const pid = el.dataset.playerId;
    const side = el.dataset.side;
    handleSubSelection({ type: 'bench', id: pid, side });
  }

  function handleSubSelection(sel) {
    if (!sel || !sel.id) return;
    if (!subsSelection) {
      subsSelection = sel;
      highlightSubSelected(sel, true);
      updateInfoText(`Selected ${sel.type === 'bench' ? 'bench' : 'on-pitch'} player #${sel.id}. Click the other player to swap.`);
      return;
    }
    if (subsSelection.type === sel.type && subsSelection.id === sel.id) {
      highlightSubSelected(subsSelection, false);
      subsSelection = null;
      updateInfoText('Selection cleared.');
      return;
    }
    // Require same team side for swap
    if (subsSelection.side !== sel.side) {
      updateInfoText('Swap must be between players of the same team. Selection cleared.', { temporary: true });
      highlightSubSelected(subsSelection, false);
      subsSelection = null;
      return;
    }
    performSwap(subsSelection, sel);
    highlightSubSelected(subsSelection, false);
    subsSelection = null;
  }

  function highlightSubSelected(sel, highlight) {
    if (!sel) return;
    let el = sel.type === 'pitch' ? subsPitchMap[sel.id] : subsBenchMap[sel.id];
    if (!el) return;
    el.style.boxShadow = highlight ? '0 0 0 3px rgba(34,197,94,0.7)' : 'none';
  }

  // perform swap bench <-> pitch
  function performSwap(sel1, sel2) {
    let outSel = sel1.type === 'pitch' ? sel1 : sel2.type === 'pitch' ? sel2 : null;
    let inSel  = sel1.type === 'bench' ? sel1 : sel2.type === 'bench' ? sel2 : null;
    if (!outSel || !inSel) {
      updateInfoText('Substitution requires one on-pitch and one bench player.', { temporary: true });
      return;
    }

    const side = outSel.side;
    const teamObj = config[side];
    if (!teamObj) return;

    const outPlayer = teamObj.starting11.find(p => String(p.id) === String(outSel.id));
    const benchCandidates = ['bench','substitutes','bench_players','sub'];
    let benchArray = null;
    for (const k of benchCandidates) {
      if (Array.isArray(teamObj[k])) { benchArray = teamObj[k]; break; }
    }
    if (!benchArray) {
      const allPlayers = Array.isArray(teamObj.players) ? teamObj.players : [];
      benchArray = allPlayers.filter(p => !teamObj.starting11.some(s => s.id === p.id));
      teamObj.bench = benchArray;
      benchArray = teamObj.bench;
    }
    const inPlayerIndex = benchArray.findIndex(p => String(p.id) === String(inSel.id));
    if (inPlayerIndex === -1 || !outPlayer) {
      updateInfoText('Could not find selected players in config.', { temporary: true });
      return;
    }
    const inPlayer = benchArray[inPlayerIndex];
    const startIndex = teamObj.starting11.findIndex(p => String(p.id) === String(outPlayer.id));
    if (startIndex === -1) { updateInfoText('Could not locate starting player index.', { temporary: true }); return; }

    // --- MODIFICATION START ---
    // Get the starting player's (outPlayer) position and assign it to the
    // incoming bench player (inPlayer) so they take their spot on the map.
    if (typeof outPlayer.x !== 'undefined') {
        inPlayer.x = outPlayer.x;
    }
    if (typeof outPlayer.y !== 'undefined') {
        inPlayer.y = outPlayer.y;
    }
    // --- MODIFICATION END ---

    // swap: bench slot replaced with outPlayer; starting11 slot replaced with inPlayer
    benchArray.splice(inPlayerIndex, 1, outPlayer);
    teamObj.starting11[startIndex] = inPlayer;

    substitutionsList.push({
      team_side: side,
      out: { id: outPlayer.id, name: outPlayer.name, number: outPlayer.number, position: outPlayer.position },
      in: { id: inPlayer.id, name: inPlayer.name, number: inPlayer.number, position: inPlayer.position },
      // --- ADDED ---
      x: outPlayer.x,
      y: outPlayer.y
    });

    // refresh UIs
    renderPlayers();
    renderSubstitutionUI();
    updateInfoText(`Swapped OUT #${outPlayer.number} → IN #${inPlayer.number}.`, { temporary: true });
  }

  // Save substitutions (POST per substitution)
  async function saveSubstitutions() {
    if (!substitutionsList.length) {
      updateInfoText('No substitutions to save.', { temporary: true });
      return;
    }
    if (!video) {
      alert('Video element not found. Cannot compute match time for substitutions.');
      return;
    }
    try {
      for (const s of substitutionsList) {
        const nowTs = Number(video.currentTime || 0);
        const matchSeconds = computeMatchSeconds(nowTs);
        const teamName = s.team_side === 'home' ? (config.home && config.home.name ? config.home.name : 'home') :
                         (config.away && config.away.name ? config.away.name : 'away');

        const eventObj = {
          match_id: (config && config.match) ? config.match.id : '',
          team: teamName,
          team_side: s.team_side,
          event: 'Substitution',
          player_id: s.out.id,
          player_name: s.out.name,
          player_number: s.out.number,
          player_position: s.out.position,
          match_time_minute: Math.max(0, Math.floor(matchSeconds || 0)),
          half_period: half,
          video_timestamp: nowTs,
          // --- MODIFIED ---
          origin_x: typeof s.x !== 'undefined' ? s.x : null,
          origin_y: typeof s.y !== 'undefined' ? s.y : null,
          outcome: null,
          type: null,
          additional: {
            secondary_player: {
              id: s.in.id,
              name: s.in.name,
              number: s.in.number,
              position: s.in.position,
              side: s.team_side
            }
          }
        };

        const resp = await fetch(endpoints.saveEvent, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ match_id: matchId, event: eventObj })
        });
        const data = await resp.json();
        if (!(data && data.success)) {
          console.error('Substitution save failed for', eventObj, data);
          updateInfoText('Failed to save one or more substitutions (see console).', { temporary: true });
        } else {
          if (data.event) eventsList.push(data.event);
        }
      }

      updateInfoText('Substitutions saved.');
      substitutionsList = [];
      closeSubstitutionsUI();
      await loadEvents();
      renderPlayers();
    } catch (err) {
      console.error('Error saving substitutions', err);
      updateInfoText('Error saving substitutions: ' + (err.message || err), { temporary: true });
    }
  }

  // Cancel substitutions (revert snapshot for the opened side)
  function cancelSubstitutions() {
    if (subsOpenFor && originalTeamSnapshots[subsOpenFor]) {
      config[subsOpenFor].starting11 = originalTeamSnapshots[subsOpenFor].starting11.map(p => ({ ...p }));
      const teamBenchKeyFallbacks = ['bench','substitutes','bench_players','sub'];
      let foundKey = null;
      for (const k of teamBenchKeyFallbacks) {
        if (config[subsOpenFor] && Array.isArray(config[subsOpenFor][k])) { foundKey = k; break; }
      }
      if (foundKey) config[subsOpenFor][foundKey] = originalTeamSnapshots[subsOpenFor].bench.map(p => ({ ...p }));
      else config[subsOpenFor].bench = originalTeamSnapshots[subsOpenFor].bench.map(p => ({ ...p }));
    }

    taggingEventsButtonContainer.classList.remove('hidden');

    substitutionsList = [];
    originalTeamSnapshots = {};
    updateInfoText('Substitutions cancelled (reverted).', { temporary: true });
    renderPlayers();
    renderSubstitutionUI();
    closeSubstitutionsUI();
  }

  function closeSubstitutionsUI() {
    subsOpenFor = null;
    subsActive = false;
    substitutionsPanel.style.display = 'none';
    taggingEventsButtonContainer.classList.remove('hidden');
    subsSelection = null;
    subsPitchMap = {};
    subsBenchMap = {};
    if (playersOnPitchContainer) playersOnPitchContainer.querySelectorAll('.subs-player-jersey').forEach(e => e.remove());
    if (benchPlayersContainer) benchPlayersContainer.innerHTML = '';
  }

  // wire top buttons
  if (homeSubsBtn) homeSubsBtn.addEventListener('click', () => { openSubstitutionsFor('home'); });
  if (awaySubsBtn) awaySubsBtn.addEventListener('click', () => { openSubstitutionsFor('away'); });
  if (saveSubsBtn) saveSubsBtn.addEventListener('click', saveSubstitutions);
  if (cancelSubsBtn) cancelSubsBtn.addEventListener('click', cancelSubstitutions);

  // start hidden
  if (substitutionsPanel) substitutionsPanel.style.display = 'none';

  // Flip is determined by config.match.attacking_direction (flipHorizontal) XOR half==2.
  function effectiveFlip() {
    // returns true if visual coords should be mirrored horizontally
    return Boolean(flipHorizontal) !== (half === 2);
  }

  // helpers for UI text
  function ensureInfoText() {
    if (infoText) return infoText;
    let el = document.getElementById('info-text');
    if (el) return el;
    el = document.createElement('div');
    el.id = 'info-text';
    el.style.cssText = "padding:6px;background:#374151;color:#fff;border-radius:6px;margin-bottom:8px;";
    if (pitchContainer && pitchContainer.parentElement) {
      pitchContainer.parentElement.insertBefore(el, pitchContainer);
    } else document.body.appendChild(el);
    return el;
  }
  function updateInfoText(msg, { temporary = false, timeout = 4000 } = {}) {
    const el = ensureInfoText();
    el.innerText = String(msg === undefined || msg === null ? '' : msg);
    el.style.opacity = '1';
    if (temporary) {
      clearTimeout(el._hideTimeout);
      el._hideTimeout = setTimeout(() => { el.innerText = ''; el.style.opacity = '0.9'; }, timeout);
    }
  }

  async function fetchJSON(url, opts = {}) {
    const r = await fetch(url, opts);
    const txt = await r.text();
    try { return JSON.parse(txt); } catch (e) { return txt; }
  }

  // format seconds -> mm:ss (pad)
  function formatSecondsToMMSS(sec) {
    const s = Math.max(0, Number(Math.floor(sec) || 0));
    const mm = Math.floor(s / 60);
    const ss = s % 60;
    const mmStr = String(mm).padStart(2, '0');
    const ssStr = String(ss).padStart(2, '0');
    return `${mmStr}:${ssStr}`;
  }

  // compute match seconds (whole seconds) given video timestamp
  function computeMatchSeconds(videoTs) {
    if (kickoffVideoTime === null) return 0;
    const elapsedSec = Math.max(0, Math.floor(videoTs - kickoffVideoTime));
    return elapsedSec + (halfOffsetSeconds || 0);
  }

  // keep a frequent interval to update displayed mm:ss
  setInterval(() => {
    if (!video) return;
    const seconds = computeMatchSeconds(video.currentTime);
    if (matchMinuteEl) matchMinuteEl.innerText = formatSecondsToMMSS(seconds);
    if (matchMinuteElVid) matchMinuteElVid.innerText = formatSecondsToMMSS(seconds);
  }, 400);

  function renderPlayers() {
    if (!pitchContainer) return;
    Object.values(playersMap).forEach(el => el.remove());
    playersMap = {};
    pitchContainer.style.position = 'relative';

    ['home','away'].forEach(side => {
      if (!config[side] || !Array.isArray(config[side].starting11)) return;
      const teamInfo = config[side];
      teamInfo.starting11.forEach(player => {
        const div = document.createElement('div');
        div.className = 'player-jersey';
        div.style.cssText = `
          position:absolute;
          z-index:1000;
          width:28px;
          height:28px;
          border-radius:50%;
          display:flex;
          justify-content:center;
          align-items:center;
          cursor:${(mode === 'setup' && !positionsLocked) ? 'grab' : 'pointer'};
          user-select:none;
          box-shadow:0 1px 3px rgba(0,0,0,0.4);
          font-size:12px;
          font-weight:700;
          color:${teamInfo.jersey_text_color || '#fff'};
          background:${teamInfo.jersey_color || '#888'};
        `;
        div.innerText = player.number;
        div.dataset.playerId = player.id;
        div.dataset.side = side;
        div.dataset.name = player.name || '';
        div.dataset.number = player.number || '';

        const x = typeof player.x !== 'undefined' ? Number(player.x) : 50;
        const y = typeof player.y !== 'undefined' ? Number(player.y) : (side === 'home' ? 70 : 30);
        placePlayer(div, x, y);

        if (mode === 'setup' && !positionsLocked) {
          div._dragCleanup = makeDraggable(div, player);
        } else {
          if (div._dragCleanup) {
            try { div._dragCleanup(); } catch(e) {}
            delete div._dragCleanup;
          }
          div.addEventListener('click', playerClickHandler);
          if (mode === 'tagging') div.style.display = 'none';
        }

        playersMap[player.id] = div;
        pitchContainer.appendChild(div);
      });
    });
  }

  function placePlayer(div, x, y) {
    const rect = pitchContainer.getBoundingClientRect();
    const visualX = effectiveFlip() ? (100 - x) : x;
    const cx = (visualX / 100) * rect.width;
    const cy = (y / 100) * rect.height;
    div.style.left = (cx - (div.offsetWidth / 2)) + 'px';
    div.style.top = (cy - (div.offsetHeight / 2)) + 'px';
    // store logical coords on dataset.x/y (unchanged)
    div.dataset.x = x;
    div.dataset.y = y;
    div.dataset.visualX = visualX;
  }

  function makeDraggable(div, player) {
    let dragging = false;
    let startMouse = {};
    let startPos = {};

    function onMouseDown(ev) {
      if (tagStep && tagStep.startsWith('awaiting')) {
        return;
      }
      if (ev.button !== 0) return;
      ev.preventDefault();
      ev.stopPropagation();
      dragging = true;
      startMouse = { x: ev.clientX, y: ev.clientY };
      startPos = { left: parseFloat(div.style.left) || 0, top: parseFloat(div.style.top) || 0 };
      div.style.cursor = 'grabbing';
      div._isDragging = true;
    }
    function onMouseMove(ev) {
      if (!dragging) return;
      const dx = ev.clientX - startMouse.x;
      const dy = ev.clientY - startMouse.y;
      div.style.left = (startPos.left + dx) + 'px';
      div.style.top = (startPos.top + dy) + 'px';
    }
    async function onMouseUp(ev) {
      if (!dragging) return;
      dragging = false;
      div.style.cursor = 'grab';
      div._isDragging = false;
      const rect = pitchContainer.getBoundingClientRect();
      const cx = (parseFloat(div.style.left) || 0) + (div.offsetWidth / 2);
      const cy = (parseFloat(div.style.top) || 0) + (div.offsetHeight / 2);
      const pxVisual = Math.max(0, Math.min(100, (cx / rect.width) * 100));
      const py = Math.max(0, Math.min(100, (cy / rect.height) * 100));
      const pxLogical = effectiveFlip() ? (100 - pxVisual) : pxVisual;
      updatePlayerPositionInConfig(player.id, pxLogical, py);
      div.dataset.x = pxLogical;
      div.dataset.y = py;
    }

    div.addEventListener('mousedown', onMouseDown);
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);

    return function cleanupDrag() {
      try {
        div.removeEventListener('mousedown', onMouseDown);
        window.removeEventListener('mousemove', onMouseMove);
        window.removeEventListener('mouseup', onMouseUp);
      } catch (err) {}
      div._isDragging = false;
    };
  }

  function updatePlayerPositionInConfig(pid, x, y) {
    ['home','away'].forEach(side => {
      if (!config[side] || !Array.isArray(config[side].starting11)) return;
      config[side].starting11.forEach(p => {
        if (p.id === pid) {
          p.x = Number(x);
          p.y = Number(y);
        }
      });
    });
  }

  async function savePositionsToBackend({ lock = false } = {}) {
    const positions = [];
    ['home','away'].forEach(side => {
      if (!config[side]) return;
      config[side].starting11.forEach(p => {
        positions.push({ id: p.id, x: Number(p.x), y: Number(p.y) });
      });
    });
    const payload = { match_id: matchId, positions: positions, lock: !!lock };
    const resp = await fetch(endpoints.savePositions, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await resp.json();
    if (data && data.success) {
      if (lock) config.match.positions_locked = true;
      return true;
    } else {
      console.error('Save positions failed', data);
      return false;
    }
  }

  function pitchClickToGrid(clientX, clientY) {
    const rect = pitchContainer.getBoundingClientRect();
    const xPx = clientX - rect.left;
    const yPx = clientY - rect.top;
    const visualX = Math.max(0, Math.min(100, (xPx / rect.width) * 100));
    const visualY = Math.max(0, Math.min(100, (yPx / rect.height) * 100));
    // logicalX = inverse of visualX if effectiveFlip is true
    // const logicalX = effectiveFlip() ? (100 - visualX) : visualX;
    return { x: parseFloat(visualX.toFixed(2)), y: parseFloat(visualY.toFixed(2)) };
  }

  function showTempMarker(p) {
    let m = document.getElementById('temp-marker');
    if (!m && pitchContainer) {
      m = document.createElement('div');
      m.id = 'temp-marker';
      m.style.position = 'absolute';
      m.style.width = '14px';
      m.style.height = '14px';
      m.style.borderRadius = '50%';
      m.style.border = '3px solid #fff';
      m.style.transform = 'translate(-50%,-50%)';
      m.style.zIndex = 70;
      pitchContainer.appendChild(m);
    }
    if (!m || !pitchContainer) return;
    const rect = pitchContainer.getBoundingClientRect();
    m.style.left = (p.x / 100 * rect.width) + 'px';
    m.style.top = (p.y / 100 * rect.height) + 'px';
  }

  // new: show distinct marker for PASS END
  function showEndMarker(p) {
    let m = document.getElementById('temp-end-marker');
    if (!m && pitchContainer) {
      m = document.createElement('div');
      m.id = 'temp-end-marker';
      m.style.position = 'absolute';
      m.style.width = '14px';
      m.style.height = '14px';
      m.style.borderRadius = '50%';
      m.style.border = '3px dashed #60a5fa';
      m.style.background = 'rgba(96,165,250,0.15)';
      m.style.transform = 'translate(-50%,-50%)';
      m.style.zIndex = 71;
      pitchContainer.appendChild(m);
    }
    if (!m || !pitchContainer) return;
    const rect = pitchContainer.getBoundingClientRect();
    m.style.left = (p.x / 100 * rect.width) + 'px';
    m.style.top = (p.y / 100 * rect.height) + 'px';

    // draw line when end marker is set
    drawPassLine(currentTag && currentTag.origin ? currentTag.origin : null, p);
  }

  // --- SVG pass-line helpers ---
  function ensurePassSVG() {
    if (!pitchContainer) return null;
    let svg = document.getElementById('pass-svg-overlay');
    if (!svg) {
      svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      svg.setAttribute('id', 'pass-svg-overlay');
      svg.style.position = 'absolute';
      svg.style.top = '0';
      svg.style.left = '0';
      svg.style.width = '100%';
      svg.style.height = '100%';
      svg.style.pointerEvents = 'none';
      svg.style.zIndex = 69;
      pitchContainer.appendChild(svg);
    }
    return svg;
  }

  function drawPassLine(origin, end) {
    // origin/end in logical percent coords {x,y}
    const svg = ensurePassSVG();
    if (!svg || !origin || !end) return;
    // remove existing line
    removePassLine();

    const rect = pitchContainer.getBoundingClientRect();
    const shouldFlip = effectiveFlip();
    const originVisualX = shouldFlip ? (100 - origin.x) : origin.x;
    const endVisualX = shouldFlip ? (100 - end.x) : end.x;
    const x1 = (originVisualX / 100) * rect.width;
    const y1 = (origin.y / 100) * rect.height;
    const x2 = (endVisualX / 100) * rect.width;
    const y2 = (end.y / 100) * rect.height;

    const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    line.setAttribute('id', 'pass-line');
    line.setAttribute('x1', String(x1));
    line.setAttribute('y1', String(y1));
    line.setAttribute('x2', String(x2));
    line.setAttribute('y2', String(y2));
    line.setAttribute('stroke', '#60a5fa');
    line.setAttribute('stroke-width', '3');
    line.setAttribute('stroke-linecap', 'round');
    line.setAttribute('opacity', '0.95');
    svg.appendChild(line);

    // add small circle at end for clarity
    const circ = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    circ.setAttribute('id', 'pass-end-dot');
    circ.setAttribute('cx', String(x2));
    circ.setAttribute('cy', String(y2));
    circ.setAttribute('r', '6');
    circ.setAttribute('fill', 'rgba(96,165,250,0.2)');
    circ.setAttribute('stroke', '#60a5fa');
    circ.setAttribute('stroke-width', '2');
    svg.appendChild(circ);
  }


  function removePassLine() {
    const svg = document.getElementById('pass-svg-overlay');
    if (!svg) return;
    const line = document.getElementById('pass-line');
    if (line) line.remove();
    const circ = document.getElementById('pass-end-dot');
    if (circ) circ.remove();
    // if svg empty, remove it
    if (svg.childNodes.length === 0) svg.remove();
  }

  // tooltip for "Click to set pass end"
  function showPassEndTooltip() {
    if (!pitchContainer) return;
    let tip = document.getElementById('pass-end-tooltip');
    if (!tip) {
      tip = document.createElement('div');
      tip.id = 'pass-end-tooltip';
      tip.innerText = 'Click to set pass end';
      tip.style.position = 'absolute';
      tip.style.padding = '6px 8px';
      tip.style.background = 'rgba(0,0,0,0.75)';
      tip.style.color = '#fff';
      tip.style.borderRadius = '6px';
      tip.style.fontSize = '12px';
      tip.style.zIndex = 120;
      tip.style.pointerEvents = 'none';
      pitchContainer.appendChild(tip);
    }
    // position tooltip top-right inside pitch with small margin
    const rect = pitchContainer.getBoundingClientRect();
    tip.style.left = Math.max(8, rect.width - 160) + 'px';
    tip.style.top = '8px';
    tip.style.opacity = '1';
  }

  function hidePassEndTooltip() {
    const tip = document.getElementById('pass-end-tooltip');
    if (tip) tip.remove();
  }

  function removeTempMarkers() {
    const m = document.getElementById('temp-marker');
    if (m) m.remove();
    const e = document.getElementById('temp-end-marker');
    if (e) e.remove();
    // remove svg line
    removePassLine();
    hidePassEndTooltip();
  }

  // Which events require outcome/type?
  function requiresOutcome(eventType) {
    if (!eventType) return false;
    const need = ['Pass','Shot','Dribble','Duel','Tackle','Penalty'];
    return need.includes(eventType);
  }
  function requiresType(eventType) {
    if (!eventType) return false;
    return ['Pass','Shot','Duel'].includes(eventType);
  }

  // Determine player count requirements
  function determinePlayerRequirements(eventType, outcome) {
    if (!eventType) return { count: 1, secondSide: 'none' };
    const o = (typeof outcome === 'string') ? outcome.trim().toLowerCase() : '';
    switch (eventType) {
      case 'Pass':
        if (!o) return { count: 1, secondSide: 'same' };
        if (o === 'successful') return { count: 2, secondSide: 'same' };
        if (o === 'unsuccessful') return { count: 1, secondSide: 'none' };
        if (o.includes('intercept')) return { count: 2, secondSide: 'opposite' };
        return { count: 1, secondSide: 'none' };
      case 'Shot':
        if (!o) return { count: 1, secondSide: 'none' };
        if (o.includes('block')) return { count: 2, secondSide: 'opposite' };
        return { count: 1, secondSide: 'none' };
      case 'Duel':
        return { count: 2, secondSide: 'opposite' };
      case 'Penalty':
      case 'Dribble':
        return { count: 1, secondSide: 'none' };
      default:
        return { count: 1, secondSide: 'none' };
    }
  }

  function hideAllPlayers() {
    Object.values(playersMap).forEach(div => { div.style.display = 'none'; div.style.outline = 'none'; });
  }

  // Show players based on currentTag, currentTag.outcome and tagStep
  function showRelevantPlayers() {
    if (!currentTag) return;
    const req = determinePlayerRequirements(currentTag.event, currentTag.outcome);
    Object.values(playersMap).forEach(div => {
      const side = div.dataset.side;
      div.style.display = 'none';
      div.style.cursor = 'pointer';
      if (tagStep === 'awaiting_player_1') {
        if (currentTag.team_side && side === currentTag.team_side) {
          div.style.display = 'flex';
        } else if (!currentTag.team_side) {
          div.style.display = 'flex';
        }
      } else if (tagStep === 'awaiting_player_2') {
        if (req.secondSide === 'same') {
          if (currentTag.players && currentTag.players[0] && div.dataset.side === currentTag.players[0].side) div.style.display = 'flex';
        } else if (req.secondSide === 'opposite') {
          if (currentTag.players && currentTag.players[0] && div.dataset.side !== currentTag.players[0].side) div.style.display = 'flex';
        }
      }
    });
  }

  async function playerClickHandler(e) {
    e.stopPropagation();
    e.preventDefault();
    if (!currentTag) return;
    const div = e.currentTarget;
    if (div.style.display === 'none') return;
    const pid = div.dataset.playerId;
    const side = div.dataset.side;

    if (tagStep === 'awaiting_player_1') {
      currentTag.players = [ { id: pid, side } ];
      highlightSelectedPlayers();

      const req = determinePlayerRequirements(currentTag.event, currentTag.outcome);
      if (req.count === 1) {
        tagStep = 'ready';
        // if the event is Pass, require PASS END click before save (including unsuccessful)
        if (currentTag.event === 'Pass') {
          tagStep = 'awaiting_end';
          updateInfoText('Pass: click the PITCH to set PASS END location (second click).');
          // enable pitch clicks for end selection
          if (pitchImg) pitchImg.style.pointerEvents = 'auto';
          // hide players while selecting end
          hideAllPlayers();
          if (saveTagBtn) saveTagBtn.classList.add('hidden');
          if (cancelTagBtn) cancelTagBtn.classList.remove('hidden');
          showPassEndTooltip();
          return;
        }
        updateInfoText('Ready to save. Click Save Tag.');
        if (saveTagBtn) saveTagBtn.classList.remove('hidden');
      } else {
        tagStep = 'awaiting_player_2';
        updateInfoText('Click the SECOND player for this event.');
        showRelevantPlayers();
      }
    } else if (tagStep === 'awaiting_player_2') {
      currentTag.players.push({ id: pid, side });
      highlightSelectedPlayers();

      // now either require PASS END (for Pass) or mark ready
      if (currentTag.event === 'Pass' && !currentTag.end) {
        tagStep = 'awaiting_end';
        updateInfoText('Pass: click the PITCH to set PASS END location (second click).');
        if (pitchImg) pitchImg.style.pointerEvents = 'auto';
        hideAllPlayers();
        if (saveTagBtn) saveTagBtn.classList.add('hidden');
        if (cancelTagBtn) cancelTagBtn.classList.remove('hidden');
        showPassEndTooltip();
        return;
      }

      tagStep = 'ready';
      updateInfoText('Ready to save. Click Save Tag.');
      if (saveTagBtn) saveTagBtn.classList.remove('hidden');
    }
  }

  function highlightSelectedPlayers() {
    Object.values(playersMap).forEach(el => el.style.outline = 'none');
    if (!currentTag || !currentTag.players) return;
    currentTag.players.forEach(p => {
      const el = playersMap[p.id];
      if (el) el.style.outline = '3px solid rgba(255,255,255,0.8)';
    });
  }

  function setActionButtonsEnabled(enabled) {
    tagButtons.forEach(btn => {
      btn.disabled = !enabled;
      btn.style.opacity = enabled ? '1' : '0.45';
      btn.classList.toggle('cursor-not-allowed', !enabled);
    });
  }

  function selectButtonInGroup(buttonText, groupEl) {
    if (!groupEl) return;
    groupEl.querySelectorAll('button').forEach(btn => {
      if (btn.innerText.trim() === buttonText) {
        btn.classList.add('selected');
      } else {
        btn.classList.remove('selected');
      }
    });
  }

  // New helper: enable/disable keypass buttons (and visual state)
  function setKeyPassButtonsEnabled(enabled) {
    keyPassButtons.forEach(b => {
      b.disabled = !enabled;
      b.style.opacity = enabled ? '1' : '0.45';
      b.classList.toggle('cursor-not-allowed', !enabled);
    });
  }

  // New helper: select key pass value (true => yes, false => no)
  function selectKeyPass(isKey) {
    if (keyPassYesBtn) {
      if (isKey) {
        keyPassYesBtn.classList.add('selected');
        keyPassYesBtn.dataset.selected = 'true';
      } else {
        keyPassYesBtn.classList.remove('selected');
        keyPassYesBtn.dataset.selected = 'false';
      }
    }
    if (keyPassNoBtn) {
      if (!isKey) {
        keyPassNoBtn.classList.add('selected');
        keyPassNoBtn.dataset.selected = 'true';
      } else {
        keyPassNoBtn.classList.remove('selected');
        keyPassNoBtn.dataset.selected = 'false';
      }
    }
    // Persist to currentTag...
    if (currentTag && currentTag.event === 'Pass') {
      if (!currentTag.additional) currentTag.additional = {};
      currentTag.additional.key_pass = !!isKey;
    }
  }

  function setTypeOutcomeGroupsActive(activeGroups = []) {
    [passTypeGroup, shotTypeGroup, duelTypeGroup, passOutcomeGroup, shotOutcomeGroup, duelOutcomeGroup, penaltyOutcomeGroup, outcomeGroup].forEach(g => {
      if (!g) return;
      g.querySelectorAll('button').forEach(b => {
        b.disabled = true;
        b.style.opacity = 0.45;
        b.classList.add('cursor-not-allowed');
      });
    });
    // also disable keypass by default; will be enabled if Pass is active
    setKeyPassButtonsEnabled(false);

    activeGroups.forEach(gEl => {
      if (!gEl) return;
      gEl.querySelectorAll('button').forEach(b => {
        b.disabled = false;
        b.style.opacity = 1;
        b.classList.remove('cursor-not-allowed');
      });
    });
  }

  function activateTypeOutcomeButtonsFor(eventType) {
    setTypeOutcomeGroupsActive([]);
    if (eventType === 'Pass') {
      setTypeOutcomeGroupsActive([passTypeGroup, passOutcomeGroup]);
      setKeyPassButtonsEnabled(true); // <-- enable key pass controls for Pass
    }
    else if (eventType === 'Shot') setTypeOutcomeGroupsActive([shotTypeGroup, shotOutcomeGroup]);
    else if (eventType === 'Duel') setTypeOutcomeGroupsActive([duelTypeGroup, duelOutcomeGroup]);
    else if (eventType === 'Penalty') setTypeOutcomeGroupsActive([penaltyOutcomeGroup]);
    else if (eventType === 'Dribble' || eventType === 'Tackle') setTypeOutcomeGroupsActive([outcomeGroup]);
    else setTypeOutcomeGroupsActive([]);
  }

  // Begin a tag: wait for origin first (but skip origin for certain events)
  function beginTag(eventType, teamSide = null) {
    if (mode !== 'tagging') {
      alert('Click Start Tagging to enter tagging mode');
      return;
    }
    // ensure kickoff has been selected
    if (!kickoffVideoTime) {
      updateInfoText('Choose which team kicks off first (click a Kickoff button).');
      return;
    }

    // events which do NOT require an origin (we still may require players or type/outcome)
    const eventsWithoutOrigin = [
      'Throw In', 'Corner', 'Penalty', 'Yellow Card', 'Red Card'
    ];

    currentTag = {
      event: eventType,
      origin: null,
      end: null,            // new: pass end coordinates
      players: [],
      type: null,
      outcome: null,
      additional: {},
      team_side: teamSide
    };

    // Ensure no lingering selected classes
    document.querySelectorAll('button.selected').forEach(btn => btn.classList.remove('selected'));

    // Make Key Pass default to NO for Pass tags (user requirement)
    if (eventType === 'Pass') {
      currentTag.additional.key_pass = false;
      selectKeyPass(false);
    } else {
      // ensure UI shows keypass disabled when not Pass
      selectKeyPass(false);
      setKeyPassButtonsEnabled(false);
    }

    // common UI preparations
    setActionButtonsEnabled(false);
    setTypeOutcomeGroupsActive([]);
    removeTempMarkers();
    hidePassEndTooltip();
    if (cancelTagBtn) cancelTagBtn.classList.remove('hidden');
    if (saveTagBtn) saveTagBtn.classList.add('hidden');

    // If this event doesn't require origin, skip origin step and move to next
    if (eventsWithoutOrigin.includes(eventType)) {
      // If type/outcome needed, begin with type selection
      if (requiresType(currentTag.event) || requiresOutcome(currentTag.event)) {
        tagStep = 'awaiting_type';
        updateInfoText(`Tagging ${eventType} (${teamSide || 'unknown side'}): Select action type (if applicable), then choose outcome.`);
        activateTypeOutcomeButtonsFor(currentTag.event);
        // Don't allow pitch clicks (no origin)
        if (pitchImg) pitchImg.style.pointerEvents = 'none';
      } else {
        // Otherwise prompt for the first player directly
        tagStep = 'awaiting_player_1';
        updateInfoText(`Tagging ${eventType} (${teamSide || 'unknown side'}): Click the player involved (first player).`);
        // prepare and show players
        Object.values(playersMap).forEach(div => {
          try { div.removeEventListener('click', playerClickHandler); } catch(e) {}
          div.addEventListener('click', playerClickHandler);
          div.style.cursor = 'pointer';
          div.style.zIndex = 1001;
          div.style.outline = 'none';
          div.style.display = 'none';
        });
        showRelevantPlayers();
        if (pitchImg) pitchImg.style.pointerEvents = 'none';
      }
      return;
    }

    // Default flow: await origin (for most events)
    tagStep = 'awaiting_origin';
    setTypeOutcomeGroupsActive([]);
    updateInfoText(`Tagging ${eventType} (${teamSide || 'unknown side'}): Click the pitch once to set origin (x,y).`);
    // allow origin click
    if (pitchImg) pitchImg.style.pointerEvents = 'auto';
  }

  // Origin selected -> now prompt type/outcome (if required) or go directly to players
  function originSelected(p) {
    currentTag.origin = p;
    showTempMarker(p);
    // remove any previous pass line (we'll draw once end is set)
    removePassLine();

    // prepare players for selection
    Object.values(playersMap).forEach(div => {
      if (div._dragCleanup) { try { div._dragCleanup(); } catch (e) {} delete div._dragCleanup; }
      try { div.removeEventListener('click', playerClickHandler); } catch(e) {}
      div.addEventListener('click', playerClickHandler);
      div.style.cursor = 'pointer';
      div.style.zIndex = 1001;
      div.style.outline = 'none';
      div.style.display = 'none';
    });

    if (requiresType(currentTag.event) || requiresOutcome(currentTag.event)) {
      tagStep = 'awaiting_type';
      updateInfoText('Select action type (if applicable), then choose outcome.');
      activateTypeOutcomeButtonsFor(currentTag.event);
      if (pitchImg) pitchImg.style.pointerEvents = 'none';
    } else {
      tagStep = 'awaiting_player_1';
      updateInfoText('Now click the player involved (first player).');
      showRelevantPlayers();
      if (pitchImg) pitchImg.style.pointerEvents = 'none';
    }
  }

  function chooseType(t) {
    if (!currentTag) return;
    currentTag.type = t;
    updateInfoText(`Type selected: ${t}. Now choose outcome (if required).`);
    // Find the active group and select
    const activeTypeGroups = [passTypeGroup, shotTypeGroup, duelTypeGroup].filter(g => g && !g.querySelector('button:disabled'));
    activeTypeGroups.forEach(group => selectButtonInGroup(t, group));
    if (!requiresOutcome(currentTag.event)) {
      tagStep = 'awaiting_player_1';
      updateInfoText('Now click the player involved (first player).');
      showRelevantPlayers();
      if (pitchImg) pitchImg.style.pointerEvents = 'none';
    }
  }

  function chooseOutcome(o) {
    if (!currentTag) return;
    currentTag.outcome = o;
    updateInfoText(`Outcome selected: ${o}.`);
    // Find the active group and select
    const activeOutcomeGroups = [passOutcomeGroup, shotOutcomeGroup, duelOutcomeGroup, penaltyOutcomeGroup, outcomeGroup].filter(g => g && !g.querySelector('button:disabled'));
    activeOutcomeGroups.forEach(group => selectButtonInGroup(o, group));
    tagStep = 'awaiting_player_1';
    updateInfoText('Now click the player involved (first player).');
    showRelevantPlayers();
    if (pitchImg) pitchImg.style.pointerEvents = 'none';
  }

  // Build and POST event
  async function commitTag() {
    if (!currentTag) return;

    // events which DO NOT require origin and should NOT store origin coords
    const eventsWithoutOrigin = [
      'Throw In', 'Corner', 'Penalty', 'Yellow Card', 'Red Card'
    ];

    // Only require origin when the event is not in eventsWithoutOrigin
    if (!eventsWithoutOrigin.includes(currentTag.event) && !currentTag.origin) {
      alert('Origin missing');
      return;
    }

    // If Pass requires end -> block until end provided (this enforces PASS has end for all pass outcomes, including unsuccessful)
    if (currentTag.event === 'Pass' && !currentTag.end) {
      alert('This Pass requires you to set the pass END on the pitch before saving.');
      return;
    }

    const required = determinePlayerRequirements(currentTag.event, currentTag.outcome);
    if (!currentTag.players || currentTag.players.length < required.count) {
      alert(`This event requires ${required.count} player(s). Please select ${required.count} player(s).`);
      return;
    }

    const videoTs = Number(video.currentTime);
    const matchSeconds = computeMatchSeconds(videoTs); // whole seconds

    const getPlayerObj = id => {
      for (const s of ['home','away']) {
        if (!config[s]) continue;
        const p = config[s].starting11.find(x => x.id === id);
        if (p) return { ...p, side: s };
      }
      return null;
    };

    let actor = null, secondary = null;
    if (currentTag.players.length === 1) actor = getPlayerObj(currentTag.players[0].id);
    else if (currentTag.players.length >= 2) {
      const p1 = getPlayerObj(currentTag.players[0].id);
      const p2 = getPlayerObj(currentTag.players[1].id);
      actor = p1;
      secondary = p2;
    }

    let teamSideFromButton = currentTag.team_side;
    const singleTeamEvents = [
      'Dribble','Interception','Recovery','Tackle','Clearance',
      'Possession Loss','Offside','Foul','Yellow Card','Red Card',
      'Throw In','Corner','Free Kick','Penalty'
    ];
    if (singleTeamEvents.includes(currentTag.event)) teamSideFromButton = currentTag.team_side;
    const resolvedTeamSide = actor ? actor.side : (teamSideFromButton || selectedTeam);
    const resolvedTeamName = resolvedTeamSide === 'home' ? (config.home && config.home.name ? config.home.name : 'home') : (config.away && config.away.name ? config.away.name : 'away');

    // Decide origin coords: null for eventsWithoutOrigin, otherwise use currentTag.origin
    const originX = eventsWithoutOrigin.includes(currentTag.event) ? null : (currentTag.origin ? currentTag.origin.x : null);
    const originY = eventsWithoutOrigin.includes(currentTag.event) ? null : (currentTag.origin ? currentTag.origin.y : null);

    const eventObj = {
      match_id: (config && config.match) ? config.match.id : '',
      team: resolvedTeamName,
      team_side: resolvedTeamSide,
      event: currentTag.event,
      player_id: actor ? actor.id : null,
      player_name: actor ? actor.name : null,
      player_number: actor ? actor.number : null,
      player_position: actor ? actor.position : null,
      // store whole seconds per your instruction (field name kept 'match_time_minute')
      match_time_minute: matchSeconds,
      half_period: half,
      video_timestamp: videoTs,
      origin_x: originX,
      origin_y: originY,
      outcome: currentTag.outcome || null,
      type: currentTag.type || null,
      additional: currentTag.additional || {}
    };

    // 1. Get necessary variables
    const attackDir = config && config.match.attacking_direction ? String(config.match.attacking_direction).toLowerCase() : null;
    const team = resolvedTeamSide; // 'home' or 'away'
    const currentHalf = half;      // '1', '2', etc.

    let finalTeamSide = '';

    if (attackDir === 'left-to-right') {
        if (team === 'home') {
            // Home attacks: L->R (Half 1) = left side (starts on the left of pitch)
            // Home attacks: R->L (Half 2) = right side
            finalTeamSide = (currentHalf === 1) ? 'left' : 'right';
        } else if (team === 'away') {
            // Away attacks: R->L (Half 1) = right side
            // Away attacks: L->R (Half 2) = left side
            finalTeamSide = (currentHalf === 1) ? 'right' : 'left';
        }
    } else {
        // Assume default direction is 'right-to-left' if config is not L->R
        if (team === 'home') {
            // Home attacks: R->L (Half 1) = right side (starts on the right of pitch)
            // Home attacks: L->R (Half 2) = left side
            finalTeamSide = (currentHalf === 1) ? 'right' : 'left';
        } else if (team === 'away') {
            // Away attacks: L->R (Half 1) = left side
            // Away attacks: R->L (Half 2) = right side
            finalTeamSide = (currentHalf === 1) ? 'left' : 'right';
        }
    }
    
    // 2. Set the final value
    eventObj.team_side = finalTeamSide;

    if (secondary) {
      eventObj.additional.secondary_player = {
        id: secondary.id,
        name: secondary.name,
        number: secondary.number,
        position: secondary.position,
        side: secondary.side
      };
    }
    // pass-specific additional fields
    if (currentTag.event === 'Pass') {
      if (currentTag.type) eventObj.additional.pass_type = currentTag.type;
      if (secondary) eventObj.additional.receiver_id = secondary.id;
      if (currentTag.end) {
        // store pass end coordinates inside additional as requested
        eventObj.additional.pass_end_x = currentTag.end.x;
        eventObj.additional.pass_end_y = currentTag.end.y;
      }
      // store key_pass into additional
      if (typeof currentTag.additional?.key_pass !== 'undefined') {
        // boolean true/false
        eventObj.additional.key_pass = !!currentTag.additional.key_pass;
      } else {
        // default false if missing
        eventObj.additional.key_pass = false;
      }
    }
    if (currentTag.event === 'Shot' && currentTag.type) eventObj.additional.shot_type = currentTag.type;

        // --- Attach canonical opposite goalkeeper when appropriate (fixed opponent side resolution) ---
    (function attachOppositeGK() {
      const evt = String(currentTag.event || '').toLowerCase();
      const outcome = String(currentTag.outcome || '').toLowerCase();
      const shotMatches = (evt === 'shot' && (outcome === 'on target' || outcome === 'goal'));
      const penMatches  = (evt === 'penalty' && (outcome === 'goal' || outcome === 'saved'));
      if (!shotMatches && !penMatches) return;

      if (!eventObj.additional) eventObj.additional = {};

      // ---- CORRECT: determine the logical team key (home/away) ----
      // prefer canonical resolvedTeamSide (set earlier in commitTag), fall back to actor/team button/selectedTeam or eventObj.team matching config
      let ourTeamKey = null;
      if (typeof resolvedTeamSide !== 'undefined' && (resolvedTeamSide === 'home' || resolvedTeamSide === 'away')) {
        ourTeamKey = resolvedTeamSide;
      } else if (actor && (actor.side === 'home' || actor.side === 'away')) {
        ourTeamKey = actor.side;
      } else if (typeof teamSideFromButton !== 'undefined' && (teamSideFromButton === 'home' || teamSideFromButton === 'away')) {
        ourTeamKey = teamSideFromButton;
      } else if (config && config.home && config.home.name && eventObj.team === config.home.name) {
        ourTeamKey = 'home';
      } else {
        // final fallback: assume 'home' (safer to pick a deterministic default)
        ourTeamKey = 'home';
      }

      const opponentSide = ourTeamKey === 'home' ? 'away' : 'home';

      const isGoalkeeper = p => {
        if (!p) return false;
        const pos = String(p.position || p.role || p.type || '').toLowerCase();
        if (!pos) return false;
        if (pos === 'gk' || pos === 'goalkeeper') return true;
        if (pos.indexOf('goal') !== -1) return true;
        return false;
      };

      let resolvedGK = null;

      // 1) Try reconstructing on-pitch opponent starting XI from eventsList up to tag time
      (function tryFromEventsList() {
        if (!Array.isArray(eventsList) || !config || !config.match) return null;
        const cutoff = typeof matchSeconds === 'number' ? matchSeconds : Number(eventObj.match_time_minute || 0);
        const teamCfg = (config && config[opponentSide]) ? config[opponentSide] : null;
        if (!teamCfg || !Array.isArray(teamCfg.starting11)) return null;

        const currentStarting = teamCfg.starting11.map(p => ({ ...(p || {}), side: opponentSide }));

        const relevantEvents = eventsList
          .filter(e => e && String(e.match_id) === String((config.match && config.match.id) || '') && e.team_side === opponentSide)
          .filter(e => typeof e.match_time_minute !== 'undefined' && Number(e.match_time_minute) <= cutoff)
          .sort((a,b) => Number(a.match_time_minute || 0) - Number(b.match_time_minute || 0));

        for (const ev of relevantEvents) {
          const evName = String(ev.event || '').toLowerCase();
          if (evName === 'substitution') {
            const outId = (typeof ev.player_id !== 'undefined' && ev.player_id !== null) ? String(ev.player_id) : null;
            const outName = ev.player_name ? String(ev.player_name) : null;

            let inPlayer = null;
            if (ev.additional && ev.additional.secondary_player) {
              const s = ev.additional.secondary_player;
              inPlayer = {
                id: s.id ?? s.player_id ?? null,
                name: s.name ?? null,
                number: s.number ?? null,
                position: s.position ?? s.role ?? null,
                side: opponentSide
              };
            } else if (ev.secondary_player) {
              const s = ev.secondary_player;
              inPlayer = {
                id: s.id ?? null,
                name: s.name ?? null,
                number: s.number ?? null,
                position: s.position ?? s.role ?? null,
                side: opponentSide
              };
            }

            let outIndex = -1;
            if (outId) outIndex = currentStarting.findIndex(p => String(p.id) === String(outId));
            if (outIndex === -1 && outName) outIndex = currentStarting.findIndex(p => String(p.name || '').toLowerCase() === outName.toLowerCase());

            if (outIndex !== -1 && inPlayer) {
              currentStarting[outIndex] = inPlayer;
            } else if (inPlayer) {
              currentStarting.push(inPlayer);
            }
          }
        }

        const gk = currentStarting.find(isGoalkeeper);
        if (gk) {
          resolvedGK = { id: gk.id ?? null, name: gk.name ?? null, number: gk.number ?? null, position: gk.position ?? gk.role ?? null, side: opponentSide };
        }
        return resolvedGK;
      })();

      // 2) Fallback to config lookup if not found in eventsList
      if (!resolvedGK) {
        const opponentTeamData = (typeof getTeamData === 'function') ? getTeamData(opponentSide) : null;
        const teamCfg = (config && config[opponentSide]) ? config[opponentSide] : null;

        let candidates = [];
        if (teamCfg && Array.isArray(teamCfg.starting11)) candidates = candidates.concat(teamCfg.starting11);
        if (opponentTeamData && Array.isArray(opponentTeamData.bench)) candidates = candidates.concat(opponentTeamData.bench);
        if (teamCfg && Array.isArray(teamCfg.players)) candidates = candidates.concat(teamCfg.players);

        const seen = new Set();
        const uniq = [];
        for (const p of candidates) {
          if (!p) continue;
          const key = (typeof p.id !== 'undefined' && p.id !== null) ? String(p.id) : (p.name ? ('name:' + String(p.name)) : null);
          if (!key) continue;
          if (!seen.has(key)) { seen.add(key); uniq.push(p); }
        }

        const gk = uniq.find(isGoalkeeper);
        if (gk) {
          resolvedGK = { id: gk.id ?? null, name: gk.name ?? null, number: gk.number ?? null, position: gk.position ?? gk.role ?? null, side: opponentSide };
        }
      }

      // 3) Always attach an opponent_goalkeeper object (explicit 'not_found' when unresolved)
      if (resolvedGK) {
        eventObj.additional.opponent_goalkeeper = resolvedGK;
      } else {
        eventObj.additional.opponent_goalkeeper = { id: null, name: null, number: null, position: null, side: opponentSide };
      }
    })();


    const resp = await fetch(endpoints.saveEvent, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ match_id: matchId, event: eventObj })
    });
    const data = await resp.json();
    if (data && data.success) {
      eventsList.push(data.event);
      updateInfoText('Event saved.');
      if (saveTagBtn) saveTagBtn.classList.add('hidden');
      if (cancelTagBtn) cancelTagBtn.classList.add('hidden');
      hideAllPlayers();
      if (pitchImg) pitchImg.style.pointerEvents = 'auto';
      currentTag = null;
      tagStep = 'idle';
      setActionButtonsEnabled(true);
      setTypeOutcomeGroupsActive([]);
      removeTempMarkers();
      await loadEvents();
    } else {
      alert('Failed to save tag: ' + (data && data.message ? data.message : 'unknown'));
    }
  }

  function cancelCurrentTag() {
    currentTag = null;
    tagStep = 'idle';
    updateInfoText('Tag cancelled. Mode: Tagging.');
    if (saveTagBtn) saveTagBtn.classList.add('hidden');
    if (cancelTagBtn) cancelTagBtn.classList.add('hidden');
    hideAllPlayers();
    if (pitchImg) pitchImg.style.pointerEvents = 'auto';
    setActionButtonsEnabled(true);
    setTypeOutcomeGroupsActive([]);
    removeTempMarkers();
    // reset keypass visual to default NO and disable until next Pass tag
    selectKeyPass(false);
    setKeyPassButtonsEnabled(false);
  }

  // --- Instant "Out of Play" buttons (no team, no player) ---
  const outOfPlayBtn = document.getElementById('tag-out-off-play-btn');
  const outOfPlayBtn2 = document.getElementById('tag-out-off-play-btn-2');

  async function handleOutOfPlayEvent() {
    if (!config || !config.match) {
      alert('Match config not loaded yet.');
      return;
    }
    if (!video) {
      alert('Video element not found.');
      return;
    }

    const videoTs = Number(video.currentTime);
    const matchSeconds = computeMatchSeconds(videoTs); // whole seconds (your convention)
    const halfPeriod = half || 1;

    const eventObj = {
      match_id: (config && config.match) ? config.match.id : '',
      team: null,                // intentionally no team linked
      team_side: null,
      event: 'Out of Play',
      player_id: null,
      player_name: null,
      player_number: null,
      player_position: null,
      match_time_minute: matchSeconds,
      half_period: halfPeriod,
      video_timestamp: videoTs,
      origin_x: null,
      origin_y: null,
      outcome: null,
      type: null,
      additional: {}
    };

    try {
      const resp = await fetch(endpoints.saveEvent, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ match_id: matchId, event: eventObj })
      });
      const data = await resp.json();
      if (data && data.success) {
        eventsList.push(data.event);
        updateInfoText('Out of Play event saved.');
        // Mirror commitTag cleanup so UI stays consistent
        if (saveTagBtn) saveTagBtn.classList.add('hidden');
        if (cancelTagBtn) cancelTagBtn.classList.add('hidden');
        hideAllPlayers();
        if (pitchImg) pitchImg.style.pointerEvents = 'auto';
        currentTag = null;
        tagStep = 'idle';
        setActionButtonsEnabled(true);
        setTypeOutcomeGroupsActive([]);
        removeTempMarkers();
        await loadEvents();
      } else {
        alert('Failed to save Out of Play: ' + (data && data.message ? data.message : 'unknown'));
      }
    } catch (err) {
      console.error('Save event error', err);
      alert('Failed to save Out of Play: ' + (err && err.message ? err.message : err));
    }
  }

  if (outOfPlayBtn) outOfPlayBtn.addEventListener('click', handleOutOfPlayEvent);
  if (outOfPlayBtn2) outOfPlayBtn2.addEventListener('click', handleOutOfPlayEvent);

  // Determine whether an event should count as a goal.
  function isGoalEvent(ev) {
    if (!ev) return false;
    const name = (ev.event || '').toString().trim().toLowerCase();
    // Penalty event
    if (name === 'penalty') {
      const out = (ev.outcome || '').toString().toLowerCase();
      if (out.includes('goal')) return true;
    }

    // Shot resulting in a goal (common case)
    if (name === 'shot') {
      const out = (ev.outcome || '').toString().toLowerCase();
      if (out.includes('goal')) return true;
      // sometimes backends store flags in additional
      if (ev.additional && (ev.additional.is_goal === true || ev.additional.goal === true)) return true;
    }

    // Some systems may keep goal marker in additional.event/additional.is_goal
    if (ev.additional) {
      const addEvent = (ev.additional.event || '').toString().toLowerCase();
      if (addEvent === 'goal') return true;
      if (ev.additional.is_goal === true) return true;
    }

    return false;
  }

  // Count goals for home/away and update #home-score / #away-score
  function updateScoreboard(ev) {
    let homeScore = 0;
    let awayScore = 0;
    let homeScoreVid = 0;
    let awayScoreVid = 0;
    if (!Array.isArray(eventsList)) eventsList = [];

    const homeName = config && config.home && config.home.name ? config.home.name.toString().toLowerCase() : null;
    const awayName = config && config.away && config.away.name ? config.away.name.toString().toLowerCase() : null;

    eventsList.forEach(ev => {
      if (!isGoalEvent(ev)) return;

      let side = ev.team_side || null;

      // fallback: match team name to home/away (trigger even if side is 'left'/'right')
      if ((side !== 'home' && side !== 'away') && ev.team) {
          const t = ev.team.toString().toLowerCase();
          if (homeName && t === homeName) side = 'home';
          else if (awayName && t === awayName) side = 'away';
      }

      // fallback: check player id against config starting11
      if (!side && ev.player_id) {
        for (const s of ['home','away']) {
          if (!config || !config[s] || !Array.isArray(config[s].starting11)) continue;
          const found = config[s].starting11.find(p => String(p.id) === String(ev.player_id));
          if (found) { side = s; break; }
        }
      }

      if (side === 'home') homeScore++;
      else if (side === 'away') awayScore++;
      else {
        // unknown side -> ignore (or log if you want)
        // console.warn('Goal event with unknown side:', ev);
      }

      if (side === 'home') homeScoreVid++;
      else if (side === 'away') awayScoreVid++;
      else {
        // unknown side -> ignore (or log if you want)
        // console.warn('Goal event with unknown side:', ev);
      }
    });

    const homeEl = document.getElementById('home-score');
    const awayEl = document.getElementById('away-score');
    const homeElVid = document.getElementById('home-score-video');
    const awayElVid = document.getElementById('away-score-video');
    if (homeEl) homeEl.innerText = String(homeScore);
    if (awayEl) awayEl.innerText = String(awayScore);
    if (homeElVid) homeElVid.innerText = String(homeScoreVid);
    if (awayElVid) awayElVid.innerText = String(awayScoreVid);
  }

  async function loadEvents() {
    // endpoints.getEvents is a function now
    const res = await fetchJSON(endpoints.getEvents(matchId));
    eventsList = res.events || [];
    if (eventsList.length > 0) showEventCard(eventsList[eventsList.length - 1]);

    updateScoreboard();
  }

  function showTeamNameScoreboard(ev) {
    const homeNameEl = document.getElementById('home-name');
    const awayNameEl = document.getElementById('away-name');
    const homeNameElVid = document.getElementById('home-name-video');
    const awayNameElVid = document.getElementById('away-name-video');

    if (!homeNameEl || !awayNameEl) {
      console.warn('Scoreboard elements not found (#home-name or #away-name)');
      return;
    }

    // Use config if ev is not passed
    const homeTeam = ev?.home?.name || (config && config.home && config.home.name) || 'Home';
    const awayTeam = ev?.away?.name || (config && config.away && config.away.name) || 'Away';
    const homeNameVid = ev?.home?.name || (config && config.home && config.home.name) || 'Home';
    const awayNameVid = ev?.away?.name || (config && config.away && config.away.name) || 'Away';

    homeNameEl.innerText = homeTeam;
    awayNameEl.innerText = awayTeam;
    homeNameElVid.innerText = homeNameVid;
    awayNameElVid.innerText = awayNameVid;
  }

  function showEventCard(ev) {
    const el = id => document.getElementById(id);
    if (!ev) return;
    if (el('id')) el('id').innerText = ev.id || '';
    if (el('match')) el('match').innerText = ev.match_id || (config && config.match ? config.match.id : '');
    if (el('team')) el('team').innerText = ev.team || '';
    if (el('event')) el('event').innerText = ev.event || '';
    if (el('player')) el('player').innerText = (ev.player_name || '') + ' #' + (ev.player_number || '');
    // display match time as mm:ss
    if (el('match-time')) {
      if (ev.match_time_minute !== undefined && ev.match_time_minute !== null) {
        const seconds = Number(ev.match_time_minute) || 0;
        el('match-time').innerText = formatSecondsToMMSS(seconds);
      } else el('match-time').innerText = '';
    }
    if (el('half-period')) {
      if (ev.half_period == 1) el('half-period').innerText = ev.half_period + 'st half' || '';
      else el('half-period').innerText = ev.half_period + 'nd half' || '';
    }
    if (el('video-timestamp')) el('video-timestamp').innerText = (ev.video_timestamp !== undefined ? Number(ev.video_timestamp).toFixed(2) : '');
    if (el('origin-x')) el('origin-x').innerText = ev.origin_x !== undefined ? ev.origin_x : '';
    if (el('origin-y')) el('origin-y').innerText = ev.origin_y !== undefined ? ev.origin_y : '';
    // show pass end when present
    // (if you want dedicated UI fields for pass_end_x/pass_end_y add them to your markup and set here)
    if (el('outcome')) el('outcome').innerText = ev.outcome || '';
  }

  // pitch click wiring
  if (pitchContainer) {
    pitchContainer.addEventListener('click', (ev) => {
      const p = pitchClickToGrid(ev.clientX, ev.clientY);
      if (mode === 'setup' && !positionsLocked) {
        showTempMarker(p);
        return;
      }
      // setting origin
      if (mode === 'tagging' && tagStep === 'awaiting_origin') {
        currentTag.origin = p;
        showTempMarker(p);
        originSelected(p);
        return;
      }
      // new: setting pass END
      if (mode === 'tagging' && tagStep === 'awaiting_end') {
        currentTag.end = p;
        showEndMarker(p);
        // after end set, we are ready to save
        tagStep = 'ready';
        updateInfoText('Pass END set. Ready to save. Click Save Tag.');
        if (saveTagBtn) saveTagBtn.classList.remove('hidden');
        // disable pitch clicks now to avoid extra origin/end changes
        if (pitchImg) pitchImg.style.pointerEvents = 'none';
        hidePassEndTooltip();
        return;
      }
    });
  }

  // action buttons beginTag wiring (but action buttons are only enabled after kickoff)
  tagButtons.forEach(btn => {
    btn.dataset.event = btn.dataset.event || btn.innerText.trim();
    btn.addEventListener('click', (e) => {
      if (btn.disabled) return;
      const eventName = btn.dataset.event || btn.innerText.trim();
      const teamSide = btn.dataset.side || null;
      beginTag(eventName, teamSide);
    });
  });

  function wireGroupButtons(groupEl, handler) {
    if (!groupEl) return;
    groupEl.querySelectorAll('button').forEach(b => {
      b.addEventListener('click', (e) => {
        const txt = e.currentTarget.innerText.trim();
        handler(txt);
      });
    });
  }
  wireGroupButtons(passTypeGroup, (txt) => { chooseType(txt); });
  wireGroupButtons(shotTypeGroup, (txt) => { chooseType(txt); });
  wireGroupButtons(duelTypeGroup, (txt) => { chooseType(txt); });

  wireGroupButtons(passOutcomeGroup, (txt) => { chooseOutcome(txt); });
  wireGroupButtons(shotOutcomeGroup, (txt) => { chooseOutcome(txt); });
  wireGroupButtons(duelOutcomeGroup, (txt) => { chooseOutcome(txt); });
  wireGroupButtons(penaltyOutcomeGroup, (txt) => { chooseOutcome(txt); });
  wireGroupButtons(outcomeGroup, (txt) => { chooseOutcome(txt); });

  // wire key-pass buttons (NEW)
  if (keyPassYesBtn) {
    keyPassYesBtn.addEventListener('click', (e) => {
      if (keyPassYesBtn.disabled) return;
      selectKeyPass(true);
    });
  }
  if (keyPassNoBtn) {
    keyPassNoBtn.addEventListener('click', (e) => {
      if (keyPassNoBtn.disabled) return;
      selectKeyPass(false);
    });
  }

  if (cancelTagBtn) cancelTagBtn.addEventListener('click', cancelCurrentTag);
  if (saveTagBtn) saveTagBtn.addEventListener('click', commitTag);

  window.addEventListener('keydown', async (e) => {
    if (e.key === 'z' || e.key === 'Z') {
      if (!confirm('Undo most recent tag?')) return;
      const res = await fetch(endpoints.undoEvent, { 
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ match_id: matchId }) 
      });
      const data = await res.json();
      if (data && data.success) {
        await loadEvents();
        alert('Undid last event.');
      } else alert('Nothing to undo.');
    }
  });

  // Helper to toggle the config direction (for reference & saving)
  function toggleAttackingDirection() {
    if (String(config.match.attacking_direction).toLowerCase() === 'left-to-right') {
      config.match.attacking_direction = 'right-to-left';
    } else {
      config.match.attacking_direction = 'left-to-right';
    }
    // Update flipHorizontal to match
    flipHorizontal = !!(
      config.match.attacking_direction &&
      String(config.match.attacking_direction).toLowerCase() === 'left-to-right'
    );
    console.log('Attacking direction toggled to:', config.match.attacking_direction);
  }

  // Unified match-event handler that saves Kickoff / Half-Time / Full-Time events
  async function handleMatchEvent(eventName, side, options = {}) {
    if (!config || !config.match) {
      alert('Match config not loaded yet.');
      return;
    }
    if (!video) {
      alert('Video element not found.');
      return;
    }

    const nowTs = Number(video.currentTime);
    let matchSeconds = 0;
    const requestedHalf = options.halfPeriod || undefined;
    let currentTeamSide = side || selectedTeam || null; // The team side ('home' or 'away') acting in the event.

    if (eventName === 'Kickoff') {
      // Kickoff: set selectedTeam to the supplied side (first half or second half).
      kickoffVideoTime = nowTs;
      selectedTeam = side;
      // half defaults to 1 unless an explicit halfPeriod is provided
      half = requestedHalf || 1;

      // When starting 2nd half we want matchSeconds to reflect the offset (e.g. 45*60).
      if (half === 1) {
        halfOffsetSeconds = 0;
        matchSeconds = 0;
      } else {
        // If halfOffsetSeconds already set (from Half-Time) keep it; otherwise use typical 45*60
        halfOffsetSeconds = halfOffsetSeconds || (45 * 60);
        matchSeconds = halfOffsetSeconds;
      }

      // Update the currentTeamSide variable to reflect the team that kicked off
      currentTeamSide = side;

      // hide kickoff UI, show events UI
      if (kickOffButtonsContainer) kickOffButtonsContainer.classList.add('hidden');
      if (taggingEventsButtonContainer) taggingEventsButtonContainer.classList.remove('hidden');
      updateInfoText(`Kickoff: ${side === 'home' ? 'Home' : 'Away'} kicked off (half ${half}). Tagging enabled.`);
      // enable tag buttons
      setActionButtonsEnabled(true);
      hideAllPlayers();

    } else if (eventName === 'Half Time') {
      // capture time of half-time (first half's elapsed total)
      if (kickoffVideoTime === null) {
        // if timer not running, attempt to compute using last known offsets
        matchSeconds = computeMatchSeconds(nowTs);
      } else {
        matchSeconds = computeMatchSeconds(nowTs);
      }
      // ensure stored half_period references the first half
      half = 1;
      currentTeamSide = selectedTeam || 'home'; // Assign a side for the payload, often last possession or home team
      // end timer for first half
      kickoffVideoTime = null;
      // store offset so second half can use a new kickoffVideoTime and keep overall seconds consistent
      halfOffsetSeconds = matchSeconds || (45 * 60);
      updateInfoText('Half Time recorded. Click Start 2nd Half when 2nd half kickoffs.');
      // show kickoff buttons again to allow starting 2nd half
      if (kickOffButtonsContainer) kickOffButtonsContainer.classList.add('hidden');

      // disable tagging while at half-time
      setActionButtonsEnabled(false);

    } else if (eventName === 'Full Time') {
      // capture final time (if timer not running, use halfOffsetSeconds)
      if (kickoffVideoTime === null) {
        matchSeconds = halfOffsetSeconds || 0;
      } else {
        matchSeconds = computeMatchSeconds(nowTs);
      }
      // full-time is the end of 2nd half
      half = 2;
      currentTeamSide = selectedTeam || 'home';
      // stop timer
      kickoffVideoTime = null;
      updateInfoText('Full Time recorded. Tagging disabled.');
      setActionButtonsEnabled(false);

    } else {
      // unknown event
      console.warn('Unhandled match event:', eventName);
      return;
    }

    // Build event payload
    const teamName = (side === 'home') ? (config.home && config.home.name ? config.home.name : 'home') :
                    (side === 'away') ? (config.away && config.away.name ? config.away.name : 'away') :
                    (selectedTeam === 'home' ? (config.home && config.home.name ? config.home.name : 'home') : (config.away && config.away.name ? config.away.name : 'away'));

    let finalTeamSide = currentTeamSide; // Default to 'home'/'away' for safety

    const attackDir = config && config.match.attacking_direction ? String(config.match.attacking_direction).toLowerCase() : null;
    const team = currentTeamSide; // 'home' or 'away'
    const currentHalf = half;    // 1 or 2

    if (attackDir === 'left-to-right') {
        if (team === 'home') {
            // Home attacks L->R in Half 1, so their side is 'left' (pitch origin)
            // Home attacks R->L in Half 2, so their side is 'right'
            finalTeamSide = (currentHalf === 1) ? 'left' : 'right';
        } else if (team === 'away') {
            // Away attacks R->L in Half 1, so their side is 'right'
            // Away attacks L->R in Half 2, so their side is 'left'
            finalTeamSide = (currentHalf === 1) ? 'right' : 'left';
        }
    } else {
        // Assume default direction is 'right-to-left' if config is not L->R
        if (team === 'home') {
            // Home attacks R->L in Half 1, so their side is 'right'
            // Home attacks L->R in Half 2, so their side is 'left'
            finalTeamSide = (currentHalf === 1) ? 'right' : 'left';
        } else if (team === 'away') {
            // Away attacks L->R in Half 1, so their side is 'left'
            // Away attacks R->L in Half 2, so their side is 'right'
            finalTeamSide = (currentHalf === 1) ? 'left' : 'right';
        }
    }

    const eventObj = {
      match_id: (config && config.match) ? config.match.id : '',
      team: teamName,
      team_side: finalTeamSide,
      event: eventName,
      player_id: null,
      player_name: null,
      player_number: null,
      player_position: null,
      match_time_minute: Math.max(0, Math.floor(matchSeconds || 0)), // whole seconds (your format)
      half_period: half,
      video_timestamp: nowTs,
      origin_x: null,
      origin_y: null,
      outcome: null,
      type: null,
      additional: {}
    };

    // POST to backend
    try {
      const resp = await fetch(endpoints.saveEvent, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ match_id: matchId, event: eventObj })
      });
      const data = await resp.json();
      if (data && data.success) {
        eventsList.push(data.event);
        updateInfoText(`${eventName} event saved.`);
        await loadEvents();
      } else {
        alert(`Failed to save ${eventName}: ` + (data && data.message ? data.message : 'unknown error'));
      }
    } catch (err) {
      console.error('Save event error', err);
      alert(`Failed to save ${eventName}: ${err.message || err}`);
    }
  }

  // wire the kickoff buttons to the unified handler
  if (kickOffHomeBtn) kickOffHomeBtn.addEventListener('click', () => handleMatchEvent('Kickoff', 'home'));
  if (kickOffAwayBtn) kickOffAwayBtn.addEventListener('click', () => handleMatchEvent('Kickoff', 'away'));

  // Basic half controls (end/start) - legacy / extra controls still available
  const halfTimeBtn = document.getElementById('half-time-btn');
  const kickOff2ndHalfBtn = document.getElementById('kick-off-2nd-half-btn');
  const fullTimeBtn = document.getElementById('full-time-btn');

  if (halfTimeBtn) {
    halfTimeBtn.addEventListener('click', () => {
      // Create a Half-Time event (and stop the running timer)
      handleMatchEvent('Half Time');
    });
  }
  if (kickOff2ndHalfBtn) {
    kickOff2ndHalfBtn.addEventListener('click', () => {
      // Determine opposite team of the team who kicked off the first half
      const opposite = selectedTeam ? (selectedTeam === 'home' ? 'away' : 'home') : 'away';
      // Set kickoff video time and offset (if not already set from half-time)
      kickoffVideoTime = Number(video.currentTime);
      halfOffsetSeconds = halfOffsetSeconds || (45 * 60);
      // Create Kickoff event for 2nd half (halfPeriod = 2)
      handleMatchEvent('Kickoff', opposite, { halfPeriod: 2 });
      updateInfoText('2nd half started. Tagging enabled.');
      setActionButtonsEnabled(true);
      if (kickOffButtonsContainer) kickOffButtonsContainer.classList.add('hidden');
      if (taggingEventsButtonContainer) taggingEventsButtonContainer.classList.remove('hidden');
      renderPlayers();
    });
  }
  if (fullTimeBtn) {
    fullTimeBtn.addEventListener('click', () => {
      // Create Full-Time event
      handleMatchEvent('Full Time');
      // after saving, ensure UI reflects end of 2nd half
      kickoffVideoTime = null;
      half = 2;
      // Reset attacking direction back to original (if you keep that behaviour)
      config.match.attacking_direction = originalDirection;
      flipHorizontal = !!(
        config.match.attacking_direction &&
        String(config.match.attacking_direction).toLowerCase() === 'left-to-right'
      );
      updateInfoText('2nd half ended (timer stopped).');
      setActionButtonsEnabled(false);
      renderPlayers();
    });
  }

  // wire up type/outcome groups (again)
  wireGroupButtons(passTypeGroup, (txt) => { chooseType(txt); });
  wireGroupButtons(shotTypeGroup, (txt) => { chooseType(txt); });
  wireGroupButtons(duelTypeGroup, (txt) => { chooseType(txt); });
  wireGroupButtons(passOutcomeGroup, (txt) => { chooseOutcome(txt); });
  wireGroupButtons(shotOutcomeGroup, (txt) => { chooseOutcome(txt); });
  wireGroupButtons(duelOutcomeGroup, (txt) => { chooseOutcome(txt); });
  wireGroupButtons(penaltyOutcomeGroup, (txt) => { chooseOutcome(txt); });
  wireGroupButtons(outcomeGroup, (txt) => { chooseOutcome(txt); });

  originalDirection = config.match.attacking_direction;
  flipHorizontal = !!(
    config.match.attacking_direction &&
    String(config.match.attacking_direction).toLowerCase() === 'left-to-right'
  );

  await loadEvents();

  // Reset all selected buttons
  document.querySelectorAll('#pass-type-buttons button, #shot-type-buttons button, #duel-type-buttons button, #pass-outcome-buttons button, #shot-outcome-buttons button, #duel-outcome-buttons button, #penalty-outcome-buttons button, #reusable-outcome-buttons button, #key-pass-buttons button').forEach(btn => {
    btn.classList.remove('selected');
  });
  selectKeyPass(false); // Reset key pass visuals
  setKeyPassButtonsEnabled(false); // disabled until a Pass tagging or keys enabled by activateTypeOutcomeButtonsFor

  // expose for debugging
  window.TAGGING = { config, endpoints, savePositionsToBackend, loadEvents, computeMatchSeconds, handleMatchEvent };

})();
</script>

