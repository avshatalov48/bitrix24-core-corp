<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('CRM_ACTIVITY_EVENT_ADD_NAME'),
	'DESCRIPTION' => GetMessage('CRM_ACTIVITY_EVENT_ADD_DESC'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmEventAddActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => array(
		'ID' => 'document',
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	),
	'FILTER' => [
		'INCLUDE' => [
			['crm']
		]
	],
	'ROBOT_SETTINGS' => array(
		'CATEGORY' => 'employee',
		'TITLE' => GetMessage('CRM_ACTIVITY_EVENT_ADD_ROBOT_TITLE')
	),
);