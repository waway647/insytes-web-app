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
		$data['main_content'] = 'reports/lists';
		$this->load->view('layouts/main', $data);
	}
	public function match_report()
	{
		$data['title'] = 'reports';
		$data['main_content'] = 'reports/report';
		$data['report_content'] = 'reports/match_overview/match_overview'; // DEFAULT
		$this->load->view('layouts/main', $data);
	}
}
