<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="en" class="h-full w-full m-0 p-0 bg-[#131313]">
<head>
	<meta charset="utf-8">
	<title>Insytes | <?php echo $title ?></title>
    <link href="<?php echo base_url(); ?>assets/css/tailwind_output.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="<?php echo base_url(); ?>assets/css/scrollBar.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="<?php echo base_url(); ?>assets/css/dateInput.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="<?php echo base_url(); ?>assets/css/image_viewer.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>
<body class="m-0 p-0 h-full w-full flex">

    <?php
    // Provide canonical values to the page: prefer sanitized tokens, fall back to raw requested.
    // Controllers should set: requested_match_id, requested_match_name, match_id, match_name
    $page_requested_match_id   = isset($requested_match_id) ? $requested_match_id : null;
    $page_requested_match_name = isset($requested_match_name) ? $requested_match_name : null;
    $page_match_id             = isset($match_id) ? $match_id : ($this->session->userdata('current_match_id') ?? null);
    $page_match_name           = isset($match_name) ? $match_name : ($this->session->userdata('current_match_name') ?? null);

    // Hidden inputs so client JS has a reliable source of truth
    ?>
    <input type="hidden" id="REPORT_REQUESTED_MATCH_ID"   value="<?= htmlspecialchars($page_requested_match_id ?? '') ?>">
    <input type="hidden" id="REPORT_REQUESTED_MATCH_NAME" value="<?= htmlspecialchars($page_requested_match_name ?? '') ?>">
    <input type="hidden" id="REPORT_MATCH_ID"             value="<?= htmlspecialchars($page_match_id ?? '') ?>">
    <input type="hidden" id="REPORT_MATCH_NAME"           value="<?= htmlspecialchars($page_match_name ?? '') ?>">

    <!--
      Client-side fetcher:
      - Only runs if window.matchSummary is not already present (server-side embed)
      - Uses REPORT_MATCH_NAME hidden input to request /matchapi/summary
      - Calls window.renderInitialSummary(summary) if available, otherwise dispatches matchSummaryLoaded
    -->
    <script>
    (function () {
      // If server already embedded summary, skip fetch.
      if (window.matchSummary) return;

      const matchNameEl = document.getElementById('REPORT_MATCH_NAME');
      const matchName = matchNameEl ? matchNameEl.value : '';

      if (!matchName) {
        window.matchSummary = null;
        return;
      }

      const url = '<?php echo rtrim(site_url(), "/"); ?>/matchapi/summary?match_name=' + encodeURIComponent(matchName);

      fetch(url, { credentials: 'same-origin' })
        .then(resp => {
          if (!resp.ok) return null;
          return resp.json();
        })
        .then(json => {
          window.matchSummary = json || null;

          // prefer direct global function if available
          if (typeof window.renderInitialSummary === 'function') {
            try { window.renderInitialSummary(window.matchSummary); } catch (err) { console.error(err); }
            return;
          }

          // otherwise fire event for listeners
          const ev = new CustomEvent('matchSummaryLoaded', { detail: window.matchSummary });
          window.dispatchEvent(ev);
        })
        .catch(err => {
          console.error('Failed to load match summary', err);
          window.matchSummary = null;
        });
    })();
    </script>

    <div class="w-full flex gap-10 h-full overflow-hidden">

        <!-- Left scrollable content -->
        <div class="left-scrollable w-full flex flex-col gap-10 overflow-y-auto pr-4 no-scrollbar min-h-0">
            <?php $this->load->view('partials/scoreboard'); ?>
            <?php $this->load->view('partials/report_tabs'); ?>
            <?php $this->load->view($report_content); ?>
        </div>

        <!-- Fixed focus panel -->
        <div class="flex-shrink-0">
            <?php $this->load->view('partials/focus_panel'); ?>
        </div>
    </div>
    
</body>
</html>