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
	}
	
	/*function - Employee creation */
	public function create_user($data=array()) {
		$sql = "INSERT INTO `mcts_extranet`.`dbo.employees` 
				(FirstName,LastName,EmailName,Password,Role,WorkLocation,
				Department,Designation,JoinDate,Phone1,PANNumber,BankName,BankAccNumber,Manager,Grade,Address,
				FathersName,TempAddress,Phone2,AppLetterRef,BranchDetails,DOB,JobRole,Level,Vertical,PFAccount,AltEmailID,
				Technology,Language,Aadhar,PassportNumber) 
				VALUES 
				('".$data['FirstName']."','".$data['LastName']."','".$data['EmailName']."',
				'".$data['Password']."','".$data['Role']."','".$data['WorkLocation']."','".$data['Department']."',
				'".$data['Designation']."','".date('Y-m-d',strtotime($data['JoinDate']))."','".$data['Phone1']."','".$data['PANNumber']."',
				'".$data['BankName']."','".$data['BankAccNumber']."','".$data['Manager']."','".$data['Grade']."',
				'".$data['Address']."','".$data['FathersName']."','".$data['TempAddress']."','".$data['Phone2']."',
				'".$data['AppLetterRef']."','".$data['BranchDetails']."','".date('Y-m-d',strtotime($data['DOB']))."',
				'".$data['JobRole']."','".$data['Level']."','".$data['Vertical']."','".$data['PFAccount']."',
				'".$data['AltEmailID']."','".$data['Technology']."','".$data['Language']."','".$data['Aadhar']."','".$data['Passport']."')";

		$query=$this->db->query($sql);

		$insert_id = $this->db->insert_id();

		$this->accuredleaves_model->OnJoiningLeaves($insert_id);

		if($query)
			return true;
		else
			return false;
	}
	
	/* function - Login by using Password */
	public function resolve_user_login($username, $password) {
	
		#$password = base64_encode($password);
		$sql = "SELECT Password from `mcts_extranet`.`dbo.employees` where EmailName = '$username' AND Password='$password' AND RelievingDt IS NOT NULL";
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
				Department='".$data['Department']."',Designation='".$data['Designation']."'";

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
		if(isset($data['Passport']))	$sql.=",PassportNumber='".$data['Passport']."'";

		$sql.="WHERE EmployeeID=$id";

		$query=$this->db->query($sql);
		if($query==1)
			return true;
		else
			return false;
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
				$selectcol = "PassportNumber"; 
				$cond="PassportNumber='$columnvalue'";
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
}
