<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

class GoogleController extends CI_Controller {

    public $benchmark;
    public $hooks;
    public $config;
    public $log;
    public $utf8;
    public $uri;
    public $router;
    public $output;
    public $security;
    public $input;
    public $lang;
    public $google;

    public function __construct() {
        parent::__construct();
        $this->load->library('Google'); // Load the Google library
        $this->load->helper('url');
        $this->load->library('session');
        $this->load->model('usermodel');
    }

    // Google login page
    public function index() {
        $loginUrl = $this->google->getLoginUrl();
        redirect($loginUrl);  // Redirect the user to Google login page
    }

    // Callback function to handle Google response
    public function callback() {
        if (isset($_GET['code'])) {
            try {
                // 1. Get access token
                $accessToken = $this->google->authenticate($_GET['code']);
                $this->google->getClient()->setAccessToken($accessToken);

                // 2. Get user information
                $googleService = new Google_Service_Oauth2($this->google->getClient());
                $userInfo = $googleService->userinfo->get();

                // 3. 
                $user_data = $this->usermodel->verify_user_from_google($userInfo);

                if (isset($user_data['id'])) {
                    // User exists → login and redirect to dashboard
                    $this->session->set_userdata(array(
                        'user_id'      => $user_data['id'],
                        'user_email'   => $user_data['email'],
                        'google_id'    => $user_data['google_id']
                    ));
                    redirect(site_url('dashboard'));
                } else {
                    // User does not exist → save Google info in session for signup step
                    $this->session->set_userdata(array(
                        'google_email' => $userInfo->email,
                        'google_id'    => $userInfo->id
                    ));
                    redirect(site_url('auth/signupcontroller/show_google_signup_last_step'));
                }

            } catch (Exception $e) {
                // Handle authentication or token error
                log_message('error', 'Google Auth Error: ' . $e->getMessage());
                $this->session->set_flashdata('error', 'Google sign-in failed. Please try again.');
                redirect(base_url('auth/logincontroller/show_login')); 
            }
        } else {
            // User cancelled the login process
            $this->session->set_flashdata('error', 'Google sign-in cancelled.');
            redirect(base_url('auth/logincontroller/show_login'));
        }
    }

    public function handle_new_user() {
        $google_email = $this->session->userdata('google_email');
        $google_id = $this->session->userdata('google_id');
        $first_name = $this->input->post('firstname');
        $last_name = $this->input->post('lastname');

        $user_data = array(
            'email' => $google_email,
            'google_id' => $google_id,
            'first_name' => $first_name,
            'last_name' => $last_name
        );
        
        $user_data = $this->usermodel->create_new_user_from_google($user_data);

        if($user_data) {
            // user created
            redirect(site_url('auth/signupcontroller/show_signup_success'));
        }
    }
}
