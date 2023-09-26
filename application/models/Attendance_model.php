<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Attendance_model extends CI_Model {

    public function __construct() {
		
		parent::__construct();
	}

    public function CaptureSwipe($empid,$type)
    {
        $sql = "INSERT INTO `mcts_extranet`.`dbo.attendancelogs` (EmployeeID, SwipeDate, SwipeTime, SwipeType) 
                            VALUES ($empid, '".date('Y-m-d')."', '".date('H:i:s')."', '$type')";
		$query=$this->db->query($sql);

        if($query)
            return true;
        else
            return false;
    }

    public function GetSwipes($empid)
    {
        $sql = "SELECT * FROM `mcts_extranet`.`dbo.attendancelogs` WHERE EmployeeID=$empid AND SwipeDate='".date('Y-m-d')."'";
		$query=$this->db->query($sql);
        return $query->result();
    }

    public function GetEmpSwipes($id)
    {
        $sql = "SELECT * FROM mcts_extranet.`dbo.attendancelogs` WHERE EmployeeID IN (SELECT EmployeeID FROM mcts_extranet.`dbo.employees` where Manager='$id') AND MONTH(SwipeDate)='".date('m')."'";

        $query=$this->db->query($sql);
        return $query->result();
    }
}