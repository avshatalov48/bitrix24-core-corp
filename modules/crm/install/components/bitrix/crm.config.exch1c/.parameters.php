<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule('crm'))
	return false;

$arComponentParameters = Array(
	'GROUPS' => array(

	),
	'PARAMETERS' => array(
		'VARIABLE_ALIASES' => Array(
		),
		'SEF_MODE' => Array(
			'index' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_INDEX'),
				'DEFAULT' => 'index.php',
				'VARIABLES' => array()
			),
			'catalog' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_CATALOG'),
				'DEFAULT' => 'catalog/'
			),
			'invoice' => array(
				'NAME' => GetMessage('CRM_SEF_PATH_TO_INVOICE'),
				'DEFAULT' => 'invoice/'
			)
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
