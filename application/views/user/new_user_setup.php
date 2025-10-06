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
  <body>
    <div class="flex flex-col w-[1440px] h-[1024px] items-center justify-center gap-[50px] px-5 py-[150px] relative bg-[#131314]">
        <div class="sm:mx-auto sm:w-full sm:max-w-sm flex flex-col gap-2">
			    <img src="<?php echo base_url('assets/images/logo/logo-indigo.png'); ?>" alt="Your Company" class="mx-auto h-12 w-auto" />
          <p class="text-white font-semibold text-sm">Insytes</p>
	      </div>
        <p class="relative w-[594px] [font-family:'Inter-Light',Helvetica] font-light text-white text-5xl text-center tracking-[4.80px] leading-[normal]">
          Which role are you in?
        </p>

        <div class="flex w-[580px] items-center gap-[70px] relative flex-[0_0_auto] ">
          <div class="inline-flex flex-col items-center gap-[50px] px-5 py-0 relative flex-[0_0_auto]">
            <div class="flex w-[100px] h-[100px] items-center gap-2.5 p-[25px] relative rounded-[30px]">
              <a href="#" class="px-6 py-3 border border-gray-700  bg-indigo-500 rounded-[100px] flex items-center justify-center gap-3 cursor-pointer hover:bg-gray-800 max-w-sm mx-auto my-6">
                <img
                  class="relative w-[50px] h-[50px] aspect-[1] object-cover"
                  alt="User coach"
                  src="<?php echo base_url('assets/images/icons/user-coach.png'); ?>"
                />
              </a>
            </div>

            <div class="relative w-[169px] [font-family:'Inter-Light',Helvetica] font-light text-white text-[32px] text-center tracking-[3.20px] leading-[normal]">
              Coach
            </div>
        </div>

        <div class="h-[289px] inline-flex flex-col items-center gap-[50px] px-5 py-0 relative flex-[0_0_auto]">
          <div class="inline-flex flex-col items-start justify-center flex-[0_0_auto] gap-2.5 p-[25px] relative rounded-[30px]">
            <a href="#" class="px-6 py-3 border border-gray-700  bg-indigo-500 rounded-[100px] flex items-center justify-center gap-3 cursor-pointer hover:bg-gray-800 max-w-sm mx-auto my-6">
              <img
                class="relative w-[250px] h-[250px] aspect-[1] object-cover"
                alt="Football player"
                src="<?php echo base_url('assets/images/icons/football-player.png'); ?>"
              />
            </a>
          </div>

          <div class="relative w-[169px] [font-family:'Inter-Light',Helvetica] font-light text-2xl text-white text-[32px] text-center tracking-[3.20px] leading-[normal]">
            Player
          </div>
        </div>
      </div>
    </div>
  </body>
</html>