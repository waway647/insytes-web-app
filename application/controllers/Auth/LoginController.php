<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

class LoginController extends CI_Controller {
	public function __construct() {
        parent::__construct();
        // Load the URL helper to enable base_url()
        $this->load->helper('url');
		$this->load->library('form_validation');
		$this->load->library('session');
		$this->load->model('AuthModel/Login_Model');
    }

	public function show_login()
	{
		$this->load->view('auth/login');
	}

	public function process_login()
	{
		// Check if the request is an AJAX request		// Set validation rules
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
			// Credentials are valid, set session data
			$this->session->set_userdata('user_id', $user->id);
			$this->session->set_userdata('email', $user->email);

			if (empty($user->role) && empty($user->team_id)) {
				$redirect_url = '/User/NewUserController/newUser';
			}else{

				$this->session->set_userdata('role', $user->role);
				$this->session->set_userdata('team_id', $user->team_id);
				$redirect_url = '/Team/DashboardController/index';
			}

			$response = array(
				'success' => true,
				'message' => 'Login successful',
				'redirect_url' => site_url($redirect_url)
			);
			echo json_encode($response);
			return;
		} else {
			// Invalid credentials
			$response = array(
				'success' => false,
				'message' => 'Invalid email or password',
				'redirect_url' => site_url('auth/login')
			);
			echo json_encode($response);
			$this->show_login();
			return;
		}
	}

	public function logout()
	{
		// Destroy the session and redirect to login page
		$this->session->sess_destroy();
		$this->load->view('auth/login');
	}
}
