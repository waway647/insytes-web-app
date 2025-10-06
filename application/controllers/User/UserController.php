<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

class UserController extends CI_Controller {
	public function __construct() {
        parent::__construct();
        // Load the URL helper to enable base_url()
        $this->load->helper('url');
		$this->load->library('session');
		$this->load->model('UserModel/NewUser_Model');
    }

	public function newUserSetup()
	{
		$this->load->view('user/new_user_setup');
	}
}
