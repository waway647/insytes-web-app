<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

class SignUp_Model extends CI_Model
{
    public function __construct()
    {
		$this->load->database();
		$this->load->helper('url');
    }

    public function insert_user($userData)
    {
        $data = [
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'email' => $userData['email'],
            'password' => password_hash($userData['password'], PASSWORD_BCRYPT),
        ];

        return $this->db->insert('users', $data);
    }
}
