<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = [
	"SORT" => 100,
	"NAME" => GetMessage("RPA_BP_APR_DESCR_NAME"),
	"TYPE" => ["activity", "robot_activity", "rpa_activity"],
	"CLASS" => "RpaApproveActivity",
	"JSCLASS" => "BizProcActivity",
	"CATEGORY" => [
		"ID" => "document",
	],
	'FILTER' => [
		'INCLUDE' => [['rpa']]
	],
	"RETURN" => [
		'TaskId' => [
			'NAME' => 'ID',
			'TYPE' => 'int'
		],
		"LastApprover" => [
			"NAME" => GetMessage("RPA_BP_APR_DESCR_LA"),
			"TYPE" => "user",
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'RESPONSIBLE_PROPERTY' => 'Responsible'
	],
];