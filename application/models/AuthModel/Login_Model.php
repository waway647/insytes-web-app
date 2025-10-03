<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

class Login_Model extends CI_Model
{
    public function __construct()
    {
		$this->load->database();
		$this->load->helper('url');
    }

    public function get_user_by_email($email)
    {
        // This will fetch all columns, including password, from the 'users' table
        $query = $this->db->get_where('users', array('email' => $email));
        return $query->row();
    }
}
