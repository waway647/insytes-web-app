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
			<form action="http://localhost/GitHub/insytes-web-app/index.php/Auth/SignupController/create_user1" method="POST" class="space-y-6">
			<div class="flex items-center justify-center gap-1.5">
				<span class="w-px h-px p-1 bg-indigo-400 text-xs rounded-2xl">&nbsp</span>
				<span class="w-px h-px p-0.5 bg-gray-400 text-xs rounded-2xl">&nbsp</span>
				<span class="w-px h-px p-0.5 bg-gray-400 text-xs rounded-2xl">&nbsp</span>
			</div>
			<div>
				<label for="email" class="block text-sm/6 font-medium text-gray-100">Email address</label>
				<div class="mt-2">
				<input id="email" type="email" name="email" required autocomplete="email" class="block w-full rounded-md bg-white/5 px-3 py-1.5 text-base text-white outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
				<!-- Feedback Message -->
                <p id="email-feedback" class="text-xs"></p>
				</div>
			</div>

			<!-- Email Validation Feedback UI -->
            <div class="flex items-center mt-2">
                <!-- Loading Spinner (Hidden by default) -->
                <svg id="loading-spinner" class="animate-spin -ml-1 mr-3 h-5 w-5 text-indigo-400 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

			<div>
				<button id="continue-button" type="continue-" class="flex w-full justify-center rounded-md bg-indigo-500 px-3 py-1.5 text-sm/6 font-semibold text-white hover:bg-indigo-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 cursor-pointer">Continue</button>
			</div>
			</form>

			<div class="my-5">
				<!--<p class="my-2 text-center text-sm/6 text-gray-400">
				<a href="<?php echo site_url('auth/passwordresetcontroller/show_password_reset_step1'); ?>" class="font-semibold text-indigo-400 hover:text-indigo-300">Forgot your password?</a>
				</p>-->

				<p class="my-2 text-center text-sm/6 text-gray-400">
				<a href="<?php echo site_url('auth/logincontroller/show_login'); ?>" class="font-semibold text-indigo-400 hover:text-indigo-300">Login to an existing account</a>
				</p>
			</div>
		</div>
	</div>

	<script>
		const checkEmailUrl = "<?php echo site_url('Auth/SignupController/check_email_unique'); ?>";
	</script>
	
	<script src="<?php echo base_url(); ?>assets/js/emailHandler.js?<?php echo time(); ?>"></script>
</body>
</html>
