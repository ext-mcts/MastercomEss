<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Form_validation extends CI_Form_validation {  

     public function __construct() {
          parent::__construct();
          $this->CI =   &get_instance();
          $this->CI->load->model("locations_model");
     }

     public function WorkLocation_check($str){
        $all = $this->CI->locations_model->get_all_locations($str);
        if(count($all)==1){
            return TRUE;
        }
        else {
            $this->form_validation->set_message('WorkLocation_check', 'Invalid Location.');
            return FALSE;
        }

     }
}