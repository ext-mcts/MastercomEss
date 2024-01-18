<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * User_model class.
 * 
 * @extends CI_Model
 */
class User_model extends CI_Model {

	
	public function __construct() {
		
		parent::__construct();
		$this->emptable="mcts_extranet.dbo.employees";
		$this->load->model('accuredleaves_model');
		$this->load->model('projects_model');
	}
	
	/*function - Employee creation */
	public function create_user($data=array()) {
		$sql = "INSERT INTO `mcts_extranet`.`dbo.employees` 
				(FirstName,LastName,EmailName,Password,Role,WorkLocation,Designation,JoinDate,Phone1,PANNumber,BankName,BankAccNumber,Manager,Grade,Address,
				FathersName,TempAddress,Phone2,AppLetterRef,BranchDetails,DOB,JobRole,Level,Vertical,PFAccount,AltEmailID,
				Technology,Language,Aadhar,Passport,Project) 
				VALUES 
				('".$data['FirstName']."','".$data['LastName']."','".$data['EmailName']."',
				'".$data['Password']."','".$data['Role']."','".$data['WorkLocation']."',
				'".$data['Designation']."','".date('Y-m-d',strtotime($data['JoinDate']))."','".$data['Phone1']."','".$data['PANNumber']."',
				'".$data['BankName']."','".$data['BankAccNumber']."','".$data['Manager']."','".$data['Grade']."',
				'".$data['Address']."','".$data['FathersName']."','".$data['TempAddress']."','".$data['Phone2']."',
				'".$data['AppLetterRef']."','".$data['BranchDetails']."','".date('Y-m-d',strtotime($data['DOB']))."',
				'".$data['JobRole']."','".$data['Level']."','".$data['Vertical']."','".$data['PFAccount']."',
				'".$data['AltEmailID']."','".$data['Technology']."','".$data['Language']."','".$data['Aadhar']."',
				'".$data['Passport']."','".$data['Project']."')";

		$query=$this->db->query($sql);

		$insert_id = $this->db->insert_id();
		$this->user_roles($insert_id,$data['Role']);
		$this->accuredleaves_model->OnJoiningLeaves($insert_id);
		$this->documents_model->create_doc($data['NewFile'],$insert_id);

		$proj_data = array();
		$proj_data["AssignedDt"] = date("Y-m-d");
		$proj_data["EmployeeID"] = $insert_id;
		$proj_data["Role"] = $data['Role'];
		$proj_data["ProjectID"] = $data['Project'];
		$proj_data['ReportingTo'] = $data['Manager'];
		$this->projects_model->assign_project($proj_data);

		if($query)
			return true;
		else
			return false;
	}

	public function user_roles($userID,$role){
		$sql = "INSERT INTO `mcts_plannerui`.`user_roles` (`userId`, `roleId`) VALUES ('".$userID."', '".$role."')";
		$query=$this->db->query($sql);
	}
	
	/* function - Login by using Password */
	public function resolve_user_login($username, $password) {
	
		#$password = base64_encode($password);
		$sql = "SELECT Password from `mcts_extranet`.`dbo.employees` where EmailName = '$username' AND Password='$password' AND (RelievingDt IS NULL OR RelievingDt='')";
		$query=$this->db->query($sql);
		#$hash = $query->row('Password');
		#return $this->verify_password_hash($password, $hash);
		$row = $query->result_id->num_rows;
		if($row==1)
			return true;
		else
			return false;
		
	}
	
	/* function - Getting Employee ID from username */
	public function get_user_id_from_username($username) {

		$sql = "SELECT EmployeeID from `mcts_extranet`.`dbo.employees` where EmailName = '$username'";
		$query=$this->db->query($sql);
		return $query->row('EmployeeID');
		
	}
	
	/* function - Getting Employee details with ID */
	public function get_user($user_id) {
		
		$sql = "SELECT * from `mcts_extranet`.`dbo.employees` where EmployeeID = '$user_id'";
		$query=$this->db->query($sql);
		return $query->row();
		
	}
	
	/* function - Hashing password */
	private function hash_password($password) {
		
		return password_hash($password, PASSWORD_BCRYPT);
		
	}
	
