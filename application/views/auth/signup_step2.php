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
			<form id="password-form" action="http://localhost/GitHub/insytes-web-app/index.php/Auth/SignupController/create_user2" method="POST" class="space-y-6">
			<div class="flex items-center justify-center gap-1.5">
				<span class="w-px h-px p-0.5 bg-gray-400 text-xs rounded-2xl">&nbsp</span>
				<span class="w-px h-px p-1 bg-indigo-400 text-xs rounded-2xl">&nbsp</span>
				<span class="w-px h-px p-0.5 bg-gray-400 text-xs rounded-2xl">&nbsp</span>
			</div>

			<!-- Password Field Group -->
			<div>
				<div class="flex items-center justify-between">
					<label for="password" class="block text-sm/6 font-medium text-gray-100">Password</label>
				</div>
				<div class="mt-2 relative">
					<input 
						id="password" 
						type="password" 
						name="password" 
						required 
						autocomplete="new-password" 
						class="block w-full rounded-md bg-white/5 px-3 py-1.5 pr-10 text-base text-white outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" 
					/>
					<button type="button" id="toggle-password" class="absolute inset-y-0 right-0 pr-3 px-1 flex items-center text-gray-400 hover:text-gray-300">
						<!-- Eye Slash Icon (Password Hidden - Default State) -->
						<svg id="eye-closed" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
						</svg>
						<!-- Eye Icon (Password Visible) -->
						<svg id="eye-open" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
						</svg>
					</button>
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
				<div class="mt-2 relative">
					<input 
						id="retype_password" 
						type="password" 
						name="retype_password" 
						required 
						autocomplete="new-password" 
						class="block w-full rounded-md bg-white/5 px-3 py-1.5 pr-10 text-base text-white outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" 
					/>
					<button type="button" id="toggle-retype-password" class="absolute inset-y-0 right-0 pr-3 px-1 flex items-center text-gray-400 hover:text-gray-300">
						<!-- Eye Slash Icon (Password Hidden - Default State) -->
						<svg id="eye-closed-retype" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
						</svg>
						<!-- Eye Icon (Password Visible) -->
						<svg id="eye-open-retype" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
						</svg>
					</button>
				</div>
					<p id="match-error" class="text-red-400 text-xs mt-1 hidden">Passwords do not match.</p>
			</div>

			<div>
				<button id="continue-button" type="submit" class="flex w-full justify-center rounded-md bg-indigo-500 px-3 py-1.5 text-sm/6 font-semibold text-white hover:bg-indigo-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 cursor-pointer">Continue</button>
			</div>
			</form>

			<!-- <div class="my-5">
				<p class="my-2 text-center text-sm/6 text-gray-400">
				<a href="<?php echo site_url('auth/passwordresetcontroller/show_password_reset_step1'); ?>" class="font-semibold text-indigo-400 hover:text-indigo-300">Forgot your password?</a>
				</p>

				<p class="my-2 text-center text-sm/6 text-gray-400">
				<a href="<?php echo site_url('auth/logincontroller/show_login'); ?>" class="font-semibold text-indigo-400 hover:text-indigo-300">Login to an existing account</a>
				</p>
			</div> -->
		</div>
	</div>

	<script src="<?php echo base_url(); ?>assets/js/passwordHandler.js?<?php echo time(); ?>"></script>
</body>
</html>
