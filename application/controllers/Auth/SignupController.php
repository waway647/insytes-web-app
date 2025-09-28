<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SignupController extends CI_Controller {
	public function index()
	{
		$this->load->view('welcome_message');
	}
}
