<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MediaController extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->library('session');
	}
	public function index()
	{
		$data['title'] = 'Media';
		$data['main_content'] = 'studio/media/media';
		$this->load->view('layouts/studio', $data);
	}
}
