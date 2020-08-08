<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = [
	"NAME" => GetMessage("CRM_SSF_DESCR_NAME"),
	"DESCRIPTION" => GetMessage("CRM_SSF_DESCR_DESCR"),
	"TYPE" => ["activity", "robot_activity"],
	"CLASS" => "CrmSetShipmentField",
	"JSCLASS" => "BizProcActivity",
	"CATEGORY" => [
		"ID" => "document",
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Order']
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee'
	],
];