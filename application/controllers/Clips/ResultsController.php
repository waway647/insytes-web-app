<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ResultsController extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->library('session');
	}
	public function index()
	{
		$data['title'] = 'results';
		$data['main_content'] = 'clips/results';
		$this->load->view('layouts/main', $data);
	}
}
