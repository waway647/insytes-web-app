<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Coach Step 1</title>
  <link href="<?php echo base_url('assets/css/tailwind_output.css'); ?>" rel="stylesheet">
</head>
<body class= "bg-gray-900 min-h-screen flex flex-col">

  <!-- Top Left Logo -->
  <div class="flex p-5">
    <img src="<?php echo base_url('assets/images/logo/logo-indigo.png'); ?>" alt="Insytes Logo" class="h-8">
    <p class="text-white font-medium text-lg">Insytes</p>
  </div>

  <!-- Centered Card -->
  <div class="flex-grow flex items-center justify-center">

    <!-- Show image of the team logo here -->

    <div class="bg-[#1D1D1D] rounded-2xl shadow-xl p-8 w-full max-w-lg h-[400px] flex-col flex justify-center">
        <div class="flex justify-center mb-6">
            <div class="w-24 h-24 flex items-center justify-center">
                <img src="<?php echo base_url($user['team_logo']); ?>" alt="Team Logo" class="max-w-full max-h-full object-contain rounded-lg shadow-lg border-2 border-gray-600">
            </div>
        </div>
        <div>
            <!-- Title -->
            <h1 class="text-center text-2xl font-semibold text-white mb-2">
                You successfully created <span class="font-semibold"><?php echo $user['team_name']; ?></span> team!
            </h1>
            <p class="text-center text-gray-400 text-sm mb-6">
                Good luck on your coaching journey! 
            </p>
        </div>

        <div class="flex justify-center">
            <!-- Button -->
            <button type="button" onclick="window.location.href='<?php echo site_url('/Team/DashboardController/index'); ?>'"
                class="flex w-full justify-center rounded-md bg-indigo-500 px-3 py-1.5 text-sm/6 font-semibold text-white hover:bg-indigo-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 cursor-pointer">
                Continue
            </button>
        </div>
    </div>
  </div>

</body>
</html>