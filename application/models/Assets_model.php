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
                WHERE id='$id'";

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

    // Suhas

    public function get_assetsById($id)
    {
        // $sql = "SELECT id as assetId, Brand, Model, PurchaseDate, SerialNumber, ChargerSerialNumber, Ram, Rom, Processor FROM `mcts_extranet`.`dbo.assets` WHERE id = '$id'";
        $sql = "SELECT a.id as assetId, a.Brand, a.Model, a.PurchaseDate, a.SerialNumber, a.ChargerSerialNumber,
        a.Ram, a.Rom, a.Processor, e.FirstName AS AssignTo, aa.ValidUpTo
        FROM  `mcts_extranet`.`dbo.assets` AS a , mcts_extranet.`dbo.asset2employee`  AS aa,
        mcts_extranet.`dbo.employees`  AS e WHERE a.id = '$id' AND aa.Asset = '$id' AND 
        e.EmployeeID = (SELECT EmployeeID FROM mcts_extranet.`dbo.asset2employee` WHERE Asset = '$id')";
        $query = $this->db->query($sql);
        return $query->result()[0];
    }

    public function add_asset($data)
    {
        $sql = "INSERT INTO `mcts_extranet`.`dbo.assets` (Brand,Model,PurchaseDate,SerialNumber,ChargerSerialNumber,Ram,Rom,Processor)
                VALUES ('".$data['Brand']."','".$data['Model']."','".$data['PurchaseDate']."',
                        '".$data['SerialNumber']."','".$data['ChargerSerialNumber']."','".$data['Ram']."',
                        '".$data['Rom']."','".$data['Processor']."')";

        $query=$this->db->query($sql);
        // return $this->db->insert_id();
        if($query==1)
            return $this->db->insert_id();
        else
            return false; 
    }

    public function assign_asset($data, $id)
    {
        $sql = "INSERT INTO `mcts_extranet`.`dbo.asset2employee` (Asset,EmployeeID,AssignedDate,ValidUpTo)
                VALUES ('".$id."','".$data['AssignTo']."','".date("Y-m-d")."',
                        '".date("Y-m-d",strtotime($data['ValidUpTo']))."')";

        $query=$this->db->query($sql);
        if($query==1)
            return true;
        else
            return false;
    }

    public function update_assigned_asset($data, $id)
    {
        $sql = "UPDATE `mcts_extranet`.`dbo.asset2employee` SET EmployeeID = '".$data['AssignTo']."' , ValidUpTo = '".date("Y-m-d",strtotime($data['ValidUpTo']))."' WHERE Asset = '".$id."'";
        $query=$this->db->query($sql);
        // return $query;
        if($query==1)
            return true;
        else
            return false;
    }
}