	/* function - Verifying hash password */
	private function verify_password_hash($password, $hash) {
		
		return password_verify($password, $hash);
		
	}
	
	/* function - Updating Employee details with Employee ID */
	public function update_user($data,$id){
		$sql= "UPDATE `mcts_extranet`.`dbo.employees` SET 
				FirstName='".$data['FirstName']."',LastName='".$data['LastName']."',
				EmailName='".$data['EmailName']."',FirstName='".$data['FirstName']."',
				Role='".$data['Role']."',WorkLocation='".$data['WorkLocation']."',
				Designation='".$data['Designation']."',BankName='".$data['BankName']."'";

		if(isset($data['Phone1']))	$sql.=",Phone1='".$data['Phone1']."'";
		if(isset($data['Password']))	$sql.=",Password='".$data['Password']."'";
		if(isset($data['AltEmailID']))	$sql.=",AltEmailID='".$data['AltEmailID']."'";
		if(isset($data['Technology']))	$sql.=",Technology='".$data['Technology']."'";
		if(isset($data['Language']))	$sql.=",Language='".$data['Language']."'";
		if(isset($data['Address']))	$sql.=",Address='".$data['Address']."'";
		if(isset($data['FathersName']))	$sql.=",FathersName='".$data['FathersName']."'";
		if(isset($data['TempAddress']))	$sql.=",TempAddress='".$data['TempAddress']."'";
		if(isset($data['Phone2']))	$sql.=",Phone2='".$data['Phone2']."'";
		if(isset($data['AppLetterRef']))	$sql.=",AppLetterRef='".$data['AppLetterRef']."'";
		if(isset($data['BranchDetails']))	$sql.=",BranchDetails='".$data['BranchDetails']."'";
		if(isset($data['DOB']))	$sql.=",DOB='".date('Y-m-d',strtotime($data['DOB']))."'";
		if(isset($data['JobRole']))	$sql.=",JobRole='".$data['JobRole']."'";
		if(isset($data['Level']))	$sql.=",Level='".$data['Level']."'";
		if(isset($data['Vertical']))	$sql.=",Vertical='".$data['Vertical']."'";
		if(isset($data['PFAccount']))	$sql.=",PFAccount='".$data['PFAccount']."'";
		if(isset($data['Aadhar']))	$sql.=",Aadhar='".$data['Aadhar']."'";
		if(isset($data['Passport']))	$sql.=",Passport='".$data['Passport']."'";
		if(isset($data['Project']))	$sql.=",Project='".$data['Project']."'";
		if(isset($data['BankAccNumber']))	$sql.=",BankAccNumber='".$data['BankAccNumber']."'";
		if(isset($data['PANNumber']))	$sql.=",PANNumber='".$data['PANNumber']."'";
		if(isset($data['Manager']))	$sql.=",Manager='".$data['Manager']."'";
		if(isset($data['Grade']))	$sql.=",Grade='".$data['Grade']."'";
		if(isset($data['JoinDate']))	$sql.=",JoinDate='".date('Y-m-d',strtotime($data['JoinDate']))."'";
		$sql.="WHERE EmployeeID=$id";

		$query=$this->db->query($sql);
		$this->update_user_roles($id,$data['Role']);
		$this->documents_model->update_doc($data['UploadedFile'],$data['NewFile'],$id);
		if($query==1)
			return true;
		else
			return false;
	}

	public function update_user_roles($userID,$role){
		$sql = "UPDATE `mcts_plannerui`.`user_roles` SET `roleId` = '".$role."' WHERE (`userId` = '".$userID."')";
		$query=$this->db->query($sql);
	}
	/* function - checking Email */
	public function check_email($email,$id=null){
		$cond = '';
		if($id)
			$cond = " AND EmployeeID !=$id";
		$sql = "SELECT EmailName from `mcts_extranet`.`dbo.employees` WHERE EmailName='$email' $cond";
		$query=$this->db->query($sql);
		return $query->row();
	}

	/* function - checking Grade */
	public function check_grade($grade){
		$sql = "SELECT GradeID from `mcts_extranet`.`dbo.grade` WHERE GradeID='$grade'";
		$query=$this->db->query($sql);
		return $query->result();
	}

