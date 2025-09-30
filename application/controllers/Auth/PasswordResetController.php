<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PasswordResetController extends CI_Controller {
	public function __construct() {
        parent::__construct();
        // Load the URL helper to enable base_url()
        $this->load->helper('url'); 
    }

	public function show_password_reset_step1()
	{
		$this->load->view('auth/forgot_pass_step1');
	}

	public function show_password_reset_step2()
	{
		$this->load->view('auth/forgot_pass_step2');
	}

	public function show_password_reset_step3()
	{
		$this->load->view('auth/forgot_pass_step3');
	}

	public function show_password_reset_success()
	{
		$this->load->view('auth/forgot_pass_success');
	}
}
