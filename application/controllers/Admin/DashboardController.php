<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class DashboardController extends CI_Controller {
	public function adminDashboard()
	{
		$this->load->view('welcome_message');
	}
}
