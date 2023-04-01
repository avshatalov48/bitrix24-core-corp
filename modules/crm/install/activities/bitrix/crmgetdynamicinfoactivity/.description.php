<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_GDIA_NAME_2'),
	'DESCRIPTION' => Loc::getMessage('CRM_GDIA_DESC_2_MSGVER_1'),
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
		'GROUP' => ['other', 'digitalWorkplace'],
		'TITLE_GROUP' => [
			'other' => Loc::getMessage('CRM_GDIA_NAME_2'),
			'digitalWorkplace' => Loc::getMessage('CRM_GDIA_ROBOT_NAME_DIGITAL_WORKPLACE'),
		],
		'DESCRIPTION_GROUP' => [
			'other' => Loc::getMessage('CRM_GDIA_DESC_2_MSGVER_1'),
			'digitalWorkplace' => Loc::getMessage('CRM_GDIA_ROBOT_DESCRIPTION_DIGITAL_WORKPLACE_MSGVER_1'),
		],
		'SORT' => 3200,
		'IS_SUPPORTING_ROBOT' => true,
	],
];