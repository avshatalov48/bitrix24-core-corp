<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('BPVICA_DESCR_NAME_1'),
	'DESCRIPTION' => Loc::getMessage('BPVICA_DESCR_DESCR_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'VoximplantCallActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'interaction',
	],
	'RETURN' => [
		'Result' => [
			'NAME' => Loc::getMessage('BPVICA_DESCR_RESULT'),
			'TYPE' => 'bool',
		],
		'ResultText' => [
			'NAME' => Loc::getMessage('BPVICA_DESCR_RESULT_TEXT'),
			'TYPE' => 'string',
		],
		'ResultCode' => [
			'NAME' => Loc::getMessage('BPVICA_DESCR_RESULT_CODE'),
			'TYPE' => 'string',
		],
	],
	'FILTER' => [
		'EXCLUDE' => [
			['tasks'],
			['rpa'],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'client',
		'GROUP' => ['clientCommunication'],
		'ASSOCIATED_TRIGGERS' => [
			'CALLBACK' => -4,
			'OUTGOING_CALL' => -3,
			'CALL' => -2,
			'MISSED_CALL' => -1,
		],
		'SORT' => 1100
	],
	'EXCLUDED' => !\Bitrix\Main\Loader::includeModule('voximplant'),
];