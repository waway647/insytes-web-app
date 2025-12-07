<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AdminUserController extends CI_Controller {
	
	public function __construct()
	{
		parent::__construct();
		
		// Suppress deprecation warnings
		error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
		
		$this->load->helper('admin_auth');
		$this->load->helper('url');
		$this->load->library('session');
		$this->load->library('form_validation');
		$this->load->database();
		$this->load->model('AuthModel/Login_Model');
	}

	/**
	 * Show admin user creation form
	 */
	public function create()
	{
		$this->load->view('admin/create_admin_user');
	}

	/**
	 * Process admin user creation
	 */
	public function process_create()
	{
		// Set validation rules matching your password requirements
		$this->form_validation->set_rules('email', 'Email', 'required|valid_email|is_unique[users.email]');
		$this->form_validation->set_rules('password', 'Password', 'required|min_length[8]|max_length[50]|callback_password_check');
		$this->form_validation->set_rules('retype_password', 'Confirm Password', 'required|matches[password]');
		$this->form_validation->set_rules('first_name', 'First Name', 'required|min_length[2]|max_length[100]');
		$this->form_validation->set_rules('last_name', 'Last Name', 'required|min_length[2]|max_length[100]');

		if ($this->form_validation->run() == FALSE) {
			// Validation failed
			$response = array(
				'success' => false,
				'message' => validation_errors()
			);
			echo json_encode($response);
			return;
		}

		// Check if any admin already exists
		$this->db->where('role', 'admin');
		$admin_exists = $this->db->get('users')->num_rows();
		
		if ($admin_exists > 0) {
			$response = array(
				'success' => false,
				'message' => 'An admin user already exists in the system.'
			);
			echo json_encode($response);
			return;
		}

		$email = $this->input->post('email');
		$password = $this->input->post('password');
		$first_name = $this->input->post('first_name');
		$last_name = $this->input->post('last_name');

		// Hash the password
		$hashed_password = password_hash($password, PASSWORD_DEFAULT);

		// Create admin user
		$admin_data = array(
			'email' => $email,
			'password' => $hashed_password,
			'role' => 'admin',
			'first_name' => $first_name,
			'last_name' => $last_name,
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s')
		);

		$this->db->insert('users', $admin_data);

		if ($this->db->affected_rows() > 0) {
			$response = array(
				'success' => true,
				'message' => 'Admin user created successfully!',
				'redirect_url' => site_url('Auth/LoginController/show_login')
			);
		} else {
			$response = array(
				'success' => false,
				'message' => 'Failed to create admin user. Please try again.'
			);
		}

		echo json_encode($response);
	}

	/**
	 * Custom password validation callback
	 */
	public function password_check($password)
	{
		// Check for uppercase letter
		if (!preg_match('/[A-Z]/', $password)) {
			$this->form_validation->set_message('password_check', 'Password must contain at least one uppercase letter.');
			return FALSE;
		}

		// Check for lowercase letter
		if (!preg_match('/[a-z]/', $password)) {
			$this->form_validation->set_message('password_check', 'Password must contain at least one lowercase letter.');
			return FALSE;
		}

		// Check for number
		if (!preg_match('/[0-9]/', $password)) {
			$this->form_validation->set_message('password_check', 'Password must contain at least one number.');
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Check if admin exists (for initial setup)
	 */
	public function check_admin_exists()
	{
		$this->db->where('role', 'admin');
		$admin_count = $this->db->get('users')->num_rows();
		
		echo json_encode(array('admin_exists' => $admin_count > 0));
	}
}