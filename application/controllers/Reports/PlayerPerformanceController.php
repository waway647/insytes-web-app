<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PlayerPerformanceController extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
    }

    public function index()
    {
        // prefer GET, otherwise fallback to session
        $raw_match_id   = $this->input->get('match_id', true);
        $raw_match_name = $this->input->get('match_name', true);

        $match_id = $raw_match_id ?: $this->session->userdata('current_match_id');
        $match_name = $raw_match_name ?: $this->session->userdata('current_match_name');

        // optional: sanitize for view usage
        $sanitized_match_id = $match_id ? preg_replace('/[^A-Za-z0-9_\-]/', '', $match_id) : null;
        $sanitized_match_name = $match_name ? preg_replace('/[^A-Za-z0-9_\-]/', '', $match_name) : null;

        // Pull match_config and team_metrics persisted by OverviewController
        $session_match_config = $this->session->userdata('current_match_config');
        $session_team_metrics = $this->session->userdata('current_team_metrics');
        $session_metrics_file_path = $this->session->userdata('current_metrics_file_path');
        $session_metrics_file_url = $this->session->userdata('current_metrics_file_url');

        $data = [
            'title' => 'Player Performance',
            'main_content' => 'reports/report',
            'report_content' => 'reports/player_performance/player_performance',
            // preserve requested / sanitized tokens so tabs and links keep them
            'requested_match_id' => $raw_match_id ?? null,
            'requested_match_name' => $raw_match_name ?? null,
            'match_id' => $sanitized_match_id ?? null,
            'match_name' => $sanitized_match_name ?? null,
            // pass through persisted config & metrics so scoreboard.php / partials can use them
            'match_config' => $session_match_config ?? null,
            'team_metrics' => $session_team_metrics ?? null,
            'metrics_file_path' => $session_metrics_file_path ?? '',
            'metrics_file_url' => $session_metrics_file_url ?? null,
        ];

        $this->load->view('layouts/main', $data);
    }
}
