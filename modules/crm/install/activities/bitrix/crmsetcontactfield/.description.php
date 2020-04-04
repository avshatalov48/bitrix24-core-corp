<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('CRM_ACTIVITY_SET_CONTACT_NAME'),
	'DESCRIPTION' => GetMessage('CRM_ACTIVITY_SET_CONTACT_DESC'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmSetContactField',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => array(
		'ID' => 'document',
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	),
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentLead'],
			['crm', 'CCrmDocumentDeal'],
		]
	],
	'ROBOT_SETTINGS' => array(
		'CATEGORY' => 'employee',
	),
);