<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = [
	'NAME' => GetMessage('CRM_BP_GPU_NAME'),
	'DESCRIPTION' => GetMessage('CRM_BP_GPU_DESC'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmGetPaymentUrlActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	],
	'RETURN' => [
		'Url' => [
			'NAME' => GetMessage('CRM_BP_GPU_RETURN_URL'),
			'TYPE' => 'string',
		],
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentDeal']
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
	],
];