<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="en" class="h-full bg-gray-900">
<head>
	<meta charset="utf-8">
	<title>Insytes | Create Admin User</title>
	<link href="<?php echo base_url('assets/css/tailwind_output.css'); ?>" rel="stylesheet">
</head>
<body class="h-full">
	<div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8">
		<div class="sm:mx-auto sm:w-full sm:max-w-sm flex flex-col gap-2">
			<img src="<?php echo base_url('assets/images/logo/logo-indigo.png'); ?>" alt="Insytes" class="mx-auto h-12 w-auto" />
			<h2 class="mt-10 text-center text-2xl/9 font-bold tracking-tight text-white">Create Admin Account</h2>
			<p class="text-center font-normal text-gray-400">Set up your administrator account to manage Insytes.</p>
		</div>

		<div class="mt-6 sm:mx-auto sm:w-full sm:max-w-sm">
			<form id="admin-create-form" action="<?php echo site_url('Admin/AdminUserController/process_create'); ?>" method="POST" class="space-y-6">
				<!-- Feedback Area -->
				<p id="create-feedback" class="text-xs text-center hidden p-2 rounded-md"></p>
				
				<!-- Email Field -->
				<div>
					<label for="email" class="block text-sm/6 font-medium text-gray-100">Email address</label>
					<div class="mt-2">
						<input id="email" type="email" name="email" required autocomplete="email" class="block w-full rounded-md bg-white/5 px-3 py-1.5 text-base text-white outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
					</div>
				</div>

				<!-- First Name Field -->
				<div>
					<label for="first_name" class="block text-sm/6 font-medium text-gray-100">First Name</label>
					<div class="mt-2">
						<input id="first_name" type="text" name="first_name" required class="block w-full rounded-md bg-white/5 px-3 py-1.5 text-base text-white outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
					</div>
				</div>

				<!-- Last Name Field -->
				<div>
					<label for="last_name" class="block text-sm/6 font-medium text-gray-100">Last Name</label>
					<div class="mt-2">
						<input id="last_name" type="text" name="last_name" required class="block w-full rounded-md bg-white/5 px-3 py-1.5 text-base text-white outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
					</div>
				</div>

				<!-- Password Field -->
				<div>
					<div class="flex items-center justify-between">
						<label for="password" class="block text-sm/6 font-medium text-gray-100">Password</label>
					</div>
					<div class="mt-2 relative">
						<input id="password" type="password" name="password" required autocomplete="new-password" class="block w-full rounded-md bg-white/5 px-3 py-1.5 pr-10 text-base text-white outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
						<button type="button" id="toggle-password" class="absolute inset-y-0 right-0 pr-3 px-1 flex items-center text-gray-400 hover:text-gray-300">
							<svg id="eye-closed" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
							</svg>
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
								<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
								8 to 50 characters
							</li>
							<li id="req-uppercase" class="text-red-400 flex items-center">
								<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
								An uppercase letter (A-Z)
							</li>
							<li id="req-lowercase" class="text-red-400 flex items-center">
								<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
								A lowercase letter (a-z)
							</li>
							<li id="req-number" class="text-red-400 flex items-center">
								<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
								A number (0-9)
							</li>
						</ul>
					</div>
				</div>

				<!-- Confirm Password Field -->
				<div>
					<div class="flex items-center justify-between">
						<label for="retype_password" class="block text-sm/6 font-medium text-gray-100">Confirm Password</label>
					</div>
					<div class="mt-2 relative">
						<input id="retype_password" type="password" name="retype_password" required autocomplete="new-password" class="block w-full rounded-md bg-white/5 px-3 py-1.5 pr-10 text-base text-white outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
						<button type="button" id="toggle-retype-password" class="absolute inset-y-0 right-0 pr-3 px-1 flex items-center text-gray-400 hover:text-gray-300">
							<svg id="eye-closed-retype" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
							</svg>
							<svg id="eye-open-retype" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
							</svg>
						</button>
					</div>
					<p id="match-error" class="text-red-400 text-xs mt-1 hidden">Passwords do not match.</p>
				</div>

				<div>
					<button id="create-admin-button" type="submit" class="flex w-full justify-center rounded-md bg-indigo-500 px-3 py-1.5 text-sm/6 font-semibold text-white hover:bg-indigo-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 cursor-pointer">Create Admin Account</button>
				</div>
			</form>

			<div class="my-5">
				<p class="my-2 text-center text-sm/6 text-gray-400">
					Already have an admin account?
					<a href="<?php echo site_url('Auth/LoginController/show_login'); ?>" class="font-semibold text-indigo-400 hover:text-indigo-300">Sign in</a>
				</p>
			</div>
		</div>
	</div>

	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const form = document.getElementById('admin-create-form');
			const passwordInput = document.getElementById('password');
			const retypePasswordInput = document.getElementById('retype_password');
			const feedbackArea = document.getElementById('create-feedback');
			const createButton = document.getElementById('create-admin-button');
			const matchError = document.getElementById('match-error');
			const passwordRequirements = document.getElementById('password-requirements');

			// Password visibility toggles
			const togglePassword = document.getElementById('toggle-password');
			const toggleRetypePassword = document.getElementById('toggle-retype-password');
			const eyeClosed = document.getElementById('eye-closed');
			const eyeOpen = document.getElementById('eye-open');
			const eyeClosedRetype = document.getElementById('eye-closed-retype');
			const eyeOpenRetype = document.getElementById('eye-open-retype');

			// Password requirements elements
			const reqLength = document.getElementById('req-length');
			const reqUppercase = document.getElementById('req-uppercase');
			const reqLowercase = document.getElementById('req-lowercase');
			const reqNumber = document.getElementById('req-number');

			// Show/hide password requirements
			passwordInput.addEventListener('focus', function() {
				passwordRequirements.classList.remove('hidden');
			});

			// Real-time password validation
			passwordInput.addEventListener('input', function() {
				const password = passwordInput.value;
				
				// Check length
				if (password.length >= 8 && password.length <= 50) {
					updateRequirement(reqLength, true);
				} else {
					updateRequirement(reqLength, false);
				}

				// Check uppercase
				if (/[A-Z]/.test(password)) {
					updateRequirement(reqUppercase, true);
				} else {
					updateRequirement(reqUppercase, false);
				}

				// Check lowercase
				if (/[a-z]/.test(password)) {
					updateRequirement(reqLowercase, true);
				} else {
					updateRequirement(reqLowercase, false);
				}

				// Check number
				if (/[0-9]/.test(password)) {
					updateRequirement(reqNumber, true);
				} else {
					updateRequirement(reqNumber, false);
				}
			});

			// Check password match
			function checkPasswordMatch() {
				if (retypePasswordInput.value && passwordInput.value !== retypePasswordInput.value) {
					matchError.classList.remove('hidden');
				} else {
					matchError.classList.add('hidden');
				}
			}

			retypePasswordInput.addEventListener('input', checkPasswordMatch);
			passwordInput.addEventListener('input', checkPasswordMatch);

			// Update requirement styling
			function updateRequirement(element, isValid) {
				const icon = element.querySelector('svg');
				if (isValid) {
					element.classList.remove('text-red-400');
					element.classList.add('text-green-400');
					icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>';
				} else {
					element.classList.remove('text-green-400');
					element.classList.add('text-red-400');
					icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>';
				}
			}

			// Password visibility toggles
			togglePassword.addEventListener('click', function() {
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

			toggleRetypePassword.addEventListener('click', function() {
				if (retypePasswordInput.type === 'password') {
					retypePasswordInput.type = 'text';
					eyeClosedRetype.classList.add('hidden');
					eyeOpenRetype.classList.remove('hidden');
				} else {
					retypePasswordInput.type = 'password';
					eyeClosedRetype.classList.remove('hidden');
					eyeOpenRetype.classList.add('hidden');
				}
			});

			// Form submission
			form.addEventListener('submit', function(e) {
				e.preventDefault();
				
				createButton.disabled = true;
				createButton.textContent = 'Creating...';
				feedbackArea.classList.add('hidden');

				const formData = new URLSearchParams(new FormData(form));

				fetch(form.action, {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: formData
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						feedbackArea.textContent = data.message;
						feedbackArea.classList.remove('hidden', 'text-red-400');
						feedbackArea.classList.add('text-green-400');
						
						setTimeout(() => {
							window.location.href = data.redirect_url;
						}, 2000);
					} else {
						feedbackArea.textContent = data.message;
						feedbackArea.classList.remove('hidden', 'text-green-400');
						feedbackArea.classList.add('text-red-400');
					}
				})
				.catch(error => {
					console.error('Error:', error);
					feedbackArea.textContent = 'An error occurred. Please try again.';
					feedbackArea.classList.remove('hidden', 'text-green-400');
					feedbackArea.classList.add('text-red-400');
				})
				.finally(() => {
					createButton.disabled = false;
					createButton.textContent = 'Create Admin Account';
				});
			});
		});
	</script>
</body>
</html>