<?php
defined('BASEPATH') OR exit('No direct script access allowed');


require (APPPATH.'/libraries/REST_Controller.php');

class Reviews extends REST_Controller
{

    public $userdetails;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('Authorization_Token'); 
        $this->load->model('reviews_model');
        $this->load->model('user_model');

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

    public function add_post()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                $_POST = json_decode(file_get_contents("php://input"), true);

                $this->form_validation->set_rules('Accomplishments', 'Accomplishments', 'trim|required|max_length[1000]');
                $this->form_validation->set_rules('AreasOfImprovement', 'Area of Improvement', 'trim|required|max_length[1000]');
                $this->form_validation->set_rules('CurrentSkillSet', 'Current Skill Set', 'trim|required|max_length[1000]');
                $this->form_validation->set_rules('NewInitiatives', 'New Initiatives', 'trim|required|max_length[1000]');
                $this->form_validation->set_rules('AddlResponsibilities', 'Additional Responsibilities', 'trim|required|max_length[1000]');
                $this->form_validation->set_rules('Action', 'Action', 'trim|required|max_length[10]');
                
                if ($this->form_validation->run() === false) 
                {
                    $errors = $this->form_validation->error_array();
                    $errors['status'] = false;
                    $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                    return false;
                }

                $revdata = array();
                $revdata = $this->input->post();
                $revdata["EmployeeID"] = $this->userdetails->EmployeeID;
                $revdata["Manager"] = $this->userdetails->Manager;

                $data = $this->reviews_model->add_review($revdata);

                if($revdata["Action"]=='submit')
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

                    $data2 = $this->reviews_model->get_emp_submitted_reviews($this->userdetails->EmployeeID);

                    $this->load->library('email', $config);
                    $this->email->set_newline("\r\n"); 
                    $getconfigmail = $this->user_model->get_config_email(); 
                    $from_email = $getconfigmail[0]->ConfigEmail;
                    $this->email->from($from_email, 'Mastercom - Review Submitted!'); 
                    $this->email->to('aharshavardhan04@gmail.com');
                    //$this->email->to(trim($empdet->EmailName).'@mastercom.co.in',trim($this->userdetails->EmailName).'@mastercom.co.in');
                    $this->email->subject('Review Submitted - Details');
                    $message = '<html><body>';
                    $message .= '<h3>Dear '.$empdet->FirstName.',</h3>'; 
                    $message .= '<p> You got Review from '.$this->userdetails->EmailName.'. Here are the details,</P>';
                    foreach($data2 as $rev)
                    {
                        $message .= '<h4>Review Date:'.date('d M Y', strtotime($rev->CreatedDate)).'</h4>';
                        $message .= '<p><b>Accomplishments:</b>'.$rev->Accomplishments.'</p>';
                        $message .= '<p><b>Areas of Improvement:</b>'.$rev->AreasOfImprovement.'</p>';
                        $message .= '<p><b>Current Skill set:</b>'.$rev->CurrentSkillSet.'</p>';
                        $message .= '<p><b>New Initiatives:</b>'.$rev->NewInitiatives.'</p>';
                        $message .= '<p><b>Additional Responsibilities:</b>'.$rev->AddlResponsibilities.'</p>';
                        $message .= '<br>';
                    }  
                    $message .= '<br><br>';
                    $message .= '<p><h4>Mastercom HR Team.<h4></p>';
                    $message .= '</body></html>';  
                                
                    $this->email->message($message);
                    $this->email->send();

                    $message = array('message' => 'Review posted successfully.');
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

