<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class TeamSummaryController extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->library('session');
	}
	public function index()
	{
		$match_id = $this->input->get('match_id', true) ?: $this->session->userdata('current_match_id');
		$match_name = $this->input->get('match_name', true) ?: $this->session->userdata('current_match_name');

		// If still empty -> show selection UI or error
		if (!$match_id || !$match_name) {
			// fallback behaviour
		}

		$data['title'] = 'Team Summary';
		$data['main_content'] = 'reports/report';
		$data['report_content'] = 'reports/team_summary/team_summary';
		$this->load->view('layouts/main', $data);
	}
}
