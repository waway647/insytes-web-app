<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class NewUserController extends CI_Controller {

	public function __construct() {
        parent::__construct();
        // Load the URL helper to enable base_url()
        $this->load->helper('url');
		$this->load->library('session');
		$this->load->model('UserModel/NewUser_Model');
		$this->load->database();
		$this->load->library('form_validation');
		$this->load->helper('string');
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

	public function process_invite_link()
    {
        $invite_link = $this->input->post('invite_link');

        // Validate input
        if (empty($invite_link)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invite link is required.'
            ]);
            return;
        }

        // Extract invite_code from the link
        // Expected format: http://localhost/github/insytes-web-app/index.php/team/join/{invite_code}
        $base_url = base_url('team/join/');
        if (strpos($invite_link, $base_url) !== 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid invite link format.'
            ]);
            return;
        }

        // Extract the invite_code
        $invite_code = substr($invite_link, strlen($base_url));

        // Basic validation of invite_code (alphanumeric, 16 characters)
        if (!preg_match('/^[a-zA-Z0-9]{16}$/', $invite_code)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid invite code.'
            ]);
            return;
        }

        // Redirect to the join method
        /* echo json_encode([
            'success' => true,
            'message' => 'Valid invite link. Redirecting...',
            'redirect_url' => site_url('team/join/' . $invite_code)
        ]); */

		redirect('Team/InvitationController/join/' . $invite_code);
    }

	public function userCoach_create_team()
	{
		// Here you would typically process the form data and create the team
		$role = $this->session->userdata('role'); // Assuming role is stored in session
		$team_name = $this->input->post('team_name');
		$country = $this->input->post('country');
		$city = $this->input->post('city');
		$created_by = $this->session->userdata('user_id');
		$created_at = date('Y-m-d H:i:s');
		
		// VALIDATION 1: Check if team name already exists
		if ($this->NewUser_Model->isTeamNameExists($team_name)) {
			$this->session->set_flashdata('error', 'Team name "' . $team_name . '" already exists. Please choose a different name.');
			redirect('user/coach/step2');
			return;
		}
		
		// VALIDATION 2: Check if user already manages a team
		$existing_managed_team = $this->NewUser_Model->getUserManagedTeam($created_by);
		if ($existing_managed_team) {
			$this->session->set_flashdata('error', 'You already manage the team "' . $existing_managed_team->team_name . '". You cannot create multiple teams.');
			redirect('user/coach/step2');
			return;
		}
		
		// VALIDATION 3: Check if user is already assigned to a team
		$existing_team_assignment = $this->NewUser_Model->getUserTeam($created_by);
		if ($existing_team_assignment) {
			$this->session->set_flashdata('error', 'You are already assigned to a team. You cannot create a new team.');
			redirect('user/coach/step2');
			return;
		}
		
		$config['upload_path'] = './assets/team_logos/';
		$config['allowed_types'] = 'png|jpg|jpeg|gif';
		$this->load->library('upload', $config);

		if ($this->upload->do_upload('team_logo')) {
			$uploaded_data = $this->upload->data();
			$attachment_path = 'assets/team_logos/' . $uploaded_data['file_name'];
		}

		$invite_code = random_string('alnum', 16); // Generate a random alphanumeric string of length 16

		// Basic validation (you can expand this as needed)
		$team_data = array(
			'team_name' => $team_name,
			'country' => $country,
			'city' => $city,
			'team_logo' => $attachment_path ?? null,
			'created_by' => $created_by,
			'created_at' => $created_at,
			'invite_code' => $invite_code
		);

		$team_id = $this->NewUser_Model->create_team($team_data);

		if ($team_id) {
			$this->NewUser_Model->setUserRoleAndTeamById($created_by, $team_id, $role);
			
			// Update user status to active after completing onboarding
			$this->NewUser_Model->updateUserStatus($created_by, 'active');

			$user = $this->NewUser_Model->getUserById($created_by);

			if($user){
				$this->session->set_userdata([
					'user_id' => $user->id,
					'role' => $user->role,
					'team_id' => $user->team_id,
					'team_name' => $user->team_name,
				]);

				$data['user'] = [
					'team_name' => $user->team_name,
					'role' => $user->role,
					'team_logo' => $user->team_logo,
					'user_id' => $user->id,
					'team_id' => $user->team_id
				];

				$this->load->view('user/team_created_success', $data); // Pass $data to the view
				return;
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
			
			// If this is a self-registered user completing role selection, 
			// we DON'T update status yet - they still need to join/create team
			
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