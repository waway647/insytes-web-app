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
        $query = $this->db->get_where('users_vw', array('id' => $id));
        return $query->row();
    }
    
    public function updateUserStatus($user_id, $status) {
        $this->db->where('id', $user_id);
        return $this->db->update('users', ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
    }
    
    /**
     * Check if a team name already exists
     */
    public function isTeamNameExists($team_name) {
        $this->db->where('team_name', $team_name);
        $query = $this->db->get('teams');
        return $query->num_rows() > 0;
    }
    
    /**
     * Check if user already manages/owns a team
     */
    public function getUserManagedTeam($user_id) {
        $this->db->select('teams.*');
        $this->db->from('teams');
        $this->db->where('teams.created_by', $user_id);
        $query = $this->db->get();
        return $query->row();
    }
    
    /**
     * Check if user is already assigned to any team
     */
    public function getUserTeam($user_id) {
        $this->db->select('team_id');
        $this->db->from('users');
        $this->db->where('id', $user_id);
        $this->db->where('team_id IS NOT NULL');
        $query = $this->db->get();
        return $query->row();
    }
}