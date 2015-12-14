<?php
class CRM_Casereports_Form_Report_coach extends CRM_Report_Form {

	protected $_addressField = FALSE;
	protected $_emailField = FALSE;
	protected $_summary = NULL;
	protected $_customGroupExtends = FALSE;
	protected $_customGroupGroupBy = FALSE;
	protected $_caseType, $_customGroup, $_customField, $_activityTypesOptionGroup, $_activityTypes, $_statusGroup, $_openedCaseStatusses, $_coachIdentifier;
	
	function __construct() {
		$this->_groupFilter = FALSE;
		$this->_tagFilter   = FALSE;
		$this->_columns = array(
			'civicrm_activity`' => array(
				'dao' => 'CRM_Activity_DAO_Activity',
				'fields' => array(
					'activity_id' => array(
						'no_display' => TRUE,
						'required' => TRUE,
					),
					'activity_type_id' => array(
						'title' => 'Type Activiteit'
					),
					'activity_date_time' => array(
						'title' => 'Datum Activiteit'
					),
					'activity_duration' => array(
						'title' => 'Duur Activiteit'
					),
					'activity_status_id' => array(
						'title' => 'Status Activiteit'
					),
				)
			),
		);
		parent::__construct();
		$this->fetchCaseType();
		$this->fetchChequenummer();
		$this->fetchActivityTypes();
		$this->fetchStatusGroup();
		$this->fetchCaseStatusses();
		$this->fetchCoachIdentifier();
	}
	
	function fetchCaseType() {
		try {
			$this->_caseType = civicrm_api3('CaseType','getsingle',array("name" => "coachingstraject"));
		} catch (Exception $e) {
			die("<h1>Casetype Coachingstraject ontbreekt!</h1>");
		}
	}
	
	function fetchChequenummer() {
		try {
			$this->_customGroup = civicrm_api3('CustomGroup','getsingle',array("name" => "Coachingsinformatie"));
			$this->_customField = civicrm_api3('CustomField','getsingle',array("name" => "Chequenummer", "custom_group_id" => $this->_customGroup['id']));
		} catch (Exception $e) {
			die("<h1>Custom veld Chequenummer of custom group Coachingsinformatie ontbreekt!</h1>");
		}
	}
	
	function fetchActivityTypes() {
		try {
			$this->_activityTypesOptionGroup			= civicrm_api3('OptionGroup','getsingle',array("name" => "activity_type"));
			$this->_activityTypes 						= new stdClass;
			$this->_activityTypes->intakegesprek 		= civicrm_api3('OptionValue','getsingle',array("name" => "intakegesprek", "option_group_id" => $this->_activityTypesOptionGroup['id']));
			$this->_activityTypes->verdiepingsgesprek 	= civicrm_api3('OptionValue','getsingle',array("name" => "verdiepingsgesprek", "option_group_id" => $this->_activityTypesOptionGroup['id']));
			$this->_activityTypes->synthese 			= civicrm_api3('OptionValue','getsingle',array("name" => "synthese", "option_group_id" => $this->_activityTypesOptionGroup['id']));
		} catch (Exception $e) {
			die("<h1>Activiteitstypes ontbreken!</h1>");
		}
	}
	
	function fetchStatusGroup() {
		try {
			$this->_statusGroup	= civicrm_api3('OptionGroup','getsingle',array("name" => "activity_status"));
		} catch (Exception $e) {
			die("<h1>Status option group ontbreekt!</h1>");
		}
	}
	
	function fetchCaseStatusses() {
		try {
			$this->_openedCaseStatusses					= "(";
			$_caseStatusGroup							= civicrm_api3('OptionGroup','getsingle',array("name" => "case_status"));
			$_openedStatusses 							= civicrm_api3('OptionValue','get',array("grouping" => "Opened", "option_group_id" => $_caseStatusGroup['id']));
			if(isset($_openedStatusses['values'])) {
				foreach($_openedStatusses['values'] as $_status){
					$this->_openedCaseStatusses .= $_status['value'].",";
				}
				$this->_openedCaseStatusses = substr($this->_openedCaseStatusses, 0, -1).")";
			} else {
				throw new Exception(1);
			}
		} catch (Exception $e) {
			die("<h1>Casestatussen ontbreken!</h1>");
		}
	}
	
	function fetchCoachIdentifier() {
		$_session				= &CRM_Core_Session::singleton();
		$this->coachIdentifier 	= $_session->get('userID');
	}
	
	function preProcess() {
		$this->assign('reportTitle', ts('Coach Rapport'));
		parent::preProcess();
	}
	
	function select() {
		$this->_select = "
			SELECT 
			`cc`.`id` as `case_id`,
			`cc`.`subject` as `case_subject`,
			DATE_FORMAT(`cc`.`start_date`, '%d-%c-%Y') as `case_start_date`,
			`cc`.`end_date` as `case_end_date`,
			`cat`.`label` as `activity_type`,
			DATE_FORMAT(`ca`.`activity_date_time`, '%d-%c-%Y') as `activity_date`,
			`ca`.`duration` as `activity_duration`,
			`cov`.`label` as `activity_status`,
			`cclient`.`display_name` as `client`,
			`ccoach`.`display_name` as `coach`,
			`cvci`.`".$this->_customField['column_name']."` as `chequenummer`
		";
	}
	
