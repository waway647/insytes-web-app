<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class DeliverController extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->library('session');
	}
	public function index()
	{
		$data['title'] = 'Delivery';
		$data['main_content'] = 'studio/deliver/deliver';
		$this->load->view('layouts/studio', $data);
	}
}
