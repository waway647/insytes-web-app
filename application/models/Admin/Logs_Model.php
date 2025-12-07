<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Logs_Model extends CI_Model
{
    public function __construct()
    {
        $this->load->database();
        $this->load->helper('url');
    }
    
    /**
     * Log categories for professional audit trail
     */
    const LOG_CATEGORIES = [
        'authentication' => 'Authentication',
        'user_management' => 'User Management', 
        'team_operations' => 'Team Operations',
        'match_activities' => 'Match Activities',
        'system_events' => 'System Events',
        'security_events' => 'Security Events',
        'data_operations' => 'Data Operations'
    ];
    
    /**
     * Get all logs with comprehensive filtering
     */
    public function getAllLogsWithFilters($search = '', $category_filter = '', $role_filter = '', $date_from = '', $date_to = '', $limit = 50, $offset = 0)
    {
        $this->db->select('l.*, u.email as user_email, u.first_name, u.last_name, u.role as user_role');
        $this->db->from('logs l');
        $this->db->join('users u', 'l.user_id = u.id', 'left');
        
        // Search filter
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('l.action', $search);
            $this->db->or_like('l.message', $search);
            $this->db->or_like('u.email', $search);
            $this->db->or_like('CONCAT(u.first_name, " ", u.last_name)', $search);
            $this->db->group_end();
        }
        
        // Category filter
        if (!empty($category_filter)) {
            $this->db->where('l.category', $category_filter);
        }
        
        // Role filter
        if (!empty($role_filter)) {
            $this->db->where('LOWER(u.role)', strtolower($role_filter));
        }
        
        // Date range filter
        if (!empty($date_from)) {
            $this->db->where('DATE(l.created_at) >=', $date_from);
        }
        if (!empty($date_to)) {
            $this->db->where('DATE(l.created_at) <=', $date_to);
        }
        
        $this->db->order_by('l.created_at', 'DESC');
        $this->db->limit($limit, $offset);
        
        $query = $this->db->get();
        $logs = $query->result_array();
        
        // Format logs for display
        $formatted_logs = [];
        foreach ($logs as $log) {
            $formatted_logs[] = [
                'id' => $log['id'],
                'category' => self::LOG_CATEGORIES[$log['category']] ?? ucfirst($log['category']),
                'action' => $log['action'],
                'message' => $log['message'],
                'user_name' => $log['user_email'] ? $log['first_name'] . ' ' . $log['last_name'] : 'System',
                'user_email' => $log['user_email'] ?? 'system@insytes.com',
                'user_agent' => $log['user_agent'],
                'metadata' => $log['metadata'] ? json_decode($log['metadata'], true) : null,
                'created_at' => $log['created_at'],
                'formatted_time' => $this->format_log_time($log['created_at'])
            ];
        }
        
        return $formatted_logs;
    }
    
    /**
     * Get log count for pagination
     */
    public function getLogCount($search = '', $category_filter = '', $role_filter = '', $date_from = '', $date_to = '')
    {
        $this->db->from('logs l');
        $this->db->join('users u', 'l.user_id = u.id', 'left');
        
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('l.action', $search);
            $this->db->or_like('l.message', $search);
            $this->db->or_like('u.email', $search);
            $this->db->or_like('CONCAT(u.first_name, " ", u.last_name)', $search);
            $this->db->group_end();
        }
        
        if (!empty($category_filter)) {
            $this->db->where('l.category', $category_filter);
        }
        
        if (!empty($role_filter)) {
            $this->db->where('LOWER(u.role)', strtolower($role_filter));
        }
        
        if (!empty($date_from)) {
            $this->db->where('DATE(l.created_at) >=', $date_from);
        }
        if (!empty($date_to)) {
            $this->db->where('DATE(l.created_at) <=', $date_to);
        }
        
        return $this->db->count_all_results();
    }
    
    /**
     * Create a new log entry with professional metadata
     */
    public function createLog($category, $action, $message, $user_id = null, $metadata = null)
    {
        $log_data = [
            'category' => $category,
            'action' => $action,
            'message' => $message,
            'user_id' => $user_id,
            'user_agent' => $this->input->user_agent(),
            'metadata' => $metadata ? json_encode($metadata) : null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->insert('logs', $log_data);
    }
    
    /**
     * Get logs by category for dashboard widgets
     */
    public function getLogsByCategory($category, $limit = 10)
    {
        $this->db->select('l.*, u.email as user_email, u.first_name, u.last_name');
        $this->db->from('logs l');
        $this->db->join('users u', 'l.user_id = u.id', 'left');
        $this->db->where('l.category', $category);
        $this->db->order_by('l.created_at', 'DESC');
        $this->db->limit($limit);
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Get recent activity summary
     */
    public function getRecentActivitySummary($hours = 24)
    {
        $this->db->select('category, COUNT(*) as count');
        $this->db->from('logs');
        $this->db->where('created_at >=', date('Y-m-d H:i:s', strtotime("-{$hours} hours")));
        $this->db->group_by('category');
        $this->db->order_by('count', 'DESC');
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Format log timestamp for display
     */
    private function format_log_time($datetime)
    {
        if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
            return 'N/A';
        }
        
        $date = new DateTime($datetime);
        $now = new DateTime();
        
        // If today, show time
        if ($date->format('Y-m-d') === $now->format('Y-m-d')) {
            return 'Today ' . $date->format('g:i A');
        }
        
        // If yesterday
        $yesterday = clone $now;
        $yesterday->sub(new DateInterval('P1D'));
        if ($date->format('Y-m-d') === $yesterday->format('Y-m-d')) {
            return 'Yesterday ' . $date->format('g:i A');
        }
        
        // If this year
        if ($date->format('Y') === $now->format('Y')) {
            return $date->format('M j, g:i A');
        }
        
        // Different year
        return $date->format('M j, Y g:i A');
    }
    
    /**
     * Static helper method to log events from anywhere in the application
     */
    public static function log($category, $action, $message, $user_id = null, $metadata = null)
    {
        $CI = &get_instance();
        $CI->load->model('Admin/Logs_Model');
        return $CI->Logs_Model->createLog($category, $action, $message, $user_id, $metadata);
    }
}
?>
