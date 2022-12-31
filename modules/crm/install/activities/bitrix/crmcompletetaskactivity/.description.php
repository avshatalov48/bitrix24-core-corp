<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_CTA_NAME'),
	'DESCRIPTION' => Loc::getMessage('CRM_CTA_DESC_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmCompleteTaskActivity',
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
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Dynamic'],
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Quote'],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'GROUP' => ['employeeControl', 'taskManagement'],
		'SORT' => 2300,
	],
];