<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = [
	'NAME' => GetMessage('CRM_BP_GPI_NAME'),
	'DESCRIPTION' => GetMessage('CRM_BP_GPI_DESC'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmGetPaymentInfoActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	],
	'RETURN' => [
		'PaymentId' => [
			'NAME' => GetMessage('CRM_BP_GPI_RETURN_PAYMENT_ID'),
			'TYPE' => 'int',
		],
		'PaymentAccountNumber' => [
			'NAME' => GetMessage('CRM_BP_GPI_RETURN_PAYMENT_NUM'),
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