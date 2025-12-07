<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Suppress PHP 8+ deprecation warnings for CodeIgniter compatibility
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

class User_Model extends CI_Model {
	public function __construct() {
		$this->load->database();
	}
	public function verify_user_from_google($userInfo) {
		$email = $userInfo->email;
		$google_id = $userInfo->id;

		// 1. Check if user exists by Google ID
		$this->db->where('google_id', $google_id);
		$query = $this->db->get('users');
		$user = $query->row_array();

		if ($user) {
			// User exists, return user data
			return $user;
		}

		// 2. If not found by Google ID, check by email
		$this->db->where('email', $email);
		$query = $this->db->get('users');
		$user = $query->row_array();

		if ($user) {
			// User exists by email, update Google ID
			$this->db->where('id', $user['id']);
			$this->db->update('users', ['google_id' => $google_id]);
			$user['google_id'] = $google_id;
			return $user;
		}

		// 3. If user doesn't exist
		return null;
	}

	public function create_new_user_from_google($user_data) {
		$user_data = [
			'email' => $user_data['email'],
			'first_name' => $user_data['first_name'],
			'last_name' => $user_data['last_name'],
			'password' => null,
			'google_id' => $user_data['google_id'],
			'role' => null
		];

		$this->db->insert('users', $user_data);
		$user_data['id'] = $this->db->insert_id();

		return $user_data;
	}

	public function getByEmail($email) {
        return $this->db->where('email', $email)->get('users')->row();
    }

    public function updatePassword($email, $hashedPassword) {
        $this->db->where('email', $email)
                 ->update('users', ['password' => $hashedPassword]);
    }

	public function get_user_by_id($user_id) {
		// First try to get user from the view
		$this->db->where('id', $user_id);
		$query = $this->db->get('users_vw');
		$user = $query->row_array();
		
		// If not found in view (e.g., admin users), get from base users table
		if (empty($user)) {
			$this->db->select('id, first_name, last_name, email, role, team_id');
			$this->db->where('id', $user_id);
			$query = $this->db->get('users');
			$user = $query->row_array();
			
			// Add team_name if user has team_id
			if (!empty($user) && !empty($user['team_id'])) {
				$this->db->select('team_name');
				$this->db->where('id', $user['team_id']);
				$team_query = $this->db->get('teams');
				$team = $team_query->row_array();
				$user['team_name'] = $team ? $team['team_name'] : null;
			} else {
				$user['team_name'] = null;
			}
		}
		
		return $user;
	}
	
	public function update_user_data($user_id, $update_data) {
        // Set the WHERE condition to target the specific user
        $this->db->where('id', $user_id);
        
        // Use the $update_data array directly, which contains the field and its new value.
        // E.g., if $update_data is ['email' => 'new@email.com'], it updates the 'email' column.
        return $this->db->update('users', $update_data);
    }

	public function deleteUser(int $userId): bool
    {
        // Start a database transaction to ensure related data is also deleted
        $this->db->transBegin();

        try {
            // 1. Delete associated records (e.g., logs, settings, preferences)
            // You MUST uncomment and complete these steps based on your application's schema!
            
            // Example: $this->db->table('user_logs')->where('user_id', $userId)->delete();
            // Example: $this->db->table('user_settings')->where('user_id', $userId)->delete();
            
			// 2. Delete the user record from the 'users' table
			$this->db->where('id', $userId);
			$isDeleted = $this->db->delete('users');

			if ($isDeleted && $this->db->affected_rows() > 0) {
				$this->db->transCommit();
				return true;
			} else {
				// User ID not found
				$this->db->transRollback();
				return false;
			}

        } catch (\Exception $e) {
            $this->db->transRollback();
            // Log the error for debugging
            log_message('error', 'Database deletion error for user ID ' . $userId . ': ' . $e->getMessage());
            throw $e; // Re-throw or handle as appropriate
        }
    }

    public function getAllUsersWithFilters($search = null, $role_filter = null, $team_filter = null) {
        // Select all necessary fields including temp_password, status, and updated_at
        $this->db->select('users.id, users.first_name, users.last_name, users.email, users.role, users.status, users.temp_password, users.team_id, users.updated_at, teams.team_name');
        $this->db->from('users');
        $this->db->join('teams', 'teams.id = users.team_id', 'left');
        
        // Apply search filter if provided
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('users.first_name', $search);
            $this->db->or_like('users.last_name', $search);
            $this->db->or_like('users.email', $search);
            $this->db->or_like('users.role', $search);
            $this->db->or_like('teams.team_name', $search);
            $this->db->group_end();
        }
        
        // Apply role filter if provided
        if (!empty($role_filter)) {
            $this->db->where('users.role', $role_filter);
        }
        
        // Apply team filter if provided
        if (!empty($team_filter)) {
            $this->db->where('users.team_id', $team_filter);
        }
        
        $this->db->order_by('users.first_name', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }

    public function createUser($userData) {
        // Insert the user data into the database
        return $this->db->insert('users', $userData);
    }
}