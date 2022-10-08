<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

$arActivityDescription = [
	"NAME" => GetMessage("BPVICA_DESCR_NAME"),
	"DESCRIPTION" => GetMessage("BPVICA_DESCR_DESCR"),
	"TYPE" => ['activity', 'robot_activity'],
	"CLASS" => "VoximplantCallActivity",
	"JSCLASS" => "BizProcActivity",
	"CATEGORY" => [
		"ID" => "interaction",
	],
	"RETURN" => [
		"Result" => [
			"NAME" => GetMessage("BPVICA_DESCR_RESULT"),
			"TYPE" => "bool",
		],
		"ResultText" => [
			"NAME" => GetMessage("BPVICA_DESCR_RESULT_TEXT"),
			"TYPE" => "string",
		],
		"ResultCode" => [
			"NAME" => GetMessage("BPVICA_DESCR_RESULT_CODE"),
			"TYPE" => "string",
		],
	],
	'FILTER' => [
		'EXCLUDE' => [
			['tasks'],
			['rpa'],
		]
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'client'
	],
	'EXCLUDED' => (
		!\Bitrix\Main\Loader::includeModule('voximplant')
		|| !\Bitrix\Voximplant\Limits::hasAccountBalance()
	),
];