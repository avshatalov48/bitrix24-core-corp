<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_CDA_NAME_1'),
	'DESCRIPTION' => Loc::getMessage('CRM_CDA_DESC_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmCreateDynamicActivity',
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
	'RETURN' => [
		'ItemId' => [
			'NAME' => Loc::getMessage('CRM_CDA_RETURN_ITEM_ID_1'),
			'TYPE' => 'int',
		]
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
	],
];