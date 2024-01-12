<?php
defined('BASEPATH') OR exit('No direct script access allowed');


require (APPPATH.'/libraries/REST_Controller.php');

class Departments extends REST_Controller
{

    public $userdetails;

    public function __construct()
    {
        parent::__construct();
        $this->load->model("department_model");
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
                header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");         
    
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
                header("Access-Control-Allow-Headers:        {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    
            exit(0);
        }
        /* End - this block is for avoiding CROS error */

    }

    public function create_post()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role==1)  //1- admin
                {
                    $_POST = json_decode(file_get_contents("php://input"), true);

                    $this->form_validation->set_rules('DepartmentName', 'Department Name', 'trim|required|max_length[100]');

                    if ($this->form_validation->run() === false) 
                    {
                        $errors = $this->form_validation->error_array();
                        $errors['status'] = false;
                        $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    $deptdata = array();
                    $deptdata = $this->input->post();

                    $check = $this->department_model->check_dept_name($deptdata["DepartmentName"]);

                    if(count($check)>=1){
                        $message = array('message' => "Department Name is duplicate!");
                        $message['status'] = false;
                        $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    $data = $this->department_model->create_department($deptdata);

                    if($data)
                    {
                        $message = array('message' => 'Department created successfully.');
                        $message['status'] = true;
                        $this->response($message, REST_Controller::HTTP_CREATED);
                    }
                    else{ 
                        $message = array('message' => 'Something went wrong!.');
                        $message['status'] = false;
                        $this->response($message, REST_Controller::HTTP_OK);
                    }
                } else {
                    $message = array('message' => 'This Role not allowed to create Department');
                    $message['status'] = false;
                    $this->response($message,REST_Controller::HTTP_UNAUTHORIZED);
                }
                
            } else {
                $this->response($decodedToken,REST_Controller::HTTP_UNAUTHORIZED);
            }
        }
         else {
            $message = array('message' => 'Authentication failed');
            $message['status'] = false;
            $this->response($message, REST_Controller::HTTP_UNAUTHORIZED);
        }
    }

    public function update_put()
    {
        $deptid = $this->uri->segment(3);

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
                    $this->form_validation->set_rules('DepartmentName', 'Department Name', 'trim|required|max_length[100]');

                    if ($this->form_validation->run() === false) {
                        $errors = $this->form_validation->error_array();
                        $errors['status'] = false;
                        $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    $deptdata = array();
                    $deptdata = $this->put();

                    $check = $this->department_model->check_dept_name($deptdata["DepartmentName"],$deptid);

                    if(count($check)>=1){
                        $message = array('message' => "Department Name is duplicate!");
                        $message['status'] = false;
                        $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    $data = $this->department_model->update_department($deptdata["DepartmentName"],$deptid);

                    if($data)
                    {
                        $message = array('message' => 'Department updated successfully.');
                        $message['status'] = true;
                        $this->response($message, REST_Controller::HTTP_CREATED);
                    }
                    else{ 
                        $message = array('message' => 'Something went wrong!.');
                        $message['status'] = false;
                        $this->response($message, REST_Controller::HTTP_OK);
                    }
                }
                else {
                    $message = array('message' => 'This Role not allowed to modify Department details');
                    $message['status'] = false;
                    $this->response($message,REST_Controller::HTTP_UNAUTHORIZED);
                    return false;
                }
            }
            else {
                $this->response($decodedToken,REST_Controller::HTTP_UNAUTHORIZED);
            }
        }
        else {
            $message = array('message' => 'Authentication failed');
            $message['status'] = false;
            $this->response($message, REST_Controller::HTTP_UNAUTHORIZED);
            return false;
        }

    }

    public function index_delete()
    {
        $deptid = $this->uri->segment(3);

        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role==1)
                {
                    $check = $this->department_model->check_dept_allocation($deptid);

                    if(count($check)>=1){
                        $message = array('message' => "Sorry, can't delete. This Department mapped in some where");
                        $message['status'] = false;
                        $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    $data = $this->locations_model->delete_department($deptid);

                    if($data)
                    {
                        $message = array('message' => 'Department deleted successfully!.');
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
                    $message = array('message' => 'This Role not allowed to delete Department');
                    $message['status'] = false;
                    $this->response($message,REST_Controller::HTTP_UNAUTHORIZED);
                    return false;
                }
            }
            else
            {
                $this->response($decodedToken,REST_Controller::HTTP_UNAUTHORIZED);
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