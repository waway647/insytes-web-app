<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

?><!DOCTYPE html>
<html lang="en" class="h-full bg-gray-900">
<head>
	<meta charset="utf-8">
	<title>Insytes | Login</title>
	<link href="<?php echo base_url('assets/css/tailwind_output.css'); ?>" rel="stylesheet">
</head>
<body class="h-full">
	<div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8">
		<div class="sm:mx-auto sm:w-full sm:max-w-sm flex flex-col gap-2">
			<img src="<?php echo base_url('assets/images/logo/logo-indigo.png'); ?>" alt="Your Company" class="mx-auto h-12 w-auto" />
			<h2 class="mt-10 text-center text-2xl/9 font-bold tracking-tight text-white">Welcome back</h2>
		</div>

		<div>
			<a href="<?php echo site_url('auth/googlecontroller/index'); ?>" class="px-6 py-3 border border-gray-700 rounded-[100px] flex items-center justify-center gap-3 cursor-pointer hover:bg-gray-800 max-w-sm mx-auto my-6">
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
			<form id="login-form" action="http://localhost/github/insytes-web-app/index.php/Auth/LoginController/process_login" method="POST" class="space-y-6">
			<!-- Feedback Area -->
        	<p id="login-feedback" class="text-xs text-center hidden p-2 rounded-md"></p>
			
			<div>
				<label for="email" class="block text-sm/6 font-medium text-gray-100">Email address</label>
				<div class="mt-2">
				<input id="email" type="email" name="email" required autocomplete="email" class="block w-full rounded-md bg-white/5 px-3 py-1.5 text-base text-white outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
				</div>
			</div>

			<div>
				<div class="flex items-center justify-between">
				<label for="password" class="block text-sm/6 font-medium text-gray-100">Password</label>
				
				</div>
				<div class="mt-2 relative">
				<input id="password" type="password" name="password" required autocomplete="current-password" class="block w-full rounded-md bg-white/5 px-3 py-1.5 pr-10 text-base text-white outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
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
			</div>

			<div>
				<button id="login-button" type="submit" class="flex w-full justify-center rounded-md bg-indigo-500 px-3 py-1.5 text-sm/6 font-semibold text-white hover:bg-indigo-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 cursor-pointer">Sign in</button>
			</div>
			</form>

			<div class="my-5">
				<p class="my-2 text-center text-sm/6 text-gray-400">
				<a href="<?php echo site_url('auth/passwordresetcontroller/show_password_reset_step1'); ?>" class="font-semibold text-indigo-400 hover:text-indigo-300">Forgot your password?</a>
				</p>

				<p class="my-2 text-center text-sm/6 text-gray-400">
				Don't have an account?
				<a href="<?php echo site_url('auth/signupcontroller/show_signup_step1'); ?>" class="font-semibold text-indigo-400 hover:text-indigo-300">Sign up</a>
				</p>
			</div>
		</div>
	</div>
	<script>
		const validateLoginURL = "<?php echo site_url('Auth/LoginController/process_login'); ?>";
		
		// Password toggle functionality
		document.addEventListener('DOMContentLoaded', function() {
			const togglePassword = document.getElementById('toggle-password');
			const passwordInput = document.getElementById('password');
			const eyeClosed = document.getElementById('eye-closed');
			const eyeOpen = document.getElementById('eye-open');
			
			togglePassword.addEventListener('click', function() {
				// Toggle password input type
				if (passwordInput.type === 'password') {
					passwordInput.type = 'text';
					eyeClosed.classList.add('hidden');
					eyeOpen.classList.remove('hidden');
				} else {
					passwordInput.type = 'password';
					eyeClosed.classList.remove('hidden');
					eyeOpen.classList.add('hidden');
				}
			});
		});
	</script>

	<script src="<?php echo base_url(); ?>assets/js/loginHandler.js?<?php echo time(); ?>"></script>				
</body>
</html>
