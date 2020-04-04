<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule('crm'))
	return false;

$arComponentParameters = Array(
	'PARAMETERS' => array(
		'DISABLE_COMPENSATION' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_DEAL_DISABLE_COMPENSATION'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'N'
		),
		'ALLOW_FUNNEL_TYPE_CHANGE' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CRM_DEAL_FUNNEL_PARAM_ALLOW_FUNNEL_TYPE_CHANGE'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'Y'
		),
		"NAME_TEMPLATE" => array(
			"TYPE" => "LIST",
			"NAME" => GetMessage("CRM_NAME_TEMPLATE"),
			"VALUES" => CComponentUtil::GetDefaultNameTemplates(),
			"MULTIPLE" => "N",
			"ADDITIONAL_VALUES" => "Y",
			"DEFAULT" => "",
			"PARENT" => "ADDITIONAL_SETTINGS",
		),
	)
);
?>