	/* function - checking duplicacy in one function for different columns */
	public function check_emp_details($column,$columnvalue,$id=null)
	{
		switch($column)
		{
			case "PAN":
				$selectcol = "PANNumber"; 
				$cond="PANNumber='$columnvalue'";
				$cond2 = '';
				if($id)	$cond2 = " AND EmployeeID !=$id";
				$sql = "SELECT $selectcol from `mcts_extranet`.`dbo.employees` WHERE $cond $cond2";
				$query=$this->db->query($sql);
				//return $query->result();
				break;

			case "AADHAR":
				$selectcol = "Aadhar"; 
				$cond="Aadhar='$columnvalue'";
				$cond2 = '';
				if($id)	$cond2 = " AND EmployeeID !=$id";
				$sql = "SELECT $selectcol from `mcts_extranet`.`dbo.employees` WHERE $cond $cond2";
				$query=$this->db->query($sql);
				// return $query->result();
				break;

			case "PASSPORT":
				$selectcol = "Passport"; 
				$cond="Passport='$columnvalue'";
				$cond2 = '';
				if($id)	$cond2 = " AND EmployeeID !=$id";
				$sql = "SELECT $selectcol from `mcts_extranet`.`dbo.employees` WHERE $cond $cond2";
				$query=$this->db->query($sql);
				// return $query->result();
				break;

			case "BANKACCNUMBER":
				$selectcol = "BankAccNumber"; 
				$cond="BankAccNumber='$columnvalue'";
				$cond2 = '';
				if($id)	$cond2 = " AND EmployeeID !=$id";
				$sql = "SELECT $selectcol from `mcts_extranet`.`dbo.employees` WHERE $cond $cond2";
				$query=$this->db->query($sql);
				// return $query->result();
				break;

			case "PFNUMBER":
				$selectcol = "PFAccount"; 
				$cond="PFAccount='$columnvalue'";
				$cond2 = '';
				if($id)	$cond2 = " AND EmployeeID !=$id";
				$sql = "SELECT $selectcol from `mcts_extranet`.`dbo.employees` WHERE $cond $cond2";
				$query=$this->db->query($sql);
				// return $query->result();
				break;
		}
		return $query->result();
	}

	/* function - get all Employees with or without filter */
	public function get_all_users($data=null)
	{
		$sql = "SELECT * FROM `mcts_extranet`.`dbo.employees` WHERE RelievingDt IS NOT NULL";
		if(!empty($data))
		{
			$page = (isset($data['Page']) && is_numeric($data['Page']) ) ? $data['Page'] : 1;
  			$paginationStart = ($page - 1) * PER_PAGE_RECORDS;
			$wherecond = 'RelievingDt IS NOT NULL AND';
			if(!empty($data['EmployeeID'])) $wherecond .= " EmployeeID=".$data['EmployeeID']." AND";
			if(!empty($data['EmailName']))	$wherecond .= " EmailName LIKE '%".$data['EmailName']."%' AND";
			$wherecond = rtrim($wherecond, ' AND');
			$sql = "SELECT * FROM `mcts_extranet`.`dbo.employees` WHERE $wherecond ORDER BY EmployeeID DESC LIMIT $paginationStart,".PER_PAGE_RECORDS;
		}
		
		$query=$this->db->query($sql);
		return $query->result();
	}

	/* function - get Configure email */
	public function get_config_email()
	{
		$sql = "SELECT * FROM `mcts_extranet`.`dbo.configemails` WHERE Status=1";
		$query=$this->db->query($sql);
		return $query->result();
	}

	/* function - get all Grades */
	public function get_grades(){
		$sql = "SELECT * from `mcts_extranet`.`dbo.grade`";
		$query=$this->db->query($sql);
		return $query->result();
	}

	public function get_global_config_params()
	{
		$sql = "SELECT * from `mcts_extranet`.`dbo.globalconfigparameters` WHERE Status=1";
		$query=$this->db->query($sql);
		return $query->result();
	}

