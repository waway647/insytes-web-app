<div class="flex w-full h-full">
    <div class="flex flex-col w-180 h-full p-12 bg-[#2A2A2A] border-x-1 border-black text-[#B6BABD]">
        <div id="video-input-container" class="w-full">
            <form action="">
                <input id="match-upload" type="file" accept="video/mp4, video/mov" class="border-1 border-[#131313] bg-[#8f8c8c] text-[#1b1b1b] rounded">
            </form>
        </div>
        <div id="video-display-container" class="hidden">
            <img id="video-thumbnail" src="<?php echo base_url('assets/matches/match_20252026_01_thumbnail.png'); ?>" alt="">
            <span id="video-file" class="text-[#B6BABD] text-xs">upvssanbeda.mp4</span>
        </div>
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
                        <source src="<?php echo base_url('assets/videos/SBU_vs_2WORLDS.mp4'); ?>" type="video/mp4">
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
    <div class="flex w-300 h-full p-12 bg-[#1C1C1C] border-x-1 border-black">

    </div>
</div>

<script src="<?php echo base_url(); ?>assets/js/studioVideoPlayer.js?<?php echo time(); ?>"></script>