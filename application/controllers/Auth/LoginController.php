<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

class LoginController extends CI_Controller {
	public function __construct() {
        parent::__construct();
        // Load the URL helper to enable base_url()
        $this->load->helper('url'); 
    }

	public function show_login()
	{
		$this->load->view('auth/login');
	}
}
