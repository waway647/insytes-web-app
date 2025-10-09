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
}