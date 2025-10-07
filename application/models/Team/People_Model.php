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
}