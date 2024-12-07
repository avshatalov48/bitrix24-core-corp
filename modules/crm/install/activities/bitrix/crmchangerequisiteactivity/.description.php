<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_CRQ_NAME_1'),
	'DESCRIPTION' => Loc::getMessage('CRM_CRQ_DESC_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmChangeRequisiteActivity',
	'JSCLASS' => 'BizProcActivity',
	'FILTER' => [
		'INCLUDE' => [
			['crm'],
		],
		'EXCLUDE' => [
			['crm', \Bitrix\Crm\Integration\BizProc\Document\SmartDocument::class],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\SmartB2eDocument::class],
		],
	],
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'GROUP' => ['paperwork', 'payment'],
		'SORT' => 1600,
	],
];
