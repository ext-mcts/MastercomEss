<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reviews_model extends CI_Model {

    public function __construct() {
		
		parent::__construct();
		$this->emptable="mcts_extranet.dbo.reviews";
        
	}

    public function add_review($data)
    {
        $sql = "INSERT INTO `mcts_extranet`.`dbo.reviews` (EmployeeID,Review,Manager)
                VALUES('".$data['EmployeeID']."','".$data['Review']."','".$data['Manager']."')";

        $query=$this->db->query($sql);
        if($query)
			return true;
		else
			return false;
    }

    public function add_reply($data)
    {
        $sql = "INSERT INTO `mcts_extranet`.`dbo.reviews` (Review,ReplyFor)
                VALUES('".$data['Review']."','".$data['ReplyFor']."')";

        $query=$this->db->query($sql);
        if($query)
			return true;
		else
			return false;
    }

    public function add_annual_review($data)
    {
        $sql = "INSERT INTO `mcts_extranet`.`dbo.reviews` (EmployeeID,Review,Manager,AnnualReview,ReviewYear)
                VALUES('".$data['EmployeeID']."','".$data['Review']."','".$data['Manager']."','1','".date('Y')."')";

        $query=$this->db->query($sql);
        if($query)
			return true;
		else
			return false;
    }
}