	public function get_in_probation_emp_details($data)
	{
		$get_global_params = $this->get_global_config_params();

		$cycle = $get_global_params[0]->LeavesCycle;
		$prob_period = $get_global_params[0]->ProbationPeriod;
		$leave_cons = $get_global_params[0]->LeavesConsiderInProbation;
		$caryforward = $get_global_params[0]->LeavesCarryForward;
		$leavescycle = $get_global_params[0]->LeavesCycleYear;

		$sql = "SELECT EmployeeID, FirstName, LastName, EmailName, JoinDate FROM `mcts_extranet`.`dbo.employees` WHERE JoinDate <= (now() - interval ".$prob_period." MONTH) AND ProbationConfirmDate IS NULL";
		
		if(!empty($data))
		{
			$page = (isset($data['Page']) && is_numeric($data['Page']) ) ? $data['Page'] : 1;
  			$paginationStart = ($page - 1) * PER_PAGE_RECORDS;
			$sql = "SELECT EmployeeID, FirstName, LastName, EmailName, JoinDate FROM `mcts_extranet`.`dbo.employees` WHERE JoinDate <= (now() - interval ".$prob_period." MONTH) AND ProbationConfirmDate IS NULL ORDER BY EmployeeID DESC LIMIT $paginationStart,".PER_PAGE_RECORDS;
		}

		$query=$this->db->query($sql);
		return $query->result();
	}

