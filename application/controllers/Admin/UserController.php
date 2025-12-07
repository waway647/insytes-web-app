<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Suppress PHP 8+ deprecation warnings for CodeIgniter compatibility
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

class UserController extends CI_Controller {
	
	public function __construct() {
		parent::__construct();
		$this->load->model('Admin/User_Model');
		$this->load->model('Admin/Logs_Model');
		$this->load->helper('admin_auth');
		$this->load->helper('log_helper');
		$this->load->library('session');
		$this->load->helper('url');
		$this->load->database();
	}

	public function index()
	{
		// Load the users management page
		$data['title'] = 'Users Management';
		$data['main_content'] = 'admin/users';
		
		$this->load->view('layouts/main', $data);
	}
	
	public function getAllUsers() {
		header('Content-Type: application/json');
		
		// Debug information
		error_log("getAllUsers called");
		
		try {
			$search = $this->input->get('search');
			$role_filter = $this->input->get('role');
			$team_filter = $this->input->get('team');
			
			$users = $this->User_Model->getAllUsersWithFilters($search, $role_filter, $team_filter);
			
			error_log("Users found: " . count($users));
			echo json_encode($users);
		} catch (Exception $e) {
			error_log("Error in getAllUsers: " . $e->getMessage());
			echo json_encode(['error' => $e->getMessage()]);
		}
	}
	
	public function getUserCount() {
		header('Content-Type: application/json');
		
		$count = $this->User_Model->getTotalUserCount();
		echo json_encode(['total_users' => $count]);
	}
	
	public function getTeams() {
		header('Content-Type: application/json');
		
		// Debug information
		error_log("getTeams called");
		
		try {
			$teams = $this->User_Model->getAllTeams();
			error_log("Teams found: " . count($teams));
			echo json_encode($teams);
		} catch (Exception $e) {
			error_log("Error in getTeams: " . $e->getMessage());
			echo json_encode(['error' => $e->getMessage()]);
		}
	}
	
	public function addUser() {
		header('Content-Type: application/json');
		
		if ($this->input->server('REQUEST_METHOD') !== 'POST') {
			echo json_encode(['success' => false, 'message' => 'Invalid request method']);
			return;
		}
		
		$userData = [
			'first_name' => $this->input->post('first_name'),
			'last_name' => $this->input->post('last_name'),
			'email' => $this->input->post('email'),
			'role' => ucfirst(strtolower($this->input->post('role'))),
			'status' => 'new', // Mark as new user needing onboarding
			'password' => password_hash($this->input->post('password'), PASSWORD_DEFAULT),
			'temp_password' => 1, // Mark as temporary password
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s')
		];
		
		// Add team_id if provided (now optional)
		$team_id = $this->input->post('team_id');
		if (!empty($team_id) && $team_id !== 'none') {
			$userData['team_id'] = $team_id;
		}
		
		$result = $this->User_Model->createUser($userData);
		
		if ($result) {
			// Get the created user ID
			$created_user_id = $this->db->insert_id();
			
			// Log user creation
			LogHelper::logUserCreated(
				$created_user_id,
				$userData['email'],
				$userData['role'],
				$this->session->userdata('user_id'),
				$this->session->userdata('email')
			);
			
			echo json_encode([
				'success' => true, 
				'message' => 'User created successfully',
				'data' => [
					'email' => $this->input->post('email'),
					'password' => $this->input->post('password')
				]
			]);
		} else {
			echo json_encode(['success' => false, 'message' => 'Failed to create user']);
		}
	}
	
	public function updateUser() {
		header('Content-Type: application/json');
		
		if ($this->input->server('REQUEST_METHOD') !== 'POST') {
			echo json_encode(['success' => false, 'message' => 'Invalid request method']);
			return;
		}
		
		$user_id = $this->input->post('user_id');
		
		if (!$user_id) {
			echo json_encode(['success' => false, 'message' => 'User ID is required']);
			return;
		}
		
		$userData = [
			'first_name' => $this->input->post('first_name'),
			'last_name' => $this->input->post('last_name'),
			'email' => $this->input->post('email'),
			'role' => ucfirst(strtolower($this->input->post('role'))),
			'updated_at' => date('Y-m-d H:i:s')
		];
		
		// Add team_id if provided
		$team_id = $this->input->post('team_id');
		if (!empty($team_id)) {
			$userData['team_id'] = $team_id;
		}
		
		// Only update password if provided
		$password = $this->input->post('password');
		if (!empty($password)) {
			$userData['password'] = password_hash($password, PASSWORD_DEFAULT);
		}
		
		$result = $this->User_Model->updateUser($user_id, $userData);
		
		if ($result) {
			// Log user update
			LogHelper::logUserUpdated(
				$user_id,
				$userData['email'],
				$this->session->userdata('user_id'),
				$this->session->userdata('email')
			);
			
			echo json_encode(['success' => true, 'message' => 'User updated successfully']);
		} else {
			echo json_encode(['success' => false, 'message' => 'Failed to update user']);
		}
	}
	
	public function deleteUser() {
		header('Content-Type: application/json');
		
		if ($this->input->server('REQUEST_METHOD') !== 'POST') {
			echo json_encode(['success' => false, 'message' => 'Invalid request method']);
			return;
		}
		
		$user_id = $this->input->post('user_id');
		
		if (!$user_id) {
			echo json_encode(['success' => false, 'message' => 'User ID is required']);
			return;
		}
		
		// Get user info before deletion
		$user = $this->User_Model->getUserById($user_id);
		
		$result = $this->User_Model->deleteUser($user_id);
		
		if ($result) {
			// Log user deletion
			LogHelper::logUserDeleted(
				$user_id,
				$user['email'] ?? 'Unknown',
				$this->session->userdata('user_id'),
				$this->session->userdata('email')
			);
			
			echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
		} else {
			echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
		}
	}

}
