<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Timesheet_model extends CI_Model
{

    public function __construct() {
		
		parent::__construct();
		$this->timesheet="mcts_extranet.dbo.timesheet";
	}

    /* function - checking Timesheet with ID */
    public function check_ts_id($tsid)
    {
        $sql = "SELECT * FROM `mcts_extranet`.`dbo.timesheetdetails` WHERE TSDetailsID=$tsid";
        $query = $this->db->query($sql);
        return $query->result();
    }

    /* function - Creating Timesheet entry */
    public function timesheet_single_entry($data)
    {
        $sql = "INSERT INTO `mcts_extranet`.`dbo.timesheetdetails` (TSID, TSDate, Start, Finish, Activity, ProjectId, NumofHrs)
                VALUES ('".$data['TSID']."','".date('Y-m-d 00:00:00',strtotime($data['TSDate']))."','".$data['Start']."',
                        '".$data['Finish']."','".$data['Activity']."','".$data['ProjectId']."','".$data['NumofHrs']."')";
        $query=$this->db->query($sql);
        if($query)
            return true;
        else
            return false;
    }

    /* function - checking Timesheet entry with ID and Project ID */
    public function check_tsentry_bytsid($tsid,$projectid)
    {
        $sql = "SELECT * FROM `mcts_extranet`.`dbo.timesheetdetails` WHERE TSID='$tsid' AND ProjectId='".$projectid."'";
        $query = $this->db->query($sql);
        return $query->result();
    }

    /* function - Updating Timesheet entry with Timesheet ID */
    public function update_tsdetails_byproject($tsid,$data)
    {
        $sql = "UPDATE `mcts_extranet`.`dbo.timesheetdetails` SET
                TSDate = '".date('d-m-Y 00:00:00',strtotime($data['TSDate']))."', Start = '".$data['Start']."',Finish='".$data['Finish']."',Activity='".$data['Activity']."',ProjectId='".$data['ProjectId']."'
                WHERE TSDetailsID='$tsid'";

        $query=$this->db->query($sql);
        if($query==1)
            return true;
        else
            return false;
    }

    /* function - Accept/Reject Timesheet */
    public function accept_reject_timesheet($tsid,$status)
    {
        $sql = "UPDATE `mcts_extranet`.`dbo.timesheetdetails` SET
                Status='$status'
                WHERE TSDetailsID='$tsid'";
            
        $query=$this->db->query($sql);
        if($query==1)
            return true;
        else
            return false;
    }

    /* function - Getting all Timesheet entries with or with out filters */
    public function get_all_timesheet_entries($data)
    {
        $wherecond = '1=1 AND';
        if($data['Role']=='User') 
        {
            $tsid = "TS".$data['EmployeeID'];
            $wherecond .= " TSID LIKE '%$tsid%'";
        } 
        $wherecond = rtrim($wherecond, ' AND');
        $sql = "SELECT * FROM `mcts_extranet`.`dbo.timesheetdetails` $wherecond";

        if(!empty($data))
		{
			$page = (isset($data['Page']) && is_numeric($data['Page']) ) ? $data['Page'] : 1;
  			$paginationStart = ($page - 1) * PER_PAGE_RECORDS;
			$wherecond = '1=1 AND';
            if($data['Role']=='User') 
            {
                $tsid = "TS".$data['EmployeeID'];
                $wherecond .= " TSID LIKE '%$tsid%' AND";
            } 
			//if(!empty($data['TSID'])) $wherecond .= " TSID='".$data['TSID']."' AND";
			if(!empty($data['TSDate']))	$wherecond .= " TSDate = '".date('d-m-Y 00:00:00',strtotime($data['TSDate']))."' AND";
            if(!empty($data['Start'])) $wherecond .= " Start=".$data['Start']." AND";
            if(!empty($data['Finish'])) $wherecond .= " Finish=".$data['Finish']." AND";
            if(!empty($data['Location'])) $wherecond .= " Location=".$data['Location']." AND";
            if(!empty($data['ProjectId'])) $wherecond .= " ProjectId=".$data['ProjectId']." AND";
            if(!empty($data['FromDate']) && !empty($data['ToDate'])) 
            {
                $wherecond .= " TSDate>='".date('d-m-Y 00:00:00',strtotime($data['FromDate']))."' AND TSDate<='".date('d-m-Y 00:00:00',strtotime($data['ToDate']))."' AND";
            }
            if(!empty($data['FromDate']) && empty($data['ToDate'])) 
            {
                $wherecond .= " TSDate>='".date('d-m-Y 00:00:00',strtotime($data['FromDate']))."' AND";
            }
            if(empty($data['FromDate']) && !empty($data['ToDate'])) 
            {
                $wherecond .= " TSDate<='".date('d-m-Y 00:00:00',strtotime($data['ToDate']))."' AND";
            }
			$wherecond = rtrim($wherecond, ' AND');
			$sql = "SELECT * FROM `mcts_extranet`.`dbo.timesheetdetails` WHERE $wherecond ORDER BY TSDate DESC LIMIT $paginationStart,".PER_PAGE_RECORDS;
		}
		$query=$this->db->query($sql);
		return $query->result();
    }

    /* function - Deleting Timesheet with Timesheet ID */
    public function delete_timesheet($tsid)
    {
        $sql = "DELETE FROM `mcts_extranet`.`dbo.timesheetdetails` WHERE TSDetailsID='$tsid'";

        $query=$this->db->query($sql);
        if($query==1)
            return true;
        else
            return false;
    }

    /* function - Getting Timesheet detgails with Timesheet Date */
    public function check_start_finish_time($date,$id=null)
    {
        $cond='';
        if($id)
            $cond=" AND TSDetailsID!='$id'";

        $sql = "SELECT * FROM `mcts_extranet`.`dbo.timesheetdetails` WHERE TSDate ='$date'";
        $query=$this->db->query($sql);
		return $query->result();
    }

    public function get_timesheet_by_empid($month,$empid)
    {
        $tsid = "TS".$empid;
        $sql = "SELECT * FROM `mcts_extranet`.`dbo.timesheetdetails` WHERE TSID LIKE '$tsid%' AND MONTH(TSDate)='$month' ORDER BY TSDate ASC";
        $query=$this->db->query($sql);
		return $query->result();
    }

    public function get_categories()
	{
		$sql = "SELECT * FROM `mcts_extranet`.`dbo.tktcategories`";
		$query=$this->db->query($sql);
		return $query->result();
	}

    public function get_priorities()
	{
		$sql = "SELECT * FROM `mcts_extranet`.`dbo.tktpriorities`";
		$query=$this->db->query($sql);
		return $query->result();
	}
}