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
	<!-- Sidebar Navigation -->
    <?php $this->load->view('partials/sidebar'); ?>

    <!-- Main Content Area -->
    <main class="flex-1 h-full overflow-y-auto p-10">
        <?php $this->load->view($main_content); ?>
    </main>
</body>
</html>