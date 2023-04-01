<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('IMOL_MA_DESCR_NAME_1'),
	'DESCRIPTION' => Loc::getMessage('IMOL_MA_DESCR_DESCR_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'ImOpenLinesMessageActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'interaction',
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm'],
		],
		'EXCLUDE' => [
			['crm', \Bitrix\Crm\Integration\BizProc\Document\SmartDocument::class],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'client',
		'GROUP' => ['clientCommunication', 'delivery'],
		'ASSOCIATED_TRIGGERS' => [
			'OPENLINE' => -2,
			'OPENLINE_ANSWER' => -1,
			'OPENLINE_ANSWER_CTRL' => 1,
			'OPENLINE_MSG' => 2,
		],
		'SORT' => 1200,
	],
];