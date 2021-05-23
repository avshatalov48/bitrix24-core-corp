<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule('crm'))
	return false;  

$arComponentParameters = Array(
	'GROUPS' => array(
	
	),
	'PARAMETERS' => array(
		'VARIABLE_ALIASES' => Array(
			'invoice_id' => Array(
				'NAME' => GetMessage('CRM_INVOICE_VAR'),
				'DEFAULT' => 'invoice_id'
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
				'VARIABLES' => array('invoice_id')
			),
			'edit' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_EDIT'),
				'DEFAULT' => 'edit/#invoice_id#/',
				'VARIABLES' => array('invoice_id')
			),
			'show' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_SHOW'),
				'DEFAULT' => 'show/#invoice_id#/',
				'VARIABLES' => array('invoice_id')
			),
			'payment' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_PAYMENT'),
				'DEFAULT' => 'payment/#invoice_id#/',
				'VARIABLES' => array('invoice_id')
			)
		),				
		'ELEMENT_ID' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_ELEMENT_ID'),
			'TYPE' => 'STRING',
			'DEFAULT' => '={$_REQUEST["invoice_id"]}'
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