<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Debug extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->library('session');
		$this->load->model('user_model');
	}

	public function check_admin_user() {
		header('Content-Type: application/json');
		
		$user_id = $this->session->userdata('user_id');
		$role = $this->session->userdata('role');
		$email = $this->session->userdata('email');
		
		echo json_encode([
			'session_user_id' => $user_id,
			'session_role' => $role,
			'session_email' => $email,
			'all_session_data' => $this->session->all_userdata()
		]);
	}
	
	public function check_database() {
		header('Content-Type: application/json');
		
		$user_id = $this->session->userdata('user_id');
		
		// Check users table directly
		$this->db->where('id', $user_id);
		$user = $this->db->get('users')->row_array();
		
		echo json_encode([
			'user_from_users_table' => $user
		]);
	}
}