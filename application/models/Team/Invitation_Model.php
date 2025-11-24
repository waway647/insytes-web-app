<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

class Invitation_Model extends CI_Model {
    public function __construct()
    {
		$this->load->database();
		$this->load->helper('url');
    }

    public function get_team_by_invite_code($invite_code)
    {
        $query = $this->db->get_where('teams', array('invite_code' => $invite_code));
        return $query->row();
    }

    public function get_team_by_owner($user_id)
    {
        $this->db->where('created_by', $user_id);
        return $this->db->get('teams')->row_array();
    }

    public function get_team_by_member($user_id)
    {
        // First try to get team by owner (if they're the main coach)
        $team = $this->get_team_by_owner($user_id);
        
        if (!$team) {
            // If not owner, get their team_id from users table and fetch the team
            $this->db->select('team_id');
            $this->db->from('users');
            $this->db->where('id', $user_id);
            $user = $this->db->get()->row();
            
            if ($user && $user->team_id) {
                $this->db->where('id', $user->team_id);
                $team = $this->db->get('teams')->row_array();
            }
        }
        
        return $team;
    }
}