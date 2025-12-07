<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

class LibraryModel extends CI_Model {

    public function __construct()
    {
        $this->load->database();
    }

    public function get_all_matches($my_team_id)
    {
        $this->db->select('
            match_id,
            match_date,
            status,
            opponent_team_name,
            opponent_team_abbreviation,
            my_team_result,
            video_thumbnail
        ');
        $this->db->from('matches_vw');
        $this->db->where('my_team_id', $my_team_id);
        $this->db->order_by('match_date', 'DESC');

        $query = $this->db->get();

        if (!$query) {
            throw new Exception('Database error: ' . $this->db->error()['message']);
        }

        return $query->result_array();
    }

    public function get_items($type) {
        $mapping = [
            'season' => 'seasons',
            'competition' => 'competitions',
            'venue' => 'venues',
            'team' => 'teams',
            'player' => 'players'
        ];

        if (!isset($mapping[$type])) return false;

        $table = $mapping[$type];

        // 🔽 Apply sorting rules per type
        switch ($type) {
            case 'season':
                $this->db->order_by('start_year', 'DESC');
                break;
            case 'competition':
                $this->db->order_by('name', 'ASC');
                break;
            case 'venue':
                $this->db->order_by('name', 'ASC');
                break;
            case 'team':
                $this->db->order_by('team_name', 'ASC');
                break;
            case 'player':
                $this->db->order_by('last_name', 'ASC');
                $this->db->order_by('first_name', 'ASC');
                break;
        }

        $query = $this->db->get($table);
        $rows = $query->result_array();

        // Map results based on type
        if ($type === 'season') {
            return array_map(function ($r) {
                return [
                    'id' => $r['id'],
                    'name' => $r['start_year'] . '/' . $r['end_year'],
                    'start_year' => $r['start_year'],
                    'end_year' => $r['end_year'],
                    'start_date' => $r['start_date'],
                    'end_date' => $r['end_date'],
                    'is_active' => $r['is_active']
                ];
            }, $rows);
        }

        if ($type === 'competition') {
            return array_map(function ($r) {
                return [
                    'id' => $r['id'],
                    'name' => $r['name'],
                    'type' => $r['type']
                ];
            }, $rows);
        }

        if ($type === 'venue') {
            return array_map(function ($r) {
                return [
                    'id' => $r['id'],
                    'name' => $r['name'],
                    'city' => $r['city']
                ];
            }, $rows);
        }

        if ($type === 'team') {
            return array_map(function ($r) {
                return [
                    'id' => $r['id'],
                    'name' => $r['team_name'],
                    'abbreviation' => $r['abbreviation'],
                    'country' => $r['country'],
                    'city' => $r['city'],
                    'team_logo' => $r['team_logo']
                ];
            }, $rows);
        }

        if ($type === 'player') {
            return array_map(function ($r) {
                $nameParts = [];

                // Safely get individual components
                $last  = trim($r['last_name'] ?? '');
                $first = trim($r['first_name'] ?? '');
                $middle = isset($r['middle_initial']) && $r['middle_initial'] !== ''
                    ? ' ' . strtoupper($r['middle_initial']) . '.'
                    : '';

                // Conditional formatting
                if ($last !== '' && $first !== '') {
                    $nameParts[] = "{$last}, {$first}{$middle}";
                } elseif ($last !== '') {
                    $nameParts[] = $last;
                } elseif ($first !== '') {
                    $nameParts[] = "{$first}{$middle}";
                }

                return [
                        'id' => $r['id'] ?? null,
                        'team_id' => $r['team_id'] ?? null,
                        'name' => $nameParts ? $nameParts[0] : '',
                        'position' => $r['position'] ?? '',
                        'jersey' => $r['jersey'] ?? ''
                    ];
            }, $rows);
        }

        return [];
    }

    public function get_item($type, $id) {
        $mapping = [
            'season' => 'seasons',
            'competition' => 'competitions',
            'venue' => 'venues',
            'team' => 'teams',
            'player' => 'players'
        ];

        if (!isset($mapping[$type])) {  // <-- corrected
            return false;
        }

        $table = $mapping[$type];  // <-- get table name
        $query = $this->db->get_where($table, ['id' => $id]);
        return $query->row_array(); // returns single row as assoc array or null
    }

    // Add item to DB
    public function add_item($type, $data) {
        $mapping = [
            'season' => 'seasons',
            'competition' => 'competitions',
            'venue' => 'venues',
            'team' => 'teams',
            'player' => 'players'
        ];

        if (!isset($mapping[$type])) return false;

        $table = $mapping[$type];
        $this->db->insert($table, $data);
        return $this->db->insert_id();
    }

    // Edit item
    public function edit_item($type, $id, $data) {
        $mapping = [
            'season' => 'seasons',
            'competition' => 'competitions',
            'venue' => 'venues',
            'team' => 'teams',
            'player' => 'players'
        ];

        if (!isset($mapping[$type])) return false;

        $table = $mapping[$type];
        $this->db->where('id', $id);
        return $this->db->update($table, $data);
    }

    // Delete item
    public function delete_item($type, $id) {
        $mapping = [
            'season' => 'seasons',
            'competition' => 'competitions',
            'venue' => 'venues',
            'team' => 'teams',
            'player' => 'players'
        ];

        if (!isset($mapping[$type])) return false;

        $table = $mapping[$type];
        $this->db->where('id', $id);
        return $this->db->delete($table);
    }

    public function get_match_data($match_id) {
        $this->db->where('match_id', $match_id);
        $query = $this->db->get('matches_vw');

        return $query->row_array(); // ✅ Use row_array() for associative array access
    }
}
