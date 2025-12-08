<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class DashboardController extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->helper('admin_auth');
        $this->load->helper('url');
        $this->load->library('session');

        // Correct model load (lowercase 'model' in method name)
        $this->load->model('Admin/Dashboard_Model');

        // check_admin_access(); // enable if you want admin guard
    }

    public function adminDashboard()
    {
        // Fetch data from model
        $data = array();
        $kpis = $this->Dashboard_Model->get_kpis();

        // Ensure kpis has sensible defaults so view doesn't break
        $defaults = [
            'total_users' => 0,
            'active_users' => 0,
            'new_users_30d' => 0,
            'total_teams' => 0,
            'new_teams_30d' => 0,
            'events_24h' => 0,
            'errors_warnings_24h' => 0,
            'last_refreshed' => date('Y-m-d H:i:s')
        ];

        $data['kpis'] = array_merge($defaults, $kpis ?: []);

        // Time series / snapshots
        $data['user_signups'] = $this->Dashboard_Model->get_user_signups_daily(30);
        $data['logs_daily'] = $this->Dashboard_Model->get_logs_daily(30);
        $data['recent_users'] = $this->Dashboard_Model->get_recent_users(10);
        $data['recent_logs'] = $this->Dashboard_Model->get_recent_logs(10);
        $data['recent_teams'] = $this->Dashboard_Model->get_recent_teams(5);
        $data['users_snapshot'] = $this->Dashboard_Model->get_users_snapshot(10);
        $data['teams_snapshot'] = $this->Dashboard_Model->get_teams_snapshot(10);
        $data['logs_snapshot'] = $this->Dashboard_Model->get_logs_snapshot(10);
        $data['role_distribution'] = $this->Dashboard_Model->get_role_distribution();
        $data['active_teams'] = $this->Dashboard_Model->get_active_teams();

        $data['title'] = 'Dashboard';
        $data['main_content'] = 'admin/dashboard';

		//var_dump($data);

        $this->load->view('layouts/main', $data);
    }
}
