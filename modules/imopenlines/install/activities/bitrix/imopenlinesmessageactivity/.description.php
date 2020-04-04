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
		'INCLUDE' => array(
			array('crm'),
		),
	),
	'ROBOT_SETTINGS' => array(
		'CATEGORY' => 'client'
	),
);