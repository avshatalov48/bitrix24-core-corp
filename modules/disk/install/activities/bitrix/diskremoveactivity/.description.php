<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	"NAME" => GetMessage("BPDRMV_DESCR_NAME2"),
	"DESCRIPTION" => GetMessage("BPDRMV_DESCR_DESCR2"),
	"TYPE" => "activity",
	"CLASS" => "DiskRemoveActivity",
	"JSCLASS" => "BizProcActivity",
	"CATEGORY" => array(
		"ID" => "document",
		"OWN_ID" => 'disk',
		"OWN_NAME" => GetMessage("BPDRMV_DESCR_CATEGORY"),
	),
);
?>