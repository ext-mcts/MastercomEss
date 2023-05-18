<?php
defined('BASEPATH') OR exit('No direct script access allowed');


require (APPPATH.'/libraries/REST_Controller.php');

class Leave extends REST_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model("user_model");
        $this->load->library('Authorization_Token');
        $this->load->model("leaves_model");
    }

    public function apply_post()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                $this->form_validation->set_rules('Date', 'Leave Date', 'trim|required');
                $this->form_validation->set_rules('Reason', 'Leave Reason', 'trim|required|max_length[255]');

                if ($this->form_validation->run() === false) 
                {
                    $errors = $this->form_validation->error_array();
                    $errors['status'] = false;
                    $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                    return false;
                }

                //checking date format
                if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$this->input->post('Date'))) {
                    $message = array('message' => 'Leave Date format is invalid, please give date format as YYYY-MM-DD');
                    $message['status'] = false;
                    $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                    return false;
                } 

                $startDate = strtotime(date('Y-m-d', strtotime($this->input->post('Date')) ) );
                $currentDate = strtotime(date('Y-m-d'));

                if($startDate < $currentDate) {
                    $message = array('message' => 'Past Dates not allowed to select!');
                    $message['status'] = false;
                    $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
                    return false;
                }

                $check = $this->leaves_model->check_leaves($this->session->userdata('EmployeeID'),$this->input->post('Date'));

                if(count($check)==1){
                    $message = array('message' => 'This Employee already applied leave for this date!');
                    $message['status'] = false;
                    $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                    return false;
                }

                $leavedata = array();
                $leavedata = $this->input->post();
                $leavedata["EmployeeID"] = $this->session->userdata('EmployeeID');

                $data = $this->leaves_model->apply_leave($leavedata);// Inserting Employee

                if($data)
                {
                    $message = array('message' => 'Leave applied successfully.');
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
        $leaveid = $this->uri->segment(3);

        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                $getleave = $this->leaves_model->get_leave($leaveid);

                $startDate = strtotime(date('Y-m-d', strtotime($getleave->LStartDt) ) );
                $currentDate = strtotime(date('Y-m-d'));

                if($startDate < $currentDate) {
                    $message = array('message' => 'Leave can not delete after planned day!');
                    $message['status'] = false;
                    $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
                    return false;
                }

                $data = $this->leaves_model->delete_leave($leaveid);

                if($data)
                {
                    $message = array('message' => 'Leave deleted successfully.');
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

    public function approve_put()
    {
        $leaveid = $this->uri->segment(3); // leave id
        $status = $this->uri->segment(4); // status 1- approve

        if(empty($leaveid) || $leaveid==0){
            $message = array('message' => 'Leave ID required/can not be zero');
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
                if($this->session->userdata('Role')=='User')
                {
                    $data = $this->leaves_model->accept_reject_leave($leaveid,$status); // updating status as Approve

                    if($data)
                    {
                        /* mail sending with approving details */
                        $config = Array(        
                            'protocol' => 'smtp',
                            'smtp_host' => 'smtp.mailhostbox.com',
                            'smtp_port' => 587,
                            'smtp_user' => 'autoreply@mastercom.co.in',
                            'smtp_pass' => 'aqQcleX9',
                            'smtp_timeout' => '4',
                            'mailtype'  => 'html', 
                            'charset'   => 'iso-8859-1'
                        );
                       
                        $leavedet = $this->leaves_model->get_leave($leaveid);
                       
                        $empdet = $this->user_model->get_user($leavedet[0]->EmployeeID);
                        $email = $empdet->EmailName;
                        $this->load->library('email', $config);
                        $this->email->set_newline("\r\n"); 
                        $getconfigmail = $this->user_model->get_config_email(); 
                        $from_email = $getconfigmail[0]->ConfigEmail;
                        $this->email->from($from_email, 'Mastercom - Leave Approved!'); 
                        $this->email->to($email);
                        $this->email->subject('Leave Approved - Details');
                        $message = '<html><body>';
                        $message .= '<h3>Dear '.$empdet->FirstName.',</h3>'; 
                        $message .= '<p> Your Leave has been approved. Here are the details,</P>';  
                        $message .= '<p>Leave Date:'.date('d M Y',strtotime($leavedet[0]->LStartDt)).'</p>';
                        $message .= '<p>Reason:'.$leavedet[0]->Reason.'</p>';
                        $message .= '<p>For more details: <a href="/mcts_extranet-api/leaves">Click Here</a></p>';
                        $message .= '<br><br>';
                        $message .= '<p><h4>Mastercom Team.<h4></p>';
                        $message .= '</body></html>';  
                                    
                        $this->email->message($message);
                        $this->email->send();

                        $message = array('message' => 'Leave has been Approved.');
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
                    $message = array('message' => 'This Role not allowed to Approve/Reject Leave');
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

    public function reject_put()
    {
        $leaveid = $this->uri->segment(3); // leave id
        $status = $this->uri->segment(4); // status 2- reject

        if(empty($leaveid) || $leaveid==0){
            $message = array('message' => 'Leave ID required/can not be zero');
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

        $this->form_validation->set_data($this->put());
        $this->form_validation->set_rules('RejectReason', 'Reject Reason', 'trim|required|max_length[255]');

        if ($this->form_validation->run() === false) {
            $errors = $this->form_validation->error_array();
            $errors['status'] = false;
            $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }

        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->session->userdata('Role')=='User')
                {
                    $ldata = $this->put();
                    $data = $this->leaves_model->accept_reject_leave($leaveid,$status,$ldata); // updating status as Approve

                    if($data)
                    {
                        /* mail sending with approving details */
                        $config = Array(        
                            'protocol' => 'smtp',
                            'smtp_host' => 'smtp.mailhostbox.com',
                            'smtp_port' => 587,
                            'smtp_user' => 'autoreply@mastercom.co.in',
                            'smtp_pass' => 'aqQcleX9',
                            'smtp_timeout' => '4',
                            'mailtype'  => 'html', 
                            'charset'   => 'iso-8859-1'
                        );
                       
                        $leavedet = $this->leaves_model->get_leave($leaveid);
                       
                        $empdet = $this->user_model->get_user($leavedet[0]->EmployeeID);
                        $email = $empdet->EmailName;
                        $this->load->library('email', $config);
                        $this->email->set_newline("\r\n"); 
                        $getconfigmail = $this->user_model->get_config_email(); 
                        $from_email = $getconfigmail[0]->ConfigEmail;
                        $this->email->from($from_email, 'Mastercom - Leave Rejected!'); 
                        $this->email->to($email);
                        $this->email->subject('Leave Rejected - Details');
                        $message = '<html><body>';
                        $message .= '<h3>Dear '.$empdet->FirstName.',</h3>'; 
                        $message .= '<p> Your Leave has been rejected. Here are the details,</P>';  
                        $message .= '<p>Leave Date:'.date('d M Y',strtotime($leavedet[0]->LStartDt)).'</p>';
                        $message .= '<p>Reject Reason:'.$leavedet[0]->RejectReason.'</p>';
                        $message .= '<p>For more details: <a href="/mcts_extranet-api/leaves">Click Here</a></p>';
                        $message .= '<br><br>';
                        $message .= '<p><h4>Mastercom Team.<h4></p>';
                        $message .= '</body></html>';  
                                    
                        $this->email->message($message);
                        $this->email->send();

                        $message = array('message' => 'Leave has been Rejected.');
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
                    $message = array('message' => 'This Role not allowed to Approve/Reject Leave');
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

    public function summary_get()
    {
        $_GET['Page'] = $_GET['Page'] ?? 1;
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {

            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                $filterdata['Page'] = $_GET['Page'];

                if($this->input->get("EmployeeID")) $filterdata['EmployeeID']=$this->input->get("EmployeeID");
                
                $data = $this->leaves_model->get_all_leaves($filterdata); // getting all leaves
                if(count($data)>=1)
                {
                    $message = array('page' => $_GET['Page'],
                                    'total_rows' => count($data),
                                    'results' => $data,);
                    $message['status'] = true;
                    $this->response($message, REST_Controller::HTTP_OK);
                }
                else{ 
                    $message = array('message' => 'No data found!.');
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

    public function calculate_lop_post()
    {
        $this->form_validation->set_rules('month', 'Select LOP Calculation Month', 'trim|required');

        if ($this->form_validation->run() === false) 
        {
            $errors = $this->form_validation->error_array();
            $errors['status'] = false;
            $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }

        $lopdata = array();
        $lopdata = $this->input->post();

        $data = $this->accuredleaves_model->CalculateLOP($lopdata);
    }
}