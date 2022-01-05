<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	"NAME" => GetMessage("BPVICA_DESCR_NAME"),
	"DESCRIPTION" => GetMessage("BPVICA_DESCR_DESCR"),
	"TYPE" => array('activity', 'robot_activity'),
	"CLASS" => "VoximplantCallActivity",
	"JSCLASS" => "BizProcActivity",
	"CATEGORY" => array(
		"ID" => "interaction",
	),
	"RETURN" => array(
		"Result" => array(
			"NAME" => GetMessage("BPVICA_DESCR_RESULT"),
			"TYPE" => "bool",
		),
		"ResultText" => array(
			"NAME" => GetMessage("BPVICA_DESCR_RESULT_TEXT"),
			"TYPE" => "string",
		),
		"ResultCode" => array(
			"NAME" => GetMessage("BPVICA_DESCR_RESULT_CODE"),
			"TYPE" => "string",
		),
	),
	'FILTER' => [
		'EXCLUDE' => [
			['tasks'],
			['rpa'],
		]
	],
	'ROBOT_SETTINGS' => array(
		'CATEGORY' => 'client'
	)
);