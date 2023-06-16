<?php
defined('BASEPATH') OR exit('No direct script access allowed');


require (APPPATH.'/libraries/REST_Controller.php');

class Locations extends REST_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model("user_model");
        $this->load->model("locations_model");
        $this->load->model("holidays_model");
        $this->load->library('Authorization_Token');   
    }

    /* Create Location API */
    public function create_post()
    {
        $headers = $this->input->request_headers(); 
        if(isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if($decodedToken['status'])
            {
                if($this->session->userdata('Role')=='Admin')
                {
                    $_POST = json_decode(file_get_contents("php://input"), true);

                    $this->form_validation->set_rules('LocationName', 'Location Name', 'trim|required|max_length[21]');
                    $this->form_validation->set_rules('ParentLocationID', 'Parent Location Name', 'trim|required|numeric');

                    if ($this->form_validation->run() === false) {
                        $errors = $this->form_validation->error_array();
                        $errors['status'] = false;
                        $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    // Validating Location Name 
                    $check = $this->locations_model->get_location_by_name($this->input->post('LocationName'));
                    
                    if(count($check)==1){
                        $message = array('message' => 'Location Name exists');
                        $message['status'] = false;
                        $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    $locationdata = array();
                    $locationdata = $this->input->post();

                    $data = $this->locations_model->create_location($locationdata);// Inserting Location

                    if($data)
                    {
                        $message = array('message' => 'Location created successfully.');
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
                    $message = array('message' => 'This Role not allowed to create Location');
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

    /* Update Location API */
    public function update_put()
    {
        $locationid = $this->uri->segment(3); // Location ID

        if(empty($locationid) || $locationid==0){
            $message = array('message' => 'Location ID required/can not be zero');
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
                if($this->session->userdata('Role')=='Admin')
                {
                    $_POST = json_decode(file_get_contents("php://input"), true);
                    
                    $this->form_validation->set_data($this->put());
                    $this->form_validation->set_rules('LocationName', 'Location Name', 'trim|required|max_length[21]');
                    $this->form_validation->set_rules('ParentLocationID', 'Parent Location Name', 'trim|required|numeric');

                    if ($this->form_validation->run() === false) {
                        $errors = $this->form_validation->error_array();
                        $errors['status'] = false;
                        $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    // Validating Location Name
                    $checkloc = $this->locations_model->get_location_by_name($this->put('LocationName'),$locationid);

                    if(count($checkloc)>=1){
                        $message = array('message' => 'Location Name exists');
                        $message['status'] = false;
                        $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    $locationdata = array();
                    $locationdata = $this->put();

                    $data = $this->locations_model->update_location($locationdata,$locationid); // Updating Location by Location ID

                    if($data)
                    {
                        $message = array('message' => 'Location Updated successfully.');
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
                    $message = array('message' => 'This Role not allowed to update Location');
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

    /* Delete Location API */
    public function index_delete()
    {
        $locationid = $this->uri->segment(3); //Location ID

        if(empty($locationid) || $locationid==0){
            $message = array('message' => 'Location ID required/can not be zero');
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
                if($this->session->userdata('Role')=='Admin')
                {
                    $check = $this->locations_model->check_location_allocation($locationid);

                    /* Checking before Deleting, this Location is mapped with some other table(i.e, Projects, Holidays etc..)
                        If mapped this Location in some where, we are not allowing to Delete Location */
                    if(count($check)>=1){
                        $message = array('message' => "Sorry, can't delete. This Location mapped in some where");
                        $message['status'] = false;
                        $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    $data = $this->locations_model->delete_location($locationid); // Deleting Location by Location ID

                    if($data)
                    {
                        $message = array('message' => 'Location deleted successfully.');
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
                    $message = array('message' => 'This Role not allowed to delete Location');
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