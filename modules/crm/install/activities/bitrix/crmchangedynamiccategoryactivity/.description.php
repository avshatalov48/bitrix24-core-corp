<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_CDCA_NAME_1'),
	'DESCRIPTION' => Loc::getMessage('CRM_CDCA_DESC_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmChangeDynamicCategoryActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm', \Bitrix\Crm\Integration\BizProc\Document\Dynamic::class]
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'RESPONSIBLE_PROPERTY' => 'Responsible',
		'GROUP' => ['elementControl'],
	],
];