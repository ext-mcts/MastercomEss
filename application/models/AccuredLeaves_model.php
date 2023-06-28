<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AccuredLeaves_model extends CI_Model {

    public function __construct() {
		
		parent::__construct();
		$this->emptable="mcts_extranet.dbo.accuredleaves";
        $this->load->model("user_model");
        $this->load->model("leaves_model");
        $this->load->model("timesheet_model");
        $this->load->model("holidays_model");
	}

    public function add_accuredleaves($data)
    {
        $get_emps = $this->user_model->get_all_users();
        $get_grades = $this->user_model->get_grades();
        
        $cycle = '';
        $prob_period = 0;
        $leave_cons = '';
        
        $grades=array();
        foreach($get_grades as $value)
        {
            $grades[$value->GradeID] = $value->TotalAnnualLeave;
        }

        $get_global_params_prev = $this->user_model->get_global_config_params();

        $cycle_prev = $get_global_params_prev[0]->LeavesCycle;
        $prob_period_prev = $get_global_params_prev[0]->ProbationPeriod;
        $leave_cons_prev = $get_global_params_prev[0]->LeavesConsiderInProbation;
        $caryforward_prev = $get_global_params_prev[0]->LeavesCarryForward;
        $leavescycle_prev = $get_global_params_prev[0]->LeavesCycleYear;
        $cycletype_prev = $get_global_params_prev[0]->CycleType;

        /* Start - taking backup from original leaves table to temp leaves table, based on previous global parameters */
        $sql = "TRUNCATE TABLE `mcts_extranet`.`dbo.temp_accuredleaves`";

        $query=$this->db->query($sql);

        $sql = "SELECT * FROM `mcts_extranet`.`dbo.accuredleaves`";

        $query=$this->db->query($sql);

        $rows = $query->num_rows();
        $diff = 0;
        if($rows>0)
        {
            $sql = "INSERT INTO `mcts_extranet`.`dbo.temp_accuredleaves` (id,EmployeeID,AccuredLeaves,LOP,CreatedDate)
                        SELECT id,EmployeeID,AccuredLeaves,LOP,CreatedDate
                        FROM `mcts_extranet`.`dbo.accuredleaves`";
                        
            $query=$this->db->query($sql);

            $sql = "UPDATE `mcts_extranet`.`dbo.temp_accuredleaves` SET 
                    LeavesCycle='$cycle_prev',ProbationPeriod='$prob_period_prev',LeavesConsiderInProbation='$leave_cons_prev',
                    LeavesCarryForward='$caryforward_prev',LeavesCycleYear='$leavescycle_prev',CycleType='$cycletype_prev'";

            $query=$this->db->query($sql);

            $today = date('m');
            $months = explode('-',$leavescycle_prev);
            $strt = $months[0];
            $end = $months[1];
            $prev_cycle = date('m',strtotime($strt));

            $diff = ($today - $prev_cycle);
            
            if($diff<0)
            {
                $df = date('m',strtotime($end))-date('m');
                $diff = 12-$df;
            }
        }
        /* End - taking backup from original leaves table to temp leaves table, based on previous global parameters */
        
        /* Start - Updating global parameter table with current values */
        $startmonth = date('M',mktime(0, 0, 0, $data['LeavesCycleYear'], 10));
        $lastmonth= date('M', strtotime('+11 month', mktime(0, 0, 0, $data['LeavesCycleYear'], 10)));
        $ls = $startmonth.'-'.$lastmonth.'-'.date('Y');

        $frm = date('m',strtotime($data['LeavesCycleYear']));

        switch($data["Cycle"]) {
            case $data["Cycle"]=='Monthly' || $data["Cycle"]=='monthly':
                //$monthNum  = $data['LeavesCycleYear']+1;
                $monthNum  = $data['LeavesCycleYear'];
                $dateObj   = DateTime::createFromFormat('!m', $monthNum);
                $nextcron = $dateObj->format('M');
                break;
            case $data["Cycle"]=='Quartarly' || $data["Cycle"]=='quartarly':
                //$first = $data['LeavesCycleYear']+2;
                $first = $data['LeavesCycleYear'];
                if($first>12) $first = $first-12;
                $second = $first+3;
                if($second>12) $second = $second-12;
                $third = $second+3;
                if($third>12) $third = $third-12;
                $fourth = $third+3;
                if($fourth>12) $fourth = $fourth-12;

                if($second>=date('m') && $first<=date('m'))
                {
                    $monthNum  = $second;
                    $dateObj   = DateTime::createFromFormat('!m', $monthNum);
                    $nextcron = $dateObj->format('M');
                    break;
                }
                
                if($third>=date('m') && $second<=date('m'))
                {
                    $monthNum  = $third;
                    $dateObj   = DateTime::createFromFormat('!m', $monthNum);
                    $nextcron = $dateObj->format('M');
                    break;
                }
                if($fourth>=date('m') && $first<=date('m'))
                {
                    $monthNum  = $fourth;
                    $dateObj   = DateTime::createFromFormat('!m', $monthNum);
                    $nextcron = $dateObj->format('M');
                    break;
                }
                
            case $data["Cycle"]=='Half Yearly' || $data["Cycle"]=='half yearly':
                //$first = $data['LeavesCycleYear']+5;
                $first = $data['LeavesCycleYear'];
                if($first>12) $first = $first-12;
                $second = $first+6;
                if($second>12) $second = $second-12;
                if($first>=date('m') && $second<date('m'))
                {
                    $monthNum  = $first;
                    $dateObj   = DateTime::createFromFormat('!m', $monthNum);
                    $nextcron = $dateObj->format('M');
                    break;
                }
                if($second>=date('m') && $first<date('m'))
                {
                    $monthNum  = $second;
                    $dateObj   = DateTime::createFromFormat('!m', $monthNum);
                    $nextcron = $dateObj->format('M');
                    break;
                }
                $monthNum  = $second;
                $dateObj   = DateTime::createFromFormat('!m', $monthNum);
                $nextcron = $dateObj->format('M');
                break;
            case $data["Cycle"]=='Yearly' || $data["Cycle"]=='yearly':
                $first = $data['LeavesCycleYear']+12;
                if($first>12) $first = $first-12;
                $monthNum  = $first;
                $dateObj   = DateTime::createFromFormat('!m', $monthNum);
                $nextcron = $dateObj->format('M');
                break;
        }
        $nextcron = date("M", strtotime("+1 month", strtotime($nextcron)));
        
        $sql = "UPDATE `mcts_extranet`.`dbo.globalconfigparameters` 
                SET LeavesConsiderInProbation=NULL, ProbationPeriod=NULL,
                LeavesCarryForward=NULL,LeavesCycleYear=NULL,Status=NULL";

        $query=$this->db->query($sql);

        $sql = "UPDATE `mcts_extranet`.`dbo.globalconfigparameters` 
                SET LeavesConsiderInProbation='".$data["LeavesConsiderInProbation"]."', ProbationPeriod='".$data["ProbationPeriod"]."',
                LeavesCarryForward='".$data["LeavesCarryForward"]."',LeavesCycleYear='".$ls."',CycleType='".$data["CycleType"]."',
                NextCronRunMonth='".$nextcron."',Status=1
                WHERE LeavesCycle='".$data["Cycle"]."'";
        
        $query=$this->db->query($sql);

        /* End - Updating global parameter table with current values */

        /* Start - getting global parameters  after update latest */
        $get_global_params_now = $this->user_model->get_global_config_params();

        $cycle_now = $get_global_params_now[0]->LeavesCycle;
        $prob_period_now = $get_global_params_now[0]->ProbationPeriod;
        $leave_cons_now = $get_global_params_now[0]->LeavesConsiderInProbation;
        $caryforward_now = $get_global_params_now[0]->LeavesCarryForward;
        $leavescycle_now = $get_global_params_now[0]->LeavesCycleYear;
        $cycletype_now = $get_global_params_now[0]->CycleType;
        /* End - getting global parameters  after update latest */

        /* Start - If we try to add leaves on Leaves Cycle Year start/end and carry forward is No  */
        $months = explode('-',$leavescycle_now);
        $strt = $months[0];
        $end = $months[1];
        $lastday = date("t",strtotime($strt));
        if((date('Y-m-d')==date('Y-'.date('m',strtotime($strt)).'-01') || date('Y-m-d')==date('Y-'.date('m',strtotime($end)).'-'.$lastday)) && $caryforward_now=='No')
        {
            $sql2 = "TRUNCATE TABLE `mcts_extranet`.`dbo.accuredleaves`";
            $query=$this->db->query($sql2);

        }
        /* End - If we try to add leaves on Leaves Cycle Year start/end and carry forward is No */

        $accureddata = array();
        foreach($get_emps as $emps)
        {
            $back_leaves = $this->get_backup_leaves($emps->EmployeeID);
            
            $lop = 0;
            $prev_leave = $diff*($grades[$emps->Grade]/12);
            if(!empty($back_leaves))
            {
                $lop = $back_leaves->LOP;
                $prev_leave = $back_leaves->AccuredLeaves;
                if((date('Y-m-d')==date('Y-'.date('m',strtotime($strt)).'-01') || date('Y-m-d')==date('Y-'.date('m',strtotime($end)).'-'.$lastday)) && $caryforward_now=='Yes')
                {
                    $prev_leave = $back_leaves->AccuredLeaves;
                }
                if((date('Y-m-d')==date('Y-'.date('m',strtotime($strt)).'-01') || date('Y-m-d')==date('Y-'.date('m',strtotime($end)).'-'.$lastday)) && $caryforward_now=='No')
                {
                    $prev_leave = 0;
                }
            }
            $accureddata['LOP'] = $lop;
            $accureddata['EmployeeID'] = $emps->EmployeeID;
            $accureddata['Date'] = date('Y-m-d');
            if((($leave_cons_now=='Yes' || $leave_cons_now=='yes' || $leave_cons_now=='YES') && $emps->ProbationConfirmDate!='') ||
                    (($leave_cons_now=='Yes' || $leave_cons_now=='yes' || $leave_cons_now=='YES') && $emps->ProbationConfirmDate==''))
            {
                if($cycle_now=="Monthly" || $cycle_now=="monthly") $total = $grades[$emps->Grade]/12;
                if($cycle_now=="Quarterly" || $cycle_now=="quarterly") $total = $grades[$emps->Grade]/4;
                if($cycle_now=="Half Yearly" || $cycle_now=="Half yearly") $total = $grades[$emps->Grade]/2;
                if($cycle_now=="Yearly" || $cycle_now=="yearly") $total = $grades[$emps->Grade];

                $accureddata['AccuredLeaves'] = $total+$prev_leave;
            }           
            if((($leave_cons_now=='No' || $leave_cons_now=='no' || $leave_cons_now=='NO') && $emps->ProbationConfirmDate==''))
            {
                $accureddata['AccuredLeaves'] = 0;
            }
            if((($leave_cons_now=='No' || $leave_cons_now=='no' || $leave_cons_now=='NO') && $emps->ProbationConfirmDate!=''))
            {
                if($cycle_now=="Monthly" || $cycle_now=="monthly") $total = $grades[$emps->Grade]/12;
                if($cycle_now=="Quartarly" || $cycle_now=="quarterly") $total = $grades[$emps->Grade]/4;
                if($cycle_now=="Half Yearly" || $cycle_now=="Half yearly") $total = $grades[$emps->Grade]/2;
                if($cycle_now=="Yearly" || $cycle_now=="yearly") $total = $grades[$emps->Grade];

                $ts1 = strtotime($emps->JoinDate);
                
                $ts2 = strtotime($emps->ProbationConfirmDate);

                if(date('Y',strtotime($emps->ProbationConfirmDate))==date('Y') || date('Y',strtotime($emps->ProbationConfirmDate))==date('Y')+1 || date('Y',strtotime($emps->ProbationConfirmDate))==date('Y')-1)
                {
                    $year1 = date('Y', $ts1);
                    $year2 = date('Y', $ts2);
    
                    $month1 = date('m', $ts1);
                    $month2 = date('m', $ts2);
    
                    $diff2 = (($year2 - $year1) * 12) + ($month2 - $month1);
    
                    if($cycle_now=="Monthly" || $cycle_now=="monthly") $total = $diff2*($grades[$emps->Grade]/12);
                    if($cycle_now=="Quarterly" || $cycle_now=="quarterly") $total = $diff2*($grades[$emps->Grade]/4);
                    if($cycle_now=="Half Yearly" || $cycle_now=="Half yearly") $total = $diff2*($grades[$emps->Grade]/2);
                    if($cycle_now=="Yearly" || $cycle_now=="yearly") $total = $diff2*($grades[$emps->Grade]);
                }
                //$total = $diff2*($grades[$emps->Grade]/12);

                $accureddata['AccuredLeaves'] = $total+$prev_leave;
            }
            $result = $this->db->query("SELECT * FROM `mcts_extranet`.`dbo.accuredleaves` WHERE EmployeeID =$emps->EmployeeID ");

            if( $result->num_rows() > 0) {
                $this->db->query("UPDATE `mcts_extranet`.`dbo.accuredleaves` 
                            SET  AccuredLeaves='".$accureddata['AccuredLeaves']."',LOP='".$accureddata['LOP']."'
                            WHERE EmployeeID =$emps->EmployeeID ");
            }
            else
            {
                $this->db->query("INSERT INTO `mcts_extranet`.`dbo.accuredleaves` (EmployeeID, AccuredLeaves, LOP)
                                VALUES ('".$accureddata['EmployeeID']."','".$accureddata['AccuredLeaves']."','".$accureddata['LOP']."')");
            }
        }
        
    }

    public function get_backup_leaves($id=null)
    {
        $wherecon = '';
		if($id)
			$wherecon = " WHERE EmployeeID=$id";

        $sql = "SELECT * FROM `mcts_extranet`.`dbo.temp_accuredleaves`".$wherecon;
        $query=$this->db->query($sql);
        
        if($id)
            return $query->row();
        else
            return $query->result();
    }

    public function get_temp_leaves_params()
    {
        $sql = "SELECT LeavesCycle,LeavesConsiderInProbation,ProbationPeriod,LeavesCarryForward,LeavesCycleYear,
                CycleType FROM mcts_extranet.`dbo.temp_accuredleaves` LIMIT 1";

        return $query->row();
    }

    /* calling while updating Probation period of Employee */
    public function CheckProbationLeaves($empdata)
    {
        $get_global_params_now = $this->user_model->get_global_config_params(); // getting Global config parameters

        $cycle_now = $get_global_params_now[0]->LeavesCycle;
        $prob_period_now = $get_global_params_now[0]->ProbationPeriod;
        $leave_cons_now = $get_global_params_now[0]->LeavesConsiderInProbation;
        $caryforward_now = $get_global_params_now[0]->LeavesCarryForward;
        $leavescycle_now = $get_global_params_now[0]->LeavesCycleYear;
        $cycletype_now = $get_global_params_now[0]->CycleType;
        $next_cron = $get_global_params_now[0]->NextCronRunMonth;

        $get_grades = $this->user_model->get_grades();

        $grades=array();
        foreach($get_grades as $value)
        {
            $grades[$value->GradeID] = $value->TotalAnnualLeave;
        }

        $user = $this->user_model->get_user($empdata['EmployeeID']); // getting employee details from Employees table
        
        $probdate = date('Y-m-d');

        if(!empty($empdata['probationdate']))
        {
            $probdate = date('Y-m-d',strtotime($empdata['probationdate']));
        }

        if($leave_cons_now=='no') // if Leaves consider in probation is 'No'
        {
            $joinmonth = date('m',strtotime($user->JoinDate));
            $joinday = date('d',strtotime($user->JoinDate));
            $jointotaldays = date('t',strtotime($user->JoinDate));

            $actualmonths = 0;
            if(date('m',strtotime($user->JoinDate)) > date('m',strtotime($probdate)))
            {
                //$addleaves = ((12-date('m',strtotime($user->JoinDate)))+(($jointotaldays-$joinday)/$jointotaldays)+date('m',strtotime($probdate)))*$grades[$user->Grade]/12;
                $addleaves = ((12-date('m',strtotime($user->JoinDate)))+1+date('m',strtotime($probdate)))*$grades[$user->Grade]/12;
                $actualmonths = 12-date('m',strtotime($user->JoinDate));
            }
            else
            { 
                //$addleaves = ((date('m',strtotime($probdate))-date('m',strtotime($user->JoinDate)))+(($jointotaldays-$joinday)/$jointotaldays))*$grades[$user->Grade]/12;
                $addleaves = ((date('m',strtotime($probdate))-date('m',strtotime($user->JoinDate)))+1)*$grades[$user->Grade]/12;
                $actualmonths = date('m',strtotime($probdate))-date('m',strtotime($user->JoinDate));
                
            }

            $nextcyclemonth = date('m',strtotime($next_cron));

            $advanceleaves = 0;
            
            if($nextcyclemonth < date('m',strtotime($probdate)))
            {
                $advanceleaves = ((12-date('m',strtotime($probdate)))+$nextcyclemonth)*$grades[$user->Grade]/12;
            }
            // else
            // {
            //     $advanceleaves = $nextcyclemonth-date('m',strtotime($probdate))*$grades[$user->Grade]/12;
            // }
            // in else part negative is coming

            $ts1 = strtotime($user->JoinDate);
            $ts2 = strtotime(date('Y-m-d',strtotime($probdate)));

            $year1 = date('Y', $ts1);
            $year2 = date('Y', $ts2);

            $month1 = date('m', $ts1);
            $month2 = date('m', $ts2);

            $diff2 = (($year2 - $year1) * 12) + ($month2 - $month1);
            
            $excesleaves = 0;
            if($diff2>=12)
            {
                $excesmonths=$diff2-$actualmonths;
                
                if($excesmonths==0)
                {
                    $excesleaves = 12*$grades[$user->Grade]/12;
                }
                else
                {
                    $excesleaves = $excesmonths*$grades[$user->Grade]/12;
                }
            }

            //echo $addleaves;echo '<br>';echo $excesleaves;echo '<br>';echo $advanceleaves;exit;
            $accuredleaves = $addleaves+$advanceleaves+$excesleaves;

            $result = $this->db->query("SELECT * FROM `mcts_extranet`.`dbo.accuredleaves` WHERE EmployeeID ='".$empdata['EmployeeID']."' ");

            if( $result->num_rows() > 0) {
                $this->db->query("UPDATE `mcts_extranet`.`dbo.accuredleaves` 
                            SET  AccuredLeaves='".$accuredleaves."',LOP=0
                            WHERE EmployeeID ='".$empdata['EmployeeID']."' ");
            }
            else
            {
                $this->db->query("INSERT INTO `mcts_extranet`.`dbo.accuredleaves` (EmployeeID, AccuredLeaves, LOP)
                                VALUES ('".$empdata['EmployeeID']."','".$accuredleaves."',0)");
            }

        }

        $this->db->query("UPDATE `mcts_extranet`.`dbo.employees` 
                            SET  ProbationConfirmDate='".date('Y-m-d',strtotime($probdate))."'
                            WHERE EmployeeID ='".$empdata['EmployeeID']."' ");

        return true;
    }

    /* calling while joining of Employee */
    public function OnJoiningLeaves($empid, $nextcycle=null, $nextcronchangedate=null, $leavetype=null)
    {
        $get_global_params_now = $this->user_model->get_global_config_params(); // getting Global config parameters

        $cycle_now = $get_global_params_now[0]->LeavesCycle;
        $prob_period_now = $get_global_params_now[0]->ProbationPeriod;
        $leave_cons_now = $get_global_params_now[0]->LeavesConsiderInProbation;
        $caryforward_now = $get_global_params_now[0]->LeavesCarryForward;
        $leavescycle_now = $get_global_params_now[0]->LeavesCycleYear;
        $cycletype_now = $get_global_params_now[0]->CycleType;
        $next_cron = $get_global_params_now[0]->NextCronRunMonth;

        $get_grades = $this->user_model->get_grades();

        $grades=array();
        foreach($get_grades as $value)
        {
            $grades[$value->GradeID] = $value->TotalAnnualLeave;
        }

        $user = $this->user_model->get_user($empid); // getting employee details from Employees table

        /*$method = new ReflectionMethod('AccuredLeaves_model', 'OnJoiningLeaves');
        $num = $method->getNumberOfParameters();*/

        if($leave_cons_now=='yes' || $leave_cons_now=='Yes') // if Leaves consider in probation is 'Yes'
        {
            if($nextcycle=='')
                $currentcycle = date('m',strtotime($next_cron));
            else
                $currentcycle = $nextcycle;

            $joinmonth = date('m',strtotime($user->JoinDate));
            $joinday = date('d',strtotime($user->JoinDate));
            $jointotaldays = date('t',strtotime($user->JoinDate));
            
            if($nextcronchangedate!='')
            {
                $dt = date('Y-'.$nextcronchangedate.'-d');
                $joinmonth = date('m',strtotime($nextcronchangedate));
                $joinday = date('d',strtotime($nextcronchangedate));
                $jointotaldays = date('t',strtotime($nextcronchangedate));
            }

            if($currentcycle < $joinmonth)
            {
                //$addleaves = ((12-$joinmonth)+$nextcycle+(($jointotaldays-$joinday)/$jointotaldays))*$grades[$user->Grade]/12;
                $addleaves = ((12-$joinmonth)+$currentcycle+1)*$grades[$user->Grade]/12;
            }
            else
            {
                //$addleaves = (($nextcycle-$joinmonth)+(($jointotaldays-$joinday)/$jointotaldays))*$grades[$user->Grade]/12;
                $addleaves = (($currentcycle-$joinmonth)+1)*$grades[$user->Grade]/12;
            }

            $cycle = 0;
            switch($leavetype){
                case "Monthly":
                    $cycle = 1;
                    break; 
                case "Quartarly":
                    $cycle = 3;
                    break;  
                case "Half Yearly":
                    $cycle = 6;
                    break;
                case 'Yearly':
                    $cycle = 12;
                    break;
            }

            if($leavetype!='')
            {
                $addleaves = ($joinmonth-date('m'))*($grades[$user->Grade]/12);

                if($joinmonth<date('m'))
                {
                    $addleaves = $this->modulo(($joinmonth-date('m')), $cycle)*($grades[$user->Grade]/12);
                }

                if(($cycle_now=='Half Yearly' || $cycle_now=='Half yearly') && ($leavetype=='Yearly' || $leavetype=='Yearly'))
                {
                    if(date('m')>6)    
                        $addleaves = intval(($joinmonth-date('m'))/12)*($grades[$user->Grade]/12); //12 is current cycle
                    else
                        $addleaves = ($joinmonth-6)*($grades[$user->Grade]/12); 
                }
            }
        }
        else
        {
            $addleaves = 0;
        }
        
        if($leavetype=='')
        {
            $this->db->query("INSERT INTO `mcts_extranet`.`dbo.accuredleaves` (EmployeeID, AccuredLeaves, LeaveBalance, LOP)
                                VALUES ('".$empid."','".$addleaves."','".$addleaves."',0)");
        }

        return $addleaves;
    }

    public function CheckLeavesOnReleavingDate($empdata, $nextcron = null,  $newnextcronchangedate=null, $leavetype=null)
    {

        $get_global_params_now = $this->user_model->get_global_config_params(); // getting Global config parameters

        $cycle_now = $get_global_params_now[0]->LeavesCycle;
        $prob_period_now = $get_global_params_now[0]->ProbationPeriod;
        $leave_cons_now = $get_global_params_now[0]->LeavesConsiderInProbation;
        $caryforward_now = $get_global_params_now[0]->LeavesCarryForward;
        $leavescycle_now = $get_global_params_now[0]->LeavesCycleYear;
        $cycletype_now = $get_global_params_now[0]->CycleType;
        $next_cron = $get_global_params_now[0]->NextCronRunMonth;

        if(!empty($nextcron))
            $next_cron =   $nextcron;
            
        $get_grades = $this->user_model->get_grades();

        $grades=array();
        foreach($get_grades as $value)
        {
            $grades[$value->GradeID] = $value->TotalAnnualLeave;
        }

        $user = $this->user_model->get_user($empdata['EmployeeID']); // getting employee details from Employees table

        if($user->ProbationConfirmDate=='' && $leave_cons_now=='no')
        {
            return true;
        }
        else 
        {
            $startTimeStamp = strtotime($user->JoinDate);

            $relievedate = date('Y-m-d');

            if(!empty($empdata['relievingdate']))
            {
                $relievedate = date('Y-m-t',strtotime($user->RelievingDt));
            }

            if($newnextcronchangedate!='')
            {
                $dateObj   = DateTime::createFromFormat('!m', $newnextcronchangedate);
                $mnth = $dateObj->format('M');
                $relievedate = date('Y-m-t',strtotime($mnth));
            }

            $endTimeStamp = strtotime($relievedate);

            $timeDiff = abs($endTimeStamp - $startTimeStamp);

            $numberDays = $timeDiff/86400;  // 86400 seconds in one day

            // and you might want to convert to integer
            $numberDays = intval($numberDays);

            $exp = explode('-',$leavescycle_now);
            
            $stratdate = date('m',strtotime($user->JoinDate));

            if($numberDays>365)
            {
                $stratdate = date('m',strtotime($exp[0]));
            }
        }

        $nextcyclemonth = date('m',strtotime($next_cron));
       //echo $relievedate;echo '<br>';echo $nextcyclemonth;exit;
        if($nextcyclemonth < $stratdate)
        {
            $reducedleaves = ((12-date('m',strtotime($relievedate)))+((date('t',strtotime($relievedate))-date('d',strtotime($relievedate)))/date('t',strtotime($relievedate)))+$nextcyclemonth)*$grades[$user->Grade]/12;
        }
        else 
        {
            $reducedleaves = (($nextcyclemonth-date('m',strtotime($relievedate)))+((date('t',strtotime($relievedate))-date('d',strtotime($relievedate)))/date('t',strtotime($relievedate))))*$grades[$user->Grade]/12;
        }
//echo abs($reducedleaves);exit;

        if($leavetype!='')
        {
            $reducedleaves = ($nextcyclemonth - $newnextcronchangedate)*$grades[$user->Grade]/12;

            if($nextcyclemonth < $newnextcronchangedate)
            {
                $reducedleaves = ((12+$nextcyclemonth) - $newnextcronchangedate)*$grades[$user->Grade]/12;
            }
        }

        return abs($reducedleaves);
    }


    public function RunScheduleForLeaveCalculation()
    {
        $get_global_params_now = $this->user_model->get_global_config_params();

        $cycle_now = $get_global_params_now[0]->LeavesCycle;
        $prob_period_now = $get_global_params_now[0]->ProbationPeriod;
        $leave_cons_now = $get_global_params_now[0]->LeavesConsiderInProbation;
        $caryforward_now = $get_global_params_now[0]->LeavesCarryForward;
        $leavescycle_now = $get_global_params_now[0]->LeavesCycleYear;
        $cycletype_now = $get_global_params_now[0]->CycleType;
        $next_cron = $get_global_params_now[0]->NextCronRunMonth;
        
        $get_grades = $this->user_model->get_grades();

        $grades=array();
        foreach($get_grades as $value)
        {
            $grades[$value->GradeID] = $value->TotalAnnualLeave;
        }

        $months = explode('-',$leavescycle_now);
        $strt = $months[0];
        $end = $months[1];

        // if(date('m-d')==date('m-t',strtotime($next_cron))) // here we are checking cron run date is last day of month or not
        // {
            $CurrentNextCycleDate = 0; 

            switch($cycle_now){
                case "Monthly":
                    //$monthNum  = $data['LeavesCycleYear']+1;
                    $first  = date('m',strtotime($next_cron))+1;
                    $monthNum  = $first;
                    $dateObj   = DateTime::createFromFormat('!m', $monthNum);
                    $CurrentNextCycleDate = $dateObj->format('M');
                    break;
                case 'Quartarly':
                    //$first = $data['LeavesCycleYear']+2;
                    $first = date('m',strtotime($next_cron))+3;
                    if($first>12) $first = $first-12;
                    $monthNum  = $first;
                    $dateObj   = DateTime::createFromFormat('!m', $monthNum);
                    $CurrentNextCycleDate = $dateObj->format('M');
                    break;
                case 'Half Yearly':
                    $first = date('m',strtotime($next_cron))+6;
                    if($first>12) $first = $first-12;
                    $monthNum  = $first;
                    $dateObj   = DateTime::createFromFormat('!m', $monthNum);
                    $CurrentNextCycleDate = $dateObj->format('M');
                    break;
                case 'Yearly':
                    $first = date('m',strtotime($next_cron))+12;
                    if($first>12) $first = $first-12;
                    $monthNum  = $first;
                    $dateObj   = DateTime::createFromFormat('!m', $monthNum);
                    $CurrentNextCycleDate = $dateObj->format('M');
                    break;
            }
            //echo date('t',strtotime($CurrentNextCycleDate));exit;
            $CurrentCycleDate = date('m',strtotime($next_cron));

            if($leave_cons_now=='yes' || $leave_cons_now=='Yes')
            {
                $get_emps = $this->user_model->GetAllActiveEmps();
            }
            else
            {
                $get_emps = $this->user_model->GetProbationCompletionEmps();
            }

            //print_r($get_emps);exit;
            $addleaves = 0;

            switch($cycle_now){
                case "Monthly":
                    $addleaves = 1;
                    break; 
                case "Quartarly":
                    $addleaves = 3;
                    break;  
                case "Half Yearly":
                    $addleaves = 6;
                    break;
                case 'Yearly':
                    $addleaves = 12;
                    break;
            }

            foreach($get_emps as $emps)
            {
                //$advanceleaves = $this->OnJoiningLeaves($emps->EmployeeID, $CurrentCycleDate, date('m',strtotime($CurrentNextCycleDate)), $scheule='schedule');
                $advanceleaves = $addleaves*$grades[$emps->Grade]/12;
                $result = $this->db->query("SELECT * FROM `mcts_extranet`.`dbo.accuredleaves` WHERE EmployeeID =$emps->EmployeeID ");

                if( $result->num_rows() > 0) {
                    if(date('m-d')==date('m-t',strtotime($end))){ // here checking today is last day of Leaves Cycle or not
                        if($caryforward_now=='yes' || $caryforward_now=='Yes')
                        {
                            $this->db->query("UPDATE mcts_extranet.`dbo.accuredleaves` as t1,
                                            (select accuredleaves,LOP,LeaveBalance from mcts_extranet.`dbo.accuredleaves` where EmployeeID=$emps->EmployeeID) as t2
                                            set t1.accuredleaves = t2.accuredleaves+$advanceleaves,t1.LOP=t2.LOP+0,
                                            t1.LeaveBalance = t2.LeaveBalance+$advanceleaves
                                            where t1.EmployeeID=$emps->EmployeeID");
                        }
                        if($caryforward_now=='no')
                        {
                            $this->db->query("UPDATE `mcts_extranet`.`dbo.accuredleaves` 
                                        SET  AccuredLeaves='".$advanceleaves."',LOP=0
                                        WHERE EmployeeID =$emps->EmployeeID ");
                        }
                    }
                    else
                    {
                        $this->db->query("UPDATE mcts_extranet.`dbo.accuredleaves` as t1,
                                        (select accuredleaves,LOP,LeaveBalance from mcts_extranet.`dbo.accuredleaves` where EmployeeID=$emps->EmployeeID) as t2
                                        set t1.accuredleaves = t2.accuredleaves+$advanceleaves,t1.LOP=t2.LOP+0,
                                        t1.LeaveBalance = t2.LeaveBalance+$advanceleaves
                                        where t1.EmployeeID=$emps->EmployeeID");
                    }
                }
                else
                {
                    $this->db->query("INSERT INTO `mcts_extranet`.`dbo.accuredleaves` (EmployeeID, AccuredLeaves, LOP)
                                    VALUES ('".$emps->EmployeeID."','".$advanceleaves."',0)");
                }
            }

            $monthNum  = date('m',strtotime($CurrentNextCycleDate));
            $dateObj   = DateTime::createFromFormat('!m', $monthNum);
            $CurrentNextCycleDate = $dateObj->format('M');
            
            $this->db->query("UPDATE `mcts_extranet`.`dbo.globalconfigparameters` 
                        SET NextCronRunMonth='".$CurrentNextCycleDate."' WHERE Status=1");

            
        //}
        return true;
    }

    public function ChangePolicyRightNow($data)
    {
        $get_global_params_now = $this->user_model->get_global_config_params();

        $cycle_now = $get_global_params_now[0]->LeavesCycle;
        $prob_period_now = $get_global_params_now[0]->ProbationPeriod;
        $leave_cons_now = $get_global_params_now[0]->LeavesConsiderInProbation;
        $caryforward_now = $get_global_params_now[0]->LeavesCarryForward;
        $leavescycle_now = $get_global_params_now[0]->LeavesCycleYear;
        $cycletype_now = $get_global_params_now[0]->CycleType;
        $next_cron = $get_global_params_now[0]->NextCronRunMonth;
        
        $get_grades = $this->user_model->get_grades();

        $grades=array();
        foreach($get_grades as $value)
        {
            $grades[$value->GradeID] = $value->TotalAnnualLeave;
        }

        $current_cycle = 0;

        switch($cycle_now){
            case "Monthly":
                $current_cycle = 1;
                break; 
            case "Quartarly":
                $current_cycle = 3;
                break;  
            case "Half Yearly":
                $current_cycle = 6;
                break;
            case 'Yearly':
                $current_cycle = 12;
                break;
        }

        $next_cycle = 0;

        switch($data['Cycle']){
            case "Monthly":
                $next_cycle = 1;
                break; 
            case "Quartarly":
                $next_cycle = 3;
                break;  
            case "Half Yearly":
                $next_cycle = 6;
                break;
            case 'Yearly':
                $next_cycle = 12;
                break;
        }
        
        switch($data["Cycle"]) {
            case $data["Cycle"]=='Monthly' || $data["Cycle"]=='monthly':
                //$monthNum  = $data['LeavesCycleYear']+1;
                //$monthNum  = $data['LeavesCycleYear'];
                $monthNum  = date('m');
                $monthNum  = $monthNum+1;
                $dateObj   = DateTime::createFromFormat('!m', $monthNum);
                $new_nextcycle = $dateObj->format('M');
                $new_nextcycle = date("M", strtotime("-1 month", strtotime($new_nextcycle)));
                break;
            case $data["Cycle"]=='Quartarly' || $data["Cycle"]=='quartarly':
                $monthNum  = $this->calculateQuarter($data['LeavesCycleYear']);
                $dateObj   = DateTime::createFromFormat('!m', $monthNum);
                $new_nextcycle = $dateObj->format('M');
                $new_nextcycle = date("M", strtotime($new_nextcycle));
                break;
            case $data["Cycle"]=='Half Yearly' || $data["Cycle"]=='half yearly':
                $monthNum  = $this->calculateHalfyear($data['LeavesCycleYear']);
                $dateObj   = DateTime::createFromFormat('!m', $monthNum);
                $new_nextcycle = $dateObj->format('M');
                $new_nextcycle = date("M", strtotime($new_nextcycle));
                break;
            case $data["Cycle"]=='Yearly' || $data["Cycle"]=='yearly':
                $monthNum  = $this->calculateYear($data['LeavesCycleYear']);
                $dateObj   = DateTime::createFromFormat('!m', $monthNum);
                $new_nextcycle = $dateObj->format('M');
                $new_nextcycle = date("M", strtotime($new_nextcycle));
                break;
        }

        //echo $new_nextcycle;exit;
        $startmonth = date('M',mktime(0, 0, 0, $data['LeavesCycleYear'], 10));
        $lastmonth= date('M', strtotime('+11 month', mktime(0, 0, 0, $data['LeavesCycleYear'], 10)));
        $ls = $startmonth.'-'.$lastmonth;

        $get_emps = $this->user_model->GetAllActiveEmps();

        if($current_cycle > $next_cycle) // Debit leaves
        {
            foreach($get_emps as $emps)
            {
                //$result = $this->db->query("SELECT * FROM `mcts_extranet`.`dbo.accuredleaves` WHERE EmployeeID =$emps->EmployeeID ");
                $date = array('EmployeeID'=>$emps->EmployeeID);
                $reducedleaves = $this->CheckLeavesOnReleavingDate($date, $next_cron, date('m',strtotime($new_nextcycle)), 'debit');
                echo 'reduced - '.$emps->EmployeeID.'-'.$reducedleaves;echo '<br>';
                /*if( $result->num_rows() > 0) {
                    $this->db->query("UPDATE mcts_extranet.`dbo.accuredleaves` as t1,
                                        (select accuredleaves,LOP,LeaveBalance from mcts_extranet.`dbo.accuredleaves` where EmployeeID=$emps->EmployeeID) as t2
                                        set t1.accuredleaves = t2.accuredleaves-$reducedleaves,t1.LOP=t2.LOP+0,t1.LeaveBalance = t2.LeaveBalance-$reducedleaves
                                        where t1.EmployeeID=$emps->EmployeeID");
                }
                else
                {
                    $this->db->query("INSERT INTO `mcts_extranet`.`dbo.accuredleaves` (EmployeeID, AccuredLeaves, LeaveBalance, LOP)
                                    VALUES ('".$emps->EmployeeID."','".$reducedleaves."','".$reducedleaves."',0)");
                }*/
            }
        }

        if($current_cycle < $next_cycle) //Credit leaves
        {
            foreach($get_emps as $emps)
            {
                //$result = $this->db->query("SELECT * FROM `mcts_extranet`.`dbo.accuredleaves` WHERE EmployeeID =$emps->EmployeeID ");

                $addleaves = $this->OnJoiningLeaves($emps->EmployeeID,date('m',strtotime($next_cron)), $new_nextcycle,$data['Cycle']);
                echo 'added - '.$emps->EmployeeID.'-'.$addleaves;echo '<br>';
                /*if( $result->num_rows() > 0) {
                    $this->db->query("UPDATE mcts_extranet.`dbo.accuredleaves` as t1,
                                        (select accuredleaves,LOP,LeaveBalance from mcts_extranet.`dbo.accuredleaves` where EmployeeID=$emps->EmployeeID) as t2
                                        set t1.accuredleaves = t2.accuredleaves+$addleaves,t1.LOP=t2.LOP+0,t1.LeaveBalance = t2.LeaveBalance+$addleaves
                                        where t1.EmployeeID=$emps->EmployeeID");
                }
                else
                {
                    $this->db->query("INSERT INTO `mcts_extranet`.`dbo.accuredleaves` (EmployeeID, AccuredLeaves, LeaveBalance, LOP)
                                    VALUES ('".$emps->EmployeeID."','".$addleaves."','".$addleaves."',0)");
                }*/
            }
        }

        $this->db->query("UPDATE `mcts_extranet`.`dbo.globalconfigparameters` 
                        SET Status=0");

        $this->db->query("UPDATE `mcts_extranet`.`dbo.globalconfigparameters` 
                        SET NextCronRunMonth='".$new_nextcycle."', LeavesCycleYear='".$ls."',Status=1
                        WHERE LeavesCycle='".$data["Cycle"]."'");

        return true;
    }

    public function getAccuredLeaves($empid)
    {
        $sql = "SELECT * FROM `mcts_extranet`.`dbo.accuredleaves` WHERE EmployeeID = $empid";
        $query = $this->db->query($sql);
        return $query->row();

    }

    public function calculateQuarter($cycle)
    {
        $current_month = date('m');
        
        $fst = $cycle;
        $scnd = $fst+2;
        
        $thrd = $scnd+3;
        
        $frth = $thrd+3;
        
        $ffth = $frth+3;
        
//echo $fst;echo '<br>';echo $scnd;echo '<br>';echo $thrd;echo '<br>';echo $frth;echo '<br>';echo $ffth;echo '<br>';
//echo $sixth;echo '<br>';echo $svnth;echo '<br>';echo $eth;echo '<br>';exit;
        if($current_month>=abs($fst) && $current_month<=abs($scnd))
        {
            if($scnd>12) $scnd=$scnd-12;
            $dateObj   = DateTime::createFromFormat('!m', abs($scnd));
            $new_nextcycle = $dateObj->format('M');
            $end_date = date('m',strtotime($new_nextcycle));
        }
        else  if($current_month>=abs($scnd) && $current_month<=abs($thrd))
        {
            if($thrd>12) $thrd=$thrd-12;
            $dateObj   = DateTime::createFromFormat('!m', abs($thrd));
            $new_nextcycle = $dateObj->format('M');
            $end_date = date('m',strtotime($new_nextcycle));
        }
        else  if($current_month>=abs($thrd) && $current_month<=abs($frth))
        {
            if($frth>12) $frth=$frth-12;
            $dateObj   = DateTime::createFromFormat('!m', abs($frth));
            $new_nextcycle = $dateObj->format('M');
            $end_date = date('m',strtotime($new_nextcycle));
        }
        else  if($current_month>=abs($frth) && $current_month<=abs($ffth))
        {
            if($ffth>12) $ffth=$ffth-12;
            $dateObj   = DateTime::createFromFormat('!m', abs($ffth));
            $new_nextcycle = $dateObj->format('M');
            $end_date = date('m',strtotime($new_nextcycle));
        }

        return $end_date;
    }

    public function calculateHalfyear($cycle)
    {
        $current_month = date('m');
        
        $fst = $cycle;
        $scnd = $fst+5;
        $thrd = $scnd+6;

        if($current_month>=abs($fst) && $current_month<=abs($scnd))
        {
            if($scnd>12) $scnd=$scnd-12;
            $dateObj   = DateTime::createFromFormat('!m', abs($scnd));
            $new_nextcycle = $dateObj->format('M');
            $end_date = date('m',strtotime($new_nextcycle));
        }
        else  if($current_month>=abs($scnd) && $current_month<=abs($thrd))
        {
            if($thrd>12) $thrd=$thrd-12;
            $dateObj   = DateTime::createFromFormat('!m', abs($thrd));
            $new_nextcycle = $dateObj->format('M');
            $end_date = date('m',strtotime($new_nextcycle));
        }

        return $end_date;
    }

    public function calculateYear($cycle)
    {
        $current_month = date('m');
        
        $fst = $cycle;
        $scnd = $fst+11;

        if($current_month>=abs($fst) && $current_month<=abs($scnd))
        {
            if($scnd>12) $scnd=$scnd-12;
            $dateObj   = DateTime::createFromFormat('!m', abs($scnd));
            $new_nextcycle = $dateObj->format('M');
            $end_date = date('m',strtotime($new_nextcycle));
        }
        
        return $end_date;
    }

    function modulo($dividend, $divisor) {
        $result = $dividend % $divisor;
        return $result < 0 ? $result + $divisor : $result;
    }

    public function CalculateLOP($data)
    {
        $get_global_params_now = $this->user_model->get_global_config_params();

        $cycle_now = $get_global_params_now[0]->LeavesCycle;
        $prob_period_now = $get_global_params_now[0]->ProbationPeriod;
        $leave_cons_now = $get_global_params_now[0]->LeavesConsiderInProbation;
        $caryforward_now = $get_global_params_now[0]->LeavesCarryForward;
        $leavescycle_now = $get_global_params_now[0]->LeavesCycleYear;
        $cycletype_now = $get_global_params_now[0]->CycleType;
        $next_cron = $get_global_params_now[0]->NextCronRunMonth;

        if($leave_cons_now=='yes' || $leave_cons_now=='Yes')
        {
            $get_emps = $this->user_model->GetAllActiveEmps();
        }
        else
        {
            $get_emps = $this->user_model->GetProbationCompletionEmps();
        }

        $getholidaysformonth = $this->holidays_model->get_holidays_by_location($data['month'],$data['year'],1);
        
        foreach($get_emps as $emps)
        {
            $approvedleavesformonth = $this->leaves_model->getLeavesByEmpID($emps->EmployeeID,1,$data['month']);
            $unapprovedleavesformonth = $this->leaves_model->getLeavesByEmpID($emps->EmployeeID,0,$data['month']);
            //$timesheetfilleddays = $this->timesheet_model->get_timesheet_by_empid($data['month'],164);

            $aprvdleaves = 0;
            $unaprvdleaves = 0;

            if(count($approvedleavesformonth)>=1)
            {
                $aprvdleaves = $approvedleavesformonth->leaves;
            }

            if(count($unapprovedleavesformonth)>=1)
            {
                $unaprvdleaves = $unapprovedleavesformonth->leaves;
            }

            /*$availabledates = array();

            foreach($timesheetfilleddays as $tsdays)
            {
                $availabledates[] = date('Y-m-d',strtotime($tsdays->TSDate));
            }
            $timesheetnotfillingdays = array();

            $dateStart = date_create(date('Y')."-".$data['month']."-01");
            $dateEnd   = date_create(date('Y')."-".$data['month']."-".date('t',strtotime($data['month'])));
            $interval  = new DateInterval('P1D');
            $period    = new DatePeriod($dateStart, $interval, $dateEnd);
            foreach($period as $day) {
                $formatted = $day->format("Y-m-d");
                if(!in_array($formatted, $availabledates)) $timesheetnotfillingdays[] = $formatted;
            }*/
            
            
            /*$weekspermonth = $this->weeks($data['month'], $data['year']);
            $weekoffspermonth = $weekspermonth*(7-5);*/

            $weekoffdaysforemp = 'Sat,Sun';

            $empweekoffdays = $this->getEmpWeekOffDays($emps->EmployeeID, $data['month'], $data['year']);

            if(!empty($empweekoffdays))
            {
                $weekoffdaysforemp = $empweekoffdays->WeekOffDays;
            }

            $weekspermonth = $this->getWeekOffDaysCount($weekoffdaysforemp, $data['month'], $data['year']);
            
            $weekoffspermonth = $weekspermonth*(7-$emps->WorkingDaysPerWeek);

            $totaldaysinmonth = date('t',strtotime($data['month']));

            $timesheetmisseddays = $totaldaysinmonth-$weekoffspermonth-count($timesheetfilleddays)-$getholidaysformonth->holidays;

            $getaccuredleaves = $this->getAccuredLeaves($emps->EmployeeID);
           
            $lop = 0;

            $leaveBalance = $getaccuredleaves->AccuredLeaves;

            if($getaccuredleaves->LeaveBalance < 0)
            { 
                $lop_final = $aprvdleaves + ($getaccuredleaves->LeaveBalance*-1) + $unaprvdleaves;
            }

            if($getaccuredleaves->LeaveBalance==0)
            { 
                $lop_final = $aprvdleaves+$unaprvdleaves + $timesheetmisseddays;
            }
            else if(($getaccuredleaves->LeaveBalance - $aprvdleaves)<0)
            {
                
                $lop_final = (($getaccuredleaves->LeaveBalance - $aprvdleaves) * -1)+$unaprvdleaves + $timesheetmisseddays;
                $leaveBalance = 0;
            }
            else if(($getaccuredleaves->LeaveBalance - $aprvdleaves) > 0)
            {
                $lop_final = $unaprvdleaves + $timesheetmisseddays;
                $leaveBalance = $getaccuredleaves->LeaveBalance - $aprvdleaves;
            }

            $this->db->query("UPDATE `mcts_extranet`.`dbo.accuredleaves` SET  AccuredLeaves='".$leaveBalance."', LOP='".$lop_final."', LeaveBalance='".$leaveBalance."', 
                            ApprvdLeavesTakenTillDate='".$aprvdleaves."', UnApprovedLeavesForTheMonth='".$unaprvdleaves."'
                            WHERE EmployeeID='".$emps->EmployeeID."'");

        }
    }
    return true;

    function getWeeks($date, $rollover)
    {
        $cut = substr($date, 0, 8);
        $daylen = 86400;

        $timestamp = strtotime($date);
        $first = strtotime($cut . "00");
        $elapsed = ($timestamp - $first) / $daylen;

        $weeks = 1;

        for ($i = 1; $i <= $elapsed; $i++)
        {
            $dayfind = $cut . (strlen($i) < 2 ? '0' . $i : $i);
            $daytimestamp = strtotime($dayfind);

            $day = strtolower(date("l", $daytimestamp));

            if($day == strtolower($rollover))  $weeks ++;
        }

        return $weeks;
    }

    function weeks($month, $year){
        $firstday = date("w", mktime(0, 0, 0, $month, 1, $year)); 
        $lastday = date("t", mktime(0, 0, 0, $month, 1, $year)); 
        if ($firstday!=0) $count_weeks = 1 + ceil(($lastday-8+$firstday)/7);
        else $count_weeks = 1 + ceil(($lastday-1)/7);
        return $count_weeks;
    } 

    function getWeekOffDaysCount($weekoffdays, $month, $year, )
    {
        $days = explode(',', $weekoffdays);

        $noofdays = date('t',strtotime($month));

        $weekoffs = 0;

        for($i=1; $i<=$noofdays;$i++)
        {
            if(count($days)==2)
            {
                $day = $days[0];
                $day2 = $days[1];
                if(date('D',strtotime($year.'-'.$month.'-'.$i))==$day || date('D',strtotime($year.'-'.$month.'-'.$i))==$day2)
                {
                    $weekoffs++;
                }
            }
            else
            {
                $day = $days[0];

                if(date('D',strtotime($year.'-'.$month.'-'.$i))==$day)
                {
                    $weekoffs++;
                }
            }
        }
        return $weekoffs;
    }

    public function getEmpWeekOffDays($empid, $month, $year)
    {
        $sql = "SELECT * FROM mcts_extranet.`dbo.employeeshifts` WHERE EmployeeID=$empid AND ShiftMonth=$month AND ShiftYear=$year";
        $query=$this->db->query($sql);
		return $query->row();
    }
}