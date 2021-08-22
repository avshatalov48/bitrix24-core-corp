<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	"NAME" => GetMessage("IMOL_MA_DESCR_NAME"),
	"DESCRIPTION" => GetMessage("IMOL_MA_DESCR_DESCR"),
	"TYPE" => array('activity', 'robot_activity'),
	"CLASS" => "ImOpenLinesMessageActivity",
	"JSCLASS" => "BizProcActivity",
	"CATEGORY" => array(
		"ID" => "interaction",
	),
	'FILTER' => array(
		'INCLUDE' => [
			['crm']
		],
		'EXCLUDE' => array(
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Dynamic'],
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Quote'],
		)
	),
	'ROBOT_SETTINGS' => array(
		'CATEGORY' => 'client'
	),
);