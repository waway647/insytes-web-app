<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ReviewController extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->library('session');
	}
	public function index()
	{
		$data['title'] = 'review';
		$data['main_content'] = 'clips/review';
		$this->load->view('layouts/main', $data);
	}
}
