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