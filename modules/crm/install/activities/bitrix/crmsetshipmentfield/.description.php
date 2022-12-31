<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_SSF_DESCR_NAME'),
	'DESCRIPTION' => Loc::getMessage('CRM_SSF_DESCR_DESCR_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmSetShipmentField',
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
		'SORT' => 500,
		'ASSOCIATED_TRIGGERS' => [
			'SHIPMENT_CHANGED' => 1,
		],
	],
];