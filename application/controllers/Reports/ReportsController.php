<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ReportsController extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->library('session');
	}
	public function index()
	{
		$data['title'] = 'reports';
		$data['main_content'] = 'reports/reports';
		$this->load->view('layouts/main', $data);
	}
}
