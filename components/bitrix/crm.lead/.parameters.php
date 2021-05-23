<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule('crm'))
	return false;  

$arComponentParameters = Array(
	'GROUPS' => array(
	
	),
	'PARAMETERS' => array(
		'VARIABLE_ALIASES' => Array(
			'lead_id' => Array(
				'NAME' => GetMessage('CRM_LEAD_VAR'),
				'DEFAULT' => 'lead_id'
			)				
		),
		'SEF_MODE' => Array(
			'index' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_INDEX'),
				'DEFAULT' => 'index.php',
				'VARIABLES' => array()
			),
			'list' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_LIST'),
				'DEFAULT' => 'list/',
				'VARIABLES' => array('lead_id')
			),
			'edit' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_EDIT'),
				'DEFAULT' => 'edit/#lead_id#/',
				'VARIABLES' => array('lead_id')
			),
			'show' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_SHOW'),
				'DEFAULT' => 'show/#lead_id#/',
				'VARIABLES' => array('lead_id')			
			),					
			'convert' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_CONVERT'),
				'DEFAULT' => 'convert/#lead_id#/',
				'VARIABLES' => array()
			),
			'import' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_IMPORT'),
				'DEFAULT' => 'import/',
				'VARIABLES' => array()
			),			
			'service' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_SERVICE'),
				'DEFAULT' => 'service/',
				'VARIABLES' => array()
			)																						
		),				
		'ELEMENT_ID' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_ELEMENT_ID'),
			'TYPE' => 'STRING',
			'DEFAULT' => '={$_REQUEST["lead_id"]}',
		),
		"NAME_TEMPLATE" => array(
			"TYPE" => "LIST",
			"NAME" => GetMessage("CRM_NAME_TEMPLATE"),
			"VALUES" => CComponentUtil::GetDefaultNameTemplates(),
			"MULTIPLE" => "N",
			"ADDITIONAL_VALUES" => "Y",
			"DEFAULT" => "",
			"PARENT" => "ADDITIONAL_SETTINGS",
		)
	)
);


?>