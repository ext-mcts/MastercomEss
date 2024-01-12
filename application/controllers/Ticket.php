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
                $_POST = json_decode(file_get_contents("php://input"), true);

                $this->form_validation->set_rules('dept', 'Department', 'trim|required|numeric|max_length[3]');
                $this->form_validation->set_rules('deptperson', 'Department Person', 'trim|required|numeric|max_length[3]');
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
                $tktdata['EmployeeID'] = $this->userdetails->EmployeeID;

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
                
                $this->form_validation->set_rules('dept', 'Department', 'trim|required|numeric|max_length[3]');
                $this->form_validation->set_rules('deptperson', 'Department Person', 'trim|required|numeric|max_length[3]');
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
                $this->response($decodedToken,REST_Controller::HTTP_UNAUTHORIZED);
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
                $this->response($decodedToken,REST_Controller::HTTP_UNAUTHORIZED);
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
                $this->response($decodedToken,REST_Controller::HTTP_UNAUTHORIZED);
            }
        }
        else {
            $message = array('message' => 'Authentication failed');
            $message['status'] = false;
            $this->response($message, REST_Controller::HTTP_UNAUTHORIZED);
        }
    }

    public function get_category_get()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
				$data = $this->ticket_model->get_categories();
				if(count($data)>=1)
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

    public function get_priority_get()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
				$data = $this->ticket_model->get_priorities();
				if(count($data)>=1)
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

    public function send_escalate_email_post()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role==1 || $this->userdetails->Role==3 || $this->userdetails->Role==5)
                {
                    $data = $this->ticket_model->get_delayed_tickets();

                    if(count($data)>=1)
                    {
                        foreach($data as $user)
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
    
                            $empdet = $this->user_model->get_user($user->AssignedToPerson);

                            $empdet2 = $this->user_model->get_user($user->RaisedBy);

                            $this->load->library('email', $config);
                            $this->email->set_newline("\r\n"); 
                            $getconfigmail = $this->user_model->get_config_email(); 
                            $from_email = $getconfigmail[0]->ConfigEmail;
                            $this->email->from($from_email, 'Mastercom - Ticket Escalated Email!'); 
                            //$this->email->to($empdet->EmailName);
                            $this->email->to('harshavardhan4891@gmail.com');
                            $this->email->subject('Escalated Ticket Details - Details');
                            $message = '<html><body>';
                            $message .= '<h3>Dear '.$empdet->FirstName.',</h3>'; 
                            $message .= '<p> Your Assigned Ticket is delayed. Here are the details,</P>';  
                            $message .= '<p><h4>Ticket Subject:</h4> '.$user->TktSubject.'</p>';
                            $message .= '<p><h4>Ticket Description:</h4> '.$user->TktDescription.'</p>';
                            $message .= '<p><h4>Raised By:</h4> '.$empdet2->FirstName.'</p>';
                            $message .= '<p><h4>Raised On:</h4> '.date('d M Y',strtotime($user->TktCreatedDt)).'</p>';
                            $message .= '<p><h4>Mastercom Team.<h4></p>';
                            $message .= '</body></html>';  
                                        
                            $this->email->message($message);
                            $this->email->send();
                        }
                        $message = array('message' => 'Escalated Emails sent successfully!');
                        $message['status'] = true;
                        $this->response($message, REST_Controller::HTTP_OK);
                    }
                    else{ 
                        $message = array('message' => 'No Records found!.');
                        $message['status'] = false;
                        $this->response($message, REST_Controller::HTTP_OK);
                    }
                }
                else 
                {
                    $message = array('message' => 'This Role not allowed to Send Escalete Email!');
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

    // Suhas

 /* Ticket details view API */
 public function view_get()
 {
     $tktId = $this->uri->segment(3); // Ticket ID

     if(!is_numeric($tktId) || empty($tktId) || $tktId==0){
         $message = array('message' => 'Ticket ID not numeric/empty/too lengthy');
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
             if($this->userdetails->Role==1)
             {
                 $data = $this->ticket_model->get_ticketById($tktId); // Getting ticket details with ID

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
                 $message = array('message' => 'This Role not allowed to view Ticket details');
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

 public function status_get()
    {
        $tktId = $this->uri->segment(3); // ticket id
        $status = $this->uri->segment(4); // status 1- open

        if(empty($tktId) || $tktId==0){
            $message = array('message' => 'Ticket ID required/can not be zero');
            $message['status'] = false;
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }

        if(empty($status) || $status==0){
            $message = array('message' => 'Status required/can not be zero');
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
                if($this->userdetails->Role==1 || $this->userdetails->Role==2)
                {
                    $data = $this->ticket_model->open_close_ticket($tktId,$status); // updating status as open or close
                    $message =  $data;
                    $message['status'] = true;
                    $this->response($message, REST_Controller::HTTP_OK);
                }
                else 
                {
                    $message = array('message' => 'This Role not allowed to Open/Close Ticket');
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