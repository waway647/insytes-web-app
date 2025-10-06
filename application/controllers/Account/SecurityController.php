<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

class SecurityController extends CI_Controller {
	public function index()
	{
		$data['title'] = 'Account';
		$data['main_content'] = 'account/security';
		$this->load->view('layouts/main', $data);
	}
}
