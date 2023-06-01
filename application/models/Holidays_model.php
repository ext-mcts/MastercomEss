<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Holidays_model extends CI_Model {

    public function __construct() {
		
		parent::__construct();
		$this->emptable="mcts_extranet.dbo.holidays";
	}

    /* function - Create Holiday */
    public function create_holiday($data)
    {
        $sql = "INSERT INTO `mcts_extranet`.`dbo.holidays` (HolidayDt, HolidayDesc, Location, HolidayType) 
                VALUES ('".date('Y-m-d 00:00:00',strtotime($data['HolidayDate']))."','".$data['HolidayDesc']."',
                '".$data['Location']."','".$data['HolidayType']."')";

        $query=$this->db->query($sql);
        if($query)
            return true;
        else
            return false;
    }

    /* function - Checking given Holiday Date previously occupued or not*/
    public function check_holiday_date($date,$location,$holidayid=null)
    {
        $cond='';
        if($holidayid)
            $cond=" AND HolidayID!='$holidayid'";
        $sql = "SELECT * FROM `mcts_extranet`.`dbo.holidays` WHERE HolidayDt='$date' AND Location=$location $cond";
        $query = $this->db->query($sql);
        return $query->result();
    }

    /* function - Getting Holiday by Holiday ID */
    public function validate_holiday_byid($id)
    {
        $sql = "SELECT * FROM `mcts_extranet`.`dbo.holidays` WHERE HolidayID='$id'";
        $query = $this->db->query($sql);
        return $query->result();
    }

    /* function - Updating Holiday by Holiday ID */
    public function update_holidays($data,$holidayid)
    {
        $sql = "UPDATE `mcts_extranet`.`dbo.holidays` SET 
                HolidayDt= '".date('Y-m-d 00:00:00',strtotime($data['HolidayDate']))."',
                HolidayDesc='".$data['HolidayDesc']."',Location='".$data['Location']."',HolidayType='".$data['HolidayType']."'
                WHERE HolidayID='$holidayid'";

        $query=$this->db->query($sql);
        if($query==1)
            return true;
        else
            return false;
    }

    /* function - Deleting Holiday by Holiday ID */
    public function delete_holiday($holidayid)
    {
        $sql = "DELETE FROM `mcts_extranet`.`dbo.holidays` WHERE HolidayID='$holidayid'";

        $query=$this->db->query($sql);
        if($query==1)
            return true;
        else
            return false;
    }

    /* function - Getting all Holidays with/out filters */
    public function get_all_holidays($data)
    {
        $sql = "SELECT * FROM `mcts_extranet`.`dbo.holidays`";
		if(!empty($data))
		{
			$page = (isset($data['Page']) && is_numeric($data['Page']) ) ? $data['Page'] : 1;
  			$paginationStart = ($page - 1) * PER_PAGE_RECORDS;
            $wherecond = '';
			$wherecond .= '1=1 AND';
			if(!empty($data['HolidayDt'])) $wherecond .= " HolidayDt='".date('Y-m-d 00:00:00',strtotime($data['HolidayDt']))."' AND";
			if(!empty($data['HolidayDesc']))	$wherecond .= " HolidayDesc LIKE '%".$data['HolidayDesc']."%' AND";
            if(!empty($data['Location'])) $wherecond .= " Location=".$data['Location']." AND";
            if(!empty($data['Year'])){
                 $wherecond .= " YEAR(STR_TO_DATE(HolidayDt, '%d-%m-%Y'))='".$data['Year']."' AND";
            }
            else 
            {
                $wherecond .= " YEAR(STR_TO_DATE(HolidayDt, '%d-%m-%Y'))='".date('Y')."' AND"; // showing current year holidays default
            }
            if(!empty($data['HolidayType'])) $wherecond .= " HolidayType=".date('Y',strtotime($data['HolidayType']))." AND";
			$wherecond = rtrim($wherecond, ' AND');
			$sql = "SELECT * FROM `mcts_extranet`.`dbo.holidays` WHERE $wherecond ORDER BY HolidayDt ASC LIMIT $paginationStart,".PER_PAGE_RECORDS;
		}
		$query=$this->db->query($sql);
		return $query->result();
    }

    public function get_holidays_by_location($month, $year, $location)
    {
        $sql = "SELECT COUNT(*) AS holidays FROM mcts_extranet.`dbo.holidays` WHERE MONTH(HolidayDt)='$month' AND YEAR(HolidayDt)='$year' AND Location='$location'";
        $query=$this->db->query($sql);
		return $query->row();
    }
}