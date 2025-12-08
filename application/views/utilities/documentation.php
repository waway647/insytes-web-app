<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Insytes — Documentation</title>

  <!-- Tailwind (CDN for convenience) -->
  <script src="https://cdn.tailwindcss.com"></script>

  <style>
    /* glass card subtle blur */
    .glass {
      background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01));
      backdrop-filter: blur(6px);
    }

    /* modern video container styling */
    .video-wrap {
      position: relative;
      border-radius: 1rem;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(2,6,23,0.6), 0 2px 8px rgba(0,0,0,0.45);
      border: 1px solid rgba(255,255,255,0.03);
    }

    /* translucent glass control bar */
    .video-controls {
      position: absolute;
      left: 0;
      right: 0;
      bottom: 8px;
      display: flex;
      gap: 0.5rem;
      padding: .5rem .75rem;
      align-items: center;
      justify-content: space-between;
      background: linear-gradient(180deg, rgba(8,10,12,0.12), rgba(8,10,12,0.08));
      backdrop-filter: blur(6px);
      margin: 0 12px 12px;
      border-radius: 999px;
      border: 1px solid rgba(255,255,255,0.03);
    }

    /* big central play button */
    .video-overlay-btn {
      position: absolute;
      inset: 0;
      display: grid;
      place-items: center;
      pointer-events: none; /* allow clicks through when hidden */
    }
    .video-overlay-btn button {
      pointer-events: auto;
    }

    /* subtle pulse effect for autoplay */
    .pulse-ring {
      animation: pulse 1.8s infinite cubic-bezier(.4,0,.2,1);
      box-shadow: 0 6px 30px rgba(99,102,241,0.12);
    }
    @keyframes pulse {
      0% { transform: scale(1); opacity: 1; }
      70% { transform: scale(1.08); opacity: .65; }
      100% { transform: scale(1); opacity: 1; }
    }

    /* ensure poster covers fully and darken for legibility */
    .video-wrap::after {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(6,8,12,0.18), rgba(6,8,12,0.22));
      pointer-events: none;
    }
  </style>
