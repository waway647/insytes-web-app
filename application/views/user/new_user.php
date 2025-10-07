<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

?><!DOCTYPE html>
<html lang="en" class="h-full bg-gray-900">
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta charset="utf-8" />
    <title>Insytes | New User Setup</title>
    <link href="<?php echo base_url('assets/css/tailwind_output.css'); ?>" rel="stylesheet">
  </head>
  <body class="flex min-h-screen flex-col items-center justify-center px-6 py-12">
    
    <!-- Logo -->
    <div class="flex flex-col items-center mb-10">
      <img src="<?php echo base_url('assets/images/logo/logo-indigo.png'); ?>" alt="Your Company" class="h-12 w-auto" />
      <p class="text-white font-medium text-lg">Insytes</p>
    </div>

    <!-- Title -->
    <h1 class="text-white text-3xl sm:text-4xl font-medium tracking-wide text-center">
      Which role are you in?
    </h1>

    <!-- Roles Container -->
    <div class="flex flex-col sm:flex-row items-center justify-center gap-15 w-full max-w-3xl p-10">
      
      <!-- Coach -->
      <div class="flex flex-col items-center p-5">
        <a href="http://localhost/github/insytes-web-app/index.php/User/NewUserController/userCoach_step1" class="role-link p-6 bg-indigo-500 rounded-[30px] hover:bg-indigo-400 transition">
          <img
            class="w-24 h-24 sm:w-32 sm:h-32 object-contain"
            alt="User coach"
            src="<?php echo base_url('assets/images/icons/user-coach.png'); ?>"
          />
        </a>
        <p class="text-white text-lg sm:text-xl mt-4 role-text">Coach</p>
      </div>

      <!-- Player -->
      <div class="flex flex-col items-center p-5">
        <a href="http://localhost/github/insytes-web-app/index.php/User/NewUserController/userPlayer_step1" class="role-link p-6 bg-indigo-500 rounded-[30px] hover:bg-indigo-400 transition">
          <img
            class="w-24 h-24 sm:w-32 sm:h-32 object-contain"
            alt="Football player"
            src="<?php echo base_url('assets/images/icons/football-player.png'); ?>"
          />
        </a>
        <p class="text-white text-lg sm:text-xl mt-4 role-text">Player</p>
      </div>

    </div>
    <script src="<?php echo base_url('assets/js/roleHandler.js'); ?>"></script>
  </body>
</html>