    public function add_comment_put()
    {
        $revid = $this->uri->segment(3);

        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role==3)
                {
                    $_POST = json_decode(file_get_contents("php://input"), true);

                    $this->form_validation->set_data($this->put());
                    
                    $this->form_validation->set_rules('Comments', 'Comments', 'trim|required|max_length[1000]');
                    $this->form_validation->set_rules('Action', 'Action', 'trim|required|max_length[10]');

                    if ($this->form_validation->run() === false) {
                        $errors = $this->form_validation->error_array();
                        $errors['status'] = false;
                        $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                        return false;
                    }

                    $revdata = array();
                    $revdata = $this->put();
                    $revdata['ReplyFor'] = $revid;

                    $data = $this->reviews_model->add_comment($revdata,$revid);

                    if($data)
                    {
                        $message = array('message' => 'Commented successfully!.');
                        $message['status'] = true;
                        $this->response($message, REST_Controller::HTTP_OK);
                        return false;
                    }
                    else{ 
                        $message = array('message' => 'No records found!.');
                        $message['status'] = false;
                        $this->response($message, REST_Controller::HTTP_OK);
                        return false;
                    }

                }
                else {
                    $message = array('message' => 'This Role not allowed to add comments!');
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
        }
    }

    public function annual_review_post()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                $_POST = json_decode(file_get_contents("php://input"), true);

                $this->form_validation->set_rules('EmployeeID', 'Employee', 'trim|required|numeric');
                $this->form_validation->set_rules('Accomplishments', 'Accomplishments', 'trim|required|max_length[1000]');
                $this->form_validation->set_rules('AreasOfImprovement', 'Area of Improvement', 'trim|required|max_length[1000]');
                $this->form_validation->set_rules('CurrentSkillSet', 'Current Skill Set', 'trim|required|max_length[1000]');
                $this->form_validation->set_rules('KRAOfTheYear', 'KRA Of The Year', 'trim|required|max_length[1000]');
                $this->form_validation->set_rules('OverAllRating', 'Over All Rating', 'trim|required|max_length[45]');

                if ($this->form_validation->run() === false) 
                {
                    $errors = $this->form_validation->error_array();
                    $errors['status'] = false;
                    $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                    return false;
                }

                $revdata = array();
                $revdata = $this->input->post();
                $revdata["Manager"] = $this->userdetails->EmployeeID;

                $data = $this->reviews_model->add_annual_review($revdata);

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

                    $empdet = $this->user_model->get_hr();
                    $empdet2 = $this->user_model->get_user($revdata["EmployeeID"]);
                    $this->load->library('email', $config);
                    $this->email->set_newline("\r\n"); 
                    $getconfigmail = $this->user_model->get_config_email(); 
                    $from_email = $getconfigmail[0]->ConfigEmail;
                    $this->email->from($from_email, 'Mastercom - Annual Review!'); 
                    $this->email->to('aharshavardhan04@gmail.com');
                    //$this->email->to(trim($empdet->EmailName).'@mastercom.co.in',trim($this->userdetails->EmailName).'@mastercom.co.in');
                    $this->email->subject('Annual Review Submitted - Details');
                    $message = '<html><body>';
                    $message .= '<h3>Dear '.$empdet->FirstName.',</h3>'; 
                    $message .= '<p> You got Annual Review from '.$this->userdetails->EmailName.'. Here are the details,</P>';
                    $message .= '<p>Here are the details,</P>';
                    $message .= '<h4>Review Date: '.date('d M Y').'</h4>';
                    $message .= '<p><b>Employee Name: </b>'.$empdet2->FirstName.'</p>';
                    $message .= '<p><b>Accomplishments: </b>'.$revdata["Accomplishments"].'</p>';
                    $message .= '<p><b>Areas of Improvement: </b>'.$revdata["AreasOfImprovement"].'</p>';
                    $message .= '<p><b>Current Skill set: </b>'.$revdata["CurrentSkillSet"].'</p>';
                    $message .= '<p><b>KRA Of The Year: </b>'.$revdata["KRAOfTheYear"].'</p>';
                    $message .= '<p><b>Over All Rating: </b>'.$revdata["OverAllRating"].'</p>';
                    $message .= '<br>';
                    $message .= '<p>Please Accept/Reject Review.</p>';
                    $message .= '<br><br>';
                    $message .= '<p><h4>Mastercom HR Team.<h4></p>';
                    $message .= '</body></html>';  
                                
                    $this->email->message($message);
                    $this->email->send();

                    $message = array('message' => 'Annual Appraisal done!.');
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

    public function update_put()
    {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                $revid = $this->uri->segment(3);

                $check = $this->reviews_model->check_review($revid);

                if($check){
                    $message = array('message' => 'This Review has been submitted, unable to update!');
                    $message['status'] = false;
                    $this->response($message, REST_Controller::HTTP_OK);
                    return false;
                }

                $_POST = json_decode(file_get_contents("php://input"), true);
                $this->form_validation->set_data($this->put());
                $this->form_validation->set_rules('Accomplishments', 'Accomplishments', 'trim|required|max_length[1000]');
                $this->form_validation->set_rules('AreasOfImprovement', 'Area of Improvement', 'trim|required|max_length[1000]');
                $this->form_validation->set_rules('CurrentSkillSet', 'Current Skill Set', 'trim|required|max_length[1000]');
                $this->form_validation->set_rules('NewInitiatives', 'New Initiatives', 'trim|required|max_length[1000]');
                $this->form_validation->set_rules('AddlResponsibilities', 'Additional Responsibilities', 'trim|required|max_length[1000]');

                if ($this->form_validation->run() === false) {
                    $errors = $this->form_validation->error_array();
                    $errors['status'] = false;
                    $this->response($errors,REST_Controller::HTTP_BAD_REQUEST);
                    return false;
                }

                $revdata = array();
                $revdata = $this->put();

                $data = $this->reviews_model->update_review($revdata,$revid);

                if($data)
                {
                    $message = array('message' => 'Review Updated successfully!');
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
            return false;
        }
    }

    public function manager_view_reviews_get()
    {
        $empid = $this->uri->segment(3);

        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role==3)
                {
                    $data = $this->reviews_model->get_emp_submitted_reviews($empid);

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
                    $message = array('message' => 'This Role not allowed to view Employees Reviews!');
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

    public function hr_view_reviews_get()
    {
        $manid = $this->uri->segment(3);

        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) 
        {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if($this->userdetails->Role==5)
                {
                    $data = $this->reviews_model->get_manager_submitted_reviews($manid);

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
                    $message = array('message' => 'This Role not allowed to view Manager Reviews!');
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

    public function accept_review_put()
    {
        $revid = $this->uri->segment(3); // Review ID
        $status = $this->uri->segment(4); // status -1 for accept

        $this->form_validation->set_data($this->put());
        $this->form_validation->set_rules('Reason', 'Accept Reason', 'trim|required|max_length[255]');

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
                if($this->userdetails->Role==5)
                {
                    $rdata = $this->put();
                    $data = $this->reviews_model->accept_reject_review($revid,$status,$rdata);

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

                        $revdet = $this->reviews_model->get_review($revid);
                       
                        $empdet = $this->user_model->get_user($revdet->Manager);

                        $empdet2 = $this->user_model->get_user($revdet->EmployeeID);

                        $this->load->library('email', $config);
                        $this->email->set_newline("\r\n"); 
                        $getconfigmail = $this->user_model->get_config_email(); 
                        $from_email = $getconfigmail[0]->ConfigEmail;
                        $this->email->from($from_email, 'Mastercom - Review Accepted!'); 
                        $this->email->to('aharshavardhan04@gmail.com');
                        //$this->email->to(trim($empdet->EmailName).'@mastercom.co.in',trim($this->userdetails->EmailName).'@mastercom.co.in');
                        $this->email->subject('Review Accepted - Details');
                        $message = '<html><body>';
                        $message .= '<h3>Dear '.$empdet->FirstName.',</h3>'; 
                        $message .= '<p> Your Annual Review for '.$empdet2->FirstName.' has been accepted. Here are the some comments from HR,</P>';
                        $message .= '<p><b>Accept Comments: </b>'.$rdata["Reason"].'</p>';
                        $message .= '<br>';
                        $message .= '<br><br>';
                        $message .= '<p><h4>Mastercom HR Team.<h4></p>';
                        $message .= '</body></html>';  
                                    
                        $this->email->message($message);
                        $this->email->send();

                        $message = array('message' => 'Review Accepted!.');
                        $message['status'] = true;
                        $this->response($message, REST_Controller::HTTP_OK);
                        return false;
                    }
                    else{ 
                        $message = array('message' => 'Something went wrong!.');
                        $message['status'] = false;
                        $this->response($message, REST_Controller::HTTP_OK);
                    }
                }
                else 
                {
                    $message = array('message' => 'This Role not allowed to Approve/Reject Review');
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

    public function reject_review_put()
    {
        $revid = $this->uri->segment(3); // Review ID
        $status = $this->uri->segment(4); // status -1 for accept

        $this->form_validation->set_data($this->put());
        $this->form_validation->set_rules('Reason', 'Accept Reason', 'trim|required|max_length[255]');

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
                if($this->userdetails->Role==5)
                {
                    $rdata = $this->put();
                    $data = $this->reviews_model->accept_reject_review($revid,$status,$rdata);

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

                        $revdet = $this->reviews_model->get_review($revid);
                       
                        $empdet = $this->user_model->get_user($revdet->Manager);

                        $empdet2 = $this->user_model->get_user($revdet->EmployeeID);

                        $this->load->library('email', $config);
                        $this->email->set_newline("\r\n"); 
                        $getconfigmail = $this->user_model->get_config_email(); 
                        $from_email = $getconfigmail[0]->ConfigEmail;
                        $this->email->from($from_email, 'Mastercom - Review Rejected!'); 
                        $this->email->to('aharshavardhan04@gmail.com');
                        //$this->email->to(trim($empdet->EmailName).'@mastercom.co.in',trim($this->userdetails->EmailName).'@mastercom.co.in');
                        $this->email->subject('Review Rejected - Details');
                        $message = '<html><body>';
                        $message .= '<h3>Dear '.$empdet->FirstName.',</h3>'; 
                        $message .= '<p> Your Annual Review for '.$empdet2->FirstName.' has been rejected. Here are the some comments from HR,</P>';
                        $message .= '<p><b>Reject Reason: </b>'.$rdata["Reason"].'</p>';
                        $message .= '<p>So, please review again and re-submit.</p>';
                        $message .= '<br>';
                        $message .= '<br><br>';
                        $message .= '<p><h4>Mastercom HR Team.<h4></p>';
                        $message .= '</body></html>';  
                                    
                        $this->email->message($message);
                        $this->email->send();

                        $message = array('message' => 'Review Rejected!.');
                        $message['status'] = true;
                        $this->response($message, REST_Controller::HTTP_OK);
                        return false;
                    }
                    else{ 
                        $message = array('message' => 'Something went wrong!.');
                        $message['status'] = false;
                        $this->response($message, REST_Controller::HTTP_OK);
                    }
                }
                else 
                {
                    $message = array('message' => 'This Role not allowed to Approve/Reject Review');
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