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

    <div class="w-full flex gap-10">
        <div class="w-full flex flex-col gap-10">
            <?php $this->load->view('partials/scoreboard'); ?>

            <?php $this->load->view('partials/report_tabs'); ?>

            <?php $this->load->view($report_content); ?>
        </div>

        <?php $this->load->view('partials/focus_panel'); ?>
    </div>
</body>
</html>
