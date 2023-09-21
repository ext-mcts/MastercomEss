<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Assets_model extends CI_Model {

    public function __construct() {
		
		parent::__construct();
		$this->emptable="mcts_extranet.dbo.assets";
	}

    public function validate_asset($col, $num, $id=null)
    {
        switch($col)
		{
			case "SerialNumber":
				$cond="SerialNumber='$num'";
				$cond2 = '';
				if($id)	$cond2 = " AND Id !=$id";
				$sql = "SELECT SerialNumber from `mcts_extranet`.`dbo.assets` WHERE $cond $cond2";
				$query=$this->db->query($sql);
				//return $query->result();
				break;

			case "ChargerSerialNumber":
				$cond="ChargerSerialNumber='$num'";
				$cond2 = '';
				if($id)	$cond2 = " AND Id !=$id";
				$sql = "SELECT ChargerSerialNumber from `mcts_extranet`.`dbo.assets` WHERE $cond $cond2";
				$query=$this->db->query($sql);
				// return $query->result();
				break;
        }
        return $query->result();
    }

    public function create_asset($data)
    {
        $sql = "INSERT INTO `mcts_extranet`.`dbo.assets` (Brand,Model,PurchaseDate,SerialNumber,ChargerSerialNumber,Ram,Rom,Processor)
                VALUES ('".$data['Brand']."','".$data['Model']."','".$data['PurchaseDate']."',
                        '".$data['SerialNumber']."','".$data['ChargerSerialNumber']."','".$data['Ram']."',
                        '".$data['Rom']."','".$data['Processor']."')";

        $query=$this->db->query($sql);
        if($query==1)
            return true;
        else
            return false;
    }

    public function update_asset($id, $data)
    {
        $sql = "UPDATE `mcts_extranet`.`dbo.assets` SET Brand='".$data['Brand']."',Model='".$data['Model']."',
                PurchaseDate='".$data['PurchaseDate']."',SerialNumber='".$data['SerialNumber']."',
                ChargerSerialNumber='".$data['ChargerSerialNumber']."',Ram='".$data['Ram']."',
                Rom='".$data['Rom']."',Processor='".$data['Processor']."'
                WHERE Id='$id'";

        $query=$this->db->query($sql);
        if($query==1)
            return true;
        else
            return false;
    }

    public function check_emp_asset($empid,$astid)
    {
        $sql = "SELECT * FROM `mcts_extranet`.`dbo.asset2employee` WHERE Asset='$astid' AND EmployeeID='$empid'";

        $query=$this->db->query($sql);
        return $query->result();
    }

    public function assign_asset($data)
    {
        $sql = "INSERT INTO `mcts_extranet`.`dbo.asset2employee` (Asset,EmployeeID,AssignedDate,ValidUpTo)
                VALUES ('".$data['Asset']."','".$data['EmployeeID']."','".date("Y-m-d")."',
                        '".date("Y-m-d",strtotime($data['ValidUpTo']))."')";

        $query=$this->db->query($sql);
        if($query==1)
            return true;
        else
            return false;
    }

    public function check_asset_allocation($id)
    {
        $sql = "SELECT * FROM mcts_extranet.`dbo.assets` WHERE Id='$id'";

        $query=$this->db->query($sql);
		return $query->result();
    }

    public function delete_asset($id)
    {
        $sql = "DELETE FROM mcts_extranet.`dbo.assets` WHERE id='$id'";

        $query=$this->db->query($sql);
        if($query==1)
            return true;
        else
            return false;
    }

    public function get_emp_assets($empid)
    {
        $sql = "SELECT * FROM mcts_extranet.`dbo.asset2employee` WHERE EmployeeID='$empid'";

        $query=$this->db->query($sql);
		return $query->result();
    }
}