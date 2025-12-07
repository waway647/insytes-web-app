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

    public function get_opponent_name($match_id)
    {
        if ($match_id === null || $match_id === '') return null;

        $this->db->select('opponent_team_abbreviation, opponent_team_name');
        // Use the view/table you have that contains these fields:
        $this->db->from('matches_vw'); // change to 'matches' if you use a different table
        $this->db->where('match_id', $match_id);
        $this->db->limit(1);

        $query = $this->db->get();
        if (!$query || $query->num_rows() === 0) {
            return null;
        }

        $row = $query->row_array();

        // prefer abbreviation if present & non-empty
        if (!empty($row['opponent_team_abbreviation'])) {
            return trim($row['opponent_team_abbreviation']);
        }
        if (!empty($row['opponent_team_name'])) {
            return trim($row['opponent_team_name']);
        }
        return null;
    }

    public function delete_match($match_id)
    {
        if (empty($match_id)) {
            return 0;
        }

        // normalize to string for comparisons
        $match_key = (string)$match_id;

        try {
            // Start transaction
            $this->db->trans_start();

            // 1) Remove any related rows in match_players (if that table exists in your schema)
            //    This is a best-effort delete — if you don't have this table remove the block below.
            if ($this->db->table_exists('match_players')) {
                $this->db->where('match_id', $match_key);
                $this->db->delete('match_players');
                // no need to act on affected_rows here, it's cleanup
            }
            if ($this->db->table_exists('match_videos')) {
                $this->db->where('match_id', $match_key);
                $this->db->delete('match_videos');
                // no need to act on affected_rows here, it's cleanup
            }

            // 2) Delete the match row.
            //    Support deleting by either `match_id` column (string-style id) or numeric `id` column.
            //    Adjust column names to match your schema if different.
            $this->db->group_start();
                $this->db->where('id', $match_key);
                // if $match_id is numeric, also try `id` column
                if (is_numeric($match_key)) {
                    $this->db->or_where('id', intval($match_key));
                }
            $this->db->group_end();

            $this->db->delete('matches');

            // Capture how many rows were removed from matches
            $deletedCount = $this->db->affected_rows();

            // Complete transaction
            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                // Transaction failed; log and return false
                log_message('error', 'Match_model::delete_match - DB transaction failed for match_id=' . $match_key);
                return false;
            }

            // Return number of deleted match rows (0 if nothing deleted)
            return (int)$deletedCount;

        } catch (Exception $ex) {
            // Log and rethrow or return false depending on how you want to handle exceptions
            log_message('error', 'Match_model::delete_match exception: ' . $ex->getMessage());
            return false;
        }
    }
}
