<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_auth{

    function __construct()
	{
        $this->load->model('users_Model');

    }

    function auth($username,$password)
    {
        $valid = $this->users_Model->validate($username,$password);
        return true;
    }
}

?>