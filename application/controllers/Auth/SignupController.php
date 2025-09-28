<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SignupController extends CI_Controller {
	public function __construct() {
        parent::__construct();
        // Load the URL helper to enable base_url()
        $this->load->helper('url'); 
    }

	public function show_signup_step1()
	{
		$this->load->view('auth/signup_step1');
	}

	public function show_signup_step2()
	{
		$this->load->view('auth/signup_step2');
	}

	public function show_signup_step3()
	{
		$this->load->view('auth/signup_step3');
	}
}
