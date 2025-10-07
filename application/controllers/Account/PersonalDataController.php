<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

class PersonalDataController extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->library('session');
		$this->load->model('user_model');
	}

	public function get_user() {
		header('Content-Type: application/json');

		// 1. Get the identifying data (e.g., from session)
        $user_id = $this->session->userdata('user_id');

		$user_data_result = $this->user_model->get_user_by_id($user_id);
        
        if (empty($user_data_result)) {
            // Handle case where user is not found
            echo json_encode([['error' => 'User not found or not logged in.']]);
            return; // CRITICAL: Stop execution
        }

        // 2. Output the JSON data
        echo json_encode($user_data_result);

        // 3. CRITICAL: Stop execution immediately after echo
        return; 
	}

	public function update_user() {
		header('Content-Type: application/json');

		$user_id = $this->session->userdata('user_id');

		// Decode the generic JSON payload: { "field": "email", "value": "new_email@example.com" }
		$json_data = file_get_contents('php://input');
		$data = json_decode($json_data, true);

		$field = $data['field'] ?? null;
		$value = $data['value'] ?? null;
		
		// 1. Basic Validation: Check if the required data is present
		if (empty($user_id) || empty($field) || $value === null) {
			echo json_encode(['success' => false, 'message' => 'Missing user ID, field identifier, or new value in the request body.']);
			return;
		}

		// 2. Security Check: Only allow updates for specified fields
		$allowed_fields = ['email', 'first_name', 'last_name'];
		
		if (!in_array($field, $allowed_fields)) {
			echo json_encode(['success' => false, 'message' => 'Invalid field specified for update.']);
			return;
		}
		
		// Prepare data array for the model: ['field_name' => 'new_value']
		$update_data = [$field => $value];
		
		// --- Model Update Call ---
		// NOTE: This assumes your user_model has a method like update_user_data() 
		// that handles updating arbitrary fields based on the $update_data array.
		$update_result = $this->user_model->update_user_data($user_id, $update_data); 

		if ($update_result) {
			// 3. Success: Update session data for the changed field
			$this->session->set_userdata($field, $value);
			
			$message = ucfirst(str_replace('_', ' ', $field)) . ' updated successfully.';
			echo json_encode(['success' => true, 'message' => $message]);
		} else {
			// Failure: Model reported a database error or no rows were affected
			echo json_encode(['success' => false, 'message' => 'Failed to update user data in the database.']);
		}
		
		return;
	}

	public function delete_user()
    {
        $userId = $this->session->userdata('user_id');
        $input = $this->request->getJSON(true);
        $confirmation = $input['confirmation'] ?? null;

        if ($confirmation !== 'DELETE') {
            return $this->fail('Confirmation text is incorrect.', 400, 'error');
        }

        try {
            // Delegate the delete operation to the model
            $result = $this->user_Model->deleteUser($userId);

            if ($result) {
                // Destroy the session and log the user out after successful deletion
                $this->session->sess_destroy();

                return $this->respond([
                    'success' => true, 
                    'message' => 'Account deleted successfully.', 
                    'redirect_url' => site_url('auth/logincontroller/show_login') // Redirect to homepage or login page
                ]);
            }

            return $this->fail('User could not be found or deleted.', 404, 'error');

        } catch (\Exception $e) {
            log_message('error', 'User deletion failed for ID ' . $userId . ': ' . $e->getMessage());
            return $this->fail('An unexpected error occurred during deletion.', 500, 'error');
        }
    }

}
