<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard_Model extends CI_Model {

    public function __construct() {
        $this->load->database();
    }

    // KPIs: single row
    public function get_kpis() {
        $row = $this->db->get('view_dashboard_kpis')->row_array();
        return $row ? $row : array();
    }

    // Time series: users signups (last N days)
    public function get_user_signups_daily($days = 30) {
        $start_date = date('Y-m-d', strtotime('-' . intval($days) . ' days'));
        $sql = "SELECT day, signups
                FROM view_user_signups_daily
                WHERE day >= ?
                ORDER BY day";
        $query = $this->db->query($sql, array($start_date));
        return $query->result_array();
    }

    // Time series: logs per day
    public function get_logs_daily($days = 30) {
        $start_date = date('Y-m-d', strtotime('-' . intval($days) . ' days'));
        $sql = "SELECT day, logs_count
                FROM view_logs_daily
                WHERE day >= ?
                ORDER BY day";
        $query = $this->db->query($sql, array($start_date));
        return $query->result_array();
    }

    // Logs by category (optional)
    public function get_logs_daily_by_category($days = 30) {
        $start_date = date('Y-m-d', strtotime('-' . intval($days) . ' days'));
        $sql = "SELECT day, category, logs_count
                FROM view_logs_daily_by_category
                WHERE day >= ?
                ORDER BY day, category";
        $query = $this->db->query($sql, array($start_date));
        return $query->result_array();
    }

    // Recent users (limit)
    public function get_recent_users($limit = 10) {
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit((int)$limit);
        $query = $this->db->get('view_users_recent');
        return $query->result_array();
    }

    // Recent logs (limit)
    public function get_recent_logs($limit = 10) {
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit((int)$limit);
        $query = $this->db->get('view_logs_recent');
        return $query->result_array();
    }

    // Recent teams
    public function get_recent_teams($limit = 5) {
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit((int)$limit);
        $query = $this->db->get('view_teams_recent');
        return $query->result_array();
    }

    // Snapshots (safe limit binding)
    public function get_users_snapshot($limit = 10) {
        $sql = "SELECT * FROM view_users_snapshot ORDER BY id DESC LIMIT ?";
        $query = $this->db->query($sql, array((int)$limit));
        return $query->result_array();
    }

    public function get_teams_snapshot($limit = 10) {
        $sql = "SELECT * FROM view_teams_snapshot ORDER BY id DESC LIMIT ?";
        $query = $this->db->query($sql, array((int)$limit));
        return $query->result_array();
    }

    public function get_logs_snapshot($limit = 10) {
        $sql = "SELECT * FROM view_logs_snapshot ORDER BY id DESC LIMIT ?";
        $query = $this->db->query($sql, array((int)$limit));
        return $query->result_array();
    }

    // Role distribution
    public function get_role_distribution() {
        $this->db->order_by('cnt', 'DESC');
        $query = $this->db->get('view_role_distribution');
        return $query->result_array();
    }

    // Active teams
    public function get_active_teams() {
        $this->db->order_by('active_members_count', 'DESC');
        $query = $this->db->get('view_active_teams');
        return $query->result_array();
    }
}
