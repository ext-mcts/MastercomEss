<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Department_model extends CI_Model {

    public function __construct() {
		
		parent::__construct();
		$this->emptable="mcts_extranet.dbo.departments";
	}

    public function create_department($data)
    {
        $sql = "INSERT INTO mcts_extranet.`dbo.departments` (dept_name) VALUES ('".$data['DepartmentName']."')";

        $query=$this->db->query($sql);
        if($query==1)
            return true;
        else
            return false;

    }

    public function check_dept_name($name,$id=null)
    {
        $cond='';
        if($id)
            $cond=" AND id!='$id'";

        $sql = "SELECT dept_name FROM mcts_extranet.`dbo.departments` WHERE dept_name = '$name' $cond";

        $query=$this->db->query($sql);
		return $query->result();
    }

    public function update_department($name,$id)
    {
        $sql = "UPDATE mcts_extranet.`dbo.departments` SET dept_name = '$name' WHERE id='$id'";

        $query=$this->db->query($sql);
        if($query==1)
            return true;
        else
            return false;
    }

    public function check_dept_allocation($deptid)
    {
        $sql = "SELECT * FROM mcts_extranet.`dbo.employees` WHERE Department='$deptid'";

        $query=$this->db->query($sql);
		return $query->result();
    }

    public function delete_department($id)
    {
        $sql = "DELETE FROM mcts_extranet.`dbo.employees` WHERE ID='$id'";

        $query=$this->db->query($sql);
        if($query==1)
            return true;
        else
            return false;
    }
}
