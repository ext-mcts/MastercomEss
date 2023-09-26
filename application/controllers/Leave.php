<?php
defined('BASEPATH') OR exit('No direct script access allowed');


require (APPPATH.'/libraries/REST_Controller.php');

class Leave extends REST_Controller
{

    public $userdetails;

    public function __construct()
    {
        parent::__construct();
        $this->load->model("user_model");
        $this->load->library('Authorization_Token'); 
        $this->load->model("leaves_model");
        $this->load->library('Base64fileUploads');

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

    public function apply_post()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                $_POST = json_decode(file_get_contents("php://input"), true);
                
                $this->form_validation->set_rules('LeaveType', 'Leave Type', 'trim|required');
                $this->form_validation->set_rules('FromDate', 'From Date', 'trim|required');
                $this->form_validation->set_rules('FromSession', 'From Session', 'trim|required');
                $this->form_validation->set_rules('ToDate', 'To Date', 'trim|required');
                $this->form_validation->set_rules('ToSession', 'To Session', 'trim|required');
                $this->form_validation->set_rules('Reason', 'Leave Reason', 'trim|required|max_length[255]');
                //$this->form_validation->set_rules('Manager', 'Select Manager', 'trim|required|numeric');
                $this->form_validation->set_rules('Manager2', 'Select CC', 'trim|max_length[255]');
                $this->form_validation->set_rules('Contact', 'Contact Number', 'trim|required|numeric');
                $this->form_validation->set_rules('Image', 'Image', 'trim');

                if ($this->form_validation->run() === false) 
                {
                    $errors = $this->form_validation->error_array();
                    $errors['status'] = false;
                    $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                    return false;
                }

                /*$startDate = strtotime(date('Y-m-d', strtotime($this->input->post('Date')) ) );
                $currentDate = strtotime(date('Y-m-d'));

                if($startDate < $currentDate) {
                    $message = array('message' => 'Past Dates not allowed to select!');
                    $message['status'] = false;
                    $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
                    return false;
                }*/

                /*$check = $this->leaves_model->check_leaves($this->userdetails->EmployeeID,$this->input->post('Date'));

                if(count($check)==1){
                    $message = array('message' => 'This Employee already applied leave for this date!');
                    $message['status'] = false;
                    $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                    return false;
                }*/

                $leavedata = array();
                $leavedata = $this->input->post();
                $leavedata["EmployeeID"] = $this->userdetails->EmployeeID;
                $leavedata["Manager"] = $this->userdetails->Manager;
                $leavedata["Manager2"] = '';

                if($this->input->post('Manager2')) 
                    $leavedata["Manager2"] = $this->input->post('Manager2');

                $leavedata["LeaveDoc"] = '';
                $leavedata["LeaveDocPath"] = '';

                if(isset($leavedata['Image']))
                {
                    $base64file   = new Base64fileUploads();
                    $return = $base64file->du_uploads('./assets/leave_docs/',trim($leavedata['Image']));
    
                    $leavedata["LeaveDoc"] = $return['file_name'];
                    $leavedata["LeaveDocPath"] = $return['with_path'];
                }
                
                $data = $this->leaves_model->apply_leave($leavedata);

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

                    $empdet = $this->user_model->get_user($leavedata["Manager"]);
                    $leavedet = $this->leaves_model->get_leave($data);
                    $list = '';
                    if($this->input->post('Manager2')) 
                    {
                        $ids = explode(',', $leavedata["Manager2"]);
                        foreach($ids as $id)
                        {
                            $empdet = $this->user_model->get_user($id);
                            $list .= trim($empdet->EmailName).'@mastercom.co.in'.',';
                        }
                        $list = rtrim($list, ',');
                    }
                    
                    $this->load->library('email', $config);
                    $this->email->set_newline("\r\n"); 
                    $getconfigmail = $this->user_model->get_config_email(); 
                    $from_email = $getconfigmail[0]->ConfigEmail;
                    $this->email->from($from_email, 'Mastercom - Leave Application!'); 
                    $this->email->to('aharshavardhan04@gmail.com');
                    //$this->email->to(trim($empdet->EmailName).'@mastercom.co.in',trim($this->userdetails->EmailName).'@mastercom.co.in');
                    //if($this->input->post('Manager2')) 
                        //$this->email->cc($list);
                    $this->email->subject('Leave Application - Details');
                    $message = '<html><body>';
                    $message .= '<h3>Dear '.$empdet->FirstName.',</h3>'; 
                    $message .= '<p> You got leave application from '.$this->userdetails->EmailName.'. Here are the details,</P>';  
                    $message .= '<p>From Date:'.date('d M Y',strtotime($leavedet[0]->LStartDt)).' Session: '.$leavedet[0]->SessionFrom.'</p>';
                    $message .= '<p>To Date:'.date('d M Y',strtotime($leavedet[0]->LFinishDt)).' Session: '.$leavedet[0]->SessionTo.'</p>';
                    $message .= '<p>Total Days:'.$this->CaluculateDaysWithSession($leavedet[0]->LStartDt,$leavedet[0]->SessionFrom,$leavedet[0]->LFinishDt,$leavedet[0]->SessionTo).'</p>';
                    $message .= '<p>Reason:'.$leavedet[0]->Reason.'</p>';
                    $message .= '<p>For more details: <a href="/mcts_extranet-api/leaves">Click Here</a></p>';
                    $message .= '<br><br>';
                    $message .= '<p><h4>Mastercom HR Team.<h4></p>';
                    $message .= '</body></html>';  
                                
                    $this->email->message($message);
                    if(isset($leavedata['Image']))
                    {
                        $atch=base_url().$leavedata["LeaveDocPath"];
                        $this->email->attach($atch);
                    }
                    $this->email->send();

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
                if($this->userdetails->Role==1 || $this->userdetails->Role==3)
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
                            'smtp_pass' => SMTP_PASS,
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
                if($this->userdetails->Role==1 || $this->userdetails->Role==3)
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
                            'smtp_pass' => SMTP_PASS,
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
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role==1 || $this->userdetails->Role==3)
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

