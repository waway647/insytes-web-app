<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ModalInsertsController extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->library('session');
        $this->load->model('ModalInserts_Model');
	}

    public function create_season() {
        // Determine end_date_value (using robust check for empty string)
        $is_end_date_known = $this->input->post('end_date_known');
        $end_date_value = NULL;
        if ($is_end_date_known) {
            $end_date_value = $this->input->post('end_date') ?: NULL; 
        }

        // Determine is_active_value
        $is_active_value = $this->input->post('is_active') ? TRUE : FALSE;

        $season_data = array(
            'start_year' => $this->input->post('start_year'),
            'end_year' => $this->input->post('start_year') + 1,
            'start_date' => $this->input->post('start_date'),
            'end_date' => $end_date_value,
            'is_active' => $is_active_value
        );
        
        // NOTE: Ensure your insert_season returns TRUE on success and FALSE on failure
        $result = $this->ModalInserts_Model->insert_season($season_data);

        if ($result) {
            $response = array(
                'success' => TRUE,
                'message' => 'New season created successfully!',
                // 'new_id' => $result // Optional: if you need the inserted ID
            );
            $status_code = 200; // HTTP OK
        } else {
            // NOTE: In a real app, you might want to return a specific DB error message
            $response = array(
                'success' => FALSE,
                'message' => 'Failed to create the season. Please check input values or database constraints.'
            );
            $status_code = 500; // HTTP Internal Server Error or 400 Bad Request
        }

        // Set the appropriate HTTP header and output the JSON response
        $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    public function update_season() {
        $is_end_date_known = $this->input->post('end_date_known');
        $end_date_value = NULL;
        if ($is_end_date_known) {
            $end_date_value = $this->input->post('end_date') ?: NULL; 
        }

        $is_active_value = $this->input->post('is_active') ? TRUE : FALSE;

        $data = array(
            'id' => $this->input->post('id'),
            'start_year' => $this->input->post('start_year'),
            'end_year' => $this->input->post('start_year') + 1,
            'start_date' => $this->input->post('start_date'),
            'end_date' => $end_date_value,
            'is_active' => $is_active_value
        );

        $id = $data['id'];

        $result = $this->ModalInserts_Model->update_season($id, $data);

        if ($result) {
            $response = array(
                'success' => TRUE,
                'message' => 'Season updated successfully!',
            );
            $status_code = 200; // HTTP OK
        } else {
            $response = array(
                'success' => FALSE,
                'message' => 'Failed to update the season. Please check input values or database constraints.'
            );
            $status_code = 500; // HTTP Internal Server Error or 400 Bad Request
        }

        $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    public function create_competition() {
		$data = array(
            'name' => $this->input->post('name'),
            'type' => $this->input->post('type')
        );
        
        // NOTE: Ensure your insert_season returns TRUE on success and FALSE on failure
        $result = $this->ModalInserts_Model->insert_competition($data);

        if ($result) {
            $response = array(
                'success' => TRUE,
                'message' => 'New competition created successfully!',
                // 'new_id' => $result // Optional: if you need the inserted ID
            );
            $status_code = 200; // HTTP OK
        } else {
            // NOTE: In a real app, you might want to return a specific DB error message
            $response = array(
                'success' => FALSE,
                'message' => 'Failed to create the competition. Please check input values or database constraints.'
            );
            $status_code = 500; // HTTP Internal Server Error or 400 Bad Request
        }

        // Set the appropriate HTTP header and output the JSON response
        $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
	}

    public function update_competition() {
        $data = array(
            'id' => $this->input->post('id'),
            'name' => $this->input->post('name'),
            'type' => $this->input->post('type')
        );

        $id = $data['id'];

        $result = $this->ModalInserts_Model->update_competition($id, $data);

        if ($result) {
            $response = array(
                'success' => TRUE,
                'message' => 'Competition updated successfully!',
                // 'new_id' => $result // Optional: if you need the inserted ID
            );
            $status_code = 200; // HTTP OK
        } else {
            $response = array(
                'success' => FALSE,
                'message' => 'Failed to update the competition. Please check input values or database constraints.'
            );
            $status_code = 500; // HTTP Internal Server Error or 400 Bad Request
        }

        $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    public function create_venue() {
		$data = array(
            'name' => $this->input->post('name'),
            'city' => $this->input->post('city')
        );
        
        // NOTE: Ensure your insert_season returns TRUE on success and FALSE on failure
        $result = $this->ModalInserts_Model->insert_venue($data);

        if ($result) {
            $response = array(
                'success' => TRUE,
                'message' => 'New Venue created successfully!',
                // 'new_id' => $result // Optional: if you need the inserted ID
            );
            $status_code = 200; // HTTP OK
        } else {
            // NOTE: In a real app, you might want to return a specific DB error message
            $response = array(
                'success' => FALSE,
                'message' => 'Failed to create the venue. Please check input values or database constraints.'
            );
            $status_code = 500; // HTTP Internal Server Error or 400 Bad Request
        }

        // Set the appropriate HTTP header and output the JSON response
        $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
	}

    public function update_venue() {
        $data = array(
            'id' => $this->input->post('id'),
            'name' => $this->input->post('name'),
            'city' => $this->input->post('city')
        );

        $id = $data['id'];

        $result = $this->ModalInserts_Model->update_venue($id, $data);

        if ($result) {
            $response = array(
                'success' => TRUE,
                'message' => 'Venue updated successfully!',
                // 'new_id' => $result // Optional: if you need the inserted ID
            );
            $status_code = 200; // HTTP OK
        } else {
            $response = array(
                'success' => FALSE,
                'message' => 'Failed to update the venue. Please check input values or database constraints.'
            );
            $status_code = 500; // HTTP Internal Server Error or 400 Bad Request
        }

        $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    public function create_team() {
        $user_id = $this->session->userdata('user_id');

        $data = array(
            'team_name' => $this->input->post('team_name'),
            'abbreviation' => $this->input->post('abbreviation'),
            'country' => $this->input->post('country'),
            'city' => $this->input->post('city'),
            'created_by' => $user_id
        );

        $team_name = $data['team_name'];

        if (isset($_FILES['team_logo']) && $_FILES['team_logo']['error'] === UPLOAD_ERR_OK) {
			$config['upload_path'] = './assets/images/opponent_team_logos/';
			$config['allowed_types'] = 'jpg|jpeg|png';
			$config['max_size'] = 2048;
			$config['file_name'] = 'team_' . $team_name . '_' . time(); 

			$this->load->library('upload', $config);

			if ($this->upload->do_upload('team_logo')) { // Key is 'team_logo'
				$upload_data = $this->upload->data();
				$team_logo = 'assets/images/opponent_team_logos/' . $upload_data['file_name']; // Overwrite logo path
			} else {
				// Handle upload error (Return JSON is good for AJAX)
				echo json_encode(['error' => 'File upload failed: ' . $this->upload->display_errors()]);
				return;
			}
		}

        $data['team_logo'] = $team_logo;
        
        // NOTE: Ensure your insert_season returns TRUE on success and FALSE on failure
        $result = $this->ModalInserts_Model->insert_team($data);

        if ($result) {
            $response = array(
                'success' => TRUE,
                'message' => 'New Team created successfully!',
                // 'new_id' => $result // Optional: if you need the inserted ID
            );
            $status_code = 200; // HTTP OK
        } else {
            // NOTE: In a real app, you might want to return a specific DB error message
            $response = array(
                'success' => FALSE,
                'message' => 'Failed to create the team. Please check input values or database constraints.'
            );
            $status_code = 500; // HTTP Internal Server Error or 400 Bad Request
        }

        // Set the appropriate HTTP header and output the JSON response
        $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
	}

    public function update_team() {
        $data = array(
            'id' => $this->input->post('id'),
            'team_name' => $this->input->post('team_name'),
            'abbreviation' => $this->input->post('abbreviation'),
            'country' => $this->input->post('country'),
            'city' => $this->input->post('city')
        );

        $team_name = $data['team_name'];

        if (isset($_FILES['team_logo']) && $_FILES['team_logo']['error'] === UPLOAD_ERR_OK) {
			$config['upload_path'] = './assets/images/opponent_team_logos/';
			$config['allowed_types'] = 'jpg|jpeg|png';
			$config['max_size'] = 2048;
			$config['file_name'] = 'team_' . $team_name . '_' . time(); 

			$this->load->library('upload', $config);

			if ($this->upload->do_upload('team_logo')) { // Key is 'team_logo'
				$upload_data = $this->upload->data();
				$team_logo = 'assets/images/opponent_team_logos/' . $upload_data['file_name']; // Overwrite logo path
			} else {
				// Handle upload error (Return JSON is good for AJAX)
				echo json_encode(['error' => 'File upload failed: ' . $this->upload->display_errors()]);
				return;
			}
		}

        $id = $data['id'];
        $data['team_logo'] = $team_logo;
        
        // NOTE: Ensure your insert_season returns TRUE on success and FALSE on failure
        $result = $this->ModalInserts_Model->update_team($id, $data);

        if ($result) {
            $response = array(
                'success' => TRUE,
                'message' => 'Team updated successfully!',
                // 'new_id' => $result // Optional: if you need the inserted ID
            );
            $status_code = 200; // HTTP OK
        } else {
            // NOTE: In a real app, you might want to return a specific DB error message
            $response = array(
                'success' => FALSE,
                'message' => 'Failed to update the team. Please check input values or database constraints.'
            );
            $status_code = 500; // HTTP Internal Server Error or 400 Bad Request
        }

        // Set the appropriate HTTP header and output the JSON response
        $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    public function create_player() {
        $data = array(
            'team_id' => $this->input->post('team_id'),
            'first_name' => $this->input->post('first_name'),
            'middle_initial' => $this->input->post('middle_initial'),
            'last_name' => $this->input->post('last_name'),
            'position' => $this->input->post('position'),
            'jersey' => $this->input->post('jersey'),
        );
        
        // NOTE: Ensure your insert_season returns TRUE on success and FALSE on failure
        $result = $this->ModalInserts_Model->insert_player($data);

        if ($result) {
            $response = array(
                'success' => TRUE,
                'message' => 'New player created successfully!',
                // 'new_id' => $result // Optional: if you need the inserted ID
            );
            $status_code = 200; // HTTP OK
        } else {
            // NOTE: In a real app, you might want to return a specific DB error message
            $response = array(
                'success' => FALSE,
                'message' => 'Failed to create the player. Please check input values or database constraints.'
            );
            $status_code = 500; // HTTP Internal Server Error or 400 Bad Request
        }

        // Set the appropriate HTTP header and output the JSON response
        $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
	}

    public function update_player() {
        $data = array(
            'id' => $this->input->post('id'),
            'team_id' => $this->input->post('team_id'),
            'first_name' => $this->input->post('first_name'),
            'middle_initial' => $this->input->post('middle_initial'),
            'last_name' => $this->input->post('last_name'),
            'position' => $this->input->post('position'),
            'jersey' => $this->input->post('jersey'),
        );

        $id = $data['id'];
        
        // NOTE: Ensure your insert_season returns TRUE on success and FALSE on failure
        $result = $this->ModalInserts_Model->update_player($id, $data);

        if ($result) {
            $response = array(
                'success' => TRUE,
                'message' => 'New player created successfully!',
                // 'new_id' => $result // Optional: if you need the inserted ID
            );
            $status_code = 200; // HTTP OK
        } else {
            // NOTE: In a real app, you might want to return a specific DB error message
            $response = array(
                'success' => FALSE,
                'message' => 'Failed to create the player. Please check input values or database constraints.'
            );
            $status_code = 500; // HTTP Internal Server Error or 400 Bad Request
        }

        // Set the appropriate HTTP header and output the JSON response
        $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }
}
