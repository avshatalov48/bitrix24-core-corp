<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_CHANGE_RESPONSIBLE_NAME_1'),
	'DESCRIPTION' => Loc::getMessage('CRM_CHANGE_RESPONSIBLE_DESC_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmChangeResponsibleActivity',
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
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Invoice'],
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Dynamic'],
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Quote'],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'RESPONSIBLE_PROPERTY' => 'Responsible',
		'GROUP' => ['elementControl'],
		'ASSOCIATED_TRIGGERS' => [
			'RESP_CHANGED' => 1,
		],
		'SORT' => 2600,
	],
];
