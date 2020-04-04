<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	"NAME" => GetMessage("BPDAF_DESCR_NAME2"),
	"DESCRIPTION" => GetMessage("BPDAF_DESCR_DESCR2"),
	"TYPE" => "activity",
	"CLASS" => "DiskAddFolderActivity",
	"JSCLASS" => "BizProcActivity",
	"CATEGORY" => array(
		"ID" => "document",
		"OWN_ID" => 'disk',
		"OWN_NAME" => GetMessage("BPDAF_DESCR_CATEGORY"),
	),
	"RETURN" => array(
		"ObjectId" => array(
			"NAME" => GetMessage("BPDAF_DESCR_OBJECT_ID"),
			"TYPE" => "int",
		),
		"DetailUrl" => array(
			"NAME" => GetMessage("BPDAF_DESCR_DETAIL_URL"),
			"TYPE" => "string",
		),
	)
);
?>