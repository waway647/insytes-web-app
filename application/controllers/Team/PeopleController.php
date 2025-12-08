<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PeopleController extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->library('session');
		$this->load->model('Team/People_Model');
		$this->load->helper('url');
		$this->load->database();
	}
	public function index()
	{
		$data['title'] = 'People';
		$data['main_content'] = 'team/people';
		$this->load->view('layouts/main', $data);
	}

	public function getTeamMembers() {
		$team_id = $this->input->get('team_id');
		
		if (!$team_id) {
			echo json_encode(['error' => 'No team ID provided']);
			return;
		}

		$members = $this->People_Model->getMembersByTeamId($team_id);
		echo json_encode($members);
	}

	public function getTeamName() {
		$team_id = $this->input->get('team_id'); // e.g. 1 from users.team_id
    
		if (!$team_id) {
			echo json_encode(['error' => 'No team ID provided']);
			return;
		}

		$team = $this->People_Model->getTeamById($team_id);

		if ($team) {
			echo json_encode($team);
		} else {
			echo json_encode(['error' => 'Team not found']);
		}
	}

	public function getTotalTeamMembers(){
		$team_id = $this->input->get('team_id');

		if (!$team_id) {
			echo json_encode(['error' => 'No team ID provided']);
			return;
		}

		$totalMembers = $this->People_Model->getTotalMembers($team_id);

		echo json_encode(['total_members' => $totalMembers]);
	}

	public function remove_from_team()
	{
		// Accept JSON payload or form-encoded POST
		$raw = trim(file_get_contents('php://input'));
		$data = json_decode($raw, true);
		if (!is_array($data)) {
			$data = $this->input->post();
		}

		$user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;
		$team_id = isset($data['team_id']) ? $data['team_id'] : null; // keep null if not provided

		if (!$user_id) {
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode(['success' => false, 'message' => 'No user_id provided']));
		}

		$this->load->model('People_Model'); // ensure model is loaded

		$user_removed = $this->People_Model->remove_user_from_team($user_id, $team_id);

		if ($user_removed) {
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode(['success' => true]));
		} else {
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode(['success' => false, 'message' => 'Database update failed or no changes made']));
		}
	}
}
