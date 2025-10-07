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
<body class="bg-gray-900 min-h-screen flex flex-col overflow-hidden">

  <!-- Top Left Logo -->
  <div class="flex p-5">
    <img src="<?php echo base_url('assets/images/logo/logo-indigo.png'); ?>" alt="Insytes Logo" class="h-8">
    <p class="text-white font-medium text-lg">Insytes</p>
  </div>

  <!-- Centered Card -->
  <div class="flex-grow flex items-center justify-center">
    <div class="bg-[#1D1D1D] rounded-2xl shadow-xl w-full max-w-md">
      <!-- bg-neutral-900 rounded-2xl shadow-xl p-6 w-full max-w-sm -->
      
      <!-- Header -->
      <div class="p-5 space-y-2">
        <!-- Title -->
        <h1 class="text-center text-2xl font-semibold text-white">
        Create a Team!
        </h1>
        <p class="text-center text-gray-400 text-sm ">
        Start by creating your football team.
        </p>
      </div>

      <!-- Form -->
      <div class="px-10 mb-8 mt-5 ">
        <form id="create-team-form" action="http://localhost/github/insytes-web-app/index.php/User/NewUserController/userCoach_create_team" 
          method="POST" class="h-full space-y-4" enctype="multipart/form-data">
        <!-- Input -->
        <div>
          <label for="team_name" class="block text-sm font-medium text-gray-300 mb-1">Team Name</label>
          <input id="team_name" type="text" placeholder="e.g., FC Barcelona" name="team_name" required
          class="w-full px-3 py-1.5 rounded-md bg-neutral-800 text-gray-200 placeholder-gray-500 border border-neutral-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <div>
          <label for="country" class="block text-sm font-medium text-gray-300 mb-1">Country</label>
          <input id="country" type="text" placeholder="e.g., Spain" name="country" required
          class="w-full px-3 py-1.5 rounded-md bg-neutral-800 text-gray-200 placeholder-gray-500 border border-neutral-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <div>
          <label for="city" class="block text-sm font-medium text-gray-300 mb-1">City</label>
          <input id="city" type="text" placeholder="e.g., Barcelona" name="city" required
          class="w-full px-3 py-1.5 rounded-md bg-neutral-800 text-gray-200 placeholder-gray-500 border border-neutral-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <div>
          <label for="team_logo" class="block text-sm font-medium text-gray-300 mb-1">
            Team Logo
          </label>

          <!-- Hidden real file input -->
          <input id="team_logo" type="file" accept="image/*" class="hidden" name="team_logo" required>

          <!-- Custom label button -->
          <label id="teamLogoLabel" for="team_logo"
            class="flex items-center justify-center w-full px-3 py-1.5 cursor-pointer rounded-md bg-neutral-800 hover:bg-neutral-700 border border-neutral-700 text-gray-300 text-sm font-medium gap-2 transition">
            <img id="uploadIcon" src="<?php echo base_url('assets/images/icons/attach-file.png'); ?>" alt="Upload Icon" class="h-5 w-5">
            <span id="teamLogoText">Attach Logo</span>
          </label>
        </div>


        <div>
          <label for="primary_color" class="block text-sm font-medium text-gray-300">Primary Team Color</label>
          <input id="primary_color" type="color" name="primary_color" required
          class="w-full p-0.5 h-10 cursor-pointer rounded-md bg-transparent focus:outline-none" style="border: none;">
        </div>

        <div>
          <label for="secondary_color" class="block text-sm font-medium text-gray-300">Secondary Team Color</label>
          <input id="secondary_color" type="color" name="secondary_color" required
          class="w-full p-0.5 h-10 cursor-pointer rounded-md bg-transparent focus:outline-none" style="border: none;">
        </div>

        <!-- Button -->
        <div>
          <button type="submit"
          class="flex w-full justify-center rounded-md bg-indigo-500 px-3 py-1.5 text-sm/6 font-semibold text-white hover:bg-indigo-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 cursor-pointer">
          Create Team
          </button>
        </div>
        </form>
      </div>
    </div>
  </div>
  <script src="<?php echo base_url('assets/js/fileHandler.js'); ?>"></script>
</body>
</html>
