<div class="flex w-full h-13 justify-between items-center border-1 border-black px-10">
    <div class="flex w-100">
        <img id="home-logo-btn" src="<?php echo base_url('assets/images/logo/studio_logo.svg'); ?>" class="w-fit h-fit" alt="">
    </div>
    <div id="menu-buttons" class="flex w-fit h-13">
        <button id="media-tab-btn" class="flex h-full py-3 px-15 cursor-pointer hover:border-b-2 border-[#B6BABD]">
            <img id="media-img" src="<?php echo base_url('assets/images/icons/media_gray.svg'); ?>" class="w-fit h-fit" alt="">
            <img id="media-img-active" src="<?php echo base_url('assets/images/icons/media.svg'); ?>" class="w-fit h-fit hidden" alt="">
        </button>
        <button id="tagging-tab-btn" class="flex h-full py-3 px-15 cursor-pointer hover:border-b-2 border-[#B6BABD]">
            <img id="tagging-img" src="<?php echo base_url('assets/images/icons/tagging_gray.svg'); ?>" class="w-fit h-fit" alt="">
            <img id="tagging-img-active" src="<?php echo base_url('assets/images/icons/tagging.svg'); ?>" class="w-fit h-fit hidden" alt="">
        </button>
        <button id="deliver-tab-btn" class="flex h-full py-3 px-15 cursor-pointer hover:border-b-2 border-[#B6BABD]">
            <img id="deliver-img" src="<?php echo base_url('assets/images/icons/delivery_gray.svg'); ?>" class="w-fit h-fit" alt="">
            <img id="deliver-img-active" src="<?php echo base_url('assets/images/icons/delivery.svg'); ?>" class="w-fit h-fit hidden" alt="">
        </button>
    </div>
    <div class="flex w-100 justify-end">
        <img id="home-icon-btn" src="<?php echo base_url('assets/images/icons/home.svg'); ?>" class="w-fit h-fit cursor-pointer" alt="">
    </div>
</div>

<script>
    // --- 1. Element and URL Definitions ---
    const homeIconBtn = document.getElementById('home-icon-btn');
    const homeLogoBtn = document.getElementById('home-logo-btn'); // Assuming you want the logo to work as well

    // Grouping all tabs and their associated elements/URLs for cleaner iteration
    const tabs = [
        {
            btn: document.getElementById('media-tab-btn'),
            url: "<?php echo site_url('studio/mediacontroller/index'); ?>",
            img: document.getElementById('media-img'),
            imgActive: document.getElementById('media-img-active'),
        },
        {
            btn: document.getElementById('tagging-tab-btn'),
            url: "<?php echo site_url('studio/taggingcontroller/index'); ?>",
            img: document.getElementById('tagging-img'),
            imgActive: document.getElementById('tagging-img-active'),
        },
        {
            btn: document.getElementById('deliver-tab-btn'),
            url: "<?php echo site_url('studio/delivercontroller/index'); ?>",
            img: document.getElementById('deliver-img'),
            imgActive: document.getElementById('deliver-img-active'),
        }
    ];

    const homeUrl = "<?php echo site_url('team/dashboardcontroller/index'); ?>";
    const currentUrl = window.location.href;

    const TABS_ACTIVE_CLASSES = '!border-b-2 !border-[#B6BABD]';

    // --- 2. Activation Logic on Page Load (The Fix!) ---
    function setActiveTab() {
        tabs.forEach(tab => {
            if (tab.btn && tab.url) {
                // Use includes() for a more robust match, as the full URL might have query parameters.
                // It checks if the current URL contains the tab's base URL.
                if (currentUrl.includes(tab.url)) {
                    // This tab's page is the current page, so set it as active
                    tab.img.classList.add('hidden');
                    tab.imgActive.classList.remove('hidden');
                    // OPTIONAL: Add an active class to the button itself for styling the border or background
                    tab.btn.classList.add(...TABS_ACTIVE_CLASSES.split(' ')); 
                } else {
                    // Ensure non-active tabs are set to their default (gray) state
                    tab.img.classList.remove('hidden');
                    tab.imgActive.classList.add('hidden');
                    // OPTIONAL: Remove the active class from the button if it exists
                    // Example: tab.btn.classList.remove('active-tab-style');
                }
            }
        });
    }

    // Run the function when the page loads to set the correct active tab
    setActiveTab();


    // --- 3. Event Listeners for Redirection (Simplified) ---
    function redirect(url) {
        window.location.href = url;
    }

    // Home button redirection
    const homeButtons = [homeIconBtn, homeLogoBtn];
    homeButtons.forEach(button => {
        if (button) {
            button.style.cursor = 'pointer';
            button.addEventListener('click', () => {
                redirect(homeUrl);
            });
        }
    });

    // Menu button redirection
    tabs.forEach(tab => {
        if (tab.btn) {
            tab.btn.addEventListener('click', (e) => {
                // Prevent default navigation if you were using an <a> tag, but not strictly needed for <button>
                // e.preventDefault(); 
                redirect(tab.url);
            });
        }
    });
</script>