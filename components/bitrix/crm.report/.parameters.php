<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule('crm'))
	return false;

$arComponentParameters = Array(
	'GROUPS' => array(

	),
	'PARAMETERS' => array(
		'VARIABLE_ALIASES' => Array(
			'report_id' => Array(
				'NAME' => GetMessage('CRM_REPORT_VAR'),
				'DEFAULT' => 'report_id'
			),
			'action' => Array(
				'NAME' => GetMessage('CRM_ACTION_VAR'),
				'DEFAULT' => 'action'
			)
		),
		'SEF_MODE' => Array(
			'index' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_INDEX'),
				'DEFAULT' => 'index.php',
				'VARIABLES' => array()
			),
			'report' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_REPORT'),
				'DEFAULT' => 'report/',
				'VARIABLES' => array()
			),
			'construct' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_CONSTRUCT'),
				'DEFAULT' => 'construct/#report_id#/#action#/',
				'VARIABLES' => array('report_id')
			),
			'show' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_VIEW'),
				'DEFAULT' => 'view/#report_id#/',
				'VARIABLES' => array('report_id')
			)
		),
		'REPORT_ID' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_REPORT_ID'),
			'TYPE' => 'STRING',
			'DEFAULT' => '={$_REQUEST["report_id"]}'
		)
	)
);


?>