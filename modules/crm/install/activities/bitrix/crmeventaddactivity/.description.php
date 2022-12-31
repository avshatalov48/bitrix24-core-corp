<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_ACTIVITY_EVENT_ADD_NAME'),
	'DESCRIPTION' => Loc::getMessage('CRM_ACTIVITY_EVENT_ADD_DESC_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmEventAddActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm'],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'TITLE' => Loc::getMessage('CRM_ACTIVITY_EVENT_ADD_ROBOT_TITLE_1'),
		'GROUP' => ['employeeControl'],
		'SORT' => 1900,
	],
];