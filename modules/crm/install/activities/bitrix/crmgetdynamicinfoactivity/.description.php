<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_GDIA_NAME'),
	'DESCRIPTION' => Loc::getMessage('CRM_GDIA_DESC'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmGetDynamicInfoActivity',
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
	'ADDITIONAL_RESULT' => ['DynamicEntityFields'],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
	],
];