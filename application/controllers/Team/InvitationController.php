<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class InvitationController extends CI_Controller {

	 public function __construct() {
        parent::__construct();
        $this->load->model('UserModel/NewUser_Model'); // Load your model
		$this->load->library('session'); // For session management
		$this->load->model('Team/Invitation_Model'); // Load the invitation model
        $this->load->helper('url'); // For redirect and base_url()
		$this->load->database();
    }

	public function index()
	{
		$this->load->view('welcome_message');
	}

	public function join($invite_code = NULL)
    {
        if (!$invite_code) {
            show_404(); // Invalid access
        }

        // Validate the invite code
        $team = $this->Invitation_Model->get_team_by_invite_code($invite_code);

        if (!$team) {
            $this->session->set_flashdata('error', 'Invalid or expired invite link.');
            redirect('Auth/LoginController/show_login'); // Match your login route
            return;
        }

        // Check if user is logged in
        if (!$this->session->userdata('user_id')) {
            $this->session->set_flashdata('invite_code', $invite_code);
            redirect('Auth/LoginController/show_login'); // Match your login route
            return;
        }

        // Logged in - associate user with team
        $user_id = $this->session->userdata('user_id');
        $role = $this->session->userdata('role'); // Default to 'Player' if not provided
        $this->NewUser_Model->setUserRoleAndTeamById($user_id, $team->id, $role);

		$this->session->set_userdata('team_id', $team->id);
		redirect('Team/DashboardController/index');
    }

    public function get_invite_link()
    {

		$user_id = $this->input->get('user_id');
		$role = $this->input->get('role');

        // Ensure user is logged in and is a coach
        if (!$user_id || $role !== 'Coach') {
            echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
            return;
        }

        // Fetch the team owned by the logged-in coach
        $team = $this->Invitation_Model->get_team_by_owner($user_id);

        if (!$team) {
            echo json_encode(['success' => false, 'error' => 'No team found for this coach']);
            return;
        }

        // Construct the invite link
        $invite_link = base_url('team/join/' . $team['invite_code']);

        // Return JSON response
        echo json_encode([
            'success' => true,
            'invite_link' => $invite_link,
            'team_name' => $team['team_name']
        ]);
    }

    /* public function share_invite()
    {
        // Ensure user is logged in and is a coach
        if (!$this->session->userdata('user_id') || $this->session->userdata('role') !== 'Coach') {
            $this->session->set_flashdata('error', 'Unauthorized access.');
            redirect('auth/login'); // Match your login route
            return;
        }

        $this->load->view('team/invite_link');
    } */
}
