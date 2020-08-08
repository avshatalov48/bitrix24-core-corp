<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = [
	"SORT" => 400,
	"NAME" => GetMessage("RPA_BP_CHS_DESCR_NAME"),
	"TYPE" => ["activity", "robot_activity"],
	"CLASS" => "RpaChangeStageActivity",
	"JSCLASS" => "BizProcActivity",
	"CATEGORY" => [
		"ID" => "document",
	],
	'FILTER' => [
		'INCLUDE' => [['rpa']]
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'RESPONSIBLE_PROPERTY' => 'ModifiedBy'
	],
];