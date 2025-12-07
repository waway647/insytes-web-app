<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

class AccountMainController extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->library('session');
	}
	public function index()
	{
		$data['title'] = 'Account';
		$data['main_content'] = 'account/account_main';
		$this->load->view('layouts/main', $data);
	}

	public function show_login()
	{
		$this->load->view('auth/login');
	}

	public function logout()
	{
		// Destroy the session and redirect to login page
		$this->session->sess_destroy();
		$this->load->view('auth/login');
	}
}
