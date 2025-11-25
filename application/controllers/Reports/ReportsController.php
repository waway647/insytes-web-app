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
		$raw_match_id   = $this->input->get('match_id', true);
		$raw_match_name = $this->input->get('match_name', true);

		$match_id = $raw_match_id ?: $this->session->userdata('current_match_id');
		$match_name = $raw_match_name ?: $this->session->userdata('current_match_name');

		$sanitized_match_id = $match_id ? preg_replace('/[^A-Za-z0-9_\-]/', '', $match_id) : null;
		$sanitized_match_name = $match_name ? preg_replace('/[^A-Za-z0-9_\-]/', '', $match_name) : null;

		$data = [
			'title' => 'reports',
			'main_content' => 'reports/report',
			'report_content' => 'reports/match_overview/match_overview',
			'requested_match_id' => $raw_match_id ?? null,
			'requested_match_name' => $raw_match_name ?? null,
			'match_id' => $sanitized_match_id ?? null,
			'match_name' => $sanitized_match_name ?? null,
		];

		$this->load->view('layouts/main', $data);
	}
}
