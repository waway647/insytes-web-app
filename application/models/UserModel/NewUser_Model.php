<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

class NewUser_Model extends CI_Model {
    public function __construct()
    {
		$this->load->database();
		$this->load->helper('url');
    }

    public function create_team($data) {
        $this->db->insert('teams', $data);
        return $this->db->insert_id(); // Return the ID of the newly created team
    }

    public function setUserRoleAndTeamById($user_id, $team_id, $role) {
        $data = array(
            'role' => $role,
            'team_id' => $team_id
        );

        $this->db->where('id', $user_id);
        return $this->db->update('users', $data);
    }

    public function getUserById($id){
        $query = $this->db->get_where('users', array('id' => $id));
        return $query->row();
    }
}