</head>
<body class="antialiased bg-[#0b0c0d] text-slate-100 min-h-screen">

  <div class="max-w-7xl mx-auto px-6 py-10">

    <!-- Header / Hero -->
    <header class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 mb-8">
      <div>
        <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight text-white">Insytes — Documentation</h1>
        <p class="mt-2 text-sm text-slate-400 max-w-xl">
          Product demo, platform documentation and team information — everything you need to get started and understand the Insytes analytics platform.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <a href="<?php echo site_url('team/dashboardcontroller/index'); ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm shadow">
          ← Back to Homepage
        </a>
        <a href="<?php echo base_url('assets/documentation/platform_documentation.pdf'); ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-700 text-sm text-slate-200 hover:bg-slate-800">
          Download PDF
        </a>
      </div>
    </header>

    <main class="grid grid-cols-1 lg:grid-cols-3 gap-8">

      <!-- Left / Primary: Product Demo -->
      <section class="lg:col-span-2 space-y-6">
        <div class="glass rounded-2xl p-5 shadow-lg border border-slate-800">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-white">Product Video Demonstration</h2>
              <p class="text-sm text-slate-400 mt-1">Short walkthrough of the platform — tagging, dashboards, player profiles and exporting reports.</p>
            </div>
          </div>

          <!-- Video card (replace your existing video-card block with this) -->
			<div class="mt-4 video-card rounded-lg overflow-hidden border border-slate-800 shadow-lg">
			<!-- wrapper sets responsive aspect and min-height for reliability -->
			<div class="w-full bg-black rounded-md overflow-hidden">
				<video
				id="product-demo-video"
				class="w-full h-auto object-fill bg-black"
				poster="<?php echo base_url('assets/documentation/product_demo_poster.jpg'); ?>"
				playsinline
				muted
				preload="metadata"
				controls
				crossorigin="anonymous"
				aria-label="Product demo video"
				>
				<source src="<?php echo base_url('assets/documentation/product_demo_final.mp4'); ?>" type="video/mp4" />
				Your browser does not support the video tag.
				</video>
			</div>
			</div>

          <div class="mt-4 flex flex-wrap gap-3">
            <a href="<?php echo base_url('assets/documentation/product_demo_final.mp4'); ?>" download class="px-3 py-2 rounded-md bg-slate-700 hover:bg-slate-600 text-sm">Download video</a>
            <a href="#platform-docs" class="px-3 py-2 rounded-md border border-slate-700 text-sm hover:bg-slate-800">Platform docs</a>
            <a href="#about-team" class="px-3 py-2 rounded-md border border-slate-700 text-sm hover:bg-slate-800">About the team</a>
          </div>
        </div>

        <!-- Platform Documentation (detailed topics) -->
        <div id="platform-docs" class="glass rounded-2xl p-6 shadow-lg border border-slate-800">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-white">Platform Documentation</h3>
            <div class="text-sm text-slate-400">Version: <span class="font-medium text-white">v1.0.0</span></div>
          </div>

          <p class="text-sm text-slate-400 mt-3">
            The documentation below provides high-level and implementation details for the major areas of the Insytes platform.
          </p>

          <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Doc cards... (kept as before) -->
            <article class="p-4 rounded-lg bg-[#071018] border border-slate-800 shadow-sm">
              <h4 class="text-sm font-semibold text-white">1. Getting Started</h4>
              <p class="text-xs text-slate-400 mt-1">How to onboard your team, create competitions, upload matches and start taggings.</p>
              <ul class="mt-3 text-xs text-slate-400 space-y-1">
                <li>• Setup & permissions</li>
                <li>• Team & competition configuration</li>
                <li>• Video ingestion & transcoding</li>
              </ul>
            </article>

            <article class="p-4 rounded-lg bg-[#071018] border border-slate-800 shadow-sm">
              <h4 class="text-sm font-semibold text-white">2. Tagging Studio</h4>
              <p class="text-xs text-slate-400 mt-1">Guide to the tagging workflow, event types, keyboard shortcuts and recommended workflows for fast tagging.</p>
              <ul class="mt-3 text-xs text-slate-400 space-y-1">
                <li>• Event taxonomy</li>
                <li>• Quick tagging tips</li>
                <li>• Exporting event logs</li>
              </ul>
            </article>

            <article class="p-4 rounded-lg bg-[#071018] border border-slate-800 shadow-sm">
              <h4 class="text-sm font-semibold text-white">3. Dashboards & Reports</h4>
              <p class="text-xs text-slate-400 mt-1">Description of the dashboards, available KPIs, and how to build custom exports and reports.</p>
              <ul class="mt-3 text-xs text-slate-400 space-y-1">
                <li>• Match & season aggregates</li>
                <li>• Player sheets & per-90 stats</li>
                <li>• CSV / PDF export</li>
              </ul>
            </article>

            <article class="p-4 rounded-lg bg-[#071018] border border-slate-800 shadow-sm">
              <h4 class="text-sm font-semibold text-white">4. API & Integration</h4>
              <p class="text-xs text-slate-400 mt-1">Overview of public APIs and how to fetch the views and endpoints used by the UI.</p>
              <ul class="mt-3 text-xs text-slate-400 space-y-1">
                <li>• Auth & tokens</li>
                <li>• Key endpoints</li>
                <li>• Rate limits & webhooks</li>
              </ul>
            </article>
          </div>

          <div class="mt-6">
            <h4 class="text-sm font-semibold text-white">Quick Links</h4>
            <div class="mt-3 flex flex-wrap gap-2">
              <a href="<?php echo base_url('assets/documentation/platform_documentation.pdf'); ?>" class="px-3 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white text-sm shadow">Download complete docs (PDF)</a>
              <a href="<?php echo base_url('assets/documentation/api_reference.html'); ?>" class="px-3 py-2 rounded-md border border-slate-700 text-sm hover:bg-slate-800">API reference (HTML)</a>
            </div>
          </div>
        </div>

      </section>

      <!-- Right column: About team & contact -->
      <aside id="about-team" class="space-y-6">
        <div class="glass rounded-2xl p-5 shadow-lg border border-slate-800">
          <h3 class="text-lg font-semibold text-white">About the Team</h3>
          <p class="text-sm text-slate-400 mt-2">Insytes is built by a focused team of product designers, data scientists, and engineers who love sports analytics.</p>

          <div class="mt-4 space-y-3">
            <!-- Team members -->
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center text-white font-semibold shadow">
                PM
              </div>
              <div>
                <div class="text-sm font-semibold text-white">Paul Joshua Mapula</div>
                <div class="text-xs text-slate-400">Lead Developer</div>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-full bg-gradient-to-br from-green-500 to-teal-500 flex items-center justify-center text-white font-semibold shadow">
                SL
              </div>
              <div>
                <div class="text-sm font-semibold text-white">Sean Jeremy Labrador</div>
                <div class="text-xs text-slate-400">Lead Developer</div>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-full bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center text-white font-semibold shadow">
                JA
              </div>
              <div>
                <div class="text-sm font-semibold text-white">Jul-Andrei Aningalan</div>
                <div class="text-xs text-slate-400">Junior</div>
              </div>
            </div>

			<div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-full bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center text-white font-semibold shadow">
                JM
              </div>
              <div>
                <div class="text-sm font-semibold text-white">John Ariel Marquez</div>
                <div class="text-xs text-slate-400">Junior</div>
              </div>
            </div>
          </div>

          <div class="mt-5 border-t border-slate-800 pt-4">
            <div class="text-xs text-slate-400">Contact</div>
            <div class="mt-2">
              <a href="mailto:insytes.web@gmail.com" class="text-sm text-white hover:underline">insytes.web@gmail.com</a>
            </div>
          </div>
        </div>

        <!-- Mini-card with quick facts -->
        <div class="glass rounded-2xl p-5 shadow-lg border border-slate-800">
          <div class="flex items-center justify-between">
            <div>
              <h4 class="text-sm font-semibold text-white">Platform facts</h4>
              <p class="text-xs text-slate-400 mt-1">Designed for coaching teams and analysts.</p>
            </div>
            <div class="text-right">
              <div class="text-lg font-bold text-white">30+</div>
              <div class="text-xs text-slate-400">teams onboarded</div>
            </div>
          </div>

          <div class="mt-4 grid grid-cols-2 gap-3 text-xs text-slate-400">
            <div class="p-3 rounded-lg bg-[#071018]">Matches processed <div class="mt-1 text-white font-semibold">12k+</div></div>
            <div class="p-3 rounded-lg bg-[#071018]">Events catalog <div class="mt-1 text-white font-semibold">120+</div></div>
          </div>
        </div>

        <!-- Support / Changelog -->
        <div class="glass rounded-2xl p-5 shadow-lg border border-slate-800">
          <h4 class="text-sm font-semibold text-white">Support & Changelog</h4>
          <p class="text-xs text-slate-400 mt-2">For support, feature requests or bugs, open a ticket through your organization's support channel or email the team.</p>

          <div class="mt-3">
            <a href="<?php echo base_url('changelog'); ?>" class="text-sm px-3 py-2 rounded-md border border-slate-700 hover:bg-slate-800">View changelog</a>
          </div>
        </div>
      </aside>
    </main>

    <footer class="mt-12 text-center text-xs text-slate-500">
      © <?php echo date('Y'); ?> Insytes · Built with ❤️ · <span class="hidden sm:inline">Version v1.0.0</span>
    </footer>
  </div>

  <!-- Video behavior script -->
  <script>
    (function () {
      const video = document.getElementById('product-demo-video');
      const overlay = document.getElementById('video-play-overlay');
      const overlayIcon = document.getElementById('overlay-icon');
      const controls = document.getElementById('video-controls');
      const muteBtn = document.getElementById('mute-toggle');
      const fsBtn = document.getElementById('fs-toggle');
      const timeBar = document.getElementById('time-bar');
      const timeCurrent = document.getElementById('time-current');
      const timeDuration = document.getElementById('time-duration');

      if (!video) return;

      // Try to autoplay muted on load
      function tryAutoplay() {
        // ensure muted for autoplay policy
        video.muted = true;
        video.play().then(() => {
          // autoplay succeeded
          hideOverlay();
          showControls();
        }).catch((err) => {
          // autoplay blocked — show overlay play button
          showOverlay();
        });
      }

      // update time UI
      function updateTimeUI() {
        const cur = video.currentTime || 0;
        const dur = video.duration || 0;
        if (timeCurrent) timeCurrent.textContent = formatTime(cur);
        if (timeDuration) timeDuration.textContent = '/ ' + (isFinite(dur) ? formatTime(dur) : '0:00');
        if (timeBar && dur > 0) {
          timeBar.style.width = Math.min(100, (cur / dur) * 100) + '%';
        }
      }

      function formatTime(t) {
        if (!isFinite(t)) return '0:00';
        const mins = Math.floor(t / 60);
        const secs = Math.floor(t % 60).toString().padStart(2, '0');
        return mins + ':' + secs;
      }

      function showOverlay() {
        if (overlay) overlay.parentElement.style.pointerEvents = 'auto';
        overlay && (overlay.style.display = 'grid');
      }
      function hideOverlay() {
        if (overlay) overlay.parentElement.style.pointerEvents = 'none';
        overlay && (overlay.style.display = 'none');
      }

      function showControls() {
        if (!controls) return;
        controls.classList.remove('hidden');
        controls.setAttribute('aria-hidden', 'false');
      }
      function hideControls() {
        if (!controls) return;
        controls.classList.add('hidden');
        controls.setAttribute('aria-hidden', 'true');
      }

      // overlay button click: unmute & play (or pause if playing)
      overlay && overlay.addEventListener('click', (ev) => {
        ev.preventDefault();
        if (video.paused) {
          video.muted = false;
          video.play().then(() => {
            hideOverlay();
            showControls();
          }).catch(() => {
            // if still blocked, keep overlay visible
          });
        } else {
          video.pause();
          showOverlay();
        }
      });

      // clicking the overlay button element itself
      if (overlay) {
        overlay.querySelector('button').addEventListener('click', (ev) => {
          ev.preventDefault();
          if (video.paused) {
            video.muted = false;
            video.play().then(() => {
              hideOverlay();
              showControls();
            }).catch(() => {
              showOverlay();
            });
          } else {
            video.pause();
            showOverlay();
          }
        });
      }

      // clicking the video toggles play/pause (and shows overlay when paused)
      video.addEventListener('click', () => {
        if (video.paused) {
          video.play().catch(() => {});
          hideOverlay();
        } else {
          video.pause();
          showOverlay();
        }
      });

      // mute toggle behaviour
      muteBtn && muteBtn.addEventListener('click', () => {
        video.muted = !video.muted;
        muteBtn.textContent = video.muted ? 'Muted' : 'Unmuted';
      });

      // fullscreen toggle
      fsBtn && fsBtn.addEventListener('click', () => {
        const container = video.closest('.video-wrap') || video;
        if (!document.fullscreenElement) {
          container.requestFullscreen?.().catch(()=>{});
        } else {
          document.exitFullscreen?.().catch(()=>{});
        }
      });

      // time updates
      video.addEventListener('timeupdate', updateTimeUI);
      video.addEventListener('loadedmetadata', updateTimeUI);
      video.addEventListener('play', () => { hideOverlay(); showControls(); });
      video.addEventListener('pause', () => { showOverlay(); });

      // keyboard support: space toggles play/pause when focused
      document.addEventListener('keydown', (ev) => {
        if (ev.code === 'Space' && document.activeElement === video) {
          ev.preventDefault();
          if (video.paused) video.play().catch(()=>{});
          else video.pause();
        }
      });

      // start
      document.addEventListener('DOMContentLoaded', tryAutoplay);
      // also try after short delay to handle network latency
      setTimeout(tryAutoplay, 700);
    })();
  </script>
</body>
</html>
