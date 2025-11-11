<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

class ModalInserts_Model extends CI_Model {

    public function __construct() {
		$this->load->database();
	}
    public function insert_season($season_data) {
        $query = $this->db->insert('seasons', $season_data);
        return $query;
	}

    public function update_season($id, $data) {
        $this->db->where('id', $id);
        $query = $this->db->update('seasons', $data);
        return $query;
    }

    public function insert_competition($data) {
		$query = $this->db->insert('competitions', $data);
        return $query;
	}

    public function update_competition($id, $data) {
        $this->db->where('id', $id);
        $query = $this->db->update('competitions', $data);
        return $query;
    }

    public function insert_venue($data) {
		$query = $this->db->insert('venues', $data);
        return $query;
	}

    public function update_venue($id, $data) {
        $this->db->where('id', $id);
        $query = $this->db->update('venues', $data);
        return $query;
    }

    public function insert_team($data) {
		$query = $this->db->insert('teams', $data);
        return $query;
	}

    public function update_team($id, $data) {
        $this->db->where('id', $id);
        $query = $this->db->update('teams', $data);
        return $query;
    }

    public function insert_player($data) {
		$query = $this->db->insert('players', $data);
        return $query;
	}

    public function update_player($id, $data) {
        $this->db->where('id', $id);
        $query = $this->db->update('players', $data);
        return $query;
    }
}
