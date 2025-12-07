<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ProfileController extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->helper('url');
        $this->load->library('form_validation');
        $this->load->library('session');
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
            // Check if user is logged in
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

            // Get current user data to check team status
            $this->db->where('id', $user_id);
            $current_user = $this->db->get('users')->row();
            
            // Determine if user should be marked as active
            // Only mark as active if they have a team (completed onboarding) or are admin
            $new_status = 'new'; // Default to new
            if (!empty($current_user->team_id) || strtolower($current_user->role) === 'admin') {
                $new_status = 'active';
            }

            // Update DB - clear temp_password and set appropriate status
            $update_data = [
                'email' => $email,
                'password' => $hashed_password,
                'temp_password' => 0,  // Mark as no longer using temp credentials
                'status' => $new_status,  // Set status based on onboarding completion
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

            // Log password change
            LogHelper::logPasswordChange($user_id, $email, true); // true = was temp password

            // Update session - clear temp_login flag
            $this->session->set_userdata('email', $email);
            $this->session->unset_userdata('temp_login');

            // Get updated user data to check status and team_id
            $this->db->where('id', $user_id);
            $updated_user = $this->db->get('users')->row();

            // Determine redirect URL based on user status and role
            $role = strtolower($this->session->userdata('role'));
            
            // Check if user still needs onboarding after profile update
            if ($updated_user->status === 'new' && !empty($updated_user->role)) {
                // User has role but still new - redirect to role-specific onboarding
                if ($role === 'coach') {
                    if (empty($updated_user->team_id)) {
                        // Coach without team - can create or join team
                        $redirect_url = site_url('User/NewUserController/userCoach_step1');
                        $message = 'Profile updated! Now choose to create or join a team.';
                    } else {
                        // Coach with team - setup team details
                        $redirect_url = site_url('User/NewUserController/userCoach_step2');
                        $message = 'Profile updated! Complete your team setup.';
                    }
                } else if ($role === 'player') {
                    // Player needs to join a team
                    $redirect_url = site_url('User/NewUserController/userPlayer_step1');
                    $message = 'Profile updated! Now join your team.';
                } else {
                    // Other roles - redirect to general new user setup
                    $redirect_url = site_url('User/NewUserController/newUser');
                    $message = 'Profile updated! Complete your profile setup.';
                }
            } else if ($updated_user->status === 'new' && empty($updated_user->role)) {
                // No role assigned - choose role
                $redirect_url = site_url('User/NewUserController/newUser');
                $message = 'Profile updated! Choose your role.';
            } else {
                // User is active or has completed onboarding - go to dashboard
                if ($role === 'admin') {
                    $redirect_url = site_url('admin/dashboard');
                    $message = 'Profile updated successfully!';
                } elseif ($role === 'coach' || $role === 'player') {
                    $redirect_url = site_url('team/dashboard');
                    $message = 'Profile updated successfully!';
                } else {
                    $redirect_url = site_url('dashboard');
                    $message = 'Profile updated successfully!';
                }
            }

            echo json_encode([
                'success' => true,
                'message' => $message,
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
