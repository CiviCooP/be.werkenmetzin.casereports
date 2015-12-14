<?php

class CRM_Casereports_Form_Report_derdevoorkeur extends CRM_Report_Form {

protected $_addressField = FALSE;
	protected $_emailField = FALSE;
	protected $_summary = NULL;
	protected $_customGroupExtends = FALSE;
	protected $_customGroupGroupBy = FALSE;
	protected $_customGroup, $_customField, $_activityTypesOptionGroup, $_activityTypes, $_statusGroup, $_activityStatusses, $_coachIdentifier, $_coachLocatie;
	
	function __construct() {
		$this->fetchActivityType();
		$this->fetchActivityStatus();
		$this->fetchDerdelocatie();
		$this->_groupFilter = TRUE;
		$this->_tagFilter   = FALSE;
		$this->_columns = array(
			'civicrm_activity`' => array(
				'dao' => 'CRM_Activity_DAO_Activity',
				'fields' => array(
					'activity_id' => array(
						'no_display' => TRUE,
						'required' => TRUE,
					),
					'klant' => array(
						'title' => 'Klant'
					),
					'activity_date_time' => array(
						'title' => 'Datum'
					),
					'regio' => array(
						'title' => 'Regio'
					),
					'voorkeur_contact' => array(
						'title' => 'Voorkeur Contact'
					),
					'voorkeur_dag' => array(
						'title' => 'Voorkeur Dag'
					),
				)
			),
		);
		parent::__construct();
	}
		
	function fetchActivityType() {
		try {
			$this->_activityTypesOptionGroup				= civicrm_api3('OptionGroup','getsingle',array("name" => "activity_type"));
			$this->_activityTypes 							= new stdClass;
			$this->_activityTypes->aanmeldinginschrijving 	= civicrm_api3('OptionValue','getsingle',array("name" => "Aanmelding Inschrijving", "option_group_id" => $this->_activityTypesOptionGroup['id']));
		} catch (Exception $e) {
			die("<h1>Activiteitstypes ontbreken!</h1>");
		}
	}	
	
	function fetchActivityStatus() {
		try {
			$this->_statusGroup	= civicrm_api3('OptionGroup','getsingle',array("name" => "activity_status"));
		} catch (Exception $e) {
			die("<h1>Status option group ontbreekt!</h1>");
		}
	}
	
	function fetchDerdelocatie() {
		try {
			$this->_customGroup 					= civicrm_api3('CustomGroup','getsingle',array("title" => "Aanmelding Inschrijving"));
			$this->_customFields 					= new stdClass;
			$this->_customFields->locatievoorkeur 	= civicrm_api3('CustomField','getsingle',array("name" => "Locatievoorkeur_3e", "custom_group_id" => $this->_customGroup['id']));
			$this->_customFields->contactvoorkeur 	= civicrm_api3('CustomField','getsingle',array("name" => "voorkeur_contact", "custom_group_id" => $this->_customGroup['id']));
			$this->_customFields->dagvoorkeur 		= civicrm_api3('CustomField','getsingle',array("name" => "Indien_overdag_welke_dagen_hebben_uw_voorkeur", "custom_group_id" => $this->_customGroup['id']));
		} catch (Exception $e) {
			die("<h1>Custom veld Locatievoorkeur_1e, voorkeur_contact of Indien_overdag_welke_dagen_hebben_uw_voorkeur of custom group Aanmelding Inschrijving ontbreekt!</h1>");
		}
	}
	
