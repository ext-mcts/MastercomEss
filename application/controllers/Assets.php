<?php
defined('BASEPATH') OR exit('No direct script access allowed');


require (APPPATH.'/libraries/REST_Controller.php');

class Assets extends REST_Controller
{

    public $userdetails;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('Authorization_Token');
        $this->load->model("assets_model");
        #$this->load->library('MY_Form_validation'); 
        
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

                    $this->form_validation->set_rules('Brand', 'AssetBrand', 'trim|required|max_length[100]');
                    $this->form_validation->set_rules('Model', 'Asset Model', 'trim|required|max_length[100]');
                    $this->form_validation->set_rules('PurchaseDate', 'Purchased Date', 'trim|required');
                    $this->form_validation->set_rules('SerialNumber', 'Asset Serial Number', 'trim|required|max_length[100]');
                    $this->form_validation->set_rules('ChargerSerialNumber', 'Asset Charger Serial Number', 'trim|required|max_length[100]');
                    $this->form_validation->set_rules('Ram', 'Asset Ram', 'trim|required|max_length[45]');
                    $this->form_validation->set_rules('Rom', 'Asset Rom', 'trim|required|max_length[45]');
                    $this->form_validation->set_rules('Processor', 'Asset Processor', 'trim|required|max_length[45]');

                    if ($this->form_validation->run() === false) 
                    {
                        $errors = $this->form_validation->error_array();
                        $errors['status'] = false;
                        $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    if($this->input->post('SerialNumber')) { 
                        $check = $this->assets_model->validate_asset("SerialNumber",$this->input->post('SerialNumber'));
                        if(count($check)>=1){
                            $message = array('message' => "Asset Serial Number is duplicate!");
                            $message['status'] = false;
                            $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                            return false;
                        }
                    }

                    if($this->input->post('ChargerSerialNumber')) { 
                        $check = $this->assets_model->validate_asset("ChargerSerialNumber",$this->input->post('ChargerSerialNumber'));
                        if(count($check)>=1){
                            $message = array('message' => "Asset Charger Serial Number is duplicate!");
                            $message['status'] = false;
                            $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                            return false;
                        }
                    }

                    $assetdata = array();
                    $assetdata = $this->input->post();

                    $data = $this->assets_model->create_asset($assetdata);

                    if($data)
                    {
                        $message = array('message' => 'Asset created successfully.');
                        $message['status'] = true;
                        $this->response($message, REST_Controller::HTTP_CREATED);
                    }
                    else{ 
                        $message = array('message' => 'Something went wrong!.');
                        $message['status'] = false;
                        $this->response($message, REST_Controller::HTTP_OK);
                    }

                } else {
                    $message = array('message' => 'This Role not allowed to create Asset');
                    $message['status'] = false;
                    $this->response($message,REST_Controller::HTTP_UNAUTHORIZED);
                }
                
            } else {
                $this->response($decodedToken);
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
        $astid = $this->uri->segment(3);

        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role==1)
                {
                    $_POST = json_decode(file_get_contents("php://input"), true);

                    $this->form_validation->set_rules('Brand', 'AssetBrand', 'trim|required|max_length[100]');
                    $this->form_validation->set_rules('Model', 'Asset Model', 'trim|required|max_length[100]');
                    $this->form_validation->set_rules('PurchaseDate', 'Purchased Date', 'trim|required');
                    $this->form_validation->set_rules('SerialNumber', 'Asset Serial Number', 'trim|required|max_length[100]');
                    $this->form_validation->set_rules('ChargerSerialNumber', 'Asset Charger Serial Number', 'trim|required|max_length[100]');
                    $this->form_validation->set_rules('Ram', 'Asset Ram', 'trim|required|max_length[45]');
                    $this->form_validation->set_rules('Rom', 'Asset Rom', 'trim|required|max_length[45]');
                    $this->form_validation->set_rules('Processor', 'Asset Processor', 'trim|required|max_length[45]');

                    if ($this->form_validation->run() === false) 
                    {
                        $errors = $this->form_validation->error_array();
                        $errors['status'] = false;
                        $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    if($this->input->post('SerialNumber')) {
                        $check = $this->assets_model->validate_asset("SerialNumber",$this->input->post('SerialNumber'),$astid);
                        if(count($check)>=1){
                            $message = array('message' => "Asset Serial Number is duplicate!");
                            $message['status'] = false;
                            $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                            return false;
                        }
                    }

                    if($this->input->post('ChargerSerialNumber')) { 
                        $check = $this->assets_model->validate_asset("ChargerSerialNumber",$this->input->post('ChargerSerialNumber'),$astid);
                        if(count($check)>=1){
                            $message = array('message' => "Asset Charger Serial Number is duplicate!");
                            $message['status'] = false;
                            $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                            return false;
                        }
                    }

                    $assetdata = array();
                    $assetdata = $this->put();

                    $data = $this->assets_model->update_asset($assetdata,$astid);

                    if($data)
                    {
                        $message = array('message' => 'Asset updated successfully.');
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
                    $message = array('message' => 'This Role not allowed to modify Asset details');
                    $message['status'] = false;
                    $this->response($message,REST_Controller::HTTP_UNAUTHORIZED);
                    return false;
                }
            }
            else {
                $this->response($decodedToken);
            }
        }
        else {
            $message = array('message' => 'Authentication failed');
            $message['status'] = false;
            $this->response($message, REST_Controller::HTTP_UNAUTHORIZED);
            return false;
        }
    }

    public function assign_post()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role==1)
                {
                    $_POST = json_decode(file_get_contents("php://input"), true);

                    $this->form_validation->set_rules('EmployeeID', 'Employee', 'trim|required|numeric');
                    $this->form_validation->set_rules('Asset', 'Asset', 'trim|required|numeric');
                    $this->form_validation->set_rules('ValidUpTo', 'Asset Valid Up To', 'trim|required|max_length[45]');

                    if ($this->form_validation->run() === false) 
                    {
                        $errors = $this->form_validation->error_array();
                        $errors['status'] = false;
                        $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    $assetdata = array();
                    $assetdata = $this->input->post();

                    $check = $this->assets_model->check_emp_asset($assetdata['EmployeeID'],$assetdata['Asset']);

                    if(count($check)>=1){
                        $message = array('message' => "This Asset/Employee has assigned previously!");
                        $message['status'] = false;
                        $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    $data = $this->assets_model->assign_asset($assetdata);

                    if($data)
                    {
                        $message = array('message' => 'Asset asigned to Employee Successfully!.');
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
                    $message = array('message' => 'This Role not allowed to Asign Asset!');
                    $message['status'] = false;
                    $this->response($message,REST_Controller::HTTP_UNAUTHORIZED);
                    return false;
                }
            }
            else {
                $this->response($decodedToken);
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
        $astid = $this->uri->segment(3);

        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role==1)
                {
                    $check = $this->assets_model->check_asset_allocation($astid);

                    if(count($check)>=1){
                        $message = array('message' => "Sorry, can't delete. This Asset assigned to Employee!");
                        $message['status'] = false;
                        $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    $data = $this->assets_model->delete_asset($astid);

                    if($data)
                    {
                        $message = array('message' => 'Asset deleted successfully!.');
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

    public function my_assets_get()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                $data = $this->assets_model->get_emp_assets($this->userdetails->EmployeeID);

                if($data)
                {
                    $message = array('results' => $data);
                    $message['status'] = true;
                    $this->response($message, REST_Controller::HTTP_OK);
                }
                else{ 
                    $message = array('message' => 'No records found!.');
                    $message['status'] = false;
                    $this->response($message, REST_Controller::HTTP_OK);
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
}