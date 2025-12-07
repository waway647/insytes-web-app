<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

class LoginController extends CI_Controller {
	public function __construct() {
        parent::__construct();
        // Load the URL helper to enable base_url()
        $this->load->helper('url');
        $this->load->helper('log_helper');
		$this->load->library('form_validation');
		$this->load->library('session');
		$this->load->model('AuthModel/Login_Model');
		$this->load->model('Admin/Logs_Model');
    }

	public function show_login()
	{
		$this->load->view('auth/login');
	}

	public function process_login()
	{
		// Set validation rules
		$this->form_validation->set_rules('email', 'Email', 'required|valid_email');
		$this->form_validation->set_rules('password', 'Password', 'required|min_length[6]');

		if ($this->form_validation->run() == FALSE) {
			// Validation failed
			$response = array(
				'success' => false,
				'message' => validation_errors()
			);
			echo json_encode($response);
			return;
		}

		$email = $this->input->post('email');
		$password = $this->input->post('password');

		// Check user credentials
		$user = $this->Login_Model->get_user_by_email($email);

		$redirect_url = 'http://localhost/github/insytes-web-app/index.php';

		if ($user && password_verify($password, $user->password)) {
			// Check if user has temporary password
			if ($user->temp_password == 1) {
				// User has temporary password, redirect to change password
				$this->session->set_userdata('user_id', $user->id);
				$this->session->set_userdata('email', $user->email);
				$this->session->set_userdata('temp_login', true);
				
				$response = array(
					'success' => true,
					'temp_password' => true,
					'message' => 'Please change your temporary password',
					'redirect_url' => site_url('auth/change-password')
				);
				echo json_encode($response);
				return;
			}
			
			// Credentials are valid, set session data
			$this->session->set_userdata('user_id', $user->id);
			$this->session->set_userdata('email', $user->email);
			
			// Log successful login
			LogHelper::logLogin($user->id, $user->email, true);
			
			// Standardize role to proper case
			$role = $user->role;
			if (strtolower($role) === 'admin') {
				$role = 'Admin'; // Standardize to uppercase
			}
			$this->session->set_userdata('role', $role);

			// Check user role and status for proper redirect
			if ($user->temp_password == 1) {
				// User has temporary credentials - force password change first
				$this->session->set_userdata('temp_login', true);
				$redirect_url = '/auth/update-profile';
				
				$response = array(
					'success' => true,
					'message' => 'Please update your credentials',
					'redirect_url' => site_url($redirect_url)
				);
			} else if (strtolower($user->role) === 'admin') {
				// Admin user - redirect to admin dashboard
				$this->session->set_userdata('is_admin', true);
				$redirect_url = '/Admin/DashboardController/adminDashboard';
				
				$response = array(
					'success' => true,
					'message' => 'Admin login successful',
					'redirect_url' => site_url($redirect_url)
				);
			} else if ($user->status === 'new' && !empty($user->role)) {
				// Admin-created user with assigned role - redirect to role-specific onboarding
				if (strtolower($user->role) === 'coach') {
					if (empty($user->team_id)) {
						// Coach without team - can create or join team
						$redirect_url = '/User/NewUserController/userCoach_step1';
						$message = 'Welcome! Choose to create or join a team';
					} else {
						// Coach with team - setup team details
						$redirect_url = '/User/NewUserController/userCoach_step2';
						$message = 'Welcome! Complete your team setup';
					}
				} else if (strtolower($user->role) === 'player') {
					// Player always needs to join a team
					$redirect_url = '/User/NewUserController/userPlayer_step1';
					$message = 'Welcome! Join your team';
				} else {
					// Other roles - redirect to general new user setup
					$redirect_url = '/User/NewUserController/newUser';
					$message = 'Login successful - Complete your profile';
				}
				
				$response = array(
					'success' => true,
					'message' => $message,
					'redirect_url' => site_url($redirect_url)
				);
			} else if ((empty($user->role) && empty($user->team_id)) || ($user->status === 'new' && empty($user->role))) {
				// Self-registered user without role or new user without role - choose role
				$redirect_url = '/User/NewUserController/newUser';
				
				$response = array(
					'success' => true,
					'message' => 'Login successful - Complete your profile',
					'redirect_url' => site_url($redirect_url)
				);
			} else {
				// Regular user with role and team (active status)
				$this->session->set_userdata('team_id', $user->team_id);
				$redirect_url = '/Team/DashboardController/index';
				
				$response = array(
					'success' => true,
					'message' => 'Login successful',
					'redirect_url' => site_url($redirect_url)
				);
			}

			echo json_encode($response);
			return;
		} else {
			// Invalid credentials - log failed attempt
			LogHelper::logLogin(null, $email, false, 'Invalid credentials');
			
			$response = array(
				'success' => false,
				'message' => 'Invalid email or password',
				'redirect_url' => site_url('auth/login')
			);
			echo json_encode($response);
			return;
		}
	}

	public function logout()
	{
		// Get user info before destroying session
		$user_id = $this->session->userdata('user_id');
		$email = $this->session->userdata('email');
		
		// Log logout
		if ($user_id) {
			LogHelper::logLogout($user_id, $email);
		}
		
		// Destroy the session and redirect to login page
		$this->session->sess_destroy();
		$this->load->view('auth/login');
	}
}
