<?php
defined('BASEPATH') OR exit('No direct script access allowed');


require (APPPATH.'/libraries/REST_Controller.php');

class Holidays extends REST_Controller
{
    public $userdetails;

    public function __construct()
    {
        parent::__construct();
        $this->load->model("user_model");
        $this->load->model("locations_model");
        $this->load->model("holidays_model");
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

    /* Create Holiday API */
    public function create_post()
    {
        $headers = $this->input->request_headers(); 
        if(isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if($decodedToken['status'])
            {
                if($this->userdetails->Role==1 || $this->userdetails->Role==3)
                {
                    $_POST = json_decode(file_get_contents("php://input"), true);

                    $this->form_validation->set_rules('HolidayDate', 'Holiday Date', 'trim|required');
                    $this->form_validation->set_rules('HolidayDesc', 'Holiday Description', 'trim|required|max_length[39]');
                    $this->form_validation->set_rules('Location', 'Location', 'trim|required|numeric');
                    $this->form_validation->set_rules('HolidayType', 'Holiday Type', 'trim|required|numeric');

                    if ($this->form_validation->run() === false) {
                        $errors = $this->form_validation->error_array();
                        $errors['status'] = false;
                        $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    $date = date('Y-m-d 00:00:00',strtotime($this->input->post('HolidayDate')));
                    $date_check = $this->holidays_model->check_holiday_date($date,$this->input->post('Location'));
                    
                    // Checking given Holiday date is allocated previously or not
                    if(count($date_check)==1){
                        $message = array('message' => 'This Date and Location occupy another Holiday in List');
                        $message['status'] = false;
                        $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    $holidaydata = array();
                    $holidaydata = $this->input->post();

                    $data = $this->holidays_model->create_holiday($holidaydata);// Inserting Holiday

                    if($data)
                    {
                        $message = array('message' => 'Holiday created successfully.');
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
                    $message = array('message' => 'This Role not allowed to create Holiday');
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

    /* Updating Holiday API */
    public function update_put()
    {
        $holidayid = $this->uri->segment(3); // Holiday ID

        if(empty($holidayid) || $holidayid==0){
            $message = array('message' => 'Holiday ID required/can not be zero');
            $message['status'] = false;
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }

        $check = $this->holidays_model->validate_holiday_byid($holidayid);

        if(count($check)==0){
            $message = array('message' => 'Holiday ID not exist');
            $message['status'] = false;
            $this->response($message, REST_Controller::HTTP_OK);
            return false;
        }

        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role==1 || $this->userdetails->Role==3)
                {
                    $_POST = json_decode(file_get_contents("php://input"), true);
                    
                    $this->form_validation->set_data($this->put());
                    $this->form_validation->set_rules('HolidayDate', 'Holiday Date', 'trim|required');
                    $this->form_validation->set_rules('HolidayDesc', 'Holiday Description', 'trim|required|max_length[39]');
                    $this->form_validation->set_rules('Location', 'Location', 'trim|required|numeric');
                    $this->form_validation->set_rules('HolidayType', 'Holiday Type', 'trim|required|numeric');


                    if ($this->form_validation->run() === false) {
                        $errors = $this->form_validation->error_array();
                        $errors['status'] = false;
                        $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    $checkdate = $this->holidays_model->check_holiday_date(date('Y-m-d 00:00:00',strtotime($this->put('HolidayDate'))),$this->put('Location'),$holidayid);

                    // Checking given Holiday date is allocated previously or not
                    if(count($checkdate)>=1){
                        $message = array('message' => 'This Holiday Date and Location has been assigned to another Holiday');
                        $message['status'] = false;
                        $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    
                    $holidaydata = array();
                    $holidaydata = $this->put();

                    $data = $this->holidays_model->update_holidays($holidaydata,$holidayid); // Updating Holiday

                    if($data)
                    {
                        $message = array('message' => 'Holiday Updated successfully.');
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
                    $message = array('message' => 'This Role not allowed to update Holiday');
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

    /* Delete Holiday API */
    public function index_delete()
    {
        $holidayid = $this->uri->segment(3); // Holiday ID

        if(empty($holidayid) || $holidayid==0){
            $message = array('message' => 'Holiday ID required/can not be zero');
            $message['status'] = false;
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }

        $check = $this->holidays_model->validate_holiday_byid($holidayid);

        if(count($check)==0){
            $message = array('message' => 'Holiday ID not exist');
            $message['status'] = false;
            $this->response($message, REST_Controller::HTTP_NO_CONTENT);
            return false;
        }

        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role==1 || $this->userdetails->Role==3)
                {
                    $data = $this->holidays_model->delete_holiday($holidayid); // Deleting Holiday

                    if($data)
                    {
                        $message = array('message' => 'Holiday deleted successfully.');
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
                    $message = array('message' => 'This Role not allowed to delete Holiday');
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

    /* Holidays List API */
    public function index_get()
    {
        $_GET['Page'] = $_GET['Page'] ?? 1;

        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
            
                if($this->input->get("HolidayDt")) $filterdata['HolidayDt']=$this->input->get("HolidayDt");
                if($this->input->get("HolidayDesc")) $filterdata['HolidayDesc']=$this->input->get("HolidayDesc");
                if($this->input->get("Location")) $filterdata['Location']=$this->input->get("Location");
                if($this->input->get("Year")) $filterdata['Year']=$this->input->get("Year");
                if($this->input->get("HolidayType")) $filterdata['HolidayType']=$this->input->get("HolidayType");

                $filterdata = array();
                $filterdata = $this->input->get();
                $filterdata['Page'] = $_GET['Page'];

                $data = $this->holidays_model->get_all_holidays($filterdata); // Getting all Holiday
                if($data)
                {
                    $message = array('page' => $_GET['Page'],
                                    'total_rows' => count($data),
                                    'results' => $data,);
                    $message['status'] = true;
                    $this->response($message, REST_Controller::HTTP_OK);
                }
                else{ 
                    $message = array('message' => 'No Data found!');
                    $message['status'] = false;
                    $this->response($message, REST_Controller::HTTP_OK);
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

    public function upcoming_holidays_get()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                $data = $this->holidays_model->get_upcoming_holidays($this->userdetails->Location);
                if($data)
                {
                    $message = array('results' => $data);
                    $message['status'] = true;
                    $this->response($message, REST_Controller::HTTP_OK);
                }
                else{ 
                    $message = array('message' => 'No Data found!');
                    $message['status'] = false;
                    $this->response($message, REST_Controller::HTTP_OK);
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
}