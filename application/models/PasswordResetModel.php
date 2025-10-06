<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PasswordResetModel extends CI_Model {
	public function __construct() {
		$this->load->database();
	}
	public function insertCode($email, $code, $expires_at) {
        // delete old codes for this email
        $this->db->where('email', $email)->delete('password_resets');

        $data = [
            'email' => $email,
            'code' => $code,
            'expires_at' => $expires_at
        ];
        $this->db->insert('password_resets', $data);
    }

    public function verifyCode($email, $code) {
        $this->db->where('email', $email);
        $this->db->where('code', $code);
        $this->db->where('expires_at >=', date("Y-m-d H:i:s"));
        $query = $this->db->get('password_resets');
        return $query->num_rows() > 0; // return row if found, false if not
    }

    public function deleteByEmail($email) {
        $this->db->where('email', $email)->delete('password_resets');
    }
}
