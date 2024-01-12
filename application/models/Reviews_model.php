<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reviews_model extends CI_Model {

    public function __construct() {
		
		parent::__construct();
		$this->emptable="mcts_extranet.dbo.reviews";
        
	}

    public function add_review($data)
    {
        // $stringDate = date("Y-m-d", strtotime($date['Month']));
        $dateParts = explode('-', $data['Month']);
        $year = $dateParts[0];
        $month = $dateParts[1];
        $action = 0;
        if($data['ReviewAction']=='submit')   $action = 1;
        $query=false;
        if($data['ReviewAction']=='submit'){
            $sql2 = 'UPDATE `mcts_extranet`.`dbo.reviews` SET ReviewAction=1, SubmittedDate="'.date('Y-m-d').'" 
            WHERE EmployeeID="'.$data['EmployeeID'].'" AND Manager="'.$data['Manager'].'" AND YEAR(CreatedDate)=year(curdate())';
        $query2=$this->db->query($sql2);
        }else{
            $sql = 'INSERT INTO `mcts_extranet`.`dbo.reviews` (EmployeeID,Manager,Accomplishments,AreasOfImprovement,
            CurrentSkillSet,NewInitiatives,AddlResponsibilities,ReviewBy,ReviewAction, ReviewYear, ReviewMonth)
            VALUES("'.$data['EmployeeID'].'","'.$data['Manager'].'","'.$data['Accomplishments'].'","'.$data['AreasOfImprovement'].'",
            "'.$data['CurrentSkillSet'].'","'.$data['NewInitiatives'].'","'.$data['AddlResponsibilities'].'",
            "'.$data['EmployeeID'].'","'.$action.'","'.$year.'","'.$month.'")';
            $query=$this->db->query($sql);
        }
        if($query)
			return true;
		else
			return false;
    }

    public function add_comment($data,$id)
    {
        $action = 0;
        if($data['ReviewAction']=='submit')   $action = 1;

        $sql = "UPDATE `mcts_extranet`.`dbo.reviews` SET ReviewComments='".$data['Comments']."',CommentedDate='".date('Y-m-d H:i:s')."',
                CommentAction='$action' WHERE Id='$id'";

        $query=$this->db->query($sql);
        if($query)
			return true;
		else
			return false;
    }

    public function add_annual_review($data)
    {
        $sql = "INSERT INTO `mcts_extranet`.`dbo.reviews` (EmployeeID,Accomplishments,AreasOfImprovement,CurrentSkillSet,KRAOfTheYear,OverAllRating,Manager,AnnualReview,ReviewYear)
                VALUES('".$data['EmployeeID']."','".$data['Accomplishments']."','".$data['AreasOfImprovement']."','".$data['CurrentSkillSet']."',
                '".$data['KRAOfTheYear']."','".$data['OverAllRating']."','".$data['Manager']."','1','".date('Y')."')";

        $query=$this->db->query($sql);

        $sql2 = "UPDATE `mcts_extranet`.`dbo.reviews` SET CommentAction=1 WHERE EmployeeID='".$data['EmployeeID']."'";

        $query=$this->db->query($sql2);

        if($query)
			return true;
		else
			return false;
    }

    public function check_review($id)
    {
        $sql = "SELECT * FROM `mcts_extranet`.`dbo.reviews` WHERE Id='$id' AND ReviewAction=1";

        $query=$this->db->query($sql);
		return $query->row();
    }

    public function update_review($data,$id)
    {
        $sql = 'UPDATE `mcts_extranet`.`dbo.reviews` SET Accomplishments="'.$data['Accomplishments'].'",AreasOfImprovement="'.$data['AreasOfImprovement'].'",
                CurrentSkillSet="'.$data['CurrentSkillSet'].'",NewInitiatives="'.$data['NewInitiatives'].'",AddlResponsibilities="'.$data['AddlResponsibilities'].'",
                UpdatedDate="'.date('Y-m-d').'"
                WHERE Id="$id"';

        $query=$this->db->query($sql);
        if($query==1)
            return true;
        else
            return false;
    }

    public function get_emp_submitted_reviews($empid)
    {
        $sql = "SELECT Accomplishments,AreasOfImprovement,CurrentSkillSet,NewInitiatives,AddlResponsibilities,CreatedDate,UpdatedDate,SubmittedDate FROM `mcts_extranet`.`dbo.reviews`
                WHERE EmployeeID='$empid' AND ReviewAction=1";

        $query=$this->db->query($sql);
        return $query->result();
    }

    public function get_emp_reviews_comments($empid)
    {
        $sql = "SELECT Id,Accomplishments,AreasOfImprovement,CurrentSkillSet,NewInitiatives,AddlResponsibilities,ReviewComments,CommentedDate,CreatedDate,UpdatedDate 
                FROM `mcts_extranet`.`dbo.reviews`
                WHERE EmployeeID='$empid'";

        $query=$this->db->query($sql);
        return $query->result();
    }

    public function get_appraisal_comments($empid)
    {
        $sql = "SELECT Id,AppraisalComments,CreatedDate from `mcts_extranet`.`dbo.reviews` WHERE AppraisalComments IS NOT NULL AND EmployeeID='$empid'";

        $query=$this->db->query($sql);
        return $query->row();
    }

    public function accept_reject_review($revid,$status,$data=null)
    {
        $sql = "UPDATE `mcts_extranet`.`dbo.reviews` SET
                Status='$status', Reason='".$data['Reason']."'
                WHERE Id='$revid'";
            
        $query=$this->db->query($sql);

        if($query==1)
            return true;
        else
            return false;
    }

    public function get_manager_submitted_reviews($manid)
    {
        $sql = "SELECT Id,Accomplishments,AreasOfImprovement,CurrentSkillSet,KRAOfTheYear,OverAllRating,e.FirstName as EmployeeName,CreatedDate from `mcts_extranet`.`dbo.reviews` r
                LEFT JOIN mcts_extranet.`dbo.employees` e on r.EmployeeID=e.EmployeeID
                WHERE r.Manager='$manid' AND AnnualReview=1";

        $query=$this->db->query($sql);
        return $query->result();
    }

    public function get_review($revid)
    {
        $sql = "SELECT * FROM `mcts_extranet`.`dbo.reviews` WHERE Id='$revid'";

        $query=$this->db->query($sql);
        return $query->row();
    }

    //Suhas

    public function get_reviewById($revid)
    {
        $sql = "SELECT Accomplishments,AreasOfImprovement,CurrentSkillSet,NewInitiatives,AddlResponsibilities,ReviewAction, ReviewYear AS Month FROM `mcts_extranet`.`dbo.reviews` WHERE Id='$revid'";

        $query=$this->db->query($sql);
        return $query->row();
    }
    
    public function get_monthlyReviewById($empId,$month)
    {
        $sql = "SELECT Id AS ReviewID, Accomplishments,AreasOfImprovement,CurrentSkillSet,NewInitiatives,AddlResponsibilities,ReviewComments AS Comments,ReviewAction FROM `mcts_extranet`.`dbo.reviews` WHERE EmployeeID='$empId' AND ReviewYear='$month'";

        $query=$this->db->query($sql);
        return $query->row();
    }
    public function get_emp_review_by_year($empid,$year)
    {
        $sql = "SELECT Id, Accomplishments,AreasOfImprovement,CurrentSkillSet,NewInitiatives,AddlResponsibilities,Status, ReviewYear, ReviewMonth, CreatedDate,UpdatedDate,SubmittedDate, CONCAT(ReviewYear, '-', ReviewMonth) as Month, ReviewComments as Comments FROM `mcts_extranet`.`dbo.reviews`
                WHERE EmployeeID='$empid' AND ReviewYear='$year'";
        $query=$this->db->query($sql);
		return $query->result();
    }
}