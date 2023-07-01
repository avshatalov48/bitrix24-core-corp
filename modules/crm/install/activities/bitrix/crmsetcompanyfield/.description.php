<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_ACTIVITY_SET_COMPANY_NAME_1'),
	'DESCRIPTION' => Loc::getMessage('CRM_ACTIVITY_SET_COMPANY_DESC_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmSetCompanyField',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'RETURN' => [
		'ErrorMessage' => [
			'NAME' => GetMessage('CRM_ACTIVITY_SET_COMPANY_ERROR_MESSAGE'),
			'TYPE' => 'string',
		],
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentLead'],
			['crm', 'CCrmDocumentDeal'],
			['crm', 'CCrmDocumentContact'],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\Dynamic::class],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\Quote::class],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\SmartInvoice::class],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\Order::class],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'GROUP' => ['clientData'],
		'SORT' => 4600,
	],
];