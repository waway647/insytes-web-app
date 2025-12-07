<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

class MainController extends CI_Controller {
	public function __construct() {
        parent::__construct();
        // Load the URL helper to enable base_url()
        $this->load->helper('url'); 
		$this->load->library('session');
    }

	public function index()
	{
		$this->load->view('layouts/main');
	}

	public function dashboard() 
	{
		// Check if user is logged in
		if (!$this->session->userdata('user_id')) {
			redirect('auth/login');
			return;
		}
		
		// Redirect based on user role
		$role = $this->session->userdata('role');
		
		switch(strtolower($role)) {
			case 'admin':
				redirect('admin/dashboard');
				break;
			case 'coach':
				redirect('team/dashboard');
				break;
			case 'player':
				redirect('team/dashboard');
				break;
			default:
				$this->load->view('layouts/main');
		}
	}
}
