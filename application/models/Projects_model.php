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
}