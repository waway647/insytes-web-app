<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Team_Model extends CI_Model {
	public function __construct() {
		$this->load->database();
	}

	public function get_team($team_id) {
		$this->db->where('id', $team_id);
		$query = $this->db->get('teams');

		return $query->row_array();
	}

	public function update_team($team_id, $team_update_data) {
		$this->db->where('id', $team_id);
		$this->db->update('teams', $team_update_data);

		// Return the updated team data
		return $this->get_team($team_id);
	}
}
