<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * User_model class.
 * 
 * @extends CI_Model
 */
class Locations_model extends CI_Model {

    public function __construct() {
		
		parent::__construct();
		$this->locations="mcts_extranet.dbo.locations";
	}

    /* function - Getting Location by Location ID */
    public function get_location_by_id($id){
        $sql = "SELECT * FROM `mcts_extranet`.`dbo.locations` WHERE LocationID=$id";
        $query = $this->db->query($sql);
        return $query->result();
    }

    /* function - Getting Location by Location name */
    public function get_location_by_name($name,$id=null){
        $cond='';
        if($id)
            $cond=" AND LocationID!='$id'";
        $sql = "SELECT * FROM `mcts_extranet`.`dbo.locations` WHERE LocationName='$name' $cond";
        $query = $this->db->query($sql);
        return $query->result();
    }

    /* function - Creating Location */
    public function create_location($data)
    {
        $sql = "INSERT INTO `mcts_extranet`.`dbo.locations` (LocationName,ParentLocationID) 
                VALUES ('".$data['LocationName']."','".$data['ParentLocationID']."')";

        $query=$this->db->query($sql);
        if($query)
            return true;
        else
            return false;
    }

    /* function - Updating Location by Location ID */
    public function update_location($data,$locationid)
    {
        $sql = "UPDATE `mcts_extranet`.`dbo.locations` SET 
                LocationName='".$data['LocationName']."',ParentLocationID='".$data['ParentLocationID']."'
                WHERE LocationID='$locationid'";

        $query=$this->db->query($sql);
        if($query==1)
            return true;
        else
            return false;
    }

    /* function - Checking Location by Location ID wheather Location is mapped any where */
    public function check_location_allocation($id)
    {
        $sql = "select WorkLocation from `dbo.employees` where WorkLocation=$id
                union
                select Location from `dbo.holidays` where Location=$id
                union
                select Location from `dbo.timesheetdetails` where Location=$id";

        $query = $this->db->query($sql);
        return $query->result();
    }

    /* function - Deleting Location by Location ID */
    public function delete_location($id)
    {
        $sql = "DELETE FROM `mcts_extranet`.`dbo.locations` WHERE LocationID='$id'";

        $query=$this->db->query($sql);
        if($query==1)
            return true;
        else
            return false;
    }
}