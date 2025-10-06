<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

class PasswordResetController extends CI_Controller {
	public function __construct() {
        parent::__construct();
        $this->load->helper(['url','string']);
        $this->load->library('session');
        $this->load->model('PasswordResetModel');
        $this->load->model('UserModel');
        $this->load->library('email'); // CI email library
		$this->load->database();
    }

	// STEP 1: Show form to enter email to send reset code
	public function show_password_reset_step1()
	{
		$this->load->view('auth/forgot_pass_step1');
	}

		public function send_code_to_email() {
			$email = $this->input->post('email', TRUE);

			// check if email exists in users
			$user = $this->UserModel->getByEmail($email);
			if (!$user) {
				$this->session->set_flashdata('error', 'Email not found.');
				redirect(site_url('auth/passwordresetcontroller/show_password_reset_step1'));
				return;
			}

			// generate 6-digit code
			$code = sprintf("%06d", mt_rand(0, 999999));

			// expire in 3 minutes
			$expires_at = date("Y-m-d H:i:s", strtotime("+3 minutes"));

			// save to password_resets
			$this->PasswordResetModel->insertCode($email, $code, $expires_at);

			// send email
			$this->email->from('insytes.web@gmail.com', 'Insytes');
			$this->email->to($email);
			$this->email->subject('Password Reset Code');
			$this->email->message("Your password reset code is: {$code}\nThis will expire in 3 minutes.");
			$this->email->send();

			// save email in session for next step
			$this->session->set_userdata('reset_email', $email);

			redirect(site_url('auth/passwordresetcontroller/show_password_reset_step2'));
		}

	// STEP 2: Show form to enter code
	public function show_password_reset_step2()
	{
		$this->load->view('auth/forgot_pass_step2');
	}

		public function verify_code() {
			$email = $this->session->userdata('reset_email');
			$code = $this->input->post('otp_code', TRUE);

			$valid = $this->PasswordResetModel->verifyCode($email, $code);

			if ($valid) {
				echo json_encode([
					'status' => 'success',
					'redirect' => site_url('auth/passwordresetcontroller/show_password_reset_step3')
				]);
			} else {
				echo json_encode([
					'status' => 'error',
					'message' => 'Invalid or expired code.',
				]);	
			}
		}

		public function resend_otp() {
			$email = $this->session->userdata('reset_email');

			if (!$email) {
				echo json_encode([
					'status' => 'error',
					'message' => 'Session expired. Please start over.',
					'redirect' => site_url('auth/passwordresetcontroller/show_password_reset_step1')
				]);
				return;
			}

			// Generate new OTP
			$newCode = sprintf("%06d", mt_rand(0, 999999));
			$expires_at = date("Y-m-d H:i:s", strtotime("+3 minutes"));

			// Update code in password_resets
			$this->db->where('email', $email);
			$this->db->update('password_resets', ['code' => $newCode, 'expires_at' => $expires_at]);

			// Send email directly
			$this->email->from('insytes.web@gmail.com', 'Insytes');
			$this->email->to($email);
			$this->email->subject('Password Reset Code');
			$this->email->message("Your password reset code is: {$newCode}\nThis will expire in 3 minutes.");
			$sent = $this->email->send();

			if ($sent) {
				echo json_encode([
					'status' => 'success',
					'message' => 'A new code has been sent to your email.'
				]);
			} else {
				echo json_encode([
					'status' => 'error',
					'message' => 'Failed to send email. Please try again later.'
				]);
			}
		}


	// STEP 3: Show form to enter new password
	public function show_password_reset_step3()
	{
		$this->load->view('auth/forgot_pass_step3');
	}

		public function reset_password() {
			$email = $this->session->userdata('reset_email');
			$password = $this->input->post('password', TRUE);
			$retype = $this->input->post('retype_password', TRUE);

			if ($password !== $retype) {
				$this->session->set_flashdata('error', 'Passwords do not match.');
				redirect(site_url('auth/passwordresetcontroller/show_password_reset_step3'));
				return;
			}

			// hash password
			$hashed = password_hash($password, PASSWORD_BCRYPT);

			// update user
			$this->UserModel->updatePassword($email, $hashed);

			// cleanup: delete reset codes
			$this->PasswordResetModel->deleteByEmail($email);

			$this->session->unset_userdata('reset_email');

			redirect(site_url('auth/passwordresetcontroller/show_password_reset_success'));
		}

	public function show_password_reset_success()
	{
		$this->load->view('auth/forgot_pass_success');
	}
}
