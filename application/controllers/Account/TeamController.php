<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

class TeamController extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->library('session');
		$this->load->model('Team_Model');
	}

	public function index()
	{
		$data['title'] = 'Account';
		$data['main_content'] = 'account/team';
		$this->load->view('layouts/main', $data);
	}

	public function get_team() {
		$team_id = $this->session->userdata('team_id');

		$team_data = $this->Team_Model->get_team($team_id);

		if (empty($team_data)) {
            // Handle case where user is not found
            echo json_encode([['error' => 'User not found or not logged in.']]);
            return; // CRITICAL: Stop execution
        }

        echo json_encode($team_data);

        return; 
	}

	public function update_team() {
		$team_id = $this->session->userdata('team_id');
		
		// 1. Get existing data to determine the current logo path
		$existing_team = $this->Team_Model->get_team($team_id);
		$team_logo = isset($existing_team['team_logo']) ? $existing_team['team_logo'] : null; // Set default logo

		// 2. Prepare update data from POST
		$team_update_data = array(
			'team_name' => $this->input->post('team_name'),
			'country' => $this->input->post('country'),
			'city' => $this->input->post('city'),
			'primary_color' => $this->input->post('primary_color'),
			'secondary_color' => $this->input->post('secondary_color'),
			'team_link' => $this->input->post('team_link'),
		);

		// 3. Handle file upload (key MUST be 'team_logo' to match Alpine.js FormData)
		if (isset($_FILES['team_logo']) && $_FILES['team_logo']['error'] === UPLOAD_ERR_OK) {
			$config['upload_path'] = './assets/team_logos/';
			$config['allowed_types'] = 'jpg|jpeg|png|gif';
			$config['max_size'] = 2048;
			$config['file_name'] = 'team_' . $team_id . '_' . time(); 

			$this->load->library('upload', $config);

			if ($this->upload->do_upload('team_logo')) { // Key is 'team_logo'
				$upload_data = $this->upload->data();
				$team_logo = 'assets/team_logos/' . $upload_data['file_name']; // Overwrite logo path
			} else {
				// Handle upload error (Return JSON is good for AJAX)
				echo json_encode(['error' => 'File upload failed: ' . $this->upload->display_errors()]);
				return;
			}
		} 
		// If no new file, $team_logo remains the existing logo from step 1.

		// 4. Add the final logo path to the update data
		$team_update_data['team_logo'] = $team_logo;

		// 5. Update and Respond
		$updated_team = $this->Team_Model->update_team($team_id, $team_update_data);

		if ($updated_team) {
			// NOTE: It is best practice to return an object with a success flag
			echo json_encode(['success' => true, 'new_team_data' => $updated_team]);
		} else {
			echo json_encode(['error' => 'Failed to update team.']);
		}

		// You don't need a final 'return;' if you've already echoed JSON.
	}
}
