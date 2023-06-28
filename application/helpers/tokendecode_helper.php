<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

    function decode_token($token)
    {
        //$token = $this->input->get_request_header('Authorization');
        $tokenParts = explode(".", $token);  
        $tokenHeader = base64_decode($tokenParts[0]);
        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtHeader = json_decode($tokenHeader);
        $jwtPayload = json_decode($tokenPayload);

        return $jwtPayload;
    }
?>