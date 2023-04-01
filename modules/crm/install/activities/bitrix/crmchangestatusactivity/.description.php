<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_CHANGE_STATUS_NAME_1'),
	'DESCRIPTION' => Loc::getMessage('CRM_CHANGE_STATUS_DESC_2'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmChangeStatusActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentDeal'],
			['crm', 'CCrmDocumentLead'],
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Order'],
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Dynamic'],
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Quote'],
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\SmartInvoice'],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\SmartDocument::class],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'GROUP' => ['elementControl'],
		'SORT' => 2700,
	],
];
