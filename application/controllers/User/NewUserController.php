<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class NewUserController extends CI_Controller {

	public function __construct() {
        parent::__construct();
        // Load the URL helper to enable base_url()
        $this->load->helper('url');
		$this->load->library('session');
		$this->load->model('UserModel/NewUser_Model');
    }

	public function newUser()
	{
		$data['user_id'] = $this->session->userdata('user_id');

		if (!$data['user_id']) {
			// If user_id is not set in session, redirect to login or another appropriate page
			redirect('Auth/LoginController/show_login');
			return;
		}

		$this->load->view('user/new_user', $data);
	}

	public function userCoach_step1()
	{
		$this->load->view('user/user_coach_step1');
	}

	public function userCoach_step2()
	{
		$data['user_id'] = $this->session->userdata('user_id');
		$this->load->view('user/user_coach_step2', $data);
	}

	public function userPlayer_step1()
	{
		$this->load->view('user/user_player_step1');
	}

	/* public function process_invite_link()
	{
		$invite_link = $this->input->post('invite');

		// Basic validation
		if (empty($invite_link)) {
			$this->session->set_flashdata('error', 'Invite link cannot be empty.');
			redirect('Admin/UserController/userCoach_step1');
			return;
		}

		// Process the invite link (e.g., check if it's valid, extract team info, etc.)
		// This is a placeholder for actual invite link processing logic.
		$is_valid_link = $this->NewUser_Model->validate_invite_link($invite_link);

		if ($is_valid_link) {
			// If valid, redirect to the next step or dashboard
			redirect('Team/TeamController/index');
		} else {
			// If invalid, show an error message
			$this->session->set_flashdata('error', 'Invalid invite link. Please try again.');
			redirect('Admin/UserController/userCoach_step1');
		}
	} */

	public function userCoach_create_team()
	{
		// Here you would typically process the form data and create the team
		$role = $this->session->userdata('role'); // Assuming role is stored in session
		$team_name = $this->input->post('team_name');
		$country = $this->input->post('country');
		$city = $this->input->post('city');
		$primary_color = $this->input->post('primary_color');
		$secondary_color = $this->input->post('secondary_color');
		$created_by = $this->session->userdata('user_id');
		$created_at = date('Y-m-d H:i:s');
		
		$config['upload_path'] = './assets/team_logos/';
		$config['allowed_types'] = 'png|jpg|jpeg|gif';
		$this->load->library('upload', $config);

		if ($this->upload->do_upload('team_logo')) {
			$uploaded_data = $this->upload->data();
			$attachment_path = 'assets/team_logos/' . $uploaded_data['file_name'];
		}

		// Basic validation (you can expand this as needed)
		$team_data = array(
			'team_name' => $team_name,
			'country' => $country,
			'city' => $city,
			'primary_color' => $primary_color,
			'secondary_color' => $secondary_color,
			'team_logo' => $attachment_path ?? null,
			'created_by' => $created_by,
			'created_at' => $created_at
		);

		$created = $this->NewUser_Model->create_team($team_data);

		if ($created) {
			$this->NewUser_Model->setUserRoleAndTeamById($created_by, $created, $role);

			$user = $this->NewUser_Model->getUserById($created_by);

			if($user){
				$this->session->set_userdata('user_id', $user->id);
				$this->session->set_userdata('email', $user->email);
				$this->session->set_userdata('role', $user->role);
				$this->session->set_userdata('team_id', $user->team_id);

				$this->load->view('user/team_created_success'); 
			}
		}else {
			$this->session->set_flashdata('error', 'Failed to create team. Please try again.');
			redirect('User/NewUserController/userCoach_step2');
			return;
		}
	}

	public function userPlayer_join_team()
	{
		$this->load->view('user/user_player_join_team');
	}

	
	public function setUserRole() {
		$role = $this->input->post('role');
		if ($role) {
			$this->session->set_userdata('role', $role);
			echo json_encode(['status' => 'success', 'role' => $role]);
		} else {
			echo json_encode(['status' => 'error']);
		}
	}


	/* private function handle_team_logo_upload()
	{
		if (isset($_FILES['teamLogo']) && $_FILES['teamLogo']['error'] == UPLOAD_ERR_OK) {
			$config['upload_path']   = './uploads/team_logos/';
			$config['allowed_types'] = 'gif|jpg|png|jpeg';
			$config['max_size']      = 2048;
			$config['encrypt_name']  = TRUE;

			$this->load->library('upload', $config);

			if ($this->upload->do_upload('teamLogo')) {
				$data = $this->upload->data();
				return 'uploads/team_logos/' . $data['file_name'];
			} else {
				$this->session->set_flashdata('error', $this->upload->display_errors());
				return null;
			}
		}
		return null;
	} */
}
