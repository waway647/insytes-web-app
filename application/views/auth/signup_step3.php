<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

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
			<h2 class="mt-10 text-center text-2xl/9 font-bold tracking-tight text-white">Welcome to Insytes</h2>
			<p class="text-center font-normal text-gray-400">Create your account and take advantage with  innovation.</p>
		</div>

		<div>
			<a href="<?php echo site_url('auth/googlecontroller/index'); ?>" class="px-6 py-3 border border-gray-700 rounded-[100px] flex items-center justify-center gap-3 cursor-pointer hover:bg-gray-800 max-w-sm mx-auto my-6"">
			<img src="<?php echo base_url('assets/images/icons/google.svg'); ?>" alt="">
			<div class="w-full text-center">
				<h4 class="text-gray-100">Continue with Google</h4>
			</div>
			</a>
		</div>

		<div class=" sm:mx-auto sm:w-full sm:max-w-sm">
			<div class="flex items-center">
				<div class="flex-1 h-px bg-neutral-700"></div>
				<span class="px-3 text-gray-500 text-sm">or</span>
				<div class="flex-1 h-px bg-neutral-700"></div>
			</div>
		</div>

		<div class="mt-6 sm:mx-auto sm:w-full sm:max-w-sm">
			<form id="name-form" action="http://localhost/GitHub/insytes-web-app/index.php/Auth/SignupController/create_user3" method="POST" class="space-y-6">
			<div class="flex items-center justify-center gap-1.5">
				<span class="w-px h-px p-0.5 bg-gray-400 text-xs rounded-2xl">&nbsp</span>
				<span class="w-px h-px p-0.5 bg-gray-400 text-xs rounded-2xl">&nbsp</span>
				<span class="w-px h-px p-1 bg-indigo-400 text-xs rounded-2xl">&nbsp</span>
			</div>

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
				<!-- <p class="my-2 text-center text-sm/6 text-gray-400">
				<a href="<?php echo site_url('auth/passwordresetcontroller/show_password_reset_step1'); ?>" class="font-semibold text-indigo-400 hover:text-indigo-300">Forgot your password?</a>
				</p> -->

				<p class="my-2 text-center text-sm/6 text-gray-400">
				<a href="<?php echo site_url('auth/logincontroller/show_login'); ?>" class="font-semibold text-indigo-400 hover:text-indigo-300">Login to an existing account</a>
				</p>
			</div>
		</div>
	</div>

	<script src="<?php echo base_url(); ?>assets/js/nameHandler.js?<?php echo time(); ?>"></script>
</body>
</html>
