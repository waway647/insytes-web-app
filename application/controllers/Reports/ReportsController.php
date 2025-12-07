<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ReportsController extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->library('session');
		$this->load->Model('Reports_Model');
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

	public function get_all_match_reports()
    {
        $this->output->set_content_type('application/json');

        $my_team_id = $this->session->userdata('team_id');

        try {
            // Fetch all matches
            $matches = $this->Reports_Model->get_all_matches($my_team_id);

            if (empty($matches)) {
                $this->output->set_output(json_encode([
                    'success' => true,
                    'season' => date('Y') . '/' . (date('Y') + 1),
                    'months' => []
                ]));
                return;
            }

            // Group matches by month-year
            $grouped = [];
            foreach ($matches as $match) {
                $timestamp = strtotime($match['match_date']);
                $monthName = date('F', $timestamp);
                $year = date('Y', $timestamp);

                $groupKey = "{$monthName}_{$year}";
                if (!isset($grouped[$groupKey])) {
                    $grouped[$groupKey] = [
                        'monthName' => $monthName,
                        'year' => (int)$year,
                        'matches' => []
                    ];
                }

                // Determine status color based on status text
                $statusColor = '#B6BABD'; // default gray
                switch (strtolower($match['status'])) {
                    case 'ready': $statusColor = '#48ADF9'; break;
                    case 'completed': $statusColor = '#209435'; break;
                    case 'tagging in progress': $statusColor = '#B14D35'; break;
                    case 'waiting for video': $statusColor = '#B6BABD'; break;
                }

                // Format match display name (e.g., vs. La Salle)
                $matchName = 'vs. ' . ($match['opponent_team_name'] ?? 'Unknown');

                $matchNameConfig = 'sbu_vs_' . strtolower(str_replace(' ', '_', $match['opponent_team_abbreviation'] ?? 'unknown'));

                // Optional: provide a placeholder thumbnail
                $thumbnailUrl = base_url('assets/images/thumbnails/default.jpg');
                if (!empty($match['video_thumbnail'])) {
                    $thumbnailUrl = base_url($match['video_thumbnail']);
                }

                $grouped[$groupKey]['matches'][] = [
                    'matchId' => $match['match_id'],
                    'thumbnailUrl' => $thumbnailUrl,
                    'status' => $match['status'],
                    'statusColor' => $statusColor,
                    'matchName' => $matchName,
                    'matchNameConfig' => $matchNameConfig,
                    'matchDate' => date('M d', $timestamp),
                    'MyTeamResult' => $match['my_team_result']
                ];
            }

            // Transform grouped array to indexed array
            $months = array_values($grouped);

            $data = [
                'success' => true,
                'season' => '2025/2026',
                'months' => $months
            ];

            $this->output->set_output(json_encode($data));

        } catch (Exception $e) {
            log_message('error', 'get_all_matches failed: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Failed to fetch matches'
                ]));
        }
    }
}
