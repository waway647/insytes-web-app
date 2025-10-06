<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

class SignupController extends CI_Controller {
	public function __construct() {
        parent::__construct();
        // Load the URL helper to enable base_url()
		$this->load->library('session');
		$this->load->database();
		$this->load->model('AuthModel/SignUp_Model');
        $this->load->helper('url'); 
    }

	public function show_signup_step1()
	{
		$this->load->view('auth/signup_step1');
	}

	public function show_signup_step2()
	{
		$this->load->view('auth/signup_step2');
	}

	public function show_signup_step3()
	{
		$this->load->view('auth/signup_step3');
	}

	public function show_signup_success()
	{
		$this->load->view('auth/signup_success');
	}

	public function show_google_signup_last_step()
	{
		$this->load->view('auth/google_signup_last_step');
	}

	//From Step 1 SignUp
	public function create_user1(){
		$email = $this->input->post('email');

		$this->session->set_userdata('email', $email);

		if($email){
			$this->show_signup_step2();
		}
	}

	//From Step 2 SignUp
	public function create_user2(){
		$password = $this->input->post('password');
		$confirmPassword = $this->input->post('retype_password');

		if($password === $confirmPassword){
			$this->session->set_userdata('password', $password);
			$this->show_signup_step3();
		}else{
			$this->session->set_flashdata('error', 'An unexpected error occurred.');
			$this->show_signup_step2();
		}

	}

	//From Step 3 SignUp
	public function create_user3(){
		$email = $this->session->userdata('email');
		$password = $this->session->userdata('password');

		$firstName = $this->input->post('firstname');
		$lastName = $this->input->post('lastname');

		$userData = array(
			'first_name' => $firstName,
			'last_name' => $lastName,
			'email' => $email,
			'password' => $password,
		);

		$inserted = $this->SignUp_Model->insert_user($userData);
		if ($inserted) {
			$this->session->set_flashdata('success', 'User registered successfully.');
			$this->show_signup_success();
		} else {
			$this->session->set_flashdata('error', 'Failed to register user.');
			$this->show_signup_step1();
		}
	}

    public function check_email_unique() {
        // Read raw JSON input
        $input = json_decode($this->input->raw_input_stream, true);
        $email = isset($input['email']) ? trim($input['email']) : '';

        if (empty($email)) {
            echo json_encode(['unique' => false]); 
            return;
        }

        // Query the "users" table in "insytes_db"
        $this->db->where('email', $email);
        $query = $this->db->get('users'); // your users table

        if ($query->num_rows() > 0) {
            echo json_encode(['unique' => false]); // Email exists
        } else {
            echo json_encode(['unique' => true]); // Email available
        }
    }
}
