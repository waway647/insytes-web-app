<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class TeamSummaryController extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
    }
    public function index()
    {
        $raw_match_id   = $this->input->get('match_id', true);
        $raw_match_name = $this->input->get('match_name', true);

        $match_id = $raw_match_id ?: $this->session->userdata('current_match_id');
        $match_name = $raw_match_name ?: $this->session->userdata('current_match_name');

        $sanitized_match_id = $match_id ? preg_replace('/[^A-Za-z0-9_\-]/', '', $match_id) : null;
        $sanitized_match_name = $match_name ? preg_replace('/[^A-Za-z0-9_\-]/', '', $match_name) : null;

        $session_match_config = $this->session->userdata('current_match_config');
        $session_team_metrics = $this->session->userdata('current_team_metrics');
        $session_metrics_file_path = $this->session->userdata('current_metrics_file_path');
        $session_metrics_file_url = $this->session->userdata('current_metrics_file_url');

        $data = [
            'title' => 'Team Summary',
            'main_content' => 'reports/report',
            'report_content' => 'reports/team_summary/team_summary',
            'requested_match_id' => $raw_match_id ?? null,
            'requested_match_name' => $raw_match_name ?? null,
            'match_id' => $sanitized_match_id ?? null,
            'match_name' => $sanitized_match_name ?? null,
            'match_config' => $session_match_config ?? null,
            'team_metrics' => $session_team_metrics ?? null,
            'metrics_file_path' => $session_metrics_file_path ?? '',
            'metrics_file_url' => $session_metrics_file_url ?? null,
        ];

        $this->load->view('layouts/main', $data);
    }
}
