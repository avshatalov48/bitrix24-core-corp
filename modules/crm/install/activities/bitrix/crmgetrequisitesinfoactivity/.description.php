<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('CRM_GRI_NAME'),
	'DESCRIPTION' => GetMessage('CRM_GRI_DESC'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmGetRequisitesInfoActivity',
	'JSCLASS' => 'BizProcActivity',
	'FILTER' => [
		'INCLUDE' => [
			['crm']
		]
	],
	'CATEGORY' => array(
		'ID' => 'document',
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	),
	'ADDITIONAL_RESULT' => ['RequisitePresetFields'],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee'
	]
);