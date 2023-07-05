<?php
defined('BASEPATH') OR exit('No direct script access allowed');


require (APPPATH.'/libraries/REST_Controller.php');

class AccuredLeaves extends REST_Controller
{

    public $userdetails;

    public function __construct()
    {
        parent::__construct();
        $this->load->model("user_model");
        $this->load->library('Authorization_Token');
        $this->load->model("accuredleaves_model");

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
                if($this->userdetails->Role=='Admin')
                {
                    $this->form_validation->set_rules('Cycle', 'Cycle', 'trim|required|max_length[50]');
                    $this->form_validation->set_rules('LeavesConsiderInProbation', 'Leaves Consider In Probation', 'trim|required|max_length[5]');
                    $this->form_validation->set_rules('ProbationPeriod', 'Probation Time Period', 'trim|required|max_length[5]');
                    $this->form_validation->set_rules('LeavesCarryForward', 'Leaves Carry Forward', 'trim|required|max_length[10]');
                    $this->form_validation->set_rules('LeavesCycleYear', 'Select Leaves Cycle Year Start Month', 'trim|required|numeric');
                    $this->form_validation->set_rules('CycleType', 'Select Leaves Cycle Type', 'trim|required|max_length[40]');

                    if ($this->form_validation->run() === false) 
                    {
                        $errors = $this->form_validation->error_array();
                        $errors['status'] = false;
                        $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }
            
                    $data = $this->accuredleaves_model->add_accuredleaves($this->input->post());

                    if($data)
                    {
                        $message = array('message' => 'Leaves added successfully.');
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
                    $message = array('message' => 'This Role not allowed to add Accured Leaves');
                    $message['status'] = false;
                    $this->response($message,REST_Controller::HTTP_UNAUTHORIZED);
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

    public function changecycle_post()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role=='Admin')
                {
                    $this->form_validation->set_rules('Cycle', 'Cycle', 'trim|required|max_length[50]'); // Monthly or Quartarly or Half yearly or Yearly
                    //$this->form_validation->set_rules('Cycletype', 'Cycle Type', 'trim|required|max_length[50]');// future or current
                    //$this->form_validation->set_rules('LeavesConsiderInProbation', 'Leaves Consider In Probation', 'trim|required|max_length[5]'); // Yes or No
                    //$this->form_validation->set_rules('ProbationPeriod', 'Probation Time Period', 'trim|required|max_length[5]'); // in months (Ex: 1,2,3,4..)
                    //$this->form_validation->set_rules('LeavesCarryForward', 'Leaves Carry Forward', 'trim|required|max_length[10]'); // Yes or No
                    $this->form_validation->set_rules('LeavesCycleYear', 'Select Leaves Cycle Year Start Month', 'trim|required|numeric'); // in months (Ex: 1,2,3,4..)

                    if ($this->form_validation->run() === false) 
                    {
                        $errors = $this->form_validation->error_array();
                        $errors['status'] = false;
                        $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    $data = $this->accuredleaves_model->ChangePolicyRightNow($this->input->post());

                    if($data)
                    {
                        $message = array('message' => 'Leaves cycle changed successfully.');
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
                    $message = array('message' => 'This Role not allowed to Change leave cycle');
                    $message['status'] = false;
                    $this->response($message,REST_Controller::HTTP_UNAUTHORIZED);
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

    public function runscheduleforleaves_post()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role=='Admin')
                {
                    $data = $this->accuredleaves_model->RunScheduleForLeaveCalculation();

                    if($data)
                    {
                        $message = array('message' => 'Schedule for Leaves Running successfully.');
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
                    $message = array('message' => 'This Role not allowed to Run Schedule for Leaves');
                    $message['status'] = false;
                    $this->response($message,REST_Controller::HTTP_UNAUTHORIZED);
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

    public function calculate_get()
    {
        $data = $this->accuredleaves_model->calculateCycle();
    }
}