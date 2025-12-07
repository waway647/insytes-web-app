<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MatchApi extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->helper('url');
    }

    // GET /matchapi/summary?match_name=...
    public function summary()
    {
        $match_name = $this->input->get('match_name', true) ?: $this->session->userdata('current_match_name');
        $sanitized = $match_name ? preg_replace('/[^A-Za-z0-9_\-]/', '', $match_name) : '';

        if ($sanitized === '') {
            return $this->output
                        ->set_status_header(400)
                        ->set_content_type('application/json')
                        ->set_output(json_encode(null));
        }

        $path = FCPATH . 'output/matches/' . $sanitized . '/san_beda_university_player_insights.json';
        if (!file_exists($path)) {
            return $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode(null));
        }

        $raw = @file_get_contents($path);
        if ($raw === false) {
            return $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode(null));
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode(null));
        }

        // Build lightweight summary only
        $summary = [
            'top_scorers' => $data['top_scorers'] ?? null,
            'top_defenders' => $data['top_defenders'] ?? null,
            'top_passers' => $data['top_passers'] ?? null,
            'match_recommendations' => $data['match_recommendations'] ?? null,
        ];

        return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode($summary));
    }
}
