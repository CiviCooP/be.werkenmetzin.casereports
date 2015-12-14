<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 => 
  array (
    'name' => 'CRM_Casereports_Form_Report_tweedevoorkeur',
    'entity' => 'ReportTemplate',
    'params' => 
    array (
      'version' => 3,
      'label' => 'Coach Regio - Tweede Voorkeur',
      'description' => 'Een overzicht van alle klanten binnen de regio van de coach met als tweede voorkeur de regio van de coach',
      'class_name' => 'CRM_Casereports_Form_Report_tweedevoorkeur',
      'report_url' => 'be.werkenmetzin.casereports/tweedevoorkeur',
      'component' => '',
    ),
  ),
);