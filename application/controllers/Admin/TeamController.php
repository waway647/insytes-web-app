<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class TeamController extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->model('Admin/Team_Model');
		$this->load->model('Admin/Logs_Model');
		$this->load->helper('admin_auth');
		$this->load->helper('log_helper');
		$this->load->library('session');
	}

	public function index()
	{
		// Load the teams management page
		$data['title'] = 'Teams Management';
		$data['main_content'] = 'admin/teams';
		
		$this->load->view('layouts/main', $data);
	}
	
	/**
     * Get all teams with optional filters
     */
    public function getAllTeams()
    {
        try {
            // Set JSON header for API responses
            $this->output->set_content_type('application/json');
            
            $search = $this->input->get('search') ?? '';
            $location_filter = $this->input->get('location_filter') ?? '';
            $manager_filter = $this->input->get('manager_filter') ?? '';
            
            $teams = $this->Team_Model->getAllTeamsWithFilters($search, $location_filter, $manager_filter);
            $total_count = $this->Team_Model->getTotalTeamCount();
            
            $this->output
                ->set_status_header(200)
                ->set_output(json_encode([
                    'success' => true,
                    'teams' => $teams,
                    'total_count' => $total_count
                ]));
                
        } catch (Exception $e) {
            error_log('Error in getAllTeams: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Failed to load teams'
                ]));
        }
    }
    
    /**
     * Add new team
     */
    public function addTeam()
    {
        try {
            // Set JSON header for API responses
            $this->output->set_content_type('application/json');
            // Validate required fields
            $required_fields = ['team_name'];
            foreach ($required_fields as $field) {
                if (empty($this->input->post($field))) {
                    $this->output
                        ->set_status_header(400)
                        ->set_output(json_encode([
                            'success' => false,
                            'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'
                        ]));
                    return;
                }
            }
            
            // Handle logo upload
            $logo_url = null;
            if (!empty($_FILES['logo']['name'])) {
                $logo_url = $this->handleLogoUpload();
                if ($logo_url === false) {
                    $this->output
                        ->set_status_header(400)
                        ->set_output(json_encode([
                            'success' => false,
                            'message' => 'Logo upload failed'
                        ]));
                    return;
                }
                // Remove base_url from logo_url for storage
                $logo_url = str_replace(base_url(), '', $logo_url);
            }
            
            $teamData = [
                'team_name'        => $this->input->post('team_name'),
                'abbreviation'        => $this->input->post('abbreviation'),
                'location'         => $this->input->post('location') ?? '',
                'city'             => $this->input->post('city') ?? '',
                'country'          => $this->input->post('country') ?? '',
                'logo_url'         => $logo_url
            ];
            
            if ($this->Team_Model->createTeam($teamData)) {
                // Log team creation
                $team_id = $this->db->insert_id();
                LogHelper::logTeamCreated(
                    $team_id,
                    $teamData['team_name'],
                    $this->session->userdata('user_id'),
                    $this->session->userdata('email')
                );
                
                $this->output
                    ->set_status_header(200)
                    ->set_output(json_encode([
                        'success' => true,
                        'message' => 'Team added successfully'
                    ]));
            } else {
                $this->output
                    ->set_status_header(500)
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Failed to add team'
                    ]));
            }
            
        } catch (Exception $e) {
            error_log('Error in addTeam: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Server error occurred'
                ]));
        }
    }
    
    /**
     * Update existing team
     */
    public function updateTeam()
    {
        try {
            // Set JSON header for API responses
            $this->output->set_content_type('application/json');
            $team_id = $this->input->post('team_id');
            if (empty($team_id)) {
                $this->output
                    ->set_status_header(400)
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Team ID is required'
                    ]));
                return;
            }
            
            // Get existing team data
            $existing_team = $this->Team_Model->getTeamById($team_id);
            if (!$existing_team) {
                $this->output
                    ->set_status_header(404)
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Team not found'
                    ]));
                return;
            }
            
            // Handle logo upload
            $logo_url = $existing_team['team_logo']; // Keep existing logo by default
            if (!empty($_FILES['logo']['name'])) {
                $new_logo = $this->handleLogoUpload();
                if ($new_logo !== false) {
                    // Remove base_url from logo_url for storage
                    $logo_url = str_replace(base_url(), '', $new_logo);
                    // Delete old logo file if it exists
                    if (!empty($existing_team['team_logo']) && file_exists('./' . $existing_team['team_logo'])) {
                        unlink('./' . $existing_team['team_logo']);
                    }
                }
            }
            
            $teamData = [
                'team_name'       => $this->input->post('team_name') ?: $existing_team['team_name'],
                'abbreviation'    => $this->input->post('abbreviation'),
                'location'        => $this->input->post('location'),
                'city'            => $this->input->post('city'),
                'country'         => $this->input->post('country'),
                'manager_id'      => $this->input->post('manager_id'),
                'logo_url'        => $logo_url
            ];
            
            if ($this->Team_Model->updateTeam($team_id, $teamData)) {
                // Log team update
                LogHelper::logTeamUpdated(
                    $team_id,
                    $teamData['team_name'],
                    $this->session->userdata('user_id'),
                    $this->session->userdata('email')
                );
                
                $this->output
                    ->set_status_header(200)
                    ->set_output(json_encode([
                        'success' => true,
                        'message' => 'Team updated successfully'
                    ]));
            } else {
                $this->output
                    ->set_status_header(500)
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Failed to update team'
                    ]));
            }
            
        } catch (Exception $e) {
            error_log('Error in updateTeam: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Server error occurred'
                ]));
        }
    }
    
    /**
     * Get available managers (coaches/admins)
     */
    public function getAvailableManagers()
    {
        try {
            // Set JSON header for API responses
            $this->output->set_content_type('application/json');
            
            $managers = $this->Team_Model->getAvailableManagers();
            
            $this->output
                ->set_status_header(200)
                ->set_output(json_encode([
                    'success' => true,
                    'managers' => $managers
                ]));
                
        } catch (Exception $e) {
            error_log('Error in getAvailableManagers: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Failed to load managers'
                ]));
        }
    }
    
    /**
     * Delete team
     */
    public function deleteTeam()
    {
        try {
            // Set JSON header for API responses
            $this->output->set_content_type('application/json');
            $team_id = $this->input->post('team_id');
            if (empty($team_id)) {
                $this->output
                    ->set_status_header(400)
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Team ID is required'
                    ]));
                return;
            }
            
            // Get team data to delete logo file
            $team_data = $this->Team_Model->getTeamById($team_id);
            
            if ($this->Team_Model->deleteTeam($team_id)) {
                // Delete logo file if it exists
                if (!empty($team_data['team_logo']) && file_exists('./' . $team_data['team_logo'])) {
                    unlink('./' . $team_data['team_logo']);
                }
                
                // Log team deletion
                LogHelper::logTeamDeleted(
                    $team_id,
                    $team_data['team_name'] ?? 'Unknown',
                    $this->session->userdata('user_id'),
                    $this->session->userdata('email')
                );
                
                $this->output
                    ->set_status_header(200)
                    ->set_output(json_encode([
                        'success' => true,
                        'message' => 'Team deleted successfully'
                    ]));
            } else {
                $this->output
                    ->set_status_header(500)
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Failed to delete team'
                    ]));
            }
            
        } catch (Exception $e) {
            error_log('Error in deleteTeam: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Server error occurred'
                ]));
        }
    }
    
    /**
     * Handle logo file upload
     */
    private function handleLogoUpload()
    {
        $config['upload_path'] = './assets/team_logos/';
        $config['allowed_types'] = 'gif|jpg|jpeg|png|svg';
        $config['max_size'] = 2048; // 2MB
        $config['encrypt_name'] = TRUE;
        
        // Create directory if it doesn't exist
        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0755, true);
        }
        
        $this->load->library('upload', $config);
        
        if ($this->upload->do_upload('logo')) {
            $upload_data = $this->upload->data();
            return base_url('assets/team_logos/' . $upload_data['file_name']);
        } else {
            error_log('Logo upload error: ' . $this->upload->display_errors());
            return false;
        }
    }
}
