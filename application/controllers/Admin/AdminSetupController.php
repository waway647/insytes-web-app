<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin Setup Controller
 * 
 * This controller helps setup the initial admin user
 * IMPORTANT: Remove this file after creating your admin user for security
 */
class AdminSetupController extends CI_Controller {
	
	public function __construct()
	{
		parent::__construct();
		
		// Suppress deprecation warnings for this controller
		error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
		
		$this->load->database();
		$this->load->helper('url');
	}

	/**
	 * Create initial admin user
	 * Visit: http://localhost/github/insytes-web-app/index.php/Admin/AdminSetupController/create_admin
	 */
	public function create_admin()
	{
		// Check if admin already exists
		$this->db->where('role', 'admin');
		$admin_exists = $this->db->get('users')->num_rows();
		
		if ($admin_exists > 0) {
			echo "<h1>Admin user already exists!</h1>";
			echo "<p>An admin user has already been created. Delete this controller for security.</p>";
			echo "<a href='" . site_url('auth/logincontroller/show_login') . "'>Go to Login</a>";
			return;
		}

		// Admin user details - CHANGE THESE VALUES
		$admin_email = 'admin@insytes.com';
		$admin_password = 'admin123';  // Change this to a secure password
		$admin_first_name = 'Admin';
		$admin_last_name = 'User';
		
		// Hash the password
		$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
		
		// Create admin user
		$admin_data = array(
			'email' => $admin_email,
			'password' => $hashed_password,
			'role' => 'admin',
			'first_name' => $admin_first_name,
			'last_name' => $admin_last_name,
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s')
		);
		
		$this->db->insert('users', $admin_data);
		
		if ($this->db->affected_rows() > 0) {
			echo "<h1>Admin User Created Successfully!</h1>";
			echo "<p><strong>Email:</strong> " . $admin_email . "</p>";
			echo "<p><strong>Password:</strong> " . $admin_password . "</p>";
			echo "<p style='color: red;'><strong>IMPORTANT:</strong> Delete this controller file for security after use!</p>";
			echo "<a href='" . site_url('auth/logincontroller/show_login') . "'>Go to Login</a>";
		} else {
			echo "<h1>Error Creating Admin User</h1>";
			echo "<p>There was an error creating the admin user. Please check your database configuration.</p>";
		}
	}

	/**
	 * Remove existing admin user (for testing purposes)
	 * Visit: http://localhost/github/insytes-web-app/index.php/Admin/AdminSetupController/remove_admin
	 */
	public function remove_admin()
	{
		// Remove existing admin user
		$this->db->where('role', 'admin');
		$this->db->delete('users');
		
		if ($this->db->affected_rows() > 0) {
			echo "<h1>Admin User Removed!</h1>";
			echo "<p>The existing admin user has been removed.</p>";
			echo "<p>You can now create a new admin user with proper password requirements.</p>";
			echo "<a href='" . site_url('Admin/AdminUserController/create') . "'>Create New Admin User</a>";
		} else {
			echo "<h1>No Admin User Found</h1>";
			echo "<p>No admin user exists to remove.</p>";
			echo "<a href='" . site_url('Admin/AdminUserController/create') . "'>Create Admin User</a>";
		}
	}
	public function check_database()
	{
		// Check if users table exists
		if (!$this->db->table_exists('users')) {
			echo "<h1>Users table does not exist!</h1>";
			echo "<p>You need to create the users table first. Here's a sample SQL:</p>";
			echo "<pre>";
			echo "CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT NULL,
    team_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);";
			echo "</pre>";
			return;
		}

		// Show table structure
		$fields = $this->db->list_fields('users');
		echo "<h1>Users Table Structure:</h1>";
		echo "<ul>";
		foreach ($fields as $field) {
			echo "<li>" . $field . "</li>";
		}
		echo "</ul>";
		
		// Show existing users (without passwords)
		$users = $this->db->select('id, email, role, team_id, first_name, last_name, created_at')->get('users')->result();
		echo "<h2>Existing Users:</h2>";
		if (empty($users)) {
			echo "<p>No users found.</p>";
		} else {
			echo "<table border='1' cellpadding='5'>";
			echo "<tr><th>ID</th><th>Email</th><th>First Name</th><th>Last Name</th><th>Role</th><th>Team ID</th><th>Created At</th></tr>";
			foreach ($users as $user) {
				echo "<tr>";
				echo "<td>" . $user->id . "</td>";
				echo "<td>" . $user->email . "</td>";
				echo "<td>" . ($user->first_name ?: 'N/A') . "</td>";
				echo "<td>" . ($user->last_name ?: 'N/A') . "</td>";
				echo "<td>" . ($user->role ?: 'N/A') . "</td>";
				echo "<td>" . ($user->team_id ?: 'N/A') . "</td>";
				echo "<td>" . $user->created_at . "</td>";
				echo "</tr>";
			}
			echo "</table>";
		}
	}
}