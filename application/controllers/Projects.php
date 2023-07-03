<?php
defined('BASEPATH') OR exit('No direct script access allowed');


require (APPPATH.'/libraries/REST_Controller.php');

class Projects extends REST_Controller
{

    public $userdetails;

    public function __construct()
    {
        parent::__construct();
        $this->load->model("user_model");
        $this->load->model("locations_model");
        $this->load->model("holidays_model");
        $this->load->model("projects_model");
        $this->load->library('Authorization_Token');   

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
                header("Access-Control-Allow-Methods: GET, POST, OPTIONS");         
    
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
                header("Access-Control-Allow-Headers:        {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    
            exit(0);
        }
        /* End - this block is for avoiding CROS error */

    }

    /* Create Project API */
    public function create_post()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role=='Admin' || $this->userdetails->Role=='Manager')
                {
                    $_POST = json_decode(file_get_contents("php://input"), true);

                    $this->form_validation->set_rules('ProjectName', 'Project Name', 'trim|required|max_length[50]');
                    $this->form_validation->set_rules('VendorID', 'Vendor ID', 'trim|required|max_length[50]');
                    $this->form_validation->set_rules('EndClient', 'End Client', 'trim|required|max_length[255]');
                    $this->form_validation->set_rules('ProjectCode', 'Project Code', 'trim|required|max_length[50]');
                    $this->form_validation->set_rules('Category', 'Category', 'trim|required|max_length[255]');
                    $this->form_validation->set_rules('ProjectType', 'Project Type', 'trim|required|max_length[255]');
                    $this->form_validation->set_rules('Manager', 'Manager', 'trim|required|numeric');
                    $this->form_validation->set_rules('Manager2', 'Second Manager', 'trim|required|numeric');

                    if ($this->form_validation->run() === false) {
                        $errors = $this->form_validation->error_array();
                        $errors['status'] = false;
                        $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }
                    
                    $projectdata = array();
                    $projectdata = $this->input->post();
                    
                    $data = $this->projects_model->create_project($projectdata); // Creating Project

                    if($data)
                    {
                        $message = array('message' => 'Project has been created.');
                        $message['status'] = true;
                        $this->response($message, REST_Controller::HTTP_CREATED);
                    }
                    else{ 
                        $message = array('message' => 'Something went wrong!.');
                        $message['status'] = false;
                        $this->response($message, REST_Controller::HTTP_OK);
                    }

                }
                else 
                {
                    $message = array('message' => 'This Role not allowed to add Project');
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
            return false;
        }
    }

    /* Assign Project to Employee API */
    public function assign_post()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role=='Admin' || $this->userdetails->Role=='Manager')
                {
                    $_POST = json_decode(file_get_contents("php://input"), true);

                    $this->form_validation->set_rules('EmployeeID', 'Employee', 'trim|required|numeric');
                    $this->form_validation->set_rules('ProjectID', 'Project', 'trim|required|numeric');
                    $this->form_validation->set_rules('AssignedDt', 'Assigned Date', 'trim|required');
                    $this->form_validation->set_rules('Role', 'Role', 'trim|required|max_length[50]');
                    #$this->form_validation->set_rules('ReportingTo', 'Reporting Manager', 'trim|required|max_length[50]');

                    if ($this->form_validation->run() === false) {
                        $errors = $this->form_validation->error_array();
                        $errors['status'] = false;
                        $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    // Checking duplicate, with same Employee and same Project mapped or not
                    $check = $this->projects_model->check_project_with_employee($this->input->post('EmployeeID'),$this->input->post('ProjectID'));
                    if(count($check)>=1){
                        $message = array('message' => 'This Project assigned to same employee');
                        $message['status'] = false;
                        $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    $getproject = $this->projects_model->get_project_by_id($this->input->post('ProjectID'));
                    

                    $projectdata = array();
                    $projectdata = $this->input->post();
                    $projectdata['ReportingTo'] = $getproject->Manager; // getting Project manager from Projects table 

                    $data = $this->projects_model->assign_project($projectdata); // Assigning Project to Employee

                    if($data)
                    {
                        $message = array('message' => 'Project has been assigned.');
                        $message['status'] = true;
                        $this->response($message, REST_Controller::HTTP_CREATED);
                    }
                    else{ 
                        $message = array('message' => 'Something went wrong!.');
                        $message['status'] = false;
                        $this->response($message, REST_Controller::HTTP_OK);
                    }

                }
                else 
                {
                    $message = array('message' => 'This Role not allowed to assign Project');
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
            return false;
        }
    }

    /* Update Project API */
    public function update_put()
    {
        $projectid = $this->uri->segment(3); // Project ID

        if(empty($projectid) || $projectid==0){
            $message = array('message' => 'Project ID required/can not be zero');
            $message['status'] = false;
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }

        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role=='Admin' || $this->userdetails->Role=='Manager')
                {
                    $_POST = json_decode(file_get_contents("php://input"), true);
                    
                    $this->form_validation->set_data($this->put());
                    $this->form_validation->set_rules('ProjectName', 'Project Name', 'trim|required|max_length[50]');
                    $this->form_validation->set_rules('VendorID', 'Vendor ID', 'trim|required|max_length[50]');
                    $this->form_validation->set_rules('EndClient', 'End Client', 'trim|required|max_length[255]');
                    $this->form_validation->set_rules('ProjectCode', 'Project Code', 'trim|required|max_length[50]');
                    $this->form_validation->set_rules('Category', 'Category', 'trim|required|max_length[255]');
                    $this->form_validation->set_rules('ProjectType', 'Project Type', 'trim|required|max_length[255]');
                    $this->form_validation->set_rules('Manager', 'Manager', 'trim|required|numeric');
                    $this->form_validation->set_rules('Manager2', 'Second Manager', 'trim|required|numeric');

                    if ($this->form_validation->run() === false) {
                        $errors = $this->form_validation->error_array();
                        $errors['status'] = false;
                        $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    $projectdata = array();
                    $projectdata = $this->put();

                    $data = $this->projects_model->update_project($projectdata,$projectid); // Updating Project by Project ID

                    if($data)
                    {
                        $message = array('message' => 'Project Updated successfully.');
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
                    $message = array('message' => 'This Role not allowed to update Project');
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
            return false;
        }
    }
}