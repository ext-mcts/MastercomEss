<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Documents_model extends CI_Model {

    public function __construct() {
		
		parent::__construct();
		$this->emptable="mcts_extranet.dbo.documents";
	}

    public function create_docs($data)
    {
        $sql = "INSERT INTO `mcts_extranet`.`dbo.documents` (EmployeeID,Doc_name,Doc_path,Doc_type) 
                VALUES ('".$data['EmployeeID']."','".$data['Doc_name']."','".$data['Doc_path']."','".$data['Doc_type']."')";
        $query=$this->db->query($sql);
        if($query)
            return true;
        else
            return false;
    }

    public function get_document($id)
    {
        $sql = "SELECT * FROM `mcts_extranet`.`dbo.documents` WHERE Document_ID=$id";
        $query=$this->db->query($sql);
		return $query->row();
    }

    public function delete_document($id)
    {
        $sql = "DELETE FROM `mcts_extranet`.`dbo.documents` WHERE Document_ID='$id'";

        $query=$this->db->query($sql);
        if($query==1)
            return true;
        else
            return false;
    }
}