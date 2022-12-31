<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_CDA_NAME'),
	'DESCRIPTION' => Loc::getMessage('CRM_CDA_DESC'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmCopyDynamicActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'RETURN' => [
		'ItemId' => [
			'NAME' => Loc::getMessage('CRM_CDA_RETURN_ITEM_ID'),
			'TYPE' => 'int',
		],
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm', \Bitrix\Crm\Integration\BizProc\Document\Dynamic::class],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'RESPONSIBLE_PROPERTY' => 'Responsible',
		'GROUP' => ['digitalWorkplace'],
	],
];