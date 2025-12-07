<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class LibraryController extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->library('session');
        $this->load->model('LibraryModel');
        $this->load->database();
        // Allow JSON responses

	}
	public function index()
	{
		$data['title'] = 'Match Library';
		$data['main_content'] = 'match/library';
		$this->load->view('layouts/main', $data);
	}

	public function get_all_matches()
    {
        $this->output->set_content_type('application/json');

        $my_team_id = $this->session->userdata('team_id');

        try {
            // Fetch all matches
            $matches = $this->LibraryModel->get_all_matches($my_team_id);

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

    // GET /match/library_controller/get_items/{type}
    public function get_items($type = null) {
        if (!$type && $this->input->get('type')) {
            $type = $this->input->get('type');
        }

        if (!$type) {
            echo json_encode(['success' => false, 'message' => 'Missing type', 'items' => []]);
            return;
        }

        $items = $this->LibraryModel->get_items($type);
        if ($items === false) {
            echo json_encode(['success' => false, 'message' => 'Invalid type', 'items' => []]);
            return;
        }

        echo json_encode(['success' => true, 'items' => $items]);
    }

    public function get_item()
    {
        $type = $this->input->get('type');
        $id = $this->input->get('id');
        
        // Fetch item
        $item = $this->LibraryModel->get_item($type, $id);

        if (!$item) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Invalid type or item not found'
                ]));
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
                'item' => $item
            ]));
    }

    public function load_modal()
    {
        $this->load->helper('url'); // if not already loaded
        $name = $this->input->get('name', true); // expected keys like 'add_new_season', 'edit_season', etc.
        if (!$name) {
            show_error('Missing modal name', 400);
            return;
        }

        // map valid modal keys to view paths
        $mapping = [
            'add_new_season'         => 'match/utils/modals/add_season',
            'edit_season'            => 'match/utils/modals/edit_season',
            'add_new_competition'    => 'match/utils/modals/add_competition',
            'edit_competition'       => 'match/utils/modals/edit_competition',
            'add_new_venue'          => 'match/utils/modals/add_venue',
            'edit_venue'             => 'match/utils/modals/edit_venue',
            'add_new_team'           => 'match/utils/modals/add_team',
            'edit_team'              => 'match/utils/modals/edit_team',
            'add_new_player'         => 'match/utils/modals/add_player',
            'edit_player'         => 'match/utils/modals/edit_player'
        ];

        if (!isset($mapping[$name])) {
            show_error('Modal not found', 404);
            return;
        }

        // render view as string and echo - do not run full layout
        $html = $this->load->view($mapping[$name], [], true);
        // optional: wrap with a container if modals do not include data-modal root
        echo $html;
    }

    public function start_tagging()
    {
        $input = json_decode(trim(file_get_contents('php://input')), true);
        if (!$input || !isset($input['match_id'])) {
            $this->output->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success'=>false,'message'=>'Missing match_id']));
            return;
        }

        $match_id = $input['match_id'];

        $data = $this->LibraryModel->get_match_data($match_id);

        $video_file_name_for_tagging = str_replace('assets/videos/matches/match_' . $match_id . '/', '', $data['video_url']);

        // SESSION
            $this->session->set_userdata('active_match_id', $match_id);

            $this->session->set_userdata('my_team_id', $data['my_team_id']);
            $this->session->set_userdata('my_team_name', $data['my_team_name']);
            $this->session->set_userdata('my_team_abbreviation', $data['my_team_abbreviation']);

            $this->session->set_userdata('opponent_team_id', $data['opponent_team_id']);
            $this->session->set_userdata('opponent_team_name', $data['opponent_team_name']);
            $this->session->set_userdata('opponent_team_abbreviation', $data['opponent_team_abbreviation']);

            $this->session->set_userdata('tagging_video_url', $data['video_url']);
            $this->session->set_userdata('tagging_thumbnail_url', $data['video_thumbnail']);
            $this->session->set_userdata('video_file_name_for_tagging', $video_file_name_for_tagging);

        $redirect_url = site_url('studio/mediacontroller/index?match_id=' . $match_id);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
                'redirect_url' => $redirect_url
            ]));
    }
}