                    if($data)
                    {
                        $message = array('message' => 'LOP deleted successfully.');
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
                    $message = array('message' => 'This Role not allowed to Calculate LOP');
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

    public function get_balance_get()
    {
        $leavetype = $this->uri->segment(3);

        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                $data = $this->accuredleaves_model->get_leave_balance($leavetype,$this->userdetails->EmployeeID);

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

    public function pending_leaves_get()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                $data = $this->leaves_model->get_leaves($this->userdetails->EmployeeID,0);
                $i=0;
                foreach($data as $leaves)
                {

                    $days = $this->CaluculateDaysWithSession($leaves->LStartDt,$leaves->SessionFrom,$leaves->LFinishDt,$leaves->SessionTo);
                    
                    $data[$i]->noofdays = $days;
                    $i++;
                }

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

    public function CaluculateDaysWithSession($frmdt,$ses1,$todt,$ses2)
    {
        if($frmdt==$todt)
        {
            if($ses1==$ses2)
            {
                return 0.5;
            }
            if($ses1!=$ses2)
            {
                return 1;
            }
        }

        if($frmdt!=$todt)
        {
            if($ses1==$ses2)
            {
                $now = strtotime($todt);
                $your_date = strtotime($frmdt);
                $datediff = $now - $your_date;
                
                return round($datediff / (60 * 60 * 24))+0.5;
                
            }
            if($ses1!=$ses2)
            {
                $now = strtotime($todt);
                $your_date = strtotime(date('Y-m-d',strtotime('-1 day',strtotime($frmdt))));
                $datediff = $now - $your_date;
                
                return round($datediff / (60 * 60 * 24));
            }
        }
    }

    public function history_get()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                $data = $this->leaves_model->get_leaves($this->userdetails->EmployeeID);
                $i=0;
                foreach($data as $leaves)
                {

                    $days = $this->CaluculateDaysWithSession($leaves->LStartDt,$leaves->SessionFrom,$leaves->LFinishDt,$leaves->SessionTo);
                    
                    $data[$i]->noofdays = $days;
                    if($leaves->Approved==1)
                        $data[$i]->Approved = 'Approved';
                    if($leaves->Approved==2)
                        $data[$i]->Approved = 'Rejected';

                    $i++;
                }

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

    public function leavetypes_get()
	{
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
				$data = $this->leaves_model->get_leave_types($this->userdetails->Gender);

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
                $this->response($decodedToken);
            }
        }
        else {
            $message = array('message' => 'Authentication failed');
            $message['status'] = false;
            $this->response($message, REST_Controller::HTTP_UNAUTHORIZED);
        }
	}

    public function get_manager_get()
    {
        $data = $this->user_model->get_user($this->userdetails->Manager);

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

    public function balance_get()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                $data = $this->accuredleaves_model->get_emp_balance($this->userdetails->EmployeeID);

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

    public function all_emp_summary_get()
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
                if($this->input->get("ProjectID")) $filterdata['ProjectID']=$this->input->get("ProjectID");
                
                $data = $this->accuredleaves_model->get_all_emp_summary($filterdata);

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

    public function my_leaves_get()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                $data = $this->leaves_model->get_emp_leaves($this->userdetails->EmployeeID);

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

    public function upload_leavepolicy_doc_post()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role==1 || $this->userdetails->Role==3 || $this->userdetails->Role==5)
                {
                    $_POST = json_decode(file_get_contents("php://input"), true);

                    $this->form_validation->set_rules('Document', 'Leave Policy Document', 'trim|required');
                    $this->form_validation->set_rules('PolicyName', 'Policy Name', 'trim|required|max_length[100]');
                    $this->form_validation->set_rules('PolicyDesc', 'Policy Description', 'trim|required|max_length[100]');

                    if ($this->form_validation->run() === false) 
                    {
                        $errors = $this->form_validation->error_array();
                        $errors['status'] = false;
                        $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    $policydata = array();
                    $policydata = $this->input->post();

                    $base64file   = new Base64fileUploads();
                    $return = $base64file->du_uploads('./assets/leave_docs/',trim($policydata['Document']));
    
                    $policydata["PolicyDoc"] = $return['file_name'];
                    $policydata["PolicyDocPath"] = $return['with_path'];

                    $data = $this->leaves_model->upload_leave_policy_doc($policydata);

                    if($data)
                    {
                        $message = array('message' => 'Leave Policy Document uploaded successfully!');
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
                    $message = array('message' => 'This Role not allowed to Upload Leave Policy Document!');
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