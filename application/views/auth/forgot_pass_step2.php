<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="en" class="h-full bg-gray-900">
<head>
	<meta charset="utf-8">
	<title>Insytes | Forgot Password</title>
	<link href="<?php echo base_url('assets/css/tailwind_output.css'); ?>" rel="stylesheet">
</head>
<body class="h-full">
	<div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8">
		<div class="sm:mx-auto sm:w-full sm:max-w-sm flex flex-col gap-2">
			<img src="<?php echo base_url('assets/images/logo/logo-indigo.png'); ?>" alt="Your Company" class="mx-auto h-12 w-auto" />
			<h2 class="mt-10 text-center text-2xl/9 font-bold tracking-tight text-white">Verify code</h2>
			<p class="text-center font-normal text-gray-400">Check email for code.</p>
			<p id="otp-timer" class="mt-3 text-l text-center font-bold text-gray-400"></p> <!-- change this into 3 minutes timer -->
		</div>

		<div class="mt-6 sm:mx-auto sm:w-full sm:max-w-sm">
			<!-- Hidden input to hold the concatenated 6-digit code for form submission -->
			<form id="otp-form" action="<?php echo site_url('auth/passwordresetcontroller/verify_code'); ?>" method="POST" class="space-y-6">
				<div id="otp-feedback-container"></div>
			
				<input type="hidden" id="final_otp_code" name="otp_code" value="">

				<label for="otp-container" class="block text-sm font-medium leading-6 text-gray-400 text-center">Enter 6-digit code</label>
				
				<div id="otp-container" class="mt-2 flex justify-center gap-4" data-length="6">
					<?php for ($i = 1; $i <= 6; $i++): ?>
						<!-- 
							Unique IDs and names are crucial. 
							maxlength="1" and inputmode="numeric" ensure a mobile-friendly single-digit input.
							The new Tailwind classes make the input box small and centered.
						-->
						<input 
							id="otp-<?php echo $i; ?>" 
							type="text" 
							name="otp_digit_<?php echo $i; ?>" 
							maxlength="1"
							inputmode="numeric"
							autocomplete="one-time-code"
							required 
							class="otp-input block w-10 h-14 text-center text-xl font-bold rounded-md bg-white/5 text-white outline-none border border-gray-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition duration-150" 
						/>
					<?php endfor; ?>
				</div>
				
				<div class="pt-4">
					<button type="submit" id="verify-button" class="flex w-full justify-center rounded-md bg-indigo-500 px-3 py-1.5 text-sm/6 font-semibold text-white hover:bg-indigo-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 cursor-pointer">
						Verify
					</button>
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

	<script>
		// 1. CRITICAL FIX: Pass the resend URL and the initial time from PHP
		const APP_CONFIG = {
			resendUrl: '<?php echo site_url('auth/passwordresetcontroller/resend_otp'); ?>',
			InitialTimeSeconds: 3 * 60 // 3 minutes
		};
	</script>
	
	<script src="<?php echo base_url(); ?>assets/js/otpFormHandler.js?<?php echo time(); ?>"></script>			
</body>
</html>
