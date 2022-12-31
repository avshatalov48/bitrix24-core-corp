<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_BP_OPAY_NAME'),
	'DESCRIPTION' => Loc::getMessage('CRM_BP_OPAY_DESC_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmOrderPayActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentDeal'],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'GROUP' => ['payment'],
		'ASSOCIATED_TRIGGERS' => [
			'INVOICE' => -2,
			'ORDER_PAID' => -1,
		],
		'SORT' => 800,
	],
];