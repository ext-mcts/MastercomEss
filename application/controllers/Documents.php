<?php
defined('BASEPATH') OR exit('No direct script access allowed');


require (APPPATH.'/libraries/REST_Controller.php');

class Documents extends REST_Controller
{

    public $userdetails;

    public function __construct()
    {
        parent::__construct();
        $this->load->model("user_model");
        $this->load->library('Authorization_Token');
        $this->load->model('documents_model');

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

    public function upload_post()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role==1)
                {

                    if(empty($_FILES))
                    {
                        $message = array('message' => 'Any one Document upload required!');
                        $message['status'] = false;
                        $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    $files = $_FILES;
                    $status = 1;
                    $doctype = '';
                    $doc='';
                    foreach ($files as $key => $image) {

                        if($key=="PAN")   $doctype=2;
                        if($key=="Aadhar")   $doctype=3;
                        if($key=="Offet Letter")   $doctype=1;
                        if($key=="Voter ID")   $doctype=4;
                        
                        $config = array(
                            'upload_path' => "./assets/documents/",
                            'allowed_types' => "gif|jpg|png|jpeg|pdf",
                            'file_name' => $image['name']
                            );

                        $id = $this->userdetails->EmployeeID;

                        if (!is_dir($config['upload_path'].$id)) {
                            //Create our directory if it does not exist
                            mkdir($config['upload_path'].$id);
                        }
                        $config['upload_path'] = "./assets/documents/".$id."/";

                        $this->load->library('upload', $config);
                        $this->upload->initialize($config);
                        if ($this->upload->do_upload($key)) {

                            $data['Doc_name'] = $this->upload->data('file_name');
                            $data['Doc_path'] = "./assets/documents/".$id."/".$this->upload->data('file_name');
                            $data['EmployeeID'] = $id;
                            $data['Doc_type'] = $doctype;

                            $insert = $this->documents_model->create_docs($data);
                            if($insert)
                            {
                                $message = array('message' => "Documents uploaded successfully!");
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
                            $errors = array('error' => $this->upload->display_errors()); 
                            $errors['status'] = false;
                            $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                            return false;
                        }
                    }
                }
                else 
                {
                    $message = array('message' => 'This Role not allowed to Upload Documents!');
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

    public function view_get()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role==1)
                {
                    $doc_id = $this->uri->segment(3);

                    if(!is_numeric($doc_id) || empty($doc_id) || $doc_id==0){
                        $message = array('message' => 'Document ID not numeric/empty/too lengthy');
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
                            $data = $this->documents_model->get_document($doc_id);

                            if($data)
                            {
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
                else 
                {
                    $message = array('message' => 'This Role not allowed to View Documents!');
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

    public function index_delete()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role==1)
                {
                    $doc_id = $this->uri->segment(3); // Document ID

                    if(empty($doc_id) || $doc_id==0){
                        $message = array('message' => 'Document ID required/can not be zero');
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

                            $data = $this->documents_model->delete_document($doc_id); // Deleting Document

                            if($data)
                            {
                                $message = array('message' => 'Document deleted successfully.');
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
                else 
                {
                    $message = array('message' => 'This Role not allowed to Delete Documents!');
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

    public function download_get()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role==1)
                {
                    $docid = $this->uri->segment(3);

                    if(!is_numeric($docid) || empty($docid) || $docid==0){
                        $message = array('message' => 'Document ID not numeric/empty/too lengthy');
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
                            $this->load->helper('download');

                            $fileInfo = $this->documents_model->get_document($docid);

                            $file = $fileInfo->Doc_path;
                        
                            force_download($file, NULL);

                            if(force_download($file, NULL))
                            {
                                $message = array('message' => 'File Downloaded successfully.');
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
                else 
                {
                    $message = array('message' => 'This Role not allowed to Download Documents!');
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
}