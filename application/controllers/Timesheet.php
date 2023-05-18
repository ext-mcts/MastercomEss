<?php
defined('BASEPATH') OR exit('No direct script access allowed');


require (APPPATH.'/libraries/REST_Controller.php');

class Timesheet extends REST_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model("user_model");
        $this->load->model("locations_model");
        $this->load->model("timesheet_model");
        $this->load->library('Authorization_Token');
        $this->load->model("leaves_model");  
    }

    /* create time sheet API */
    public function add_post()
    {
        //$tsid = $this->uri->segment(3);
        
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                
                $this->form_validation->set_rules('TSDate', 'Time Sheet Date', 'trim|required');
                $this->form_validation->set_rules('Start', 'Start Time', 'trim|required|numeric|max_length[4]');
                $this->form_validation->set_rules('Finish', 'Finish Time', 'trim|required|numeric|max_length[4]');
                $this->form_validation->set_rules('Activity', 'Activity', 'trim|required');
                $this->form_validation->set_rules('ProjectId', 'Project ID', 'trim|required|numeric|max_length[3]');

                // checking date format
                if (!preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])-[0-9]{4}$/",$this->input->post('TSDate'))) {
                    $message = array('message' => 'Timesheet Date format is invalid, please give date format as DD-MM-YYYY');
                    $message['status'] = false;
                    $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                    return false;
                } 
                
                if ($this->form_validation->run() === false) {
                    $errors = $this->form_validation->error_array();
                    $errors['status'] = false;
                    $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                    return false;
                }
                
                $now = time(); // or your date as well
                $your_date = strtotime($this->input->post("TSDate"));
                $datediff = $now - $your_date;
                $days = round($datediff / (60 * 60 * 24));
                
                if($days>7)
                {
                    $message = array('message' => 'Date selection should be with in 7 days!');
                    $message['status'] = false;
                    $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                    return false;
                }

                $tsid = "";
                $tsid .= "TS";
                $tsid .=$this->session->userdata('EmployeeID');
                $tsid .="_".date('YMd',strtotime($this->input->post('TSDate')));
                $tsid .="_".$this->input->post('ProjectId');
                
                //$tsentrycheck = $this->timesheet_model->check_tsentry_bytsid($tsid,$this->input->post('ProjectId'));

                $tsentrycheck = $this->timesheet_model->check_start_finish_time(date('Y-m-d 00:00:00',strtotime($this->input->post('TSDate'))));

                foreach($tsentrycheck as $check)
                {
                    // checking given timings are overlapping for same date
                    if($this->input->post('Start')>=$check->Start && $this->input->post('Finish')<=$check->Finish)
                    {
                        $message = array('message' => 'Same Start & Finish timings given previously for this Date!');
                        $message['status'] = false;
                        $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }
                }

                
                $chunks = str_split($this->input->post('Start'), 2);
                $start_time = implode(':', $chunks).":00";
                $chunks2 = str_split($this->input->post('Finish'), 2);
                $finish_time = implode(':', $chunks2).":00";

                $time1 = strtotime($start_time);
                $time2 = strtotime($finish_time);
                $noofhrs = round(abs($time2 - $time1) / 3600,2);
                

                $tsdata = array();
                $tsdata = $this->input->post();
                $tsdata['TSID'] = $tsid;
                #$tsdata['ProjectId'] = $expld[1];
                $tsdata['NumofHrs'] = $noofhrs;
                
                /* if leave applied on selected date, we are sending alert to report Manager as Email */

                $checkleave = $this->leaves_model->check_leaves($this->session->userdata('EmployeeID'),$this->input->post('TSDate'));

                if(!empty($checkleave))
                {
                    if($checkleave[0]->Approved==1)
                    {
                        $getmanager = $this->user_model->get_user($this->session->userdata('EmployeeID'));
                        $getmanageremail = $this->user_model->get_user($getmanager->Manager);
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
                        
                        $email = $getmanageremail->EmailName;
                        $this->load->library('email', $config);
                        $this->email->set_newline("\r\n"); 
                        $getconfigmail = $this->user_model->get_config_email(); 
                        $from_email = $getconfigmail[0]->ConfigEmail;
                        $this->email->from($from_email, 'Mastercom - Timesheet Entry on Leave Date!'); 
                        $this->email->to($email);
                        $this->email->cc($getmanager->EmailName);
                        $this->email->subject('Timesheet Entry on Leave Date - Details');
                        $message = '<html><body>';
                        $message .= '<h3>Dear '.$getmanageremail->FirstName.',</h3>'; 
                        $message .= '<p> Your employee trying to fill Timesheet on Leave applied Date. Here are the details,</P>';  
                        $message .= '<p>Leave applied Date:'.date('d M Y',strtotime($this->input->post('TSDate'))).'</p>';
                        $message .= '<p>Reason:'.$checkleave[0]->Reason.'</p>';
                        $message .= '<p>Status: Approved</p>';
                        $message .= '<br><br>';
                        $message .= '<p><h4>Mastercom Team.<h4></p>';
                        $message .= '</body></html>';  
                        //echo $message;exit;            
                        $this->email->message($message);
                        $this->email->send();
                    }
                }
                $data = $this->timesheet_model->timesheet_single_entry($tsdata); // creating timesheet

                if($data)
                {
                    $message = array('message' => 'Timesheet entry has been added.');
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
            return false;
        }
    }

    /* update timesheet API */
    public function update_put()
    {
        $tsid = $this->uri->segment(3);
        #$projectid = $this->uri->segment(4);

        if(empty($tsid) || $tsid==0){
            $message = array('message' => 'Timesheet ID required/can not be zero');
            $message['status'] = false;
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }

        // if(empty($projectid) || $projectid==0){
        //     $message = array('message' => 'Project ID required/can not be zero');
        //     $message['status'] = false;
        //     $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
        //     return false;
        // }

        // $tsidcheck = $this->timesheet_model->check_ts_id($tsid);

        // if(count($tsidcheck)==0){
        //     $message = array('message' => 'Timesheet ID not exist');
        //     $message['status'] = false;
        //     $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
        //     return false;
        // }

        // $tsprojcheck = $this->timesheet_model->check_tsentry_bytsid($tsid,$projectid);

        // if(count($tsprojcheck)==0){
        //     $message = array('message' => 'No record found for given Project');
        //     $message['status'] = false;
        //     $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
        //     return false;
        // }

        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                
                $this->form_validation->set_data($this->put());
                $this->form_validation->set_rules('TSDate', 'Time Sheet Date', 'trim|required');
                $this->form_validation->set_rules('Start', 'Start Time', 'trim|required|numeric|max_length[4]');
                $this->form_validation->set_rules('Finish', 'Finish Time', 'trim|required|numeric|max_length[4]');
                $this->form_validation->set_rules('Activity', 'Activity', 'trim|required');
                $this->form_validation->set_rules('ProjectId', 'Project ID', 'trim|required|numeric');


                if ($this->form_validation->run() === false) {
                    $errors = $this->form_validation->error_array();
                    $errors['status'] = false;
                    $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                    return false;
                }

                // checking date format
                if (!preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])-[0-9]{4}$/",$this->put('TSDate'))) {
                    $message = array('message' => 'Timesheet Date format is invalid, please give date format as DD-MM-YYYY');
                    $message['status'] = false;
                    $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                    return false;
                } 
                
                $now = time(); // or your date as well
                $your_date = strtotime($this->put("TSDate"));
                $datediff = $now - $your_date;
                $days = round($datediff / (60 * 60 * 24));
                
                // allowing only before 7 days to update existing timesheet
                if($days>7)
                {
                    $message = array('message' => 'Date selection should be with in 7 days!');
                    $message['status'] = false;
                    $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                    return false;
                }

                $tsentrycheck = $this->timesheet_model->check_start_finish_time(date('Y-m-d 00:00:00',strtotime($this->put('TSDate'))),$tsid);

                foreach($tsentrycheck as $check)
                {
                    // checking given timings are overlapping for same date
                    if($this->put('Start')>=$check->Start && $this->put('Finish')<=$check->Finish)
                    {
                        $message = array('message' => 'Start & Finish timings given previously');
                        $message['status'] = false;
                        $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }
                }

                $chunks = str_split($this->put('Start'), 2);
                $start_time = implode(':', $chunks).":00";
                $chunks2 = str_split($this->put('Finish'), 2);
                $finish_time = implode(':', $chunks2).":00";

                $time1 = strtotime($start_time);
                $time2 = strtotime($finish_time);
                $noofhrs = round(abs($time2 - $time1) / 3600,2);
                

                $tsdata = array();
                $tsdata = $this->put();
                #$tsdata['TSID'] = $tsid;
                #$tsdata['ProjectId'] = $expld[1];
                $tsdata['NumofHrs'] = $noofhrs;

                $data = $this->timesheet_model->update_tsdetails_byproject($tsid,$tsdata); // updating timesheet

                if($data)
                {
                    $message = array('message' => 'Timesheet entry has been updated.');
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
            return false;
        }
    }

    /* Approve timesheet API */
    public function approve_put()
    {
        $tsid = $this->uri->segment(3); // timesheet id
        $status = $this->uri->segment(4); // status 1- approve

        if(empty($tsid) || $tsid==0){
            $message = array('message' => 'Timesheet ID required/can not be zero');
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
                    $data = $this->timesheet_model->accept_reject_timesheet($tsid,$status); // updating status as Approve

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
                       
                        $tsiddet = $this->timesheet_model->check_ts_id($tsid);
                        $tsid = $tsiddet[0]->TSID;
                        $exp = explode('_',$tsid);
                        preg_match("/([a-zA-Z]+)(\\d+)/", $exp[0] , $matches);
                        
                        $empdet = $this->user_model->get_user($matches[2]);
                        $email = $empdet->EmailName;
                        $this->load->library('email', $config);
                        $this->email->set_newline("\r\n"); 
                        $getconfigmail = $this->user_model->get_config_email(); 
                        $from_email = $getconfigmail[0]->ConfigEmail;
                        $this->email->from($from_email, 'Mastercom - Timesheet Approved!'); 
                        $this->email->to($email);
                        $this->email->subject('Timesheet Approved - Details');
                        $message = '<html><body>';
                        $message .= '<h3>Dear '.$empdet->FirstName.',</h3>'; 
                        $message .= '<p> Your Timesheet entry has been approved. Here are the details,</P>';  
                        $message .= '<p>Date:'.date('d M Y',strtotime($tsiddet[0]->TSDate)).'</p>';
                        $message .= '<p>Start Time:'.$tsiddet[0]->Start.'</p>';
                        $message .= '<p>Finish Time:'.$tsiddet[0]->Finish.'</p>';
                        $message .= '<p>Activity:'.$tsiddet[0]->Activity.'</p>';
                        $message .= '<p>For more details: <a href="/mcts_extranet-api/timesheet">Click Here</a></p>';
                        $message .= '<br><br>';
                        $message .= '<p><h4>Mastercom Team.<h4></p>';
                        $message .= '</body></html>';  
                        //echo $message;exit;            
                        $this->email->message($message);
                        $this->email->send();

                        $message = array('message' => 'Timesheet entry has been Approved.');
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
                    $message = array('message' => 'This Role not allowed to Approve/Reject Timesheet entry');
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

    /* Reject Timesheet API */
    public function reject_put()
    {
        $tsid = $this->uri->segment(3); // Timesheet ID
        $status = $this->uri->segment(4); // Status 2-Reject

        if(empty($tsid) || $tsid==0){
            $message = array('message' => 'Timesheet ID required/can not be zero');
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
                    $data = $this->timesheet_model->accept_reject_timesheet($tsid,$status);// updating status as Reject

                    if($data)
                    {
                        /* mail sending with rejecting details */
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
                       
                        $tsiddet = $this->timesheet_model->check_ts_id($tsid);
                        $tsid = $tsiddet[0]->TSID;
                        $exp = explode('_',$tsid);
                        preg_match("/([a-zA-Z]+)(\\d+)/", $exp[0] , $matches);
                        
                        $empdet = $this->user_model->get_user($matches[2]);
                        $email = $empdet->EmailName;
                        $this->load->library('email', $config);
                        $this->email->set_newline("\r\n"); 
                        $getconfigmail = $this->user_model->get_config_email(); 
                        $from_email = $getconfigmail[0]->ConfigEmail;
                        $this->email->from($from_email, 'Mastercom - Timesheet Rejected!'); 
                        $this->email->to($email);
                        $this->email->subject('Timesheet Rejected - Details');
                        $message = '<html><body>';
                        $message .= '<h3>Dear '.$empdet->FirstName.',</h3>'; 
                        $message .= '<p> Your Timesheet entry has been rejected. Here are the details,</P>';  
                        $message .= '<p>Date:'.date('d M Y',strtotime($tsiddet[0]->TSDate)).'</p>';
                        $message .= '<p>Start Time:'.$tsiddet[0]->Start.'</p>';
                        $message .= '<p>Finish Time:'.$tsiddet[0]->Finish.'</p>';
                        $message .= '<p>Activity:'.$tsiddet[0]->Activity.'</p>';
                        $message .= '<p>For more details: <a href="/mcts_extranet-api/timesheet">Click Here</a></p>';
                        $message .= '<br><br>';
                        $message .= '<p><h4>Mastercom Team.<h4></p>';
                        $message .= '</body></html>';  
                        //echo $message;exit;            
                        $this->email->message($message);
                        $this->email->send();

                        $message = array('message' => 'Timesheet entry has been rejected.');
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
                    $message = array('message' => 'This Role not allowed to Approve/Reject Timesheet entry');
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

    /* Timesheet List API */
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

                if($this->input->get("TSID")) $filterdata['TSID']=$this->input->get("TSID");
                if($this->input->get("TSDate"))
                {
                    // checking date format
                    if (!preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])-[0-9]{4}$/",$this->input->get('TSDate'))) {
                        $message = array('message' => 'Time Sheet Date format is invalid, please give date format as DD-MM-YYYY');
                        $message['status'] = false;
                        $this->response($message,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    } 
                    $filterdata['TSDate']=$this->input->get("TSDate");
                } 
                if($this->input->get("Start")) $filterdata['Start']=$this->input->get("Start");
                if($this->input->get("Finish")) $filterdata['Finish']=$this->input->get("Finish");
                if($this->input->get("Location")) $filterdata['Location']=$this->input->get("Location");
                if($this->input->get("ProjectId")) $filterdata['ProjectId']=$this->input->get("ProjectId");
                if($this->input->get("FromDate")) $filterdata['FromDate']=$this->input->get("FromDate");
                if($this->input->get("ToDate")) $filterdata['ToDate']=$this->input->get("ToDate");

                $filterdata['Page'] = $_GET['Page'];

                $data = $this->timesheet_model->get_all_timesheet_entries($filterdata); // getting all timesheets
                
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

    /* Delete Timesheet API */
    public function index_delete()
    {
        $tsid = $this->uri->segment(3); //Timesheet ID

        if(empty($tsid) || $tsid==0){
            $message = array('message' => 'Timesheet ID required/can not be zero');
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
                    $data = $this->timesheet_model->delete_timesheet($tsid); // deleting Timesheet

                    if($data)
                    {
                        $message = array('message' => 'Timesheet deleted successfully.');
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
                    $message = array('message' => 'This Role not allowed to delete Timesheet');
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
