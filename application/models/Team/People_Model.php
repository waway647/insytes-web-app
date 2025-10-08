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
}