<?php
class CRM_Casereports_Form_Report_coach extends CRM_Report_Form {

	protected $_addressField = FALSE;
	protected $_emailField = FALSE;
	protected $_summary = NULL;
	protected $_customGroupExtends = FALSE;
	protected $_customGroupGroupBy = FALSE;
	protected $_caseType, $_customGroup, $_customField, $_activityTypesOptionGroup, $_activityTypes, $_statusGroup, $_openedCaseStatusses, $_coachIdentifier;
	protected $rel_types;
	protected $show_coach = false;
	protected $_noFields = true;
	
	function __construct() {

		$this->fetchActivityTypes();
		$this->fetchCaseStatusses();
		$rels = CRM_Core_PseudoConstant::relationshipType();
		$coach_rel_type_id = false;
		foreach ($rels as $relid => $v) {
			$this->rel_types[$relid] = $v['label_b_a'];
			if ($v['label_b_a'] == 'Coach') {
				$coach_rel_type_id = $relid;
			}
		}

		$coaches = array();
		if ($coach_rel_type_id) {
			$coaches = $this->coaches($coach_rel_type_id);
		}


		$this->_groupFilter = FALSE;
		$this->_tagFilter   = FALSE;
		$this->_columns = array(
			'civicrm_activity`' => array(
				'dao' => 'CRM_Activity_DAO_Activity',
				'fields' => array(
				),
				'filters' => array(
					'activity_type_id' => array(
						'title' => ts('Activity type'),
						'operatorType' => CRM_Report_Form::OP_MULTISELECT,
						'options' => CRM_Core_OptionGroup::values('activity_type'),
						'pseudofield' => true,
						'default' => $this->_activityTypes,
					),
					'activity_status' => array(
						'title' => ts('Activity Status'),
						'operatorType' => CRM_Report_Form::OP_MULTISELECT,
						'options' => CRM_Core_OptionGroup::values('activity_status'),
						'pseudofield' => true,
						'default' => array(),
					),
					'case_status' => array(
						'title' => ts('Status'),
						'operatorType' => CRM_Report_Form::OP_MULTISELECT,
						'options' => CRM_Core_OptionGroup::values('case_status'),
						'pseudofield' => true,
						'default' => $this->_openedCaseStatusses,
					),
					'case_role' => array(
						'title' => ts('Role on case'),
						'operatorType' => CRM_Report_Form::OP_MULTISELECT,
						'options' => $this->rel_types,
					),
					'coach' => array(
						'title' => ts('Coach'),
						'type' => CRM_Utils_Type::T_INT,
						'operatorType' => CRM_Report_Form::OP_MULTISELECT,
						'options' => $coaches,
						'pseudofield' => true,
					),
					'gefactureerd' => array(
						'title' => ts('Gefactureerd'),
						'operatorType' => CRM_Report_Form::OP_SELECT,
						'options' => array('' => ts('- select -'), '1' => ts('Yes'), '0' => ts('No')),
						'pseudofield' => true,
						'default' => 0
					),
					'my_cases' => array(
						'title' => ts('My cases'),
						'type' => CRM_Utils_Type::T_BOOLEAN,
						'operatorType' => CRM_Report_Form::OP_SELECT,
						'options' => array('0' => ts('No'), '1' => ts('Yes')),
						'default' => '1',
						'pseudofield' => TRUE,
					),
				)
			),
		);
		parent::__construct();
		$this->fetchCaseType();
		$this->fetchChequenummer();
		$this->fetchStatusGroup();
		$this->fetchCoachIdentifier();
	}