	public function updaterelievingdate($data)
	{
		$sql= "UPDATE `mcts_extranet`.`dbo.employees` SET RelievingDt='".date('Y-m-d',strtotime($data['relievingdate']))."' WHERE EmployeeID='".$data['EmployeeID']."'";

		$query=$this->db->query($sql);

		$reducedleaves = $this->accuredleaves_model->CheckLeavesOnReleavingDate($data);

		$this->db->query("UPDATE mcts_extranet.`dbo.accuredleaves` as t1,
                                        (select accuredleaves,LeaveBalance from mcts_extranet.`dbo.accuredleaves` where EmployeeID='".$data['EmployeeID']."') as t2
                                        set t1.accuredleaves = t2.accuredleaves-$reducedleaves,
                                        t1.LeaveBalance = t2.LeaveBalance-$reducedleaves
                                        where t1.EmployeeID='".$data['EmployeeID']."'");
		if($query==1)
			return true;
		else
			return false;
	}

	public function GetProbationCompletionEmps()
	{
		$sql = "SELECT * FROM `mcts_extranet`.`dbo.employees` WHERE JoinDate IS NOT NULL AND ProbationConfirmDate IS NOT NULL AND QuitDate IS NULL AND RelievingDt IS NULL";
		
		$query=$this->db->query($sql);
		
		return $query->result();

	}

	public function GetAllActiveEmps()
	{
		$sql = "SELECT * FROM `mcts_extranet`.`dbo.employees` WHERE JoinDate IS NOT NULL AND ProbationConfirmDate IS NULL AND QuitDate IS NULL AND RelievingDt IS NULL ORDER BY EmployeeID DESC LIMIT 1";

		$query=$this->db->query($sql);
		
		return $query->result();
	}

	public function get_roles()
	{
		$sql = "SELECT * FROM `mcts_extranet`.`dbo.roles`";
		$query=$this->db->query($sql);
		return $query->result();
	}

	public function get_banks()
	{
		$sql = "SELECT * FROM `mcts_extranet`.`dbo.banks`";
		$query=$this->db->query($sql);
		return $query->result();
	}

	public function get_departments()
	{
		$sql = "SELECT * FROM `mcts_extranet`.`dbo.departments`";
		$query=$this->db->query($sql);
		return $query->result();
	}

	public function get_designations()
	{
		$sql = "SELECT * FROM `mcts_extranet`.`dbo.designations`";
		$query=$this->db->query($sql);
		return $query->result();
	}

	public function get_empbydept($empid)
	{
		$sql = "SELECT * FROM `mcts_extranet`.`dbo.employees` WHERE Department=$empid";
		$query=$this->db->query($sql);
		return $query->result();
	}

	public function get_users()
	{
		$sql = "SELECT * FROM `mcts_extranet`.`dbo.employees` WHERE RelievingDt IS NULL AND QuitDate IS NULL";
		$query=$this->db->query($sql);
		return $query->result();
	}

	public function get_personal_details($empid)
	{
		$sql = "SELECT EmployeeID, CONCAT(`FirstName`, ' ', `LastName`) as name, FathersName, CONCAT(EmailName,'@mastercom.co.in') as EmailName, Phone1, DOB, FathersName,
				Address, AltEmailID, Phone2, LocationName from mcts_extranet.`dbo.employees` e 
				left join mcts_extranet.`dbo.locations` l on e.WorkLocation=l.LocationID
				where e.EmployeeID='$empid'";

		$query=$this->db->query($sql);
		return $query->row();
	}

	public function get_account_stationary($empid)
	{
		$sql = "SELECT PANNumber,BankAccNumber,BranchDetails,PFAccount,bank_name from mcts_extranet.`dbo.employees` e
				left join mcts_extranet.`dbo.banks` b on e.BankName=b.id
				where e.EmployeeID='$empid'";

		$query=$this->db->query($sql);
		return $query->row();
	}

	public function get_employment($empid)
	{
		$sql = "SELECT dept_name, desgn_name, GradeName, LocationName, CONCAT(emp.FirstName, ' ', emp.LastName) as Manager, ProjectName 
				from mcts_extranet.`dbo.employees` e
				left join mcts_extranet.`dbo.grade` g on e.Grade=g.GradeID
				left join mcts_extranet.`dbo.departments` d on e.Department = d.id
				left join mcts_extranet.`dbo.designations` de on e.Designation = de.id
				left join mcts_extranet.`dbo.locations` l on e.WorkLocation = l.LocationID
				left join mcts_extranet.`dbo.employees` emp on e.Manager = emp.EmployeeID
				left join mcts_extranet.`dbo.employee2project` ep on e.EmployeeID=ep.EmployeeID
				left join mcts_extranet.`dbo.projects` p on ep.ProjectID=p.ProjectID
				where e.EmployeeID='$empid'";

		$query=$this->db->query($sql);
		return $query->row();
	}

	public function do_resign($data)
	{
		$sql = "INSERT INTO `mcts_extranet`.`dbo.resignation` (EmployeeID, Reason, ExpectedDt, submitdt)
		VALUES ('".$data['EmployeeID']."','".$data['Reason']."','".date('Y-m-d',strtotime($data['ExpectedDt']))."',
				'".date('Y-m-d')."')";
		$query=$this->db->query($sql);

		$sql2 = "UPDATE `mcts_extranet`.`dbo.employees` SET ResignationDt = '".date('Y-m-d')."' WHERE EmployeeID = '".$data['EmployeeID']."'";
		$query=$this->db->query($sql2);

		if($query)
			return true;
		else
			return false;
	}

	public function accept_resign($data)
	{
		$sql2 = "UPDATE `mcts_extranet`.`dbo.resignation` SET Status = 1 WHERE EmployeeID = '".$data['EmployeeID']."'";
		$query=$this->db->query($sql2);

		$sql = "UPDATE `mcts_extranet`.`dbo.employees` SET AceptancesDt = '".date('Y-m-d')."', RelievingDt= '".$data['RelievingDt']."'
				WHERE EmployeeID = '".$data['EmployeeID']."'";
		$query=$this->db->query($sql);

		if($query)
			return true;
		else
			return false;
	}

	public function reject_resign($data)
	{
		$sql2 = "UPDATE `mcts_extranet`.`dbo.resignation` SET Status = 2 WHERE EmployeeID = '".$data['EmployeeID']."'";
		$query=$this->db->query($sql2);

		$sql = "UPDATE `mcts_extranet`.`dbo.employees` SET AceptancesDt = '', RelievingDt= ''
				WHERE EmployeeID = '".$data['EmployeeID']."'";
		$query=$this->db->query($sql);

		if($query)
			return true;
		else
			return false;
	}

	public function get_hr()
	{
		$sql = "SELECT * FROM `mcts_extranet`.`dbo.employees` WHERE Role=5";

		$query=$this->db->query($sql);
		return $query->row();
	}
}
