<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

class People_Model extends CI_Model {
    public function __construct()
    {
		$this->load->database();
		$this->load->helper('url');
    }

    public function getMembersByTeamId($team_id) {
        $this->db->select('id, first_name, last_name, role');
        $this->db->from('users');
        $this->db->where('team_id', $team_id);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function getTeamById($team_id){
      $this->db->select('team_name');
      $this->db->from('teams');
      $this->db->where('id', $team_id);
      $query = $this->db->get();
      return $query->row_array();
    }

    public function getTotalMembers($team_id){
      $this->db->from('users');
      $this->db->where('team_id', $team_id);
      return $this->db->count_all_results();
    }

    public function remove_user_from_team($user_id, $team_id = null)
    {
        if (empty($user_id)) {
            return false;
        }

        // Ensure we operate on the users table
        $this->db->where('id', (int)$user_id);

        // If a team_id was provided, ensure it matches to avoid accidental changes
        if (!is_null($team_id) && $team_id !== '') {
            $this->db->where('team_id', (int)$team_id);
        }

        // Set team_id to NULL
        $ok = $this->db->update('users', ['team_id' => null]);

        if ($ok) {
            // affected_rows > 0 means a row was updated (otherwise maybe no-op)
            return ($this->db->affected_rows() > 0);
        }

        return false;
    }
}