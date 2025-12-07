<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class DashboardController extends CI_Controller {
	public function __construct() {
		parent::__construct();
			$this->load->helper('admin_auth');
			$this->load->helper('url');
			$this->load->library('session');
			
			/* // Check admin authentication for all methods in this controller
			check_admin_access(); */
	}

	public function adminDashboard()
	{
		$data['title'] = 'Dashboard';
		$data['main_content'] = 'admin/dashboard';
		$this->load->view('layouts/main', $data);
	}
}
