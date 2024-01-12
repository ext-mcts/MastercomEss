<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Leaves_model extends CI_Model {

    public function __construct() {
		
		parent::__construct();
		$this->emptable="mcts_extranet.dbo.leave";
	}

    public function apply_leave($data)
    {
        $reqData = $data;
        // $manager2 = $data['Manager2'];
        // $optIdValues = [];

        // Iterate through the array of objects
        // foreach ($manager2 as $object) {
        //     // Check if the 'opt_id' key exists in the object
        //     if (isset($object->opt_id)) {
        //         // Add the 'opt_id' value to the optIdValues array
        //         $optIdValues[] = $object->opt_id;
        //     }
        // }

        // Use the implode function to concatenate the values into a comma-separated string
        // $resultString = implode(',', $optIdValues);

        $sql = "INSERT INTO `mcts_extranet`.`dbo.leave` (EmployeeID,LStartDt,SessionFrom,LFinishDt,SessionTo,Reason,LeaveType,
                            Manager,Manager2,Contact,LeaveDoc,LeaveDocPath) 
                VALUES ('".$data['EmployeeID']."','".date('Y-m-d',strtotime($data['FromDate']))."','".$data['FromSession']."',
                        '".date('Y-m-d',strtotime($data['ToDate']))."','".$data['ToSession']."','".$data['Reason']."',
                        '".$data['LeaveType']."','".$data['Manager']."','".$data['Manager2']."','".$data['Contact']."',
                        '".$data['LeaveDoc']."','".$data['LeaveDocPath']."')";

        $query=$this->db->query($sql);
        if($query)
            return $this->db->insert_id();
        else
            return false;
        // return $data;
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

    public function get_leaves($empid,$leavestatus=null)
    {
        $sql = "SELECT LStartDt, SessionFrom,LFinishDt,SessionTo,Reason,AppliedDate,e.FirstName,lt.LeaveType,l.Approved
                from mcts_extranet.`dbo.leave` l
                LEFT JOIN mcts_extranet.`dbo.employees` e ON l.Manager=e.EmployeeID
                LEFT JOIN mcts_extranet.`dbo.leavetypes` lt ON l.LeaveType=lt.id
                WHERE l.EmployeeID='$empid'";
        // $sql = "SELECT l.LeaveID LeaveID, e.FirstName Name,  date(l.AppliedDate) AppliedOn, CONCAT(l.LStartDt, ' (',case l.SessionFrom when 1 then 'Session 1'
        // when 2 then 'Session 2' end ,')') FromDuration, CONCAT(l.LFinishDt, ' (', case l.SessionTo when 1 then 'Session 1' when 2 then 'Session 2' end ,')')
        // ToDuration, l.Reason FROM mcts_extranet.`dbo.leave` AS l, mcts_extranet.`dbo.employees` AS e WHERE e.EmployeeID = l.EmployeeID AND l.Manager = '$empid'
        // AND l.Approved = '0' ";
        
        if($leavestatus!='')
        {
            $sql .= "AND Approved='$leavestatus'";
        }
        if($leavestatus=='')
        {
            $sql .= "AND (Approved=1 OR Approved=2)";
        }

        $query=$this->db->query($sql);
		return $query->result();
    }

    public function get_leave_types($gender)
    {
        if($gender=='Male')
            $sql = "SELECT * FROM mcts_extranet.`dbo.leavetypes` WHERE LeaveType!='Maternity Leave'";

        if($gender=='Female')
            $sql = "SELECT * FROM mcts_extranet.`dbo.leavetypes` WHERE LeaveType!='Paternity Leave'";

        $query=$this->db->query($sql);
		return $query->result();
    }

    public function get_emp_leaves($empid)
    {
        /*$sql = "SELECT LeaveID, LStartDt, SessionFrom, LFinishDt, SessionTo, Reason, lt.LeaveType, e.FirstName as Manager,
                    case l.Approved
                        when '1' then 'Approved'
                        when '0' then 'Pending'
                        when '2' then 'Rejected'
                    end as Status from mcts_extranet.`dbo.leave` l
                left join mcts_extranet.`dbo.employees` e on l.Manager=e.EmployeeID
                left join mcts_extranet.`dbo.leavetypes` lt on l.LeaveType=lt.id
                where l.EmployeeID='$empid'";*/

        $sql =  "SELECT lt.LeaveType, e.FirstName AppliedTo , date(l.AppliedDate) AppliedOn, CONCAT(l.LStartDt, ' (',case l.SessionFrom when 1 then 'Session 1'
                when 2 then 'Session 2' end ,')') StartDuration,
                CONCAT(l.LFinishDt, ' (', case l.SessionTo when 1 then 'Session 1' when 2 then 'Session 2' end ,')') EndDuration, l.Reason, case l.Approved when 0 then 'Pending' when 1 then 'Approved' when 2 then 'Rejected' end Status
                FROM mcts_extranet.`dbo.leave` AS l, mcts_extranet.`dbo.leavetypes` AS lt, mcts_extranet.`dbo.employees` AS e
                WHERE l.EmployeeID = '$empid' AND l.LeaveType = lt.id AND l.Manager = e.EmployeeID
                ORDER BY l.LeaveID asc";

        $query=$this->db->query($sql);
		return $query->result();
    }

    public function upload_leave_policy_doc($data)
    {
        $sql = "INSERT INTO mcts_extranet.`dbo.policies` (PolicyType,PolicyName,PolicyDesc,PolicyDoc,PolicyDocPath) 
                VALUES (2,'".$data['PolicyName']."','".$data['PolicyDesc']."',
                        '".$data['PolicyDoc']."','".$data['PolicyDocPath']."')";

        $query=$this->db->query($sql);
        if($query)
            return $this->db->insert_id();
        else
            return false;
    }
    public function get_emp_pending_leaves($manager)
    {
        $sql = "SELECT l.LeaveID LeaveID, lt.LeaveType, e.FirstName,CONCAT(l.LStartDt, ' (',case l.SessionFrom when 1 then 'Session 1'
                when 2 then 'Session 2' end ,')') StartDuration,
                CONCAT(l.LFinishDt, ' (', case l.SessionTo when 1 then 'Session 1' when 2 then 'Session 2' end ,')') EndDuration, l.Reason
                FROM mcts_extranet.`dbo.leave` AS l, mcts_extranet.`dbo.leavetypes` AS lt, mcts_extranet.`dbo.employees` AS e
                WHERE l.Approved = '0' AND (l.Manager = '$manager' OR Manager2 IN ('$manager')) AND l.LeaveType = lt.id AND l.EmployeeID = e.EmployeeID
                ORDER BY l.LeaveID asc";

        $query=$this->db->query($sql);
        return $query->result();
    }


    // suhas

    //LeaveDetails
    public function get_emp_Leaves_details($empid)
    {

        $sql1= "SELECT 
        SUM(CASE WHEN l.Approved = 0 THEN 1 ELSE 0 END) AS pendingLeaves,
        SUM(CASE WHEN l.Approved = 1 THEN 1 ELSE 0 END) AS approvedLeaves,
        SUM(CASE WHEN l.Approved = 2 THEN 1 ELSE 0 END) AS rejectedLeaves,
        lt.LeaveType AS leaveType
        FROM mcts_extranet.`dbo.leave` AS l, mcts_extranet.`dbo.leavetypes` AS lt  WHERE l.EmployeeID = '$empid' and l.LeaveType = '1' and l.LeaveType = lt.id";
        $query1=$this->db->query($sql1)->result();
        if ($query1[0]->leaveType == null) {
            $query1[0]->leaveType = 'Paternity Leave';
        }

        $sql2= "SELECT 
        SUM(CASE WHEN l.Approved = 0 THEN 1 ELSE 0 END) AS pendingLeaves,
        SUM(CASE WHEN l.Approved = 1 THEN 1 ELSE 0 END) AS approvedLeaves,
        SUM(CASE WHEN l.Approved = 2 THEN 1 ELSE 0 END) AS rejectedLeaves,
        lt.LeaveType AS leaveType
        FROM mcts_extranet.`dbo.leave` AS l, mcts_extranet.`dbo.leavetypes` AS lt  WHERE l.EmployeeID = '$empid' and l.LeaveType = '2' and l.LeaveType = lt.id";
        $query2=$this->db->query($sql2)->result();
        if ($query2[0]->leaveType == null) {
            $query2[0]->leaveType = 'Loss of Pay';
        }

        $sql3= "SELECT 
        SUM(CASE WHEN l.Approved = 0 THEN 1 ELSE 0 END) AS pendingLeaves,
        SUM(CASE WHEN l.Approved = 1 THEN 1 ELSE 0 END) AS approvedLeaves,
        SUM(CASE WHEN l.Approved = 2 THEN 1 ELSE 0 END) AS rejectedLeaves,
        lt.LeaveType AS leaveType
        FROM mcts_extranet.`dbo.leave` AS l, mcts_extranet.`dbo.leavetypes` AS lt  WHERE l.EmployeeID = '$empid' and l.LeaveType = '3' and l.LeaveType = lt.id";
        $query3=$this->db->query($sql3)->result();
        if ($query3[0]->leaveType == null) {
            $query3[0]->leaveType = 'Comp Off';
        }

        $sql4= "SELECT 
        SUM(CASE WHEN l.Approved = 0 THEN 1 ELSE 0 END) AS pendingLeaves,
        SUM(CASE WHEN l.Approved = 1 THEN 1 ELSE 0 END) AS approvedLeaves,
        SUM(CASE WHEN l.Approved = 2 THEN 1 ELSE 0 END) AS rejectedLeaves,
        lt.LeaveType AS leaveType
        FROM mcts_extranet.`dbo.leave` AS l, mcts_extranet.`dbo.leavetypes` AS lt  WHERE l.EmployeeID = '$empid' and l.LeaveType = '4' and l.LeaveType = lt.id";
        $query4=$this->db->query($sql4)->result();
        if ($query4[0]->leaveType == null) {
            $query4[0]->leaveType = 'Paternity Leave';
        }

        $sql5= "SELECT 
        SUM(CASE WHEN l.Approved = 0 THEN 1 ELSE 0 END) AS pendingLeaves,
        SUM(CASE WHEN l.Approved = 1 THEN 1 ELSE 0 END) AS approvedLeaves,
        SUM(CASE WHEN l.Approved = 2 THEN 1 ELSE 0 END) AS rejectedLeaves,
        lt.LeaveType AS leaveType
        FROM mcts_extranet.`dbo.leave` AS l, mcts_extranet.`dbo.leavetypes` AS lt  WHERE l.EmployeeID = '$empid' and l.LeaveType = '5' and l.LeaveType = lt.id";
        $query5=$this->db->query($sql5)->result();
        if ($query5[0]->leaveType == null) {
            $query5[0]->leaveType = 'Maternity Leave';
        }

        $sql7= "SELECT 
        SUM(CASE WHEN Approved = 0 THEN 1 ELSE 0 END) AS pendingLeaves,
        SUM(CASE WHEN Approved = 1 THEN 1 ELSE 0 END) AS approvedLeaves,
        SUM(CASE WHEN Approved = 2 THEN 1 ELSE 0 END) AS rejectedLeaves
        FROM mcts_extranet.`dbo.leave`  WHERE EmployeeID = '$empid'";
        $query7=$this->db->query($sql7)->result();
        $query7[0]->leaveType = 'Total Leaves';

        $data = array($query1[0],$query2[0],$query3[0],$query4[0],$query5[0],$query7[0]);
		return $data;
    }

    //LeaveDetailsByEmpID
    public function get_emp_Leaves_detailsByID($empid)
    {

        $sql1= "SELECT 
        SUM(CASE WHEN l.Approved = 0 THEN 1 ELSE 0 END) AS pendingLeaves,
        SUM(CASE WHEN l.Approved = 1 THEN 1 ELSE 0 END) AS approvedLeaves,
        SUM(CASE WHEN l.Approved = 2 THEN 1 ELSE 0 END) AS rejectedLeaves,
        lt.LeaveType AS leaveType
        FROM mcts_extranet.`dbo.leave` AS l, mcts_extranet.`dbo.leavetypes` AS lt  WHERE l.EmployeeID = '$empid' and l.LeaveType = '1' and l.LeaveType = lt.id";
        $query1=$this->db->query($sql1)->result();
        if ($query1[0]->leaveType == null) {
            $query1[0]->leaveType = 'Paternity Leave';
        }

        $sql2= "SELECT 
        SUM(CASE WHEN l.Approved = 0 THEN 1 ELSE 0 END) AS pendingLeaves,
        SUM(CASE WHEN l.Approved = 1 THEN 1 ELSE 0 END) AS approvedLeaves,
        SUM(CASE WHEN l.Approved = 2 THEN 1 ELSE 0 END) AS rejectedLeaves,
        lt.LeaveType AS leaveType
        FROM mcts_extranet.`dbo.leave` AS l, mcts_extranet.`dbo.leavetypes` AS lt  WHERE l.EmployeeID = '$empid' and l.LeaveType = '2' and l.LeaveType = lt.id";
        $query2=$this->db->query($sql2)->result();
        if ($query2[0]->leaveType == null) {
            $query2[0]->leaveType = 'Loss of Pay';
        }

        $sql3= "SELECT 
        SUM(CASE WHEN l.Approved = 0 THEN 1 ELSE 0 END) AS pendingLeaves,
        SUM(CASE WHEN l.Approved = 1 THEN 1 ELSE 0 END) AS approvedLeaves,
        SUM(CASE WHEN l.Approved = 2 THEN 1 ELSE 0 END) AS rejectedLeaves,
        lt.LeaveType AS leaveType
        FROM mcts_extranet.`dbo.leave` AS l, mcts_extranet.`dbo.leavetypes` AS lt  WHERE l.EmployeeID = '$empid' and l.LeaveType = '3' and l.LeaveType = lt.id";
        $query3=$this->db->query($sql3)->result();
        if ($query3[0]->leaveType == null) {
            $query3[0]->leaveType = 'Comp Off';
        }

        $sql4= "SELECT 
        SUM(CASE WHEN l.Approved = 0 THEN 1 ELSE 0 END) AS pendingLeaves,
        SUM(CASE WHEN l.Approved = 1 THEN 1 ELSE 0 END) AS approvedLeaves,
        SUM(CASE WHEN l.Approved = 2 THEN 1 ELSE 0 END) AS rejectedLeaves,
        lt.LeaveType AS leaveType
        FROM mcts_extranet.`dbo.leave` AS l, mcts_extranet.`dbo.leavetypes` AS lt  WHERE l.EmployeeID = '$empid' and l.LeaveType = '4' and l.LeaveType = lt.id";
        $query4=$this->db->query($sql4)->result();
        if ($query4[0]->leaveType == null) {
            $query4[0]->leaveType = 'Paternity Leave';
        }

        $sql5= "SELECT 
        SUM(CASE WHEN l.Approved = 0 THEN 1 ELSE 0 END) AS pendingLeaves,
        SUM(CASE WHEN l.Approved = 1 THEN 1 ELSE 0 END) AS approvedLeaves,
        SUM(CASE WHEN l.Approved = 2 THEN 1 ELSE 0 END) AS rejectedLeaves,
        lt.LeaveType AS leaveType
        FROM mcts_extranet.`dbo.leave` AS l, mcts_extranet.`dbo.leavetypes` AS lt  WHERE l.EmployeeID = '$empid' and l.LeaveType = '5' and l.LeaveType = lt.id";
        $query5=$this->db->query($sql5)->result();
        if ($query5[0]->leaveType == null) {
            $query5[0]->leaveType = 'Maternity Leave';
        }

        $sql7= "SELECT 
        SUM(CASE WHEN Approved = 0 THEN 1 ELSE 0 END) AS pendingLeaves,
        SUM(CASE WHEN Approved = 1 THEN 1 ELSE 0 END) AS approvedLeaves,
        SUM(CASE WHEN Approved = 2 THEN 1 ELSE 0 END) AS rejectedLeaves
        FROM mcts_extranet.`dbo.leave`  WHERE EmployeeID = '$empid'";
        $query7=$this->db->query($sql7)->result();
        $query7[0]->leaveType = 'Total Leaves';

        $data = array($query1[0],$query2[0],$query3[0],$query4[0],$query5[0],$query7[0]);
		return $data;
    }
}