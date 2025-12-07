<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * System Logging Helper
 * Provides easy methods to log various system events
 */
class LogHelper 
{
    private static $CI;
    
    private static function getCI() 
    {
        if (!self::$CI) {
            self::$CI =& get_instance();
            self::$CI->load->model('Admin/Logs_Model');
        }
        return self::$CI;
    }
    
    /**
     * Authentication Events
     */
    public static function logLogin($user_id, $user_email, $success = true, $reason = '') 
    {
        $CI = self::getCI();
        
        if ($success) {
            $CI->Logs_Model->createLog(
                'authentication',
                'user_login',
                "User {$user_email} logged in successfully",
                $user_id,
                ['email' => $user_email]
            );
        } else {
            $CI->Logs_Model->createLog(
                'authentication',
                'login_failed',
                "Failed login attempt for {$user_email}: {$reason}",
                null,
                ['email' => $user_email, 'reason' => $reason]
            );
        }
    }
    
    public static function logLogout($user_id, $user_email) 
    {
        $CI = self::getCI();
        $CI->Logs_Model->createLog(
            'authentication',
            'user_logout',
            "User {$user_email} logged out",
            $user_id,
            ['email' => $user_email],
            'info'
        );
    }
    
    public static function logPasswordChange($user_id, $user_email, $was_temp = false) 
    {
        $CI = self::getCI();
        $action = $was_temp ? 'temp_password_updated' : 'password_changed';
        $description = $was_temp 
            ? "User {$user_email} updated temporary password" 
            : "User {$user_email} changed password";
            
        $CI->Logs_Model->createLog(
            'authentication',
            $action,
            $description,
            $user_id,
            ['email' => $user_email, 'was_temp' => $was_temp],
            'info'
        );
    }
    
    /**
     * User Management Events
     */
    public static function logUserCreated($created_user_id, $created_email, $role, $creator_id, $creator_email) 
    {
        $CI = self::getCI();
        $CI->Logs_Model->createLog(
            'user_management',
            'user_created',
            "Admin {$creator_email} created user {$created_email} with role {$role}",
            $creator_id,
            [
                'created_user_id' => $created_user_id,
                'created_email' => $created_email,
                'role' => $role,
                'creator_email' => $creator_email
            ],
            'info'
        );
    }
    
    public static function logUserUpdated($updated_user_id, $updated_email, $updater_id, $updater_email) 
    {
        $CI = self::getCI();
        $CI->Logs_Model->createLog(
            'user_management',
            'user_updated',
            "Admin {$updater_email} updated user {$updated_email}",
            $updater_id,
            [
                'updated_user_id' => $updated_user_id,
                'updated_email' => $updated_email,
                'updater_email' => $updater_email
            ],
            'info'
        );
    }
    
    public static function logUserDeleted($deleted_user_id, $deleted_email, $deleter_id, $deleter_email) 
    {
        $CI = self::getCI();
        $CI->Logs_Model->createLog(
            'user_management',
            'user_deleted',
            "Admin {$deleter_email} deleted user {$deleted_email}",
            $deleter_id,
            [
                'deleted_user_id' => $deleted_user_id,
                'deleted_email' => $deleted_email,
                'deleter_email' => $deleter_email
            ],
            'warning'
        );
    }
    
    public static function logRoleChange($user_id, $user_email, $old_role, $new_role, $changer_id, $changer_email) 
    {
        $CI = self::getCI();
        $CI->Logs_Model->createLog(
            'user_management',
            'role_changed',
            "Admin {$changer_email} changed role of {$user_email} from {$old_role} to {$new_role}",
            $changer_id,
            [
                'target_user_id' => $user_id,
                'user_email' => $user_email,
                'old_role' => $old_role,
                'new_role' => $new_role
            ],
            'info'
        );
    }
    
    /**
     * Team Operations Events
     */
    public static function logTeamCreated($team_id, $team_name, $creator_id, $creator_email) 
    {
        $CI = self::getCI();
        $CI->Logs_Model->createLog(
            'team_operations',
            'team_created',
            "User {$creator_email} created team '{$team_name}'",
            $creator_id,
            [
                'team_id' => $team_id,
                'team_name' => $team_name
            ],
            'info'
        );
    }
    
    public static function logTeamUpdated($team_id, $team_name, $updater_id, $updater_email) 
    {
        $CI = self::getCI();
        $CI->Logs_Model->createLog(
            'team_operations',
            'team_updated',
            "User {$updater_email} updated team '{$team_name}'",
            $updater_id,
            [
                'team_id' => $team_id,
                'team_name' => $team_name
            ],
            'info'
        );
    }
    
