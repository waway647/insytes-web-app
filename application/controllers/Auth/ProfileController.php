<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ProfileController extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->helper('url');
        $this->load->library('form_validation');
        $this->load->library('session');
        $this->load->model('Admin/Logs_Model');
        $this->load->helper('log_helper');
        $this->load->database();
    }

    /**
     * Show the update credentials page
     */
    public function change_password() {
        if (!$this->session->userdata('user_id')) {
            redirect('auth/login');
            return;
        }

        $this->load->view('auth/update_credentials');
    }

    /**
     * Check email availability (AJAX endpoint)
     */
    public function check_email_availability() {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $email = isset($input['email']) ? trim($input['email']) : '';

            if (empty($email)) {
                echo json_encode(['unique' => false, 'message' => 'Email is required']);
                return;
            }

            $this->db->where('email', $email);
            $query = $this->db->get('users');

            $isUnique = ($query->num_rows() == 0);

            echo json_encode([
                'unique' => $isUnique,
                'message' => $isUnique ? 'Email is available' : 'Email is already registered'
            ]);

        } catch (Exception $e) {
            echo json_encode(['unique' => false, 'message' => 'Database error']);
        }
    }

    /**
     * Test endpoint
     */
    public function test() {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success', 
            'message' => 'ProfileController is working', 
            'session' => $this->session->userdata()
        ]);
    }

    /**
     * Test update endpoint
     */
    public function test_update() {
        header('Content-Type: application/json');

        try {
            echo json_encode([
                'success' => true,
                'message' => 'Test update endpoint working',
                'post_data' => $this->input->post(),
                'session' => $this->session->userdata()
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update user credentials (email + password)
     */
    public function update_profile() {
        header('Content-Type: application/json');

        try {
            // Ensure user is logged in
            $user_id = $this->session->userdata('user_id');

            if (!$user_id) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Session expired. Please login again.'
                ]);
                return;
            }

            // Get POST inputs
            $email = trim($this->input->post('email'));
            $password = trim($this->input->post('password'));
            $retype_password = trim($this->input->post('retype_password'));

            // Validate required fields
            if (empty($email) || empty($password) || empty($retype_password)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'All fields are required.'
                ]);
                return;
            }

            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Please enter a valid email address.'
                ]);
                return;
            }

            // Check if email is used by other users
            $this->db->where('email', $email);
            $this->db->where('id !=', $user_id);
            $email_exists = $this->db->get('users');

            if ($email_exists->num_rows() > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'This email is already registered to another account.'
                ]);
                return;
            }

            // Check password match
            if ($password !== $retype_password) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Passwords do not match.'
                ]);
                return;
            }

            // Password strength rules
            if (strlen($password) < 8) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Password must be at least 8 characters long.'
                ]);
                return;
            }

            if (!preg_match('/[A-Z]/', $password) ||
                !preg_match('/[a-z]/', $password) ||
                !preg_match('/[0-9]/', $password)) {

                echo json_encode([
                    'success' => false,
                    'message' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.'
                ]);
                return;
            }

            // Hash new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Update DB
            $update_data = [
                'email' => $email,
                'password' => $hashed_password,
                'first_login' => 0,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->db->where('id', $user_id);
            $updated = $this->db->update('users', $update_data);

            if (!$updated) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update profile. Please try again.'
                ]);
                return;
            }

            // Update session
            $this->session->set_userdata('email', $email);
            
            // Log profile update
            LogHelper::logPasswordChange(
                $user_id,
                $email,
                $user_id,
                $email
            );

            // Determine redirect URL
            $role = strtolower($this->session->userdata('role'));
            $redirect_url = site_url('dashboard');

            if ($role === 'admin') {
                $redirect_url = site_url('admin/dashboard');
            } elseif ($role === 'coach' || $role === 'player') {
                $redirect_url = site_url('team/dashboard');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully!',
                'redirect_url' => $redirect_url
            ]);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ]);
        }
    }
}
