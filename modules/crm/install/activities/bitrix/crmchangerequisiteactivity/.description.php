<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('CRM_CRQ_NAME'),
	'DESCRIPTION' => GetMessage('CRM_CRQ_DESC'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmChangeRequisiteActivity',
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
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee'
	]
);