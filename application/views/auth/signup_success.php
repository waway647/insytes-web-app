<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

?><!DOCTYPE html>
<html lang="en" class="h-full bg-gray-900">
<head>
	<meta charset="utf-8">
	<title>Insytes</title>
	<link href="<?php echo base_url('assets/css/tailwind_output.css'); ?>" rel="stylesheet">
</head>
<body class="h-full">
	<div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8">
		<div class="sm:mx-auto sm:w-full sm:max-w-sm flex flex-col gap-2">
			<img src="<?php echo base_url('assets/images/logo/logo-indigo.png'); ?>" alt="Your Company" class="mx-auto h-12 w-auto" />
			<h2 class="mt-10 text-center text-2xl/9 font-bold tracking-tight text-white">Your are now signed up</h2>
			<p class="text-center font-normal text-gray-400">Goodluck on your Insytes journey!</p>
		</div>

		<div class="mt-6 sm:mx-auto sm:w-full sm:max-w-sm">
			<div>
				<button onclick="window.location.href='<?php echo site_url('/Auth/LoginController/show_login'); ?>'"
				type="button" class="flex w-full justify-center rounded-md bg-indigo-500 px-3 py-1.5 text-sm/6 font-semibold text-white hover:bg-indigo-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 cursor-pointer">Continue</button>
			</div>
		</div>
	</div>
</body>
</html>
