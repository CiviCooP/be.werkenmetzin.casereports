<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 => 
  array (
    'name' => 'CRM_Casereports_Form_Report_coach',
    'entity' => 'ReportTemplate',
    'params' => 
    array (
      'version' => 3,
      'label' => 'Verklaring Uren',
      'description' => 'Een lijst van alle cases van de ingelogde coach',
      'class_name' => 'CRM_Casereports_Form_Report_coach',
      'report_url' => 'be.werkenmetzin.casereports/coach',
      'component' => 'CiviCase',
    ),
  ),
);