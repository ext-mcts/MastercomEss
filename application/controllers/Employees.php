<?php
defined('BASEPATH') OR exit('No direct script access allowed');


require (APPPATH.'/libraries/REST_Controller.php');

class Employees extends REST_Controller
{

    public $userdetails;

    public function __construct()
    {
        parent::__construct();
        $this->load->model("user_model");
        $this->load->model("locations_model");
        $this->load->library('Authorization_Token');
        #$this->load->library('MY_Form_validation'); 
        
        $this->userdetails = decode_token($this->input->get_request_header('Authorization')); // here we are calling helper

        /* Start - this block is for avoiding CROS error */
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header("Access-Control-Allow-Origin: *");
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


    /* Employees List API */
    public function index_get()
    {
        $_GET['Page'] = $_GET['Page'] ?? 1;
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role==1 || $this->userdetails->Role==4)  // 1- admin
                {
                    $filterdata = array();

                    if($this->input->get("EmployeeID")) $filterdata['EmployeeID']=$this->input->get("EmployeeID");
                    if($this->input->get("Email")) $filterdata['EmailName']=$this->input->get("Email");
                    if($this->input->get("Department")) $filterdata['Department']=$this->input->get("Department");
                    $filterdata['Page'] = $_GET['Page'];

                    $data = $this->user_model->get_all_users($filterdata); // getting all employees
                    if(count($data)>=1)
                    {
                        $message = array('page' => $_GET['Page'],
                                        'total_rows' => count($data),
                                        'results' => $data,);
                        $message['status'] = true;
                        $this->response($message, REST_Controller::HTTP_OK);
                    }
                    else{ 
                        $message = array('message' => 'Something went wrong!.');
                        $message['status'] = false;
                        $this->response($message, REST_Controller::HTTP_OK);
                    }
                } else {
                    $message = array('message' => 'This Role not allowed to View list of employees');
                    $message['status'] = false;
                    $this->response($message,REST_Controller::HTTP_UNAUTHORIZED);
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
        }
    }