    public static function logTeamDeleted($team_id, $team_name, $deleter_id, $deleter_email) 
    {
        $CI = self::getCI();
        $CI->Logs_Model->createLog(
            'team_operations',
            'team_deleted',
            "User {$deleter_email} deleted team '{$team_name}'",
            $deleter_id,
            [
                'team_id' => $team_id,
                'team_name' => $team_name
            ],
            'warning'
        );
    }
    
    public static function logTeamJoined($team_id, $team_name, $user_id, $user_email, $via_invite = false) 
    {
        $CI = self::getCI();
        $method = $via_invite ? 'via invite link' : 'directly';
        $CI->Logs_Model->createLog(
            'team_operations',
            'team_joined',
            "User {$user_email} joined team '{$team_name}' {$method}",
            $user_id,
            [
                'team_id' => $team_id,
                'team_name' => $team_name,
                'via_invite' => $via_invite
            ],
            'info'
        );
    }
    
    /**
     * Match Activities Events
     */
    public static function logMatchCreated($match_id, $match_name, $creator_id, $creator_email) 
    {
        $CI = self::getCI();
        $CI->Logs_Model->createLog(
            'match_activities',
            'match_created',
            "User {$creator_email} created match '{$match_name}'",
            $creator_id,
            [
                'match_id' => $match_id,
                'match_name' => $match_name
            ],
            'info'
        );
    }
    
    public static function logMatchTaggingStarted($match_id, $match_name, $user_id, $user_email) 
    {
        $CI = self::getCI();
        $CI->Logs_Model->createLog(
            'match_activities',
            'tagging_started',
            "User {$user_email} started tagging for match '{$match_name}'",
            $user_id,
            [
                'match_id' => $match_id,
                'match_name' => $match_name
            ],
            'info'
        );
    }
    
    public static function logMatchTaggingEnded($match_id, $match_name, $user_id, $user_email, $duration_minutes = null) 
    {
        $CI = self::getCI();
        $duration_text = $duration_minutes ? " (duration: {$duration_minutes} minutes)" : "";
        $CI->Logs_Model->createLog(
            'match_activities',
            'tagging_ended',
            "User {$user_email} finished tagging for match '{$match_name}'{$duration_text}",
            $user_id,
            [
                'match_id' => $match_id,
                'match_name' => $match_name,
                'duration_minutes' => $duration_minutes
            ],
            'info'
        );
    }
    
    /**
     * Security Events
     */
    public static function logSuspiciousActivity($description, $user_id = null, $metadata = null) 
    {
        $CI = self::getCI();
        $CI->Logs_Model->createLog(
            'security_events',
            'suspicious_activity',
            $description,
            $user_id,
            $metadata,
            'warning'
        );
    }
    
    public static function logMultipleFailedLogins($email, $attempt_count, $ip_address) 
    {
        $CI = self::getCI();
        $CI->Logs_Model->createLog(
            'security_events',
            'multiple_failed_logins',
            "Multiple failed login attempts ({$attempt_count}) for {$email} from {$ip_address}",
            null,
            [
                'email' => $email,
                'attempt_count' => $attempt_count,
                'ip_address' => $ip_address
            ],
            'error'
        );
    }
    
    /**
     * System Events
     */
    public static function logSystemError($error_message, $context = null, $severity = 'error') 
    {
        $CI = self::getCI();
        $CI->Logs_Model->createLog(
            'system_events',
            'system_error',
            "System error: {$error_message}",
            null,
            $context,
            $severity
        );
    }
    
    public static function logConfigurationChange($setting, $old_value, $new_value, $user_id, $user_email) 
    {
        $CI = self::getCI();
        $CI->Logs_Model->createLog(
            'system_events',
            'config_changed',
            "Admin {$user_email} changed setting '{$setting}' from '{$old_value}' to '{$new_value}'",
            $user_id,
            [
                'setting' => $setting,
                'old_value' => $old_value,
                'new_value' => $new_value
            ],
            'info'
        );
    }
    
    /**
     * Data Operations Events
     */
    public static function logDataExport($export_type, $record_count, $user_id, $user_email) 
    {
        $CI = self::getCI();
        $CI->Logs_Model->createLog(
            'data_operations',
            'data_exported',
            "User {$user_email} exported {$record_count} {$export_type} records",
            $user_id,
            [
                'export_type' => $export_type,
                'record_count' => $record_count
            ],
            'info'
        );
    }
    
    public static function logDataImport($import_type, $record_count, $user_id, $user_email, $success = true) 
    {
        $CI = self::getCI();
        $status = $success ? 'successfully imported' : 'failed to import';
        $CI->Logs_Model->createLog(
            'data_operations',
            'data_imported',
            "User {$user_email} {$status} {$record_count} {$import_type} records",
            $user_id,
            [
                'import_type' => $import_type,
                'record_count' => $record_count,
                'success' => $success
            ],
            $success ? 'info' : 'error'
        );
    }
}
?>