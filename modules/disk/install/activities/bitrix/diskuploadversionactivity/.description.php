<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	"NAME" => GetMessage("BPDUV_DESCR_NAME2"),
	"DESCRIPTION" => GetMessage("BPDUV_DESCR_DESCR2"),
	"TYPE" => "activity",
	"CLASS" => "DiskUploadVersionActivity",
	"JSCLASS" => "BizProcActivity",
	"CATEGORY" => array(
		"ID" => "document",
		"OWN_ID" => 'disk',
		"OWN_NAME" => GetMessage("BPDUV_DESCR_CATEGORY"),
	),
	"RETURN" => array(
		"ObjectId" => array(
			"NAME" => GetMessage("BPDUV_DESCR_OBJECT_ID"),
			"TYPE" => "int",
		),
		"DetailUrl" => array(
			"NAME" => GetMessage("BPDUV_DESCR_DETAIL_URL"),
			"TYPE" => "string",
		),
		"DownloadUrl" => array(
			"NAME" => GetMessage("BPDUV_DESCR_DOWNLOAD_URL"),
			"TYPE" => "string",
		),
	)
);
?>