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
		<div class="sm:mx-auto sm:w-full sm:max-w-sm">
			<img src="<?php echo base_url('assets/images/logo/logo-indigo.png'); ?>" alt="Your Company" class="mx-auto h-12 w-auto" />
			<h2 class="mt-10 text-center text-2xl/9 font-bold tracking-tight text-white">Welcome to Insytes</h2>
		</div>

		<div>
			<a href="#" class="px-6 py-3 border border-gray-700 rounded-[100px] flex items-center justify-center gap-3 cursor-pointer hover:bg-gray-800 max-w-sm mx-auto my-6"">
			<img src="<?php echo base_url('assets/images/icons/google.svg'); ?>" alt="">
			<div class="w-full text-center">
				<h4 class="text-gray-100">Continue with Google</h4>
			</div>
			</a>
		</div>

		<div class="text-center">
			<p class="font-medium text-gray-400">or</p>
		</div>

		<div class="mt-6 sm:mx-auto sm:w-full sm:max-w-sm">
			<form action="#" method="POST" class="space-y-6">
			<div class="flex items-center justify-center gap-1.5">
				<span class="w-px h-px p-1 bg-indigo-400 text-xs rounded-2xl">&nbsp</span>
				<span class="w-px h-px p-0.5 bg-gray-400 text-xs rounded-2xl">&nbsp</span>
				<span class="w-px h-px p-0.5 bg-gray-400 text-xs rounded-2xl">&nbsp</span>
			</div>
			<div>
				<label for="email" class="block text-sm/6 font-medium text-gray-100">Email address</label>
				<div class="mt-2">
				<input id="email" type="email" name="email" required autocomplete="email" class="block w-full rounded-md bg-white/5 px-3 py-1.5 text-base text-white outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
				</div>
			</div>

			<div>
				<button type="submit" class="flex w-full justify-center rounded-md bg-indigo-500 px-3 py-1.5 text-sm/6 font-semibold text-white hover:bg-indigo-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 cursor-pointer">Continue</button>
			</div>
			</form>

			<div class="my-5">
				<p class="my-2 text-center text-sm/6 text-gray-400">
				<a href="#" class="font-semibold text-indigo-400 hover:text-indigo-300">Forgot your password?</a>
				</p>

				<p class="my-2 text-center text-sm/6 text-gray-400">
				<a href="#" class="font-semibold text-indigo-400 hover:text-indigo-300">Login to an existing account</a>
				</p>
			</div>
		</div>
	</div>
</body>
</html>
