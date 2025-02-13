<?php
defined('BASEPATH') OR exit('No direct script access allowed');


class Projects_model extends CI_Model {

    public function __construct() {
		
		parent::__construct();
		$this->emptable="mcts_extranet.dbo.projects";
	}

    /* function - CHecking Project name */
    public function check_project_name($name,$id=null)
    {
        $cond='';
        if($id)
            $cond=" AND ProjectID!='$id'";

        $sql = "SELECT * FROM `mcts_extranet`.`dbo.projects` WHERE ProjectName='$name' $cond";
        $query = $this->db->query($sql);
        return $query->result();
    }

    /* function - Creating Project */
    public function create_project($data)
    {
        $sql = "INSERT INTO `mcts_extranet`.`dbo.projects`
                (ProjectName,VendorID,EndClient,ProjectCode,Category,ProjectType,Manager,Manager2,Status) 
                VALUES ('".$data['ProjectName']."','".$data['VendorID']."','".$data['EndClient']."','".$data['ProjectCode']."',
                '".$data['Category']."','".$data['ProjectType']."','".$data['Manager']."','".$data['Manager2']."',1)";
        $query=$this->db->query($sql);
        if($query)
            return true;
        else
            return false;
    }

    /* function - Checking Project mapped with Employee */
    public function check_project_with_employee($empid,$projectid)
    {
        $sql = "SELECT * FROM `mcts_extranet`.`dbo.employee2project` WHERE EmployeeID=$empid AND ProjectID=$projectid";
        $query = $this->db->query($sql);
        return $query->result();
    }

    /* function - Assigning Project to Employee */
    public function assign_project($data)
    {
        $sql = "INSERT INTO `mcts_extranet`.`dbo.employee2project`
                (EmployeeID,ProjectID,AssignedDt,Role,ReportingTo,CreatedDt) 
                VALUES ('".$data['EmployeeID']."','".$data['ProjectID']."','".date('Y-m-d',strtotime($data['AssignedDt']))."',
                        '".$data['Role']."','".$data['ReportingTo']."','".date('Y-m-d')."')";
        $query=$this->db->query($sql);
        if($query)
            return true;
        else
            return false;
    }

    /* function - Updating Project by Project ID */
    public function update_project($data,$id)
    {
        $sql = "UPDATE `mcts_extranet`.`dbo.projects` SET 
                ProjectName='".$data['ProjectName']."', VendorID='".$data['VendorID']."',EndClient='".$data['EndClient']."',
                ProjectCode='".$data['ProjectCode']."',Category='".$data['Category']."',ProjectType='".$data['ProjectType']."',
                Manager='".$data['Manager']."',Manager2='".$data['Manager2']."'
                WHERE ProjectID=$id";

        $query=$this->db->query($sql);
        if($query)
            return true;
        else
            return false;
    }

    /* function - Get Project by Project ID */
    public function get_project_by_id($id)
    {
        $sql = "SELECT * FROM `mcts_extranet`.`dbo.projects` WHERE ProjectID=$id";
        $query = $this->db->query($sql);
        return $query->result();
    }

    public function get_vendors($data=null)
	{
		$sql = "SELECT * FROM `mcts_extranet`.`dbo.vendors`";
		if(!empty($data))
		{
			$page = (isset($data['Page']) && is_numeric($data['Page']) ) ? $data['Page'] : 1;
  			$paginationStart = ($page - 1) * PER_PAGE_RECORDS;
			$sql = "SELECT * FROM `mcts_extranet`.`dbo.vendors` ORDER BY id ASC LIMIT $paginationStart,".PER_PAGE_RECORDS;
		}
		
		$query=$this->db->query($sql);
		return $query->result();
	}

    public function get_endclients($data=null)
	{
		$sql = "SELECT * FROM `mcts_extranet`.`dbo.endclients`";
		if(!empty($data))
		{
			$page = (isset($data['Page']) && is_numeric($data['Page']) ) ? $data['Page'] : 1;
  			$paginationStart = ($page - 1) * PER_PAGE_RECORDS;
			$sql = "SELECT * FROM `mcts_extranet`.`dbo.endclients` ORDER BY id ASC LIMIT $paginationStart,".PER_PAGE_RECORDS;
		}
		
		$query=$this->db->query($sql);
		return $query->result();
	}

    public function get_all_projects($data=null)
	{
		$sql = "SELECT * FROM `mcts_extranet`.`dbo.projects`";
		if(!empty($data))
		{
			$page = (isset($data['Page']) && is_numeric($data['Page']) ) ? $data['Page'] : 1;
  			$paginationStart = ($page - 1) * PER_PAGE_RECORDS;
			$wherecond = '1=1 AND';
			if(!empty($data['VendorID'])) $wherecond .= " VendorID=".$data['VendorID']." AND";
			if(!empty($data['ProjectName']))	$wherecond .= " ProjectName LIKE '%".$data['ProjectName']."%' AND";
            if(!empty($data['EndClient'])) $wherecond .= " EndClient=".$data['EndClient']." AND";
            if(!empty($data['ProjectCode'])) $wherecond .= " ProjectCode=".$data['ProjectCode']." AND";
            if(!empty($data['Category'])) $wherecond .= " Category=".$data['Category']." AND";
			$wherecond = rtrim($wherecond, ' AND');
			$sql = "SELECT * FROM `mcts_extranet`.`dbo.projects` WHERE $wherecond ORDER BY ProjectID DESC LIMIT $paginationStart,".PER_PAGE_RECORDS;
		}
		
		$query=$this->db->query($sql);
		return $query->result();
	}

    public function get_project($project_id) {
		
		$sql = "SELECT * from `mcts_extranet`.`dbo.projects` where ProjectID = '$project_id'";
		$query=$this->db->query($sql);
		return $query->row();
		
	}

    public function get_emp_projects($empid)
    {
        $sql = "select p.ProjectName,e.FirstName as manager,AssignedDt,ReleaseDt from mcts_extranet.`dbo.employee2project` ep
                left join mcts_extranet.`dbo.projects` p on ep.ProjectID=p.ProjectID
                left join mcts_extranet.`dbo.employees` e on ep.ReportingTo=e.EmployeeID
                where ep.EmployeeID='$empid'";

        $query=$this->db->query($sql);
        return $query->result();
    }

    public function relieve_employee($data)
    {
        $sql = "UPDATE mcts_extranet.`dbo.employee2project` SET Status=2 WHERE EmployeeID='".$data['EmployeeID']."' AND ProjectID='".$data['ProjectID']."'";

        $query=$this->db->query($sql);
        if($query)
            return true;
        else
            return false;
    }
}