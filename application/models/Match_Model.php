<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);
class Match_Model extends CI_Model {
	public function __construct()
    {
        $this->load->database();
    }

    /**
     * normalize_players
     * Ensures each player has a proper team_id and required fields
     *
     * @param array $players
     * @param int|string $team_id
     * @return array
     */
    public function normalize_players(array $players, $team_id)
    {
        $normalized = [];

        foreach ($players as $p) {
            // Expected keys: player_id, name, jersey, position, xi, rowIndex
            $normalized[] = [
                'player_id' => isset($p['player_id']) ? $p['player_id'] : null,
                'name'      => isset($p['name']) ? $p['name'] : '',
                'jersey'    => isset($p['jersey']) ? $p['jersey'] : '',
                'position'  => isset($p['position']) ? $p['position'] : '',
                'xi'        => isset($p['xi']) ? $p['xi'] : '0',
                'row_index' => isset($p['rowIndex']) ? $p['rowIndex'] : null,
                'team_id'   => $team_id
            ];
        }

        return $normalized;
    }

    /**
     * create_match_with_players
     *
     * Inserts a match record and its players in a transaction.
     *
     * @param array $matchData
     * @param array $myPlayers
     * @param array $oppPlayers
     * @return int $match_id
     * @throws Exception
     */
    public function create_match_with_players(array $matchData, array $myPlayers, array $oppPlayers)
    {
        $this->db->trans_start(); // Start transaction

        try {
            // Insert match metadata
            $this->db->insert('matches', $matchData);

            if ($this->db->affected_rows() === 0) {
                throw new Exception('Failed to insert match');
            }

            $match_id = $this->db->insert_id();

            // Insert players
            $allPlayers = array_merge($myPlayers, $oppPlayers);

            foreach ($allPlayers as $p) {
                $this->insert_match_player_row($match_id, $p);
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed while creating match and players');
            }

            return $match_id;

        } catch (Exception $ex) {
            $this->db->trans_rollback();
            throw $ex;
        }
    }

    /**
     * insert_match_player_row
     *
     * Insert a single player for a match
     *
     * @param int $match_id
     * @param array $playerData
     * @throws Exception
     */
    public function insert_match_player_row($match_id, array $playerData)
    {
        $row = [
            'match_id' => $match_id,
			'team_id'  => $playerData['team_id'],
            'player_id'=> isset($playerData['player_id']) ? $playerData['player_id'] : null,
            'name'     => $playerData['name'],
            'jersey'   => $playerData['jersey'],
            'position' => $playerData['position'],
            'xi'       => isset($playerData['xi']) ? $playerData['xi'] : '0',
            'row_index'=> isset($playerData['row_index']) ? $playerData['row_index'] : null,
        ];

        $this->db->insert('match_players', $row);

        if ($this->db->affected_rows() === 0) {
            throw new Exception('Failed to insert player row for match_id: ' . $match_id);
        }
    }

    public function save_video_to_db($data) {
        $query = $this->db->insert('match_videos', $data);
        
        if ($this->db->affected_rows() === 0) {
            throw new Exception('Failed to insert player row for match_id: ' . $data);
        }

        return $query;
    }

    public function update_match_status($match_id, $updated_status) {
        $this->db->where('id', $match_id);
        $this->db->update('matches', ['status' => $updated_status]);

        // check affected rows so caller knows if update actually happened
        if ($this->db->affected_rows() === 0) {
            // get DB error if any
            $db_err = $this->db->error();
            throw new Exception('Failed to update matches.status for match_id ' . $match_id . '. DB error: ' . ($db_err['message'] ?? 'no error, maybe no matching row'));
        }

        return true;
    }

    public function get_my_team_players($match_id, $team_id)
    {
        // validate inputs
        if (empty($match_id) || empty($team_id)) {
            return [];
        }

        // select columns - use actual column names in your DB
        $this->db->select('player_id, name, jersey, position');
        $this->db->from('match_players');

        // filter by match and team
        $this->db->where('match_id', $match_id);
        $this->db->where('team_id', $team_id);

        // order by jersey (adjust column name if you use jersey_number)
        $this->db->order_by('jersey', 'ASC');

        $query = $this->db->get();

        if ($query === false) {
            // Query error — return empty or handle/log as needed
            return [];
        }

        if ($query->num_rows() === 0) {
            return [];
        }

        return $query->result_array();
    }
}