	function from() {
		$this->_from = "
			FROM `civicrm_case` as `cc`
			LEFT JOIN `civicrm_case_contact` as `ccc` ON `cc`.`id` = `ccc`.`case_id`
			LEFT JOIN `civicrm_case_activity` as `cca` ON `cc`.`id` = `cca`.`case_id`
			LEFT JOIN `civicrm_activity` as `ca` ON `cca`.`activity_id` = `ca`.`id`
			LEFT JOIN `civicrm_option_value` as `cov` ON `ca`.`status_id` = `cov`.`value` AND `cov`.`option_group_id` = ".$this->_statusGroup['id']."
			LEFT JOIN `civicrm_contact` as `cclient` ON `ccc`.`contact_id` = `cclient`.`id`
			LEFT JOIN `civicrm_relationship` as `cr` ON `cc`.`id` = `cr`.`case_id` AND `ccc`.`contact_id` = `cr`.`contact_id_a`
			LEFT JOIN `civicrm_contact` as `ccoach` ON `cr`.`contact_id_b` = `ccoach`.`id`
			LEFT JOIN `".$this->_customGroup['table_name']."` as `cvci` ON `cc`.`id` = `cvci`.`entity_id`
			LEFT JOIN `civicrm_option_value` as `cat` ON `ca`.`activity_type_id` = `cat`.`value` AND `cat`.`option_group_id` = ".$this->_activityTypesOptionGroup['id']."
		";
	}
	
	function where() {
		$this->_where = "
			WHERE `ca`.`is_current_revision` = 1
			AND `cr`.`contact_id_b` = ".$this->coachIdentifier."
			AND `cc`.`status_id` IN ".$this->_openedCaseStatusses."
			AND `ca`.`activity_type_id` IN (".$this->_activityTypes->intakegesprek['value'].", ".$this->_activityTypes->verdiepingsgesprek['value'].", ".$this->_activityTypes->synthese['value'].")
		";
	}
	
	function orderBy() {
		$this->_orderBy = "
			ORDER BY `client`, `case_id`, `ca`.`activity_date_time`
		";
	}
	
	function postProcess() {
		$this->beginPostProcess();
		$this->_columnHeaders = array(
			'activity_type' => array("title" => 'Type Activiteit'), 
			'activity_date' => array("title" => 'Datum Activiteit'), 
			'activity_duration' => array("title" => 'Duur Activiteit'), 
			'activity_status' => array("title" => 'Status Activiteit'),
			'case_id' => array("title" => 'case_id', "no_display" => true),
			'case_subject' => array("title" => 'case_subject', "no_display" => true),
			'case_start_date' => array("title" => 'case_start_date', "no_display" => true),
			'case_end_date' => array("title" => 'case_end_date', "no_display" => true),
			'client' => array("title" => 'client', "no_display" => true),
			'coach' => array("title" => 'coach', "no_display" => true),
			'chequenummer' => array("title" => 'chequenummer', "no_display" => true)			
		);
		$sql = $this->buildQuery(TRUE);
		if(strpos($sql, "LIMIT")) $sql = substr($sql, 0, strpos($sql, "LIMIT"));
		$rows = array();
		$this->buildRows($sql, $rows);
		$this->rearrangeRows($rows);
		$this->formatDisplay($rows);
		$this->doTemplateAssignment($rows);
		$this->endPostProcess($rows);
	}
	
	function rearrangeRows(&$rows) {
		$_currentCaseIdentifier 	= 0;
		$_previousCaseIdentifier 	= 0;
		$_cursor 					= -1;
		$_totalDuration				= 0;
		$_rearrangedRows 			= array();
		foreach($rows as $_row){
			$_currentCaseIdentifier 				= $_row['case_id'];
			if($_currentCaseIdentifier != $_previousCaseIdentifier) {
				if($_cursor > -1) {
					$_rearrangedRows[$_cursor][] = array("activity_type" => "<b>Totaal</b>", "activity_date" => "<b>-</b>", "activity_duration" => "<b>".$_totalDuration."</b>", "activity_status" => "<b>-</b>");
				}
				$_totalDuration = 0;
				$_cursor++;
			}
			if(empty($_row['activity_duration']))	$_row['activity_duration'] = 0;
			$_rearrangedRows[$_cursor][] 			= $_row;
			$_totalDuration							= $_totalDuration + $_row['activity_duration'];
			$_previousCaseIdentifier 				= $_currentCaseIdentifier;
		}
		$_rearrangedRows[$_cursor][] = array("activity_type" => "<b>Totaal</b>", "activity_date" => "<b>-</b>", "activity_duration" => "<b>".$_totalDuration."</b>", "activity_status" => "<b>-</b>");
		$rows = $_rearrangedRows;
	}
	
}