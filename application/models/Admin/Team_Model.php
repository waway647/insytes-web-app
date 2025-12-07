<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

class Team_Model extends CI_Model
{
    public function __construct()
    {
        $this->load->database();
        $this->load->helper('url');
        $this->load->library('session');
    }
    
    /**
     * Get all teams with filters
     * @param string $search
     * @param string $location_filter
     * @param string $manager_filter
     * @return array
     */
    public function getAllTeamsWithFilters(
        $search = '', 
        $location_filter = '', 
        $manager_filter = ''
    ) {
        $this->db->select('t.id, t.team_name, t.abbreviation, t.country, t.city, t.team_logo, t.invite_code, t.updated_at, t.created_by,
                          COUNT(u.id) as total_users, 
                          CONCAT(creator.first_name, " ", creator.last_name) as manager_name,
                          creator.id as manager_id');
        $this->db->from('teams t');
        $this->db->join('users u', 't.id = u.team_id', 'left');
        $this->db->join('users creator', 't.created_by = creator.id', 'left');
        
        // Search filter
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('t.team_name', $search);
            $this->db->or_like('t.country', $search);
            $this->db->or_like('t.city', $search);
            $this->db->group_end();
        }
        
        // Location filter (using city for consistency)
        if (!empty($location_filter)) {
            $this->db->like('t.city', $location_filter);
        }
        
        // Manager filter - Note: no manager field in current table structure
        // We'll skip this for now or use created_by
        
        $this->db->group_by('t.id');
        $this->db->order_by('t.updated_at', 'DESC');
        $query = $this->db->get();
        
        $teams = $query->result_array();
        
        $formatted_teams = [];
        foreach ($teams as $team) {
            $formatted_teams[] = [
                'id'              => $team['id'],
                'team_name'       => $team['team_name'],
                'abbreviation'    => $team['abbreviation'],
                'location'        => (!empty($team['city']) ? $team['city'] : '') . 
                                   (!empty($team['country']) ? (!empty($team['city']) ? ', ' : '') . $team['country'] : 'N/A'),
                'manager'         => $team['manager_name'] ?? 'No Manager',
                'manager_id'      => $team['manager_id'] ?? null,
                'logo_url'        => !empty($team['team_logo']) ? base_url($team['team_logo']) : null,
                'invite_code'     => $team['invite_code'],
                'total_users'     => $team['total_users'] ?? 0,
                'last_updated'    => $this->format_time_ago($team['updated_at'])
            ];
        }
        
        return $formatted_teams;
    }
    
    public function getTotalTeamCount()
    {
        return $this->db->count_all_results('teams');
    }
    
    public function createTeam($teamData)
    {
        // Parse location into city and country
        $location = $teamData['location'] ?? '';
        $city = '';
        $country = '';
        
        if (!empty($location)) {
            $location_parts = explode(',', $location);
            $city = trim($location_parts[0]);
            if (count($location_parts) > 1) {
                $country = trim($location_parts[1]);
            }
        }
        
        // Map frontend fields to database fields
        $dbData = [
            'team_name'       => $teamData['team_name'],
            'abbreviation'    => $teamData['abbreviation'],
            'country'         => $country,
            'city'            => $city,
            'team_logo'       => $teamData['logo_url'] ?? null,
            'invite_code'     => $this->generateInviteCode(),
            'created_by'      => $teamData['manager_id'] ?? $this->session->userdata('user_id') ?? 1,
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s')
        ];
        
        return $this->db->insert('teams', $dbData);
    }
    
    public function updateTeam($team_id, $teamData)
    {
        // Map frontend fields to database fields
        $dbData = [];
        
        if (isset($teamData['team_name'])) {
            $dbData['team_name'] = $teamData['team_name'];
        }
        if (isset($teamData['abbreviation'])) {
            $dbData['abbreviation'] = $teamData['abbreviation'];
        }
        if (isset($teamData['location'])) {
            // Split location into city and country if possible
            $location_parts = explode(',', $teamData['location']);
            $dbData['city'] = trim($location_parts[0]);
            if (count($location_parts) > 1) {
                $dbData['country'] = trim($location_parts[1]);
            }
        }
        if (isset($teamData['city'])) {
            $dbData['city'] = $teamData['city'];
        }
        if (isset($teamData['country'])) {
            $dbData['country'] = $teamData['country'];
        }
        if (isset($teamData['manager_id'])) {
            $dbData['created_by'] = $teamData['manager_id'];
        }
        if (isset($teamData['logo_url'])) {
            $dbData['team_logo'] = $teamData['logo_url'];
        }
        
        $dbData['updated_at'] = date('Y-m-d H:i:s');
        
        $this->db->where('id', $team_id);
        return $this->db->update('teams', $dbData);
    }
    
    public function deleteTeam($team_id)
    {
        $this->db->where('id', $team_id);
        return $this->db->delete('teams');
    }
    
    public function getTeamById($team_id)
    {
        $this->db->where('id', $team_id);
        $query = $this->db->get('teams');
        return $query->row_array();
    }
    
    /**
     * Get available managers (coaches/admins) for team assignment
     */
    public function getAvailableManagers()
    {
        // Get all coaches first
        $this->db->select('u.id, u.first_name, u.last_name, u.team_id, t.team_name');
        $this->db->from('users u');
        $this->db->join('teams t', 'u.id = t.created_by', 'left');
        $this->db->where('u.role', 'Coach');
        $this->db->order_by('u.first_name, u.last_name');
        
        $query = $this->db->get();
        $all_coaches = $query->result_array();
        
        $managers = [];
        foreach ($all_coaches as $coach) {
            // Include coach if they are:
            // 1. Managing a team (have created_by entry) OR
            // 2. Not part of any team (team_id is null or 0)
            $isManaging = !empty($coach['team_name']);
            $isUnassigned = empty($coach['team_id']) || $coach['team_id'] == 0;
            
            if ($isManaging || $isUnassigned) {
                $managers[] = [
                    'id' => $coach['id'],
                    'name' => trim($coach['first_name'] . ' ' . $coach['last_name']),
                    'display_text' => trim($coach['first_name'] . ' ' . $coach['last_name'])
                ];
            }
        }
        
        // Remove duplicates by id
        $unique_managers = [];
        $seen_ids = [];
        foreach ($managers as $manager) {
            if (!in_array($manager['id'], $seen_ids)) {
                $unique_managers[] = $manager;
                $seen_ids[] = $manager['id'];
            }
        }
        
        return $unique_managers;
    }
    
    private function generateInviteCode()
    {
        return substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'), 0, 16);
    }
    
    private function format_time_ago($datetime)
    {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'just now';
        if ($time < 3600) return floor($time/60) . ' min ago';
        if ($time < 86400) return floor($time/3600) . 'h ago';
        if ($time < 2592000) return floor($time/86400) . 'd ago';
        if ($time < 31104000) return floor($time/2592000) . 'mo ago';
        return floor($time/31104000) . 'y ago';
    }
}