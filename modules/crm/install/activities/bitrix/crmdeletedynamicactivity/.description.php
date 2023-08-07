<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_DDA_NAME_1'),
	'DESCRIPTION' => Loc::getMessage('CRM_DDA_DESC_2'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmDeleteDynamicActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'PRESETS' => [
		[
			'ID' => 'dynamic',
			'NAME' => Loc::getMessage('CRM_DDA_NAME'),
			'DESCRIPTION' => Loc::getMessage('CRM_DDA_ROBOT_DESCRIPTION_DIGITAL_WORKPLACE'),
			'PROPERTIES' => [
				'OnlyDynamicEntities' => 'Y',
			]
		],
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm'],
			['lists'],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'GROUP' => ['other', 'digitalWorkplace'],
		'TITLE_GROUP' => [
			'other' => Loc::getMessage('CRM_DDA_NAME_1'),
			'digitalWorkplace' => Loc::getMessage('CRM_DDA_NAME'),
		],
		'DESCRIPTION_GROUP' => [
			'other' => Loc::getMessage('CRM_DDA_DESC_2'),
			'digitalWorkplace' => Loc::getMessage('CRM_DDA_ROBOT_DESCRIPTION_DIGITAL_WORKPLACE'),
		],
		'SORT' => 3300,
	],
];