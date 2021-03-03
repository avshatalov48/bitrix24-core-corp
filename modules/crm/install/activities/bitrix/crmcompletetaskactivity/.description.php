<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('CRM_CTA_NAME'),
	'DESCRIPTION' => GetMessage('CRM_CTA_DESC'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmCompleteTaskActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => array(
		'ID' => 'document',
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	),
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentDeal'],
			['crm', 'CCrmDocumentLead']
		]
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee'
	]
);