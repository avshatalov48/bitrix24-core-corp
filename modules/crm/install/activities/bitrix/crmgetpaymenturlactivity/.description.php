<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_BP_GPU_DESC_NAME'),
	'DESCRIPTION' => Loc::getMessage('CRM_BP_GPU_DESC_DESC_1_MSGVER_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmGetPaymentUrlActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'RETURN' => [
		'Url' => [
			'NAME' => Loc::getMessage('CRM_BP_GPU_RETURN_URL'),
			'TYPE' => 'string',
		],
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentDeal'],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\SmartInvoice::class],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'GROUP' => ['payment'],
		'SORT' => 700,
		'IS_SUPPORTING_ROBOT' => true,
	],
];