	function coaches($coach_rel_type_id) {
		$dao = CRM_Core_DAO::executeQuery("SELECT DISTINCT c.id, c.display_name from civicrm_relationship cr inner join civicrm_contact c on c.id = cr.contact_id_b where cr.case_id IS NOT NULL and cr.relationship_type_id = %1 ORDER BY c.display_name", array(1 => array($coach_rel_type_id, 'Integer')));
		$coaches = array();
		while($dao->fetch()) {
			$coaches[$dao->id] = $dao->display_name;
		}
		return $coaches;
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
			$_activityTypes 						= new stdClass;
			$_activityTypes->intakegesprek 		= civicrm_api3('OptionValue','getsingle',array("name" => "intakegesprek", "option_group_id" => $this->_activityTypesOptionGroup['id']));
			$_activityTypes->verdiepingsgesprek 	= civicrm_api3('OptionValue','getsingle',array("name" => "verdiepingsgesprek", "option_group_id" => $this->_activityTypesOptionGroup['id']));
			$_activityTypes->synthese 			= civicrm_api3('OptionValue','getsingle',array("name" => "synthese", "option_group_id" => $this->_activityTypesOptionGroup['id']));

			$this->_activityTypes = array();
			$this->_activityTypes[] = $_activityTypes->intakegesprek['value'];
			$this->_activityTypes[] = $_activityTypes->verdiepingsgesprek['value'];
			$this->_activityTypes[] = $_activityTypes->synthese['value'];

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
			$this->_openedCaseStatusses					= array();
			$_caseStatusGroup							= civicrm_api3('OptionGroup','getsingle',array("name" => "case_status"));
			$_openedStatusses 							= civicrm_api3('OptionValue','get',array("grouping" => "Opened", "option_group_id" => $_caseStatusGroup['id']));
			if(isset($_openedStatusses['values'])) {
				foreach($_openedStatusses['values'] as $_status){
					$this->_openedCaseStatusses[] = $_status['value'];
				}
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
			LEFT JOIN civicrm_value_factuurcoach ON civicrm_value_factuurcoach.entity_id = ca.id
		";
		
	}
	
	function where() {

		$activity_type_op = $this->getSQLOperator($this->_params['activity_type_id_op']);
		$activity_type =  $this->_params['activity_type_id_value'];
		
		$activity_status_op = $this->getSQLOperator($this->_params['activity_status_op']);
		$activity_status =  $this->_params['activity_status_value'];

		$case_status_op = $this->getSQLOperator($this->_params['case_status_op']);
		$case_status =  $this->_params['case_status_value'];

		$case_role_op = $this->getSQLOperator($this->_params['case_role_op']);
		$case_role = $this->_params['case_role_value'];

		$coach_op = $this->getSQLOperator($this->_params['coach_op']);
		$coach = $this->_params['coach_value'];

		$this->_where = " WHERE `ca`.`is_current_revision` = 1";
		if ($this->_params['my_cases_value']) {
			$this->_where .= " AND `cr`.`contact_id_b` = " . $this->coachIdentifier;
		} else {
			$this->show_coach = true;
			if (is_array($coach) && count($coach)) {
				$this->_where .= " AND `cr`.`contact_id_b` " . $coach_op . " (" . implode(",", $coach) . ")";
			}
		}
		if (is_array($activity_status) && count($activity_status)) {
			$this->_where .= " AND `ca`.`status_id` " . $activity_status_op . " (" . implode(",", $activity_status) . ")";
		}
		if (is_array($case_status) && count($case_status)) {
			$this->_where .= " AND `cc`.`status_id` " . $case_status_op . " (" . implode(",", $case_status) . ")";
		}
		if (is_array($case_role) && count($case_role)) {
			$this->_where .= " AND `cr`.`relationship_type_id` " . $case_role_op . " (" . implode(",", $case_role) . ")";
		}
		if (is_array($activity_type) && count($activity_type)) {
			$this->_where .= " AND `ca`.`activity_type_id` " . $activity_type_op . " (" . implode(",", $activity_type) . ")";
		}
		if (isset($this->_params['gefactureerd']) && $this->_params['gefactureerd'] == 1) {
			$this->_where .= " AND civicrm_value_factuurcoach.gefactureerd = '1'";
		} elseif (isset($this->_params['gefactureerd']) && $this->_params['gefactureerd'] == 0) {
			$this->_where .= " AND (civicrm_value_factuurcoach.gefactureerd != '1' OR civicrm_value_factuurcoach.gefactureerd IS NULL)";
		}
	}
	
	function orderBy() {
		$this->_orderBy = "
			ORDER BY `coach`, `client`, `case_id`, `ca`.`activity_date_time`
		";
	}
	
	function postProcess() {
		$this->beginPostProcess();

		if ($this->_params['my_cases_value']) {
			$this->show_coach = false;
		} else {
			$this->show_coach = true;
		}

		$this->_columnHeaders = array(
			'coach' => array("title" => 'Coach', "no_display" => !$this->show_coach),
			'activity_type' => array("title" => 'Type Activiteit'), 
			'activity_date' => array("title" => 'Datum Activiteit'), 
			'activity_duration' => array("title" => 'Duur Activiteit'), 
			'activity_status' => array("title" => 'Status Activiteit'),
			'case_id' => array("title" => 'case_id', "no_display" => true,),
			'case_subject' => array("title" => 'case_subject', "no_display" => true),
			'case_title' => array("title" => 'case_title', "no_display" => true),
			'case_start_date' => array("title" => 'case_start_date', "no_display" => true),
			'case_end_date' => array("title" => 'case_end_date', "no_display" => true),
			'client' => array("title" => 'client', "no_display" => true),
			'chequenummer' => array("title" => 'chequenummer', "no_display" => true)
		);
		$sql = $this->buildQuery(TRUE);
		//if(strpos($sql, "LIMIT")) $sql = substr($sql, 0, strpos($sql, "LIMIT"));
		$rows = array();
		$this->buildRows($sql, $rows);
		//$this->rearrangeRows($rows);
		$this->formatDisplay($rows);
		$this->assign('sections', array('case_title' => array('title' => 'Dossier')));
		$this->doTemplateAssignment($rows);
		$this->endPostProcess($rows);
	}

	function alterDisplay(&$rows)
	{
		$sectionTotals = array();
		$i = 0;
		$previous_case_id = false;
		$previous_case_title = false;
		$original_rows = $rows;
		foreach($original_rows as $key => $row) {
			$case_id = $row['case_id'];
			$case_title = '';
			if ($row['client']) {
				if (strlen($case_title)) {
					$case_title .= ' - ';
				}
				$case_title .= $row['client'];
			}
			if ($row['chequenummer']) {
				if (strlen($case_title)) {
					$case_title .= ' - ';
				}
				$case_title .= $row['chequenummer'];
			}
			if ($row['case_start_date']) {
				if (strlen($case_title)) {
					$case_title .= ' - ';
				}
				$case_title .= $row['case_start_date'];
			}
			$rows[$i]['case_title'] = $case_title;
			if (!isset($sectionTotals[$case_title])) {
				$sectionTotals[$case_title] = 0;
			}
			$sectionTotals[$case_title] = $sectionTotals[$case_title] + $row['activity_duration'];

			if ($previous_case_title != false && $previous_case_title != $case_title) {
				$total_row = array(
					'case_id' => $previous_case_id,
					'case_title' => $previous_case_title,
					'activity_type' => '<strong>Totaal</strong>',
					'activity_duration' => '<strong>'.$sectionTotals[$previous_case_title].'</strong>',
				);

				array_splice( $rows, $i, 0, array($total_row) );
				$i++;
			}

			$i++;
			$previous_case_id = $case_id;
			$previous_case_title = $case_title;
		}
		$this->assign('sectionTotals', $sectionTotals);
	}

	function rearrangeRows(&$rows) {
		$_currentCaseIdentifier 	= 0;
		$_previousCaseIdentifier 	= 0;
		$_cursor 					= -1;
		$_totalDuration				= 0;
		$_rearrangedRows 			= array();
		/*(foreach($rows as $_row){
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
		$rows = $_rearrangedRows;*/
	}
	
}