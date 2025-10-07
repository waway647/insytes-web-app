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

}
