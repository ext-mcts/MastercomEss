<?php
defined('BASEPATH') OR exit('No direct script access allowed');


require (APPPATH.'/libraries/REST_Controller.php');

class Ticket extends REST_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library('Authorization_Token');
        $this->load->model("ticket_model");
    }

    public function create_post()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                $_POST = json_decode(file_get_contents("php://input"), true);

                $this->form_validation->set_rules('dept', 'Department', 'trim|required|max_length[50]');
                $this->form_validation->set_rules('deptperson', 'Department Person', 'trim|required|max_length[50]');
                $this->form_validation->set_rules('category', 'Category', 'trim|required|max_length[50]');
                $this->form_validation->set_rules('priority', 'Priority', 'trim|required|max_length[50]');
                $this->form_validation->set_rules('subject', 'Ticket Subject', 'trim|required|max_length[150]');
                $this->form_validation->set_rules('description', 'Ticket Description', 'trim|required|max_length[500]');
                $this->form_validation->set_rules('contact', 'Contact Number', 'trim|required|max_length[50]');
                $this->form_validation->set_rules('location', 'Location', 'trim|required|numeric|max_length[3]');

                if ($this->form_validation->run() === false) 
                {
                    $errors = $this->form_validation->error_array();
                    $errors['status'] = false;
                    $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                    return false;
                }

                $tktdata = array();
                $tktdata = $this->input->post();

                $data = $this->ticket_model->create_ticket($tktdata);

                if($data)
                {
                    $message = array('message' => 'Ticket raised successfully.');
                    $message['status'] = true;
                    $this->response($message, REST_Controller::HTTP_CREATED);
                }
                else{ 
                    $message = array('message' => 'Something went wrong!.');
                    $message['status'] = false;
                    $this->response($message, REST_Controller::HTTP_OK);
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
        $tktid = $this->uri->segment(3);

        if(empty($tktid) || $tktid==0){
            $message = array('message' => 'Ticket ID required/can not be zero');
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
                $check = $this->ticket_model->get_ticket_by_id($tktid);
        
                if(count((array)$check)==0){
                    $message = array('message' => 'Ticket not exist!');
                    $message['status'] = false;
                    $this->response($message, REST_Controller::HTTP_OK);
                    return false;
                }

                if($check[0]->TktStatus=='Closed' || $check[0]->TktStatus=='closed')
                {
                    $message = array('message' => 'This Ticket has been closed!');
                    $message['status'] = false;
                    $this->response($message, REST_Controller::HTTP_OK);
                    return false;
                }

                $_POST = json_decode(file_get_contents("php://input"), true);

                $this->form_validation->set_data($this->put());
                
                $this->form_validation->set_rules('dept', 'Department', 'trim|required|max_length[50]');
                $this->form_validation->set_rules('deptperson', 'Department Person', 'trim|required|max_length[50]');
                $this->form_validation->set_rules('category', 'Category', 'trim|required|max_length[50]');
                $this->form_validation->set_rules('priority', 'Priority', 'trim|required|max_length[50]');
                $this->form_validation->set_rules('subject', 'Ticket Subject', 'trim|required|max_length[150]');
                $this->form_validation->set_rules('description', 'Ticket Description', 'trim|required|max_length[500]');
                $this->form_validation->set_rules('contact', 'Contact Number', 'trim|required|max_length[50]');
                $this->form_validation->set_rules('location', 'Location', 'trim|required|numeric|max_length[3]');

                if ($this->form_validation->run() === false) 
                {
                    $errors = $this->form_validation->error_array();
                    $errors['status'] = false;
                    $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                    return false;
                }

                $tktdata = array();
                $tktdata = $this->put();

                $data = $this->ticket_model->update_ticket($tktdata,$tktid);

                if($data)
                {
                    $message = array('message' => 'Ticket has been updated successfully.');
                    $message['status'] = true;
                    $this->response($message, REST_Controller::HTTP_CREATED);
                }
                else{ 
                    $message = array('message' => 'Something went wrong!.');
                    $message['status'] = false;
                    $this->response($message, REST_Controller::HTTP_OK);
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

    public function index_delete()
    {
        $tktid = $this->uri->segment(3);

        if(empty($tktid) || $tktid==0){
            $message = array('message' => 'Ticket ID required/can not be zero');
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
                $check = $this->ticket_model->get_ticket_by_id($tktid);
        
                if(count((array)$check)==0){
                    $message = array('message' => 'Ticket not exist!');
                    $message['status'] = false;
                    $this->response($message, REST_Controller::HTTP_OK);
                    return false;
                }

                $data = $this->ticket_model->delete_ticket($tktid);

                if($data)
                {
                    $message = array('message' => 'Ticket deleted successfully.');
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

    public function index_get()
    {
        $_GET['Page'] = $_GET['Page'] ?? 1;

        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                $filterdata = array();

                $filterdata = $this->input->get();
                $filterdata['Page'] = $_GET['Page'];

                $data = $this->ticket_model->get_all_tickets($filterdata); 
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
                $this->response($decodedToken);
            }
        }
        else {
            $message = array('message' => 'Authentication failed');
            $message['status'] = false;
            $this->response($message, REST_Controller::HTTP_UNAUTHORIZED);
        }
    }
}