<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class LogsController extends CI_Controller 
{
    public function __construct() 
    {
        parent::__construct();
        $this->load->model('Admin/Logs_Model');
        $this->load->model('Admin/User_Model');
        $this->load->library('session');
        $this->load->helper('url');
        
        // Check if user is admin
        if (!$this->session->userdata('user_id') || strtolower($this->session->userdata('role')) !== 'admin') {
            redirect('auth/login');
            return;
        }
    }

    /**
     * Display logs page with enhanced filtering
     */
    /* public function index()
    {
        $data = [
            'current_page' => 'logs',
            'title' => 'System Logs',
            'log_categories' => $this->Logs_Model::LOG_CATEGORIES
        ];
        
        // Load admin layout with logs view
        $this->load->view('partials/admin_header', $data);
        $this->load->view('partials/sidebar', $data);
        $this->load->view('admin/logs', $data);
        $this->load->view('partials/admin_footer');
    } */

    public function index()
	{
		// Load the users management page
		$data['title'] = 'System Logs';
		$data['main_content'] = 'admin/logs';
		
		$this->load->view('layouts/main', $data);
	}

    /**
     * AJAX endpoint to get logs with filtering and pagination
     */
    public function getLogs()
    {
        try {
            // Get filter parameters
            $search = $this->input->get('search', true) ?: '';
            $category = $this->input->get('category', true) ?: '';
            $role = $this->input->get('role', true) ?: '';
            $date_from = $this->input->get('date_from', true) ?: '';
            $date_to = $this->input->get('date_to', true) ?: '';
            $page = max(1, (int)$this->input->get('page', true) ?: 1);
            $per_page = min(100, max(10, (int)$this->input->get('per_page', true) ?: 25));
            
            $offset = ($page - 1) * $per_page;
            
            // Get logs and total count
            $logs = $this->Logs_Model->getAllLogsWithFilters(
                $search, $category, $role, $date_from, $date_to, $per_page, $offset
            );
            
            $total = $this->Logs_Model->getLogCount(
                $search, $category, $role, $date_from, $date_to
            );
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => [
                    'logs' => $logs,
                    'pagination' => [
                        'total' => $total,
                        'per_page' => $per_page,
                        'current_page' => $page,
                        'total_pages' => ceil($total / $per_page),
                        'showing_from' => $offset + 1,
                        'showing_to' => min($offset + $per_page, $total)
                    ]
                ]
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch logs: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get recent activity summary for dashboard widget
     */
    public function getRecentActivity()
    {
        try {
            $hours = $this->input->get('hours', true) ?: 24;
            $activity = $this->Logs_Model->getRecentActivitySummary($hours);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $activity
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch activity: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Export logs to CSV
     */
    public function exportLogs()
    {
        try {
            // Get filter parameters (same as getLogs)
            $search = $this->input->get('search', true) ?: '';
            $category = $this->input->get('category', true) ?: '';
            $user_id = $this->input->get('user_id', true) ?: '';
            $date_from = $this->input->get('date_from', true) ?: '';
            $date_to = $this->input->get('date_to', true) ?: '';
            
            // Get all logs without pagination for export
            $logs = $this->Logs_Model->getAllLogsWithFilters(
                $search, $category, $user_id, $date_from, $date_to, 10000, 0
            );
            
            // Set CSV headers
            $filename = 'insytes_logs_' . date('Y-m-d_H-i-s') . '.csv';
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($output, [
                'Timestamp', 'Category', 'Action', 'Message', 
                'User', 'IP Address', 'Metadata'
            ]);
            
            // CSV data
            foreach ($logs as $log) {
                fputcsv($output, [
                    $log['created_at'],
                    $log['category'],
                    $log['action'],
                    $log['message'],
                    $log['user_name'] . ' (' . $log['user_email'] . ')',
                    $log['ip_address'],
                    $log['metadata'] ? json_encode($log['metadata']) : ''
                ]);
            }
            
            fclose($output);
            
        } catch (Exception $e) {
            show_error('Failed to export logs: ' . $e->getMessage());
        }
    }

    /**
     * Clear old logs (admin function)
     */
    public function clearOldLogs()
    {
        try {
            $days = max(1, (int)$this->input->post('days') ?: 90);
            $cutoff_date = date('Y-m-d', strtotime("-{$days} days"));
            
            $this->db->where('DATE(created_at) <', $cutoff_date);
            $deleted = $this->db->delete('logs');
            
            if ($deleted) {
                // Log this admin action
                $this->Logs_Model->createLog(
                    'system_events',
                    'logs_cleared',
                    "Cleared logs older than {$days} days",
                    $this->session->userdata('user_id'),
                    ['days' => $days, 'cutoff_date' => $cutoff_date]
                );
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => "Successfully cleared logs older than {$days} days"
                ]);
            } else {
                throw new Exception('No logs were deleted');
            }
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Failed to clear logs: ' . $e->getMessage()
            ]);
        }
    }
}
?>
