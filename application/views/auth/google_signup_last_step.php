<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="en" class="h-full bg-gray-900">
<head>
	<meta charset="utf-8">
	<title>Insytes | Sign Up</title>
	<link href="<?php echo base_url('assets/css/tailwind_output.css'); ?>" rel="stylesheet">
</head>
<body class="h-full">
	<div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8">
		<div class="sm:mx-auto sm:w-full sm:max-w-sm flex flex-col gap-2">
			<img src="<?php echo base_url('assets/images/logo/logo-indigo.png'); ?>" alt="Your Company" class="mx-auto h-12 w-auto" />
			<h2 class="mt-10 text-center text-2xl/9 font-bold tracking-tight text-white">One last step...</h2>
			<p class="text-center font-normal text-gray-400">Enter your name.</p>
		</div>

		<div class="mt-6 sm:mx-auto sm:w-full sm:max-w-sm">
			<form id="name-form" action="<?php echo site_url('auth/googlecontroller/handle_new_user'); ?>" method="POST" class="space-y-6">

			<div>
				<label for="firstname" class="block text-sm/6 font-medium text-gray-100">First name</label>
				<div class="mt-2">
				<input id="firstname" type="text" name="firstname" required autocomplete="firstname" class="block w-full rounded-md bg-white/5 px-3 py-1.5 text-base text-white outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
				<!-- Validation Feedback Area -->
                <p id="firstname-feedback" class="text-xs mt-1"></p>
				</div>
			</div>

			<div>
				<div class="flex items-center justify-between">
				<label for="lastname" class="block text-sm/6 font-medium text-gray-100">Last name</label>
				
				</div>
				<div class="mt-2">
				<input id="lastname" type="text" name="lastname" required autocomplete="lastname" class="block w-full rounded-md bg-white/5 px-3 py-1.5 text-base text-white outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
				<!-- Validation Feedback Area -->
                <p id="lastname-feedback" class="text-xs mt-1"></p>
				</div>
			</div>

			<div>
				<button id="finish-button" type="submit" class="flex w-full justify-center rounded-md bg-indigo-500 px-3 py-1.5 text-sm/6 font-semibold text-white hover:bg-indigo-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 cursor-pointer">Finish and sign up</button>
			</div>
			</form>

			<div class="my-5">
				<p class="my-2 text-center text-sm/6 text-gray-400">
				<a href="<?php echo site_url('auth/passwordresetcontroller/show_password_reset_step1'); ?>" class="font-semibold text-indigo-400 hover:text-indigo-300">Forgot your password?</a>
				</p>

				<p class="my-2 text-center text-sm/6 text-gray-400">
				<a href="<?php echo site_url('auth/logincontroller/show_login'); ?>" class="font-semibold text-indigo-400 hover:text-indigo-300">Login to an existing account</a>
				</p>
			</div>
		</div>
	</div>

	<script src="<?php echo base_url(); ?>assets/js/nameHandler.js?<?php echo time(); ?>"></script>	
</body>
</html>
