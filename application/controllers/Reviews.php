<?php
defined('BASEPATH') OR exit('No direct script access allowed');


require (APPPATH.'/libraries/REST_Controller.php');

class Reviews extends REST_Controller
{

    public $userdetails;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('Authorization_Token'); 
        $this->load->model('reviews_model');

        $this->userdetails = decode_token($this->input->get_request_header('Authorization')); // here we are calling helper

        /* Start - this block is for avoiding CROS error */
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');    // cache for 1 day
        }
    
        // Access-Control headers are received during OPTIONS requests
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
                header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");         
    
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
                header("Access-Control-Allow-Headers:        {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    
            exit(0);
        }
        /* End - this block is for avoiding CROS error */
    }

    public function add_post()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                $_POST = json_decode(file_get_contents("php://input"), true);

                $this->form_validation->set_rules('Accomplishments', 'Accomplishments', 'trim|required|max_length[1000]');
                $this->form_validation->set_rules('AreasOfImprovement', 'Area of Improvement', 'trim|required|max_length[1000]');
                $this->form_validation->set_rules('CurrentSkillSet', 'Current Skill Set', 'trim|required|max_length[1000]');
                $this->form_validation->set_rules('NewInitiatives', 'New Initiatives', 'trim|required|max_length[1000]');
                $this->form_validation->set_rules('AddlResponsibilities', 'Additional Responsibilities', 'trim|required|max_length[1000]');
                
                if ($this->form_validation->run() === false) 
                {
                    $errors = $this->form_validation->error_array();
                    $errors['status'] = false;
                    $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                    return false;
                }

                $revdata = array();
                $revdata = $this->input->post();
                $revdata["EmployeeID"] = $this->userdetails->EmployeeID;
                $revdata["Manager"] = $this->userdetails->Manager;

                $data = $this->reviews_model->add_review($revdata);

                if($data)
                {
                    $message = array('message' => 'Review posted successfully.');
                    $message['status'] = true;
                    $this->response($message, REST_Controller::HTTP_OK);
                    return false;
                }
                else{ 
                    $message = array('message' => 'Something went wrong!.');
                    $message['status'] = false;
                    $this->response($message, REST_Controller::HTTP_OK);
                    return false;
                }
            }
            else 
            {
                $this->response($decodedToken);
            }
        }
        else
        {
            $message = array('message' => 'Authentication failed');
            $message['status'] = false;
            $this->response($message, REST_Controller::HTTP_UNAUTHORIZED);
        }
    }

    public function reply_put()
    {
        $revid = $this->uri->segment(3);

        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role==1)
                {
                    $_POST = json_decode(file_get_contents("php://input"), true);

                    $this->form_validation->set_data($this->put());
                    
                    $this->form_validation->set_rules('Review', 'Review', 'trim|required|max_length[1000]');

                    if ($this->form_validation->run() === false) {
                        $errors = $this->form_validation->error_array();
                        $errors['status'] = false;
                        $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    $revdata = array();
                    $revdata = $this->put();
                    $revdata['ReplyFor'] = $revid;

                    $data = $this->reviews_model->add_reply($revdata);

                    if($data)
                    {
                        $message = array('message' => 'Replied successfully.');
                        $message['status'] = true;
                        $this->response($message, REST_Controller::HTTP_OK);
                        return false;
                    }
                    else{ 
                        $message = array('message' => 'Something went wrong!.');
                        $message['status'] = false;
                        $this->response($message, REST_Controller::HTTP_OK);
                        return false;
                    }

                }
                else {
                    $message = array('message' => 'This Role not allowed to give reply for review');
                    $message['status'] = false;
                    $this->response($message,REST_Controller::HTTP_UNAUTHORIZED);
                    return false;
                }

            }
            else 
            {
                $this->response($decodedToken);
            }
        }
        else
        {
            $message = array('message' => 'Authentication failed');
            $message['status'] = false;
            $this->response($message, REST_Controller::HTTP_UNAUTHORIZED);
        }
    }

    public function annual_review_post()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                $_POST = json_decode(file_get_contents("php://input"), true);

                $this->form_validation->set_rules('EmployeeID', 'Employee', 'trim|required|numeric');
                $this->form_validation->set_rules('Review', 'Review', 'trim|required|max_length[1000]');

                if ($this->form_validation->run() === false) 
                {
                    $errors = $this->form_validation->error_array();
                    $errors['status'] = false;
                    $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                    return false;
                }

                $revdata = array();
                $revdata = $this->input->post();
                $revdata["Manager"] = $this->userdetails->EmployeeID;

                $data = $this->reviews_model->add_annual_review($revdata);
            }
            else 
            {
                $this->response($decodedToken);
            }
        }
        else
        {
            $message = array('message' => 'Authentication failed');
            $message['status'] = false;
            $this->response($message, REST_Controller::HTTP_UNAUTHORIZED);
        }
    }
}