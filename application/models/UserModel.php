<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class UserModel extends CI_Model {
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
}
