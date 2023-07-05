<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Leaves_model extends CI_Model {

    public function __construct() {
		
		parent::__construct();
		$this->emptable="mcts_extranet.dbo.leave";
	}

    public function apply_leave($data)
    {
        $sql = "INSERT INTO `mcts_extranet`.`dbo.leave` (EmployeeID,LStartDt,Reason) 
                VALUES ('".$data['EmployeeID']."','".date('Y-m-d',strtotime($data['Date']))."','".$data['Reason']."')";

        $query=$this->db->query($sql);
        if($query)
            return true;
        else
            return false;
    }

    public function check_leaves($empid,$date)
    {
        $sql = "SELECT * FROM `mcts_extranet`.`dbo.leave` WHERE EmployeeID=$empid AND LStartDt='".date('Y-m-d', strtotime($date))."'";
        $query = $this->db->query($sql);
        return $query->result();
    }

    public function delete_leave($id)
    {
        $sql = "DELETE FROM `mcts_extranet`.`dbo.leave` WHERE LeaveID=$id";

        $query=$this->db->query($sql);
        if($query==1)
            return true;
        else
            return false;
    }

    public function get_leave($id)
    {
        $sql = "SELECT * FROM `mcts_extranet`.`dbo.leave` WHERE LeaveID=$id";
        $query = $this->db->query($sql);
        return $query->result();
    }

    public function accept_reject_leave($leaveid,$status,$data=null)
    {
        $where = '';
        if($data)
            $where = ",RejectReason='".$data['RejectReason']."'";

        $sql = "UPDATE `mcts_extranet`.`dbo.leave` SET
                Approved='$status' $where
                WHERE LeaveID='$leaveid'";
            
        $query=$this->db->query($sql);

        if($status==1)
        {
            $leave = $this->get_leave($leaveid);
        }

        if($query==1)
            return true;
        else
            return false;
    }

    public function get_all_leaves($data=null)
    {
        $sql = "SELECT EmployeeID,count(*) FROM `mcts_extranet`.`dbo.leave` WHERE Approved=1 GROUP BY EmployeeID";

        if(!empty($data))
		{
            $page = (isset($data['Page']) && is_numeric($data['Page']) ) ? $data['Page'] : 1;
            $paginationStart = ($page - 1) * PER_PAGE_RECORDS;
            $wherecond = '1=1 AND';
            $wherecond .= " Approved=1 AND";
            if(!empty($data['EmployeeID'])) $wherecond .= " EmployeeID=".$data['EmployeeID']." AND";

            $wherecond = rtrim($wherecond, ' AND');
			$sql = "SELECT EmployeeID,count(*) AS ApprovedLeaves FROM `mcts_extranet`.`dbo.leave` WHERE $wherecond GROUP BY EmployeeID LIMIT $paginationStart,".PER_PAGE_RECORDS;
        }

        $query=$this->db->query($sql);
		return $query->result();
    }

    public function getLeavesByEmpID($empid,$status,$month)
    {
        $sql = "SELECT count(*) as leaves FROM `mcts_extranet`.`dbo.leave` WHERE EmployeeID='$empid' AND Approved='$status' AND MONTH(LStartDt) = '$month'";
        $query=$this->db->query($sql);
		return $query->row();
    }
}