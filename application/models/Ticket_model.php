<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ticket_model extends CI_Model {

    public function __construct() {
		
		parent::__construct();
		$this->emptable="mcts_extranet.dbo.accuredleaves";
        $this->load->model("user_model");
	}

    public function create_ticket($data)
    {
        $location = $this->locations_model->get_location_by_id($data['location']);

        $sql = "INSERT INTO mcts_extranet.`dbo.ticketmaster2` 
                (TktCategory,AssignedToDept,AssignedToPerson,TktSubject,TktDescription,TktStatus,RaisedBy,Location,
                Priority,ContactNo,TktCreatedDt,TntCloseDt) 
                VALUES ('".$data['category']."','".$data['dept']."','".$data['deptperson']."','".$data['subject']."',
                        '".$data['description']."','Open','".$this->session->userdata('EmailName')."','".$location[0]->LocationName."',
                        '".$data['priority']."','".$data['contact']."','".date('Y-m-d h:i:s')."',
                        '".date('Y-m-d H:i:s', strtotime(date('Y-m-d h:i:s') . ' +1 day'))."')";

        $query=$this->db->query($sql);
        if($query==1)
            return true;
        else
            return false;
    }

    public function get_ticket_by_id($id)
    {
        $sql = "SELECT * FROM `mcts_extranet`.`dbo.ticketmaster2` WHERE TktNo='$id'";
        $query = $this->db->query($sql);
        return $query->result();
    }

    public function update_ticket($data,$tktid)
    {
        $sql = "UPDATE mcts_extranet.`dbo.ticketmaster2` SET 
                TktCategory='".$data['category']."',AssignedToDept='".$data['dept']."',AssignedToPerson='".$data['deptperson']."',
                TktSubject='".$data['subject']."',TktDescription='".$data['description']."',Priority='".$data['priority']."',
                ContactNo='".$data['contact']."'
                WHERE TktNo=$tktid";

        $query=$this->db->query($sql);
        if($query==1)
            return true;
        else
            return false;
    }

    public function delete_ticket($tktid)
    {
        $sql = "DELETE FROM `mcts_extranet`.`dbo.ticketmaster2` WHERE TktNo='$tktid'";

        $query=$this->db->query($sql);
        if($query==1)
            return true;
        else
            return false;
    }

    public function get_all_tickets($data)
    {
        $sql = "SELECT * FROM `mcts_extranet`.`dbo.ticketmaster2`";
		if(!empty($data))
		{
			$page = (isset($data['Page']) && is_numeric($data['Page']) ) ? $data['Page'] : 1;
  			$paginationStart = ($page - 1) * PER_PAGE_RECORDS;
            $wherecond = '';
			$wherecond .= '1=1 AND';
			if(!empty($data['status'])) $wherecond .= " TktStatus='".$data['status']."' AND";
			if(!empty($data['priority']))	$wherecond .= " Priority='".$data['priority']."' AND";
        
			$wherecond = rtrim($wherecond, ' AND');
			$sql = "SELECT * FROM `mcts_extranet`.`dbo.ticketmaster2` WHERE $wherecond ORDER BY TktNo ASC LIMIT $paginationStart,".PER_PAGE_RECORDS;
		}
       
		$query=$this->db->query($sql);
		return $query->result();
    }
}