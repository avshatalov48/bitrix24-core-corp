<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('BPCTLCA_NAME_1'),
	'DESCRIPTION' => Loc::getMessage('BPCTLCA_DESCRIPTION_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmTimelineCommentAdd',
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
		'GROUP' => ['informingEmployee'],
		'SORT' => 800,
	],
];