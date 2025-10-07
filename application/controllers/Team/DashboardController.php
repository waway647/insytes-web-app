<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class DashboardController extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->library('session');
	}
	public function index()
	{
		$data['title'] = 'Dashboard';
		$data['main_content'] = 'team/dashboard';
		$data['role'] = $this->session->userdata('role');
		$this->load->view('layouts/main', $data);
	}

}