	function fetchCoachLocation() {
		$_session				= &CRM_Core_Session::singleton();
		$this->coachIdentifier 	= $_session->get('userID');
		$_coachGroups 			= civicrm_api3('GroupContact', 'get', array('contact_id' => $this->coachIdentifier));
		$this->_coachGroups 	= array();
		//$_regios 				= array('Anderlecht','Affligem','Antwerpen','Brasschaat/Kappelen','Bree','Brugge','Brussel','Berchem','Dendermonde','Gent','Haacht','Hamont-Achel','Heusden-Zolder','Hoboken','Kontich','Kortrijk','Leuven','Mechelen','Overpelt','Roeselare','Schoten','Sint-Niklaas','Tienen','Turnhout','Vilvoorde','Zoersel','Zottegem','Zulte','Online','Kessel-Lier','Lier-Kessel','Huldenberg','Buggenhout','Grimbergen','Kapellen-Brasschaat');
		$_regios				= array_values($this->_defaults['gid_value']);
		if(isset($_coachGroups) && count($_coachGroups['values'])) {
			foreach($_coachGroups['values'] as $_group){
				if(in_array($_group['group_id'], $_regios)) $this->_coachGroups[] = $_group['title'];
			}
			$this->_coachGroups = (count($this->_coachGroups) > 1) ? "'".implode("','",$this->_coachGroups)."'" : "'".$this->_coachGroups[0]."'";
		}
		if(empty($this->_coachGroups)) {
			$this->_coachGroups = "NULL";
			//throw new API_Exception('Coach is niet aangemeld bij een groep');
		}
	}
	
	function preProcess() {
		$this->assign('reportTitle', ts('Coach Rapport'));
		parent::preProcess();
	}
	
	function select() {
		$this->_select = "
			SELECT
			`cc`.`display_name` as `client`,
			DATE_FORMAT(`ca`.`activity_date_time`, '%d-%c-%Y') as `activity_date`,
			`cvlv`.`".$this->_customFields->locatievoorkeur['column_name']."` as `regio`,
			`cvlv`.`".$this->_customFields->contactvoorkeur['column_name']."` as `tijdstip`,
			`cvlv`.`".$this->_customFields->dagvoorkeur['column_name']."` as `dag`,
			`cov`.`label` as `activity_status`,
			`cc`.`id` as `civicrm_contact_id`
		";
	}
	
	function from() {
		$this->_from = "
			FROM `civicrm_activity` as `ca`
			LEFT JOIN `civicrm_activity_contact` as `cac` ON `ca`.`id` = `cac`.`activity_id`
			LEFT JOIN `civicrm_contact` as `cc` ON `cac`.`contact_id` = `cc`.`id`
			LEFT JOIN `".$this->_customGroup['table_name']."` as `cvlv` ON `cvlv`.`entity_id` = `ca`.`id`
			LEFT JOIN `civicrm_option_value` as `cov` ON `ca`.`status_id` = `cov`.`value` AND `cov`.`option_group_id` = ".$this->_statusGroup['id']."
		";
	}
	
	function where() {
		$this->fetchCoachLocation();
		$this->_where = "
			WHERE `ca`.`activity_type_id` = ".$this->_activityTypes->aanmeldinginschrijving['value']."
			AND `cvlv`.`".$this->_customFields->locatievoorkeur['column_name']."` IN (".$this->_coachGroups.")
			AND `ca`.`is_current_revision` = 1
			AND `cov`.`label` = 'Gepland'
			AND `cc`.`contact_sub_type` = 'Klant'
		";
	}
	
	function groupBy() {
		$this->_groupBy = "
			GROUP BY `ca`.`id`
		";
	}
	
	function orderBy() {
		$this->_orderBy = "
			ORDER BY `ca`.`activity_date_time` ASC
		";
	}
	
	function postProcess() {
		$this->beginPostProcess();
		$this->_columnHeaders = array(
			"client" => array("title" => "Klant"),
			"activity_date" => array("title" => "Datum"),
			"regio" => array("title" => "Regio"),
			"tijdstip" => array("title" => "Voorkeur contact"),
			"dag" => array("title" => "Voorkeur dag"),
			"activity_status" => array("title" => "Status", "no_display" => TRUE),
			"civicrm_contact_id" => array("title" => "contact_id", "no_display" => TRUE),
		);
		$sql = $this->buildQuery(TRUE);
		$rows = array();
		$this->buildRows($sql, $rows);
		$this->formatDisplay($rows);
		$this->doTemplateAssignment($rows);
		$this->endPostProcess($rows);
	}
	
	function alterDisplay(&$rows) {
		foreach($rows as $rowNumber => $row) {
			$url = CRM_Utils_System::url("civicrm/contact/view",'reset=1&cid=' . $row['civicrm_contact_id'],$this->_absoluteUrl);
			$rows[$rowNumber]['client_link'] = $url;
			$rows[$rowNumber]['client_hover'] = ts("View Contact Summary for this Contact.");
		}	
	}
  
}