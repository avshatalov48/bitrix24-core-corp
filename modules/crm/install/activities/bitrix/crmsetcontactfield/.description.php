<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_ACTIVITY_SET_CONTACT_NAME_1'),
	'DESCRIPTION' => Loc::getMessage('CRM_ACTIVITY_SET_CONTACT_DESC_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmSetContactField',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentLead'],
			['crm', 'CCrmDocumentDeal'],
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Dynamic'],
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Quote'],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'GROUP' => ['clientData'],
		'SORT' => 4500,
	],
];