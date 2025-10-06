<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PeopleController extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->library('session');
	}
	public function index()
	{
		$data['title'] = 'People';
		$data['main_content'] = 'team/people';
		$this->load->view('layouts/main', $data);
	}
}
