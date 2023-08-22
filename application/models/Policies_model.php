<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Policies_model extends CI_Model {

    public function __construct() {
		
		parent::__construct();
		
	}

    public function get_mypolicies($role)
    {
        $sql = "SELECT pt.name as PolicyType,PolicyName,PolicyDesc,PolicyDoc,PolicyDocPath FROM mcts_extranet.`dbo.policies` p
                LEFT JOIN mcts_extranet.`dbo.policytypes` pt ON P.PolicyType=pt.id 
                WHERE PolicyFor = '$role'";

        $query=$this->db->query($sql);
        return $query->result();
    }
}