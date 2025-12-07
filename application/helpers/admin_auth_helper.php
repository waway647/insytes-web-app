<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin Authentication Helper
 * 
 * Helper functions for admin authentication and authorization
 */

if (!function_exists('is_admin_logged_in')) {
    /**
     * Check if admin is logged in
     *
     * @return bool
     */
    function is_admin_logged_in()
    {
        $CI =& get_instance();
        
        $user_id = $CI->session->userdata('user_id');
        $role = $CI->session->userdata('role');
        $is_admin = $CI->session->userdata('is_admin');
        
        return ($user_id && $role === 'admin' && $is_admin === true);
    }
}

if (!function_exists('check_admin_access')) {
    /**
     * Check admin access and redirect if not authorized
     *
     * @param string $redirect_url URL to redirect if not admin
     * @return void
     */
    function check_admin_access($redirect_url = 'auth/login')
    {
        if (!is_admin_logged_in()) {
            $CI =& get_instance();
            redirect($redirect_url);
            exit;
        }
    }
}

if (!function_exists('get_admin_session_data')) {
    /**
     * Get admin session data
     *
     * @return object|null
     */
    function get_admin_session_data()
    {
        $CI =& get_instance();
        
        if (is_admin_logged_in()) {
            return (object) array(
                'user_id' => $CI->session->userdata('user_id'),
                'email' => $CI->session->userdata('email'),
                'role' => $CI->session->userdata('role'),
                'is_admin' => $CI->session->userdata('is_admin')
            );
        }
        
        return null;
    }
}

if (!function_exists('admin_logout')) {
    /**
     * Logout admin user
     *
     * @param string $redirect_url URL to redirect after logout
     * @return void
     */
    function admin_logout($redirect_url = 'auth/login')
    {
        $CI =& get_instance();
        $CI->session->sess_destroy();
        redirect($redirect_url);
    }
}