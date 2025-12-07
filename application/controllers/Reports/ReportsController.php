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
            // Fetch all matches from Reports_Model (should include competition and season columns if available)
            $matches = $this->Reports_Model->get_all_matches($my_team_id);

            if (empty($matches)) {
                $this->output->set_output(json_encode([
                    'success' => true,
                    'season' => date('Y') . '/' . (date('Y') + 1),
                    'seasons' => [],
                    'competitions' => [],
                    'months' => []
                ]));
                return;
            }

            $grouped = [];
            $seasonsSet = [];
            $compsSet = [];

            // We'll also collect timestamps so we can sort months reliably
            foreach ($matches as $match) {
                // parse timestamp; fall back to now if parse fails
                $timestamp = strtotime($match['match_date']);
                if ($timestamp === false) $timestamp = time();

                $monthName = date('F', $timestamp);
                $year = date('Y', $timestamp);

                // Prefer DB-provided season column, otherwise compute heuristically (Jul-Jun)
                if (!empty($match['season'])) {
                    $season = $match['season'];
                } else {
                    $monthNum = (int) date('n', $timestamp);
                    if ($monthNum >= 7) {
                        $season = $year . '/' . ($year + 1);
                    } else {
                        $season = ($year - 1) . '/' . $year;
                    }
                }

                // competition if present
                $competition = !empty($match['competition']) ? $match['competition'] : null;

                if ($season) $seasonsSet[$season] = true;
                if ($competition) $compsSet[$competition] = true;

                $groupKey = "{$monthName}_{$year}";
                if (!isset($grouped[$groupKey])) {
                    $grouped[$groupKey] = [
                        'monthName' => $monthName,
                        'year' => (int)$year,
                        'matches' => [],
                        // we'll keep a maxTimestamp to help sorting later
                        'maxTimestamp' => 0
                    ];
                }

                // track max timestamp for month
                if ($timestamp > $grouped[$groupKey]['maxTimestamp']) {
                    $grouped[$groupKey]['maxTimestamp'] = $timestamp;
                }

                // Determine status color based on status text
                $statusColor = '#B6BABD'; // default gray
                switch (strtolower($match['status'] ?? '')) {
                    case 'ready': $statusColor = '#48ADF9'; break;
                    case 'completed': $statusColor = '#209435'; break;
                    case 'tagging in progress': $statusColor = '#B14D35'; break;
                    case 'waiting for video': $statusColor = '#B6BABD'; break;
                }

                $matchName = 'vs. ' . ($match['opponent_team_name'] ?? 'Unknown');
                $matchNameConfig = 'sbu_vs_' . strtolower(str_replace(' ', '_', $match['opponent_team_abbreviation'] ?? 'unknown'));

                $thumbnailUrl = base_url('assets/images/thumbnails/default.jpg');
                if (!empty($match['video_thumbnail'])) {
                    $thumbnailUrl = base_url($match['video_thumbnail']);
                }

                $grouped[$groupKey]['matches'][] = [
                    'matchId' => $match['match_id'],
                    'thumbnailUrl' => $thumbnailUrl,
                    'status' => $match['status'] ?? '',
                    'statusColor' => $statusColor,
                    'matchName' => $matchName,
                    'matchNameConfig' => $matchNameConfig,
                    'matchDate' => date('M d', $timestamp),
                    'MyTeamResult' => $match['my_team_result'] ?? null,
                    // exact columns included for filtering by frontend
                    'competition' => $competition,
                    'season' => $season,
                    // keep raw ISO-style date for frontend parsing
                    'raw_match_date' => date('Y-m-d', $timestamp),
                    // include original match_date if present
                    'match_date' => $match['match_date'] ?? null,
                    // include timestamp for easier client-side sorting/debugging (optional)
                    'matchTimestamp' => $timestamp
                ];
            }

            // Convert grouped to indexed array and sort months by maxTimestamp desc
            $months = array_values($grouped);
            usort($months, function($a, $b) {
                $at = isset($a['maxTimestamp']) ? (int)$a['maxTimestamp'] : 0;
                $bt = isset($b['maxTimestamp']) ? (int)$b['maxTimestamp'] : 0;
                return $bt - $at;
            });

            // Remove helper maxTimestamp from response (we don't need to expose it)
            foreach ($months as &$m) {
                unset($m['maxTimestamp']);
            }
            unset($m);

            // Prepare distinct lists for dropdowns
            $seasons = array_values(array_keys($seasonsSet));
            rsort($seasons); // newest-first
            $competitions = array_values(array_keys($compsSet));
            sort($competitions);

            $topSeason = !empty($seasons) ? $seasons[0] : (date('Y') . '/' . (date('Y') + 1));

            $data = [
                'success' => true,
                'season' => $topSeason,
                'seasons' => $seasons,
                'competitions' => $competitions,
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
