<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_SODD_DESCR_NAME'),
	'DESCRIPTION' => Loc::getMessage('CRM_SODD_DESCR_DESCR_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmSetOrderDeducted',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Order'],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'GROUP' => ['delivery'],
		'SORT' => 200,
		'ASSOCIATED_TRIGGERS' => [
			'FILL_TRACKNUM' => -1,
			'DEDUCTED' => 1,
		],
	],
];