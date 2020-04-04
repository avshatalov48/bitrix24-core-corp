<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	"NAME" => GetMessage("BPDCM_DESCR_NAME2"),
	"DESCRIPTION" => GetMessage("BPDCM_DESCR_DESCR2"),
	"TYPE" => "activity",
	"CLASS" => "DiskCopyMoveActivity",
	"JSCLASS" => "BizProcActivity",
	"CATEGORY" => array(
		"ID" => "document",
		"OWN_ID" => 'disk',
		"OWN_NAME" => GetMessage("BPDCM_DESCR_CATEGORY"),
	),
	"RETURN" => array(
		"ObjectId" => array(
			"NAME" => GetMessage("BPDCM_DESCR_OBJECT_ID"),
			"TYPE" => "int",
		),
		"DetailUrl" => array(
			"NAME" => GetMessage("BPDCM_DESCR_DETAIL_URL"),
			"TYPE" => "string",
		),
		"DownloadUrl" => array(
			"NAME" => GetMessage("BPDCM_DESCR_DOWNLOAD_URL"),
			"TYPE" => "string",
		),
	)
);
?>