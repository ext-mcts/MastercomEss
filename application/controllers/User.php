<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * User class.
 * 
 * @extends REST_Controller
 */
    require(APPPATH.'/libraries/REST_Controller.php');
    #use \Libraries\REST_Controller;

class User extends REST_Controller {

	/**
	 * __construct function.
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
        $this->load->library('Authorization_Token');
		$this->load->model('user_model');
		$this->load->model('accuredleaves_model');

		/* Start - this block is for avoiding CROS error */
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');    // cache for 1 day
        }
    
        // Access-Control headers are received during OPTIONS requests
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
                header("Access-Control-Allow-Methods: GET, POST, OPTIONS");         
    
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
                header("Access-Control-Allow-Headers:        {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    
            exit(0);
        }
        /* End - this block is for avoiding CROS error */
		
	}

	/**
	 * register function.
	 * 
	 * @access public
	 * @return void
	 */
	public function register_post() {

		// set validation rules
		$this->form_validation->set_rules('username', 'Username', 'trim|required|alpha_numeric|min_length[4]|is_unique[users.username]', array('is_unique' => 'This username already exists. Please choose another one.'));
		$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|is_unique[users.email]');
		$this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[6]');
		//$this->form_validation->set_rules('password_confirm', 'Confirm Password', 'trim|required|min_length[6]|matches[password]');
		
		if ($this->form_validation->run() === false) {
			
			// validation not ok, send validation errors to the view
            $this->response(['Validation rules violated'], REST_Controller::HTTP_OK);
			
		} else {
			
			// set variables from the form
			$username = $this->input->post('username');
			$email    = $this->input->post('email');
			$password = $this->input->post('password');
			
			if ($res = $this->user_model->create_user($username, $email, $password)) {
				
				// user creation ok
                $token_data['uid'] = $res; 
                $token_data['username'] = $username;
                $tokenData = $this->authorization_token->generateToken($token_data);
                $final = array();
                $final['access_token'] = $tokenData;
                $final['status'] = true;
                $final['uid'] = $res;
                $final['message'] = 'Thank you for registering your new account!';
                $final['note'] = 'You have successfully register. Please check your email inbox to confirm your email address.';

                $this->response($final, REST_Controller::HTTP_OK); 

			} else {
				
				// user creation failed, this should never happen
                $this->response(['There was a problem creating your new account. Please try again.'], REST_Controller::HTTP_OK);
			}
			
		}
		
	}
		
	/**
	 * login function.
	 * 
	 * @access public
	 * @return void
	 */
	public function login_post() {
		
		$_POST = json_decode(file_get_contents("php://input"), true);

		// set validation rules
		$this->form_validation->set_rules('username', 'Username', 'trim|required|max_length[50]');
		$this->form_validation->set_rules('password', 'Password', 'trim|required|max_length[50]');
		
		if ($this->form_validation->run() == false) {
			
			// validation not ok, send validation errors to the view
            $this->response(['Validation rules violated'], REST_Controller::HTTP_OK);

		} else {
			
			// set variables from the form
			$username = $this->input->post('username');
			$password = $this->input->post('password');
			
			if ($this->user_model->resolve_user_login($username, $password)) {
				
				$user_id = $this->user_model->get_user_id_from_username($username);
				$user    = $this->user_model->get_user($user_id);
				
				// set session user datas
				$_SESSION['EmployeeID']      = (int)$user->EmployeeID;
				$_SESSION['EmailName']     = (string)$user->EmailName;
				$_SESSION['Location']     = (int)$user->WorkLocation;
				$_SESSION['logged_in']    = (bool)true;
				$_SESSION['Role'] = (string)$user->Role;
				
				// user login ok
                $token_data['EmployeeID'] = $user_id;
                $token_data['EmailName'] = $user->EmailName; 
				$token_data['Role'] = $user->Role; 
                $tokenData = $this->authorization_token->generateToken($token_data);
                $final = array();
                $final['access_token'] = $tokenData;
                $final['status'] = true;
                $final['message'] = 'Login success!';
                $final['note'] = 'You are now logged in.';

                $this->response($final, REST_Controller::HTTP_OK); 
				
			} else {
				
				// login failed
                $this->response(['Wrong username or password.'], REST_Controller::HTTP_OK);
				
			}
			
		}
		
	}
	
	/**
	 * logout function.
	 * 
	 * @access public
	 * @return void
	 */
	public function logout_post() {

		if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
			
			// remove session datas
			foreach ($_SESSION as $key => $value) {
				unset($_SESSION[$key]);
			}
			
			// user logout ok
            $this->response(['Logout success!'], REST_Controller::HTTP_OK);
			
		} else {
			
			// there user was not logged in, we cannot logged him out,
			// redirect him to site root
			// redirect('/');
            $this->response(['There was a problem. Please try again.'], REST_Controller::HTTP_OK);	
		}
		
	}
	
	/* probation confirmation API */
	public function probationconfirm_post()
	{
		$_POST = json_decode(file_get_contents("php://input"), true);
		
		$this->form_validation->set_rules('EmployeeID', 'EmployeeID', 'trim|required|numeric');
		$this->form_validation->set_rules('probationdate', 'Probation Date', 'trim');

		if ($this->form_validation->run() === false) 
		{
			$errors = $this->form_validation->error_array();
			$errors['status'] = false;
			$this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
			return false;
		}

		$empdata = array();
        $empdata = $this->input->post();

		$data = $this->accuredleaves_model->CheckProbationLeaves($empdata);

		if($data)
		{
			$message = array('message' => 'Probation Confirmed successfully.');
			$message['status'] = true;
			$this->response($message, REST_Controller::HTTP_CREATED);
		}
		else{ 
			$message = array('message' => 'Something went wrong!.');
			$message['status'] = false;
			$this->response($message, REST_Controller::HTTP_OK);
		}
	}

	/* Leaves calculation at the time of joining API */
	public function joiningleavescalculation_post($empid)
	{
		$data = $this->accuredleaves_model->OnJoiningLeaves($empid);
	}

	/* updating leaves at the time of employee relieving */
	public function relieveemployee_post()
	{

		$_POST = json_decode(file_get_contents("php://input"), true);

		$this->form_validation->set_rules('relievingdate', 'Relieving Date', 'trim');
		$this->form_validation->set_rules('EmployeeID', 'EmployeeID', 'trim|required|numeric');

		if ($this->form_validation->run() === false) {
			$errors = $this->form_validation->error_array();
			$errors['status'] = false;
			$this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
			return false;
		}

		$empdata = array();
        $empdata = $this->input->post();

		$data = $this->user_model->updaterelievingdate($empdata);
	}

}
