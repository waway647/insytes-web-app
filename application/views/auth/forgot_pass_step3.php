<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="en" class="h-full bg-gray-900">
<head>
	<meta charset="utf-8">
	<title>Insytes | Password Reset</title>
	<link href="<?php echo base_url('assets/css/tailwind_output.css'); ?>" rel="stylesheet">
</head>
<body class="h-full">
	<div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8">
		<div class="sm:mx-auto sm:w-full sm:max-w-sm flex flex-col gap-2">
			<img src="<?php echo base_url('assets/images/logo/logo-indigo.png'); ?>" alt="Your Company" class="mx-auto h-12 w-auto" />
			<h2 class="mt-10 text-center text-2xl/9 font-bold tracking-tight text-white">Enter a new password</h2>
			<p class="text-center font-normal text-gray-400">You must make your new password unique and secured.</p>
		</div>

		<div class="mt-6 sm:mx-auto sm:w-full sm:max-w-sm">
			<form id="password-form" action="<?php echo site_url('auth/passwordresetcontroller/reset_password'); ?>" method="POST" class="space-y-6">

			<!-- Password Field Group -->
			<div>
				<div class="flex items-center justify-between">
					<label for="password" class="block text-sm/6 font-medium text-gray-100">Password</label>
				</div>
				<div class="mt-2">
					<input 
						id="password" 
						type="password" 
						name="password" 
						required 
						autocomplete="new-password" 
						class="block w-full rounded-md bg-white/5 px-3 py-1.5 text-base text-white outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" 
					/>
				</div>

				<!-- Password Requirements Checklist -->
				<div id="password-requirements" class="mt-4 p-4 bg-gray-700/50 rounded-lg hidden">
					<h3 class="text-sm font-semibold text-white mb-2">Password must contain:</h3>
					<ul class="text-xs space-y-1">
						<li id="req-length" class="text-red-400 flex items-center">
							<!-- Icon for failure (X) -->
							<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
							8 to 50 characters
						</li>
						<li id="req-uppercase" class="text-red-400 flex items-center">
							<!-- Icon for failure (X) -->
							<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
							An uppercase letter (A-Z)
						</li>
						<li id="req-lowercase" class="text-red-400 flex items-center">
							<!-- Icon for failure (X) -->
							<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
							A lowercase letter (a-z)
						</li>
						<li id="req-number" class="text-red-400 flex items-center">
							<!-- Icon for failure (X) -->
							<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
							A number (0-9)
						</li>
					</ul>
				</div>
			</div>

			<!-- Re-type Password Field Group -->
			<div>
				<div class="flex items-center justify-between">
					<label for="retype_password" class="block text-sm/6 font-medium text-gray-100">Confirm Password</label>
				</div>
				<div class="mt-2">
					<input 
						id="retype_password" 
						type="password" 
						name="retype_password" 
						required 
						autocomplete="new-password" 
						class="block w-full rounded-md bg-white/5 px-3 py-1.5 text-base text-white outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" 
					/>
					<p id="match-error" class="text-red-400 text-xs mt-1 hidden">Passwords do not match.</p>
				</div>
			</div>

			<div>
				<button id="continue-button" type="submit" class="flex w-full justify-center rounded-md bg-indigo-500 px-3 py-1.5 text-sm/6 font-semibold text-white hover:bg-indigo-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 cursor-pointer">Continue</button>
			</div>
			</form>

			<div class="my-5">
				<p class="my-2 text-center text-sm/6 text-gray-400">
				<a href="<?php echo site_url('auth/logincontroller/show_login'); ?>" class="font-semibold text-indigo-400 hover:text-indigo-300">Login to an existing account</a>
				</p>

				<p class="my-2 text-center text-sm/6 text-gray-400">
				Don't have an account?
				<a href="<?php echo site_url('auth/signupcontroller/show_signup_step1'); ?>" class="font-semibold text-indigo-400 hover:text-indigo-300">Sign up</a>
				</p>
			</div>
		</div>
	</div>

	<script src="<?php echo base_url(); ?>assets/js/passwordHandler.js?<?php echo time(); ?>"></script>	
</body>
</html>