    /* Employee creation API */
    public function create_post()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role==1 || $this->userdetails->Role==4)  //1- admin
                {
                    $_POST = json_decode(file_get_contents("php://input"), true);

                    $this->form_validation->set_rules('FirstName', 'Firstname', 'trim|required|alpha_numeric|max_length[50]');
                    $this->form_validation->set_rules('LastName', 'Lastname', 'trim|required|alpha_numeric|max_length[50]');
                    $this->form_validation->set_rules('EmailName', 'Email', 'trim|required|max_length[50]');
                    $this->form_validation->set_rules('Password', 'Password', 'trim|required|max_length[50]');
                    $this->form_validation->set_rules('Role', 'Role', 'trim|required|numeric|max_length[3]');
                    $this->form_validation->set_rules('WorkLocation', 'Work Location', 'trim|numeric|max_length[3]');
                    //$this->form_validation->set_rules('Department', 'Departmant', 'trim|required|numeric|max_length[3]');
                    $this->form_validation->set_rules('Designation', 'Designation', 'trim|required|numeric|max_length[3]');
                    $this->form_validation->set_rules('Phone1', 'Phone Number1', 'trim|required|max_length[50]');
                    $this->form_validation->set_rules('PANNumber', 'PAN Number', 'trim|required|max_length[10]');
                    $this->form_validation->set_rules('BankName', 'Bank Name', 'trim|required|numeric|max_length[3]');
                    $this->form_validation->set_rules('BankAccNumber', 'Bank Account Number', 'trim|required|max_length[16]');
                    $this->form_validation->set_rules('Manager', 'Reporting Manager', 'trim|required|numeric|max_length[3]');
                    $this->form_validation->set_rules('Grade', 'Grade', 'trim|required|numeric|max_length[3]');
                    $this->form_validation->set_rules('DOB', 'Date Of Birth', 'trim|required|max_length[50]');
                    $this->form_validation->set_rules('JoinDate', 'Date Of Joining', 'trim|required|max_length[50]');
                    $this->form_validation->set_rules('PFAccount', 'PF Account Number', 'trim|required|max_length[50]');
                    $this->form_validation->set_rules('Aadhar', 'Aadhar Number', 'trim|required|numeric|max_length[12]');
                    $this->form_validation->set_rules('Passport', 'Passport Number', 'trim|required');
                    
                    $this->form_validation->set_rules('Address', 'Address', 'trim|max_length[200]');
                    $this->form_validation->set_rules('FathersName', 'Father Name', 'trim|max_length[50]');
                    $this->form_validation->set_rules('TempAddress', 'Temparary Addredd', 'trim|max_length[255]');
                    $this->form_validation->set_rules('Phone2', 'Phone Number2', 'trim|max_length[50]');
                    $this->form_validation->set_rules('AppLetterRef', 'Appointment Letter Ref', 'trim|max_length[50]');
                    $this->form_validation->set_rules('BranchDetails', 'Branch Details', 'trim|max_length[50]');
                    $this->form_validation->set_rules('JobRole', 'Job Role', 'trim|max_length[255]');
                    $this->form_validation->set_rules('Level', 'Level', 'trim|max_length[255]');
                    $this->form_validation->set_rules('Vertical', 'Vertical', 'trim|max_length[255]');
                    $this->form_validation->set_rules('AltEmailID', 'Alternate Email ID', 'trim|max_length[255]');
                    $this->form_validation->set_rules('Technology', 'Technology', 'trim|max_length[255]');
                    $this->form_validation->set_rules('Language', 'Language', 'trim|max_length[255]');
                    $this->form_validation->set_rules('Project', 'Project', 'trim|numeric');

                    if ($this->form_validation->run() === false) 
                    {
                        $errors = $this->form_validation->error_array();
                        $errors['status'] = false;
                        $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    // Validating Email, actually we can do it by is_unique, but because of dots in database table name, we can't do that
                    // if($this->input->post('EmailName'))
                    // {
                    //     $email_check = $this->user_model->check_email($this->input->post('EmailName'));
                    //     if(count((array)$email_check)>=1){
                    //         $message = array('message' => 'Email already exist');
                    //         $message['status'] = false;
                    //         $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                    //         return false;
                    //     }
                    // }

                    //validating Location
                    if($this->input->post('WorkLocation'))
                    {
                        $loc_check = $this->locations_model->get_location_by_id($this->input->post('WorkLocation'));
                        
                        if(count($loc_check)==0){
                            $message = array('message' => 'Location not exist');
                            $message['status'] = false;
                            $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                            return false;
                        }
                    }

                    //validating Grades
                    if($this->input->post('Grade'))
                    {
                        $garde_check = $this->user_model->check_grade($this->input->post('Grade'));
                        if(count($garde_check)==0){
                            $message = array('message' => 'Grade not exist');
                            $message['status'] = false;
                            $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                            return false;
                        }
                    }

                    // checking duplicacy for PAN, Bank Account Number, AAdhar, Passport, PF Account Number
                    if($this->input->post('PANNumber') || $this->input->post('Aadhar') || $this->input->post('Passport') ||
                    $this->input->post('BankAccNumber') || $this->input->post('PFAccount'))
                    {
                        if($this->input->post('PANNumber')) {
                            $col = "PAN"; $post = $this->input->post('PANNumber');
                            $check = $this->user_model->check_emp_details($col,$post);
                            if(count($check)>=1){
                                $message = array('message' => "$col Number is duplicate!");
                                $message['status'] = false;
                                $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                                return false;
                            }
                        }
                        if($this->input->post('Aadhar')) {
                            $col = "AADHAR"; $post = $this->input->post('Aadhar');
                            $check = $this->user_model->check_emp_details($col,$post);
                            if(count($check)>=1){
                                $message = array('message' => "$col Number is duplicate!");
                                $message['status'] = false;
                                $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                                return false;
                            }
                        }
                        if($this->input->post('Passport')) { 
                            $col = "PASSPORT"; $post = $this->input->post('Passport');
                            $check = $this->user_model->check_emp_details($col,$post);
                            if(count($check)>=1){
                                $message = array('message' => "$col Number is duplicate!");
                                $message['status'] = false;
                                $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                                return false;
                            }
                        }
                        if($this->input->post('BankAccNumber')) { 
                            $col = "BANKACCNUMBER"; $post = $this->input->post('BankAccNumber');
                            $check = $this->user_model->check_emp_details($col,$post);
                            if(count($check)>=1){
                                $message = array('message' => "$col Number is duplicate!");
                                $message['status'] = false;
                                $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                                return false;
                            }
                        }
                        if($this->input->post('PFAccount')) { 
                            $col = "PFNUMBER"; $post = $this->input->post('PFAccount');
                            $check = $this->user_model->check_emp_details($col,$post);
                            if(count($check)>=1){
                                $message = array('message' => "$col Number is duplicate!");
                                $message['status'] = false;
                                $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                                return false;
                            }
                        }

                    }
                    $empdata = array();
                    $empdata = $this->input->post();
                    $empdata['AltEmailID'] = $this->input->post('AltEmailID') ?? "";
                    $empdata['Technology'] = $this->input->post('Technology') ?? "";
                    $empdata['Language'] = $this->input->post('Language') ?? "";
                    $empdata['Address'] = $this->input->post('Address') ?? "";
                    $empdata['FathersName'] = $this->input->post('FathersName') ?? "";
                    $empdata['TempAddress'] = $this->input->post('TempAddress') ?? "";
                    $empdata['Phone2'] = $this->input->post('Phone2') ?? "";
                    $empdata['AppLetterRef'] = $this->input->post('AppLetterRef') ?? "";
                    $empdata['BranchDetails'] = $this->input->post('BranchDetails') ?? "";
                    $empdata['DOB'] = $this->input->post('DOB') ?? "";
                    $empdata['Stream'] = $this->input->post('Stream') ?? "";
                    $empdata['JobRole'] = $this->input->post('JobRole') ?? "";
                    $empdata['Level'] = $this->input->post('Level') ?? "";
                    $empdata['Vertical'] = $this->input->post('Vertical') ?? "";
                    $empdata['PFAccount'] = $this->input->post('PFAccount') ?? "";

                    $data = $this->user_model->create_user($empdata);// Inserting Employee

                    if($data)
                    {
                        $message = array('message' => 'Employee created successfully.');
                        $message['status'] = true;
                        $this->response($message, REST_Controller::HTTP_CREATED);
                    }
                    else{ 
                        $message = array('message' => 'Something went wrong!.');
                        $message['status'] = false;
                        $this->response($message, REST_Controller::HTTP_OK);
                    }
                } else {
                    $message = array('message' => 'This Role not allowed to create Employee');
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

    /* Update Employee API */
    public function update_put()
    {
        $empid = $this->uri->segment(3); // Employee ID

        if(!is_numeric($empid) || empty($empid) || $empid==0){
            $message = array('message' => 'Employee ID not numeric/empty/too lengthy');
            $message['status'] = false;
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }

        $check = $this->user_model->get_user($empid);
        
        if(count((array)$check)==0){
            $message = array('message' => 'Employee not exist');
            $message['status'] = false;
            $this->response($message, REST_Controller::HTTP_OK);
            return false;
        }

        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role==1 || $this->userdetails->Role==4){
                    
                    $_POST = json_decode(file_get_contents("php://input"), true);

                    $this->form_validation->set_data($this->put());
                    $this->form_validation->set_rules('FirstName', 'First Name', 'trim|required|alpha_numeric|max_length[50]');
                    $this->form_validation->set_rules('LastName', 'Last Name', 'trim|required|alpha_numeric|max_length[50]');
                    $this->form_validation->set_rules('EmailName', 'Email', 'trim|required|max_length[50]');
                    $this->form_validation->set_rules('Role', 'Role', 'trim|required|numeric|max_length[3]');
                    $this->form_validation->set_rules('WorkLocation', 'Work Location', 'trim|numeric|required|max_length[3]');
                    //$this->form_validation->set_rules('Department', 'Departmant', 'trim|required|numeric|max_length[3]');
                    $this->form_validation->set_rules('Designation', 'Designation', 'trim|required|numeric|max_length[3]');
                    $this->form_validation->set_rules('Phone1', 'Phone Number1', 'trim|required|max_length[50]');
                    $this->form_validation->set_rules('PANNumber', 'PAN Number', 'trim|required|max_length[10]');
                    $this->form_validation->set_rules('BankName', 'Bank Name', 'trim|required|numeric|max_length[3]');
                    $this->form_validation->set_rules('BankAccNumber', 'Bank Account Number', 'trim|required|max_length[16]');
                    $this->form_validation->set_rules('Manager', 'Reporting Manager', 'trim|required|numeric|max_length[3]');
                    $this->form_validation->set_rules('Grade', 'Grade', 'trim|required|numeric|max_length[3]');
                    $this->form_validation->set_rules('JoinDate', 'Date Of Joining', 'trim|required|max_length[50]');
                    $this->form_validation->set_rules('PFAccount', 'PF Account Number', 'trim|required|max_length[50]');
                    $this->form_validation->set_rules('DOB', 'Date Of Birth', 'trim|required|max_length[50]');
                    $this->form_validation->set_rules('Aadhar', 'Aadhar Number', 'trim|required|numeric|max_length[12]');
                    $this->form_validation->set_rules('Passport', 'Passport Number', 'trim|required');
                    $this->form_validation->set_rules('Password', 'Password', 'trim|max_length[50]');
                    $this->form_validation->set_rules('Address', 'Address', 'trim|max_length[200]');
                    $this->form_validation->set_rules('FathersName', 'Father Name', 'trim|max_length[50]');
                    $this->form_validation->set_rules('TempAddress', 'Temparary Addredd', 'trim|max_length[255]');
                    $this->form_validation->set_rules('Phone2', 'Phone Number2', 'trim|max_length[50]');
                    $this->form_validation->set_rules('AppLetterRef', 'Appointment Letter Ref', 'trim|max_length[50]');
                    $this->form_validation->set_rules('BranchDetails', 'Branch Details', 'trim|max_length[50]');
                    $this->form_validation->set_rules('JobRole', 'Job Role', 'trim|max_length[255]');
                    $this->form_validation->set_rules('Level', 'Level', 'trim|max_length[255]');
                    $this->form_validation->set_rules('Vertical', 'Vertical', 'trim|max_length[255]');
                    $this->form_validation->set_rules('AltEmailID', 'Alternate Email ID', 'trim|max_length[255]');
                    $this->form_validation->set_rules('Technology', 'Technology', 'trim|max_length[255]');
                    $this->form_validation->set_rules('Language', 'Language', 'trim|max_length[255]');
                    $this->form_validation->set_rules('Project', 'Project', 'trim|numeric');

                    if ($this->form_validation->run() === false) {
                        $errors = $this->form_validation->error_array();
                        $errors['status'] = false;
                        $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }
                    
                    if($this->put('WorkLocation'))
                    {
                        $loc_check = $this->locations_model->get_location_by_id($this->put('WorkLocation'));
                        
                        if(count($loc_check)==0){
                            $message = array('message' => 'Location not exist');
                            $message['status'] = false;
                            $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                            return false;
                        }
                    }

                    //validating Grades
                    if($this->put('Grade'))
                    {
                        $garde_check = $this->user_model->check_grade($this->put('Grade'));
                        if(count($garde_check)==0){
                            $message = array('message' => 'Grade not exist');
                            $message['status'] = false;
                            $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                            return false;
                        }
                    }

                    // checking duplicacy for PAN, Bank Account Number, AAdhar, Passport, PF Account Number
                    if($this->put('PANNumber') || $this->put('Aadhar') || $this->put('Passport') ||
                    $this->put('BankAccNumber') || $this->put('PFAccount'))
                    {
                        if($this->input->post('PANNumber')) {
                            $col = "PAN"; $post = $this->input->post('PANNumber');
                            $check = $this->user_model->check_emp_details($col,$post,$empid);
                            if(count($check)>=1){
                                $message = array('message' => "$col Number is duplicate!");
                                $message['status'] = false;
                                $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                                return false;
                            }
                        }
                        if($this->input->post('Aadhar')) {
                            $col = "AADHAR"; $post = $this->input->post('Aadhar');
                            $check = $this->user_model->check_emp_details($col,$post,$empid);
                            if(count($check)>=1){
                                $message = array('message' => "$col Number is duplicate!");
                                $message['status'] = false;
                                $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                                return false;
                            }
                        }
                        if($this->input->post('Passport')) { 
                            $col = "PASSPORT"; $post = $this->input->post('Passport');
                            $check = $this->user_model->check_emp_details($col,$post,$empid);
                            if(count($check)>=1){
                                $message = array('message' => "$col Number is duplicate!");
                                $message['status'] = false;
                                $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                                return false;
                            }
                        }
                        if($this->input->post('BankAccNumber')) { 
                            $col = "BANKACCNUMBER"; $post = $this->input->post('BankAccNumber');
                            $check = $this->user_model->check_emp_details($col,$post,$empid);
                            if(count($check)>=1){
                                $message = array('message' => "$col Number is duplicate!");
                                $message['status'] = false;
                                $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                                return false;
                            }
                        }
                        if($this->input->post('PFAccount')) { 
                            $col = "PFNUMBER"; $post = $this->input->post('PFAccount');
                            $check = $this->user_model->check_emp_details($col,$post,$empid);
                            if(count($check)>=1){
                                $message = array('message' => "$col Number is duplicate!");
                                $message['status'] = false;
                                $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                                return false;
                            }
                        }
                    }

                    $empdata = array();
                    $empdata = $this->put();

                    if($this->put('Password'))  $empdata['Password'] = $this->put('Password');

                    if($this->put('AltEmailID'))  $empdata['AltEmailID'] = $this->put('AltEmailID');
                    if($this->put('Technology'))  $empdata['Technology'] = $this->put('Technology');
                    if($this->put('Language'))  $empdata['Language'] = $this->put('Language');
                    if($this->put('Address'))  $empdata['Address'] = $this->put('Address');
                    if($this->put('FathersName'))  $empdata['FathersName'] = $this->put('FathersName');
                    if($this->put('TempAddress'))  $empdata['TempAddress'] = $this->put('TempAddress');
                    if($this->put('Phone2'))  $empdata['Phone2'] = $this->put('Phone2');
                    if($this->put('AppLetterRef'))  $empdata['AppLetterRef'] = $this->put('AppLetterRef');
                    if($this->put('BranchDetails'))  $empdata['BranchDetails'] = $this->put('BranchDetails');
                    if($this->put('DOB'))  $empdata['DOB'] = $this->put('DOB');
                    if($this->put('Stream'))  $empdata['Stream'] = $this->put('Stream');
                    if($this->put('JobRole'))  $empdata['JobRole'] = $this->put('JobRole');
                    if($this->put('Level'))  $empdata['Level'] = $this->put('Level');
                    if($this->put('Vertical'))  $empdata['Vertical'] = $this->put('Vertical');
                    if($this->put('PFAccount'))  $empdata['PFAccount'] = $this->put('PFAccount');

                    $data = $this->user_model->update_user($empdata,$empid); //updating Employee details
                    if($data)
                    {
                        $message = array('message' => 'Employee Updated successfully.');
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
                    $message = array('message' => 'This Role not allowed to modify Employee details');
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

    /* Employee details view API */
    public function view_get()
    {
        $empid = $this->uri->segment(3); // Employee ID

        if(!is_numeric($empid) || empty($empid) || $empid==0){
            $message = array('message' => 'Employee ID not numeric/empty/too lengthy');
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
                if($this->userdetails->Role==1 || $this->userdetails->Role==4)
                {
                    $data = $this->user_model->get_user($empid); // Getting Employee details with ID

                    if($data)
                    {
                        $data->Role = intval($data->Role);
                        $data->Designation = intval($data->Designation);
                        $data->Grade = intval($data->Grade);
                        $data->Manager = intval($data->Manager);
                        $data->Project = intval($data->Project);
                        $data->WorkLocation = intval($data->WorkLocation);
                        $data->BankName = intval($data->BankName);

                        $message = array('results' => $data);
                        
                        $message['status'] = true;
                        $this->response($message, REST_Controller::HTTP_OK);
                    }
                    else{ 
                        $message = array('message' => 'Something went wrong!.');
                        $message['status'] = false;
                        $this->response($message, REST_Controller::HTTP_OK);
                    }
                }
                else
                {
                    $message = array('message' => 'This Role not allowed to view Employee details');
                    $message['status'] = false;
                    $this->response($message,REST_Controller::HTTP_UNAUTHORIZED);
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
        }
    }
    
    public function probation_pending_employees_get()
    {
        $_GET['Page'] = $_GET['Page'] ?? 1;
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role==1 || $this->userdetails->Role==4)
                {
                    $filterdata['Page'] = $_GET['Page'];

                    $data = $this->user_model->get_in_probation_emp_details($filterdata); // getting all employees
                    if(count($data)>=1)
                    {
                        $message = array('page' => $_GET['Page'],
                                        'total_rows' => count($data),
                                        'results' => $data);
                        $message['status'] = true;
                        $this->response($message, REST_Controller::HTTP_OK);
                    }
                    else{ 
                        $message = array('message' => 'Something went wrong!.');
                        $message['status'] = false;
                        $this->response($message, REST_Controller::HTTP_OK);
                    }
                }
                else
                {
                    $message = array('message' => 'This Role not allowed to view Employee details');
                    $message['status'] = false;
                    $this->response($message,REST_Controller::HTTP_UNAUTHORIZED);
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
        }

    }

    public function resignation_post()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $_POST = json_decode(file_get_contents("php://input"), true);

            $this->form_validation->set_rules('Reason', 'Resignation Reason', 'trim|required|max_length[500]');
            $this->form_validation->set_rules('ExpectedDt', 'Expected Date', 'trim|required|max_length[50]');

            if ($this->form_validation->run() === false) 
            {
                $errors = $this->form_validation->error_array();
                $errors['status'] = false;
                $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                return false;
            }

            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                $resdata = array();
                $resdata = $this->input->post();
                $resdata['EmployeeID'] = $this->userdetails->EmployeeID;

                $data = $this->user_model->do_resign($resdata);

                if($data)
                {
                    $config = Array(        
                        'protocol' => 'smtp',
                        'smtp_host' => 'smtp.mailhostbox.com',
                        'smtp_port' => 587,
                        'smtp_user' => 'autoreply@mastercom.co.in',
                        'smtp_pass' => SMTP_PASS,
                        'smtp_timeout' => '4',
                        'mailtype'  => 'html', 
                        'charset'   => 'iso-8859-1'
                    );

                    $empdet = $this->user_model->get_user($this->userdetails->Manager);

                    $this->load->library('email', $config);
                    $this->email->set_newline("\r\n"); 
                    $getconfigmail = $this->user_model->get_config_email(); 
                    $from_email = $getconfigmail[0]->ConfigEmail;
                    $this->email->from($from_email, 'Mastercom - Resignation!'); 
                    // $this->email->to('aharshavardhan04@gmail.com');
                    $this->email->to('suhasrlawate1999@gmail.com');
                    //$this->email->to(trim($empdet->EmailName).'@mastercom.co.in',trim($this->userdetails->EmailName).'@mastercom.co.in');
                    //$this->email->cc($list);
                    $this->email->subject('Resignation - Details');
                    $message = '<html><body>';
                    $message .= '<h3>Dear '.$empdet->FirstName.',</h3>'; 
                    $message .= '<p> You got Resignation Request from '.$this->userdetails->EmailName.'. Here are the details,</P>';  
                    $message .= '<p>Reason:'.$resdata['Reason'].'</p>';
                    $message .= '<p>Expected Date:'.date('d M Y',strtotime($resdata['ExpectedDt'])).'</p>';
                    $message .= '<br><br>';
                    $message .= '<p><h4>Mastercom HR Team.<h4></p>';
                    $message .= '</body></html>';  
                                
                    $this->email->message($message);
                    $this->email->send();

                    $message = array('message' => 'Resignation Submitted successfully.');
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
                $this->response($decodedToken,REST_Controller::HTTP_UNAUTHORIZED);
            }
        }
        else 
        {
            $message = array('message' => 'Authentication failed');
            $message['status'] = false;
            $this->response($message, REST_Controller::HTTP_UNAUTHORIZED);
        }    
    }

    public function accept_resignation_put()
    {
        $empid = $this->uri->segment(3);

        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role==1 || $this->userdetails->Role==3 || $this->userdetails->Role==4)
                {
                    $_POST = json_decode(file_get_contents("php://input"), true);

                    $this->form_validation->set_data($this->put());
                    $this->form_validation->set_rules('RelievingDt', 'Relieving Date', 'trim|required|max_length[50]');

                    $resdata = array();
                    $resdata = $this->put();
                    $resdata['EmployeeID'] =  $empid;

                    $data = $this->user_model->accept_resign($resdata);

                    if($data)
                    {
                        $config = Array(        
                            'protocol' => 'smtp',
                            'smtp_host' => 'smtp.mailhostbox.com',
                            'smtp_port' => 587,
                            'smtp_user' => 'autoreply@mastercom.co.in',
                            'smtp_pass' => SMTP_PASS,
                            'smtp_timeout' => '4',
                            'mailtype'  => 'html', 
                            'charset'   => 'iso-8859-1'
                        );

                        $empdet = $this->user_model->get_user($empid);

                        $this->load->library('email', $config);
                        $this->email->set_newline("\r\n"); 
                        $getconfigmail = $this->user_model->get_config_email(); 
                        $from_email = $getconfigmail[0]->ConfigEmail;
                        $this->email->from($from_email, 'Mastercom - Resignation Accepted!'); 
                        // $this->email->to('aharshavardhan04@gmail.com');
                        $this->email->to('suhasrlawate1999@gmail.com');
                        //$this->email->to(trim($empdet->EmailName).'@mastercom.co.in',trim($this->userdetails->EmailName).'@mastercom.co.in');
                        //$this->email->cc($list);
                        $this->email->subject('Resignation Accepted - Details');
                        $message = '<html><body>';
                        $message .= '<h3>Dear '.$empdet->FirstName.',</h3>'; 
                        $message .= '<p> Your Resignation Request Accepted by  <h3>'.$this->userdetails->EmailName.'</h3>. Here are the details,</P>';  
                        $message .= '<p>Relieving Date:'.date('d M Y',strtotime($resdata['RelievingDt'])).'</p>';
                        $message .= '<br><br>';
                        $message .= '<p><h4>Mastercom HR Team.<h4></p>';
                        $message .= '</body></html>';  
                                    
                        $this->email->message($message);
                        $this->email->send();

                        $message = array('message' => 'Resignation Accepted successfully.');
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
                    $message = array('message' => 'This Role not allowed to Approve/Reject Resignation!');
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
        }    
    }

    public function reject_resignation_put()
    {
        $empid = $this->uri->segment(3);

        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role==1 || $this->userdetails->Role==3 || $this->userdetails->Role==4)
                {
                    $_POST = json_decode(file_get_contents("php://input"), true);

                    $resdata = array();
                    $resdata['EmployeeID'] =  $empid;

                    $data = $this->user_model->reject_resign($resdata);

                    if($data)
                    {
                        $config = Array(        
                            'protocol' => 'smtp',
                            'smtp_host' => 'smtp.mailhostbox.com',
                            'smtp_port' => 587,
                            'smtp_user' => 'autoreply@mastercom.co.in',
                            'smtp_pass' => SMTP_PASS,
                            'smtp_timeout' => '4',
                            'mailtype'  => 'html', 
                            'charset'   => 'iso-8859-1'
                        );

                        $empdet = $this->user_model->get_user($empid);

                        $this->load->library('email', $config);
                        $this->email->set_newline("\r\n"); 
                        $getconfigmail = $this->user_model->get_config_email(); 
                        $from_email = $getconfigmail[0]->ConfigEmail;
                        $this->email->from($from_email, 'Mastercom - Resignation Rejected!'); 
                        // $this->email->to('aharshavardhan04@gmail.com');
                        $this->email->to('suhasrlawate1999@gmail.com');
                        //$this->email->to(trim($empdet->EmailName).'@mastercom.co.in',trim($this->userdetails->EmailName).'@mastercom.co.in');
                        //$this->email->cc($list);
                        $this->email->subject('Resignation Rejected - Details');
                        $message = '<html><body>';
                        $message .= '<h3>Dear '.$empdet->FirstName.',</h3>'; 
                        $message .= '<p> Your Resignation Request Rejected by <h3>'.$this->userdetails->EmailName.'</h3>.</P>';  
                        $message .= '<p>Please contact your RM.</p>';
                        $message .= '<br><br>';
                        $message .= '<p><h4>Mastercom HR Team.<h4></p>';
                        $message .= '</body></html>';  
                                    
                        $this->email->message($message);
                        $this->email->send();

                        $message = array('message' => 'Resignation Rejected successfully.');
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
                    $message = array('message' => 'This Role not allowed to Approve/Reject Resignation!');
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
        }    
    }
}
?>