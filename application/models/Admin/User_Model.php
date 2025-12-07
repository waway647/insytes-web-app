<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

class User_Model extends CI_Model
{
    public function __construct()
    {
        $this->load->database();
        $this->load->helper('url');
    }
    
    /**
     * Get all users with filters (EXCLUDES Admins by default)
     * @param string $search
     * @param string $role_filter
     * @param string $team_filter
     * @param bool   $include_admin  Set to TRUE if you want to include Admins
     * @return array
     */
    public function getAllUsersWithFilters(
        $search = '', 
        $role_filter = '', 
        $team_filter = '', 
        $include_admin = false
    ) {
        $this->db->select('u.id, u.email, u.first_name, u.last_name, u.role, u.updated_at, u.status, u.temp_password, u.team_id, t.team_name');
        $this->db->from('users u');
        $this->db->join('teams t', 'u.team_id = t.id', 'left');
        
        // EXCLUDE Admins by default
        if (!$include_admin) {
            $this->db->where('u.role !=', 'admin');
        }
        
        // Search filter
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('u.email', $search);
            $this->db->or_like('u.first_name', $search);
            $this->db->or_like('u.last_name', $search);
            $this->db->or_like('t.team_name', $search);
            $this->db->group_end();
        }
        
        // Role filter (still works for Coach/Player)
        if (!empty($role_filter)) {
            $this->db->where('u.role', $role_filter);
        }
        
        // Team filter
        if (!empty($team_filter)) {
            $this->db->where('t.team_name', $team_filter);
        }
        
        $this->db->order_by('u.updated_at', 'DESC');
        $query = $this->db->get();
        
        $users = $query->result_array(); // Use result_array() for consistency
        
        $formatted_users = [];
        foreach ($users as $user) {
            $formatted_users[] = [
                'id'           => $user['id'],
                'email'        => $user['email'],
                'first_name'   => $user['first_name'],
                'last_name'    => $user['last_name'],
                'team_name'    => $user['team_name'] ?? 'No Team',  // Changed from 'team' to 'team_name'
                'role'         => ucfirst($user['role']),
                'updated_at'   => $user['updated_at'],              // Raw updated_at field for JS formatting
                'status'       => $user['status'] ?? 'active',      // Add status field
                'temp_password'=> $user['temp_password'] ?? 0       // Add temp_password field
            ];
        }
        
        return $formatted_users;
    }

    // Optional: Separate method if you ever need ONLY admins
    public function getAdminsOnly()
    {
        return $this->getAllUsersWithFilters('', '', '', true); // force include admin
    }
    
    public function getTotalUserCount($include_admin = false)
    {
        if (!$include_admin) {
            $this->db->where('role !=', 'admin');
        }
        return $this->db->count_all_results('users');
    }
    
    public function getUserById($user_id)
    {
        $this->db->where('id', $user_id);
        $query = $this->db->get('users');
        return $query->row_array();
    }
    
    public function getAllTeams()
    {
        $this->db->select('id, team_name');
        $this->db->from('teams');
        $this->db->order_by('team_name', 'ASC');
        $query = $this->db->get();
        return $query->result_array();
    }
    
    public function createUser($userData)
    {
        return $this->db->insert('users', $userData);
    }
    
    public function updateUser($user_id, $userData)
    {
        $this->db->where('id', $user_id);
        return $this->db->update('users', $userData);
    }
    
    public function deleteUser($user_id)
    {
        $this->db->where('id', $user_id);
        // Optional: Prevent deleting the last admin?
        // $this->db->where('role !=', 'admin');
        return $this->db->delete('users');
    }
    
    /**
     * Check if email already exists in database
     */
    public function emailExists($email)
    {
        $this->db->where('email', $email);
        $query = $this->db->get('users');
        return $query->num_rows() > 0;
    }
}