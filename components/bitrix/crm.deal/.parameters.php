<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule('crm'))
	return false;  

$arComponentParameters = Array(
	'GROUPS' => array(
	
	),
	'PARAMETERS' => array(
		'VARIABLE_ALIASES' => Array(
			'deal_id' => Array(
				'NAME' => GetMessage('CRM_DEAL_VAR'),
				'DEFAULT' => 'deal_id'
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
				'VARIABLES' => array('deal_id')
			),
			'funnel' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_FUNNEL'),
				'DEFAULT' => 'funnel/',
				'VARIABLES' => array('deal_id')
			),			
			'edit' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_EDIT'),
				'DEFAULT' => 'edit/#deal_id#/',
				'VARIABLES' => array('deal_id')
			),
			'show' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_SHOW'),
				'DEFAULT' => 'show/#deal_id#/',
				'VARIABLES' => array('deal_id')
			),
			'import' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_IMPORT'),
				'DEFAULT' => 'import/',
				'VARIABLES' => array()			
			)				
		),				
		'ELEMENT_ID' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_ELEMENT_ID'),
			'TYPE' => 'STRING',
			'DEFAULT' => '={$_REQUEST["deal_id"]}'
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