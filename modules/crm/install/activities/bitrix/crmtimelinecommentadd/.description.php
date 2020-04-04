<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('BPCTLCA_NAME'),
	'DESCRIPTION' => GetMessage('BPCTLCA_DESCRIPTION'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmTimelineCommentAdd',
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
		'CATEGORY' => 'employee'
	),
);