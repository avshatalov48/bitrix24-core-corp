<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('BP_CRM_GO_TO_CHAT_NAME'),
	'DESCRIPTION' => Loc::getMessage('BP_CRM_GO_TO_CHAT_DESCRIPTION'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmGoToChat',
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
		'CATEGORY' => 'client',
		'GROUP' => ['clientCommunication'],
		'SORT' => 1350,